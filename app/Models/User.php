<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'password',
        'nombre',
        'apellido',
        'nombre_completo',
        'cedula',
        'email',
        'rol_id',
        'estatus',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    protected function casts(): array
    {
        return [
            'password'              => 'hashed',
            'two_factor_enabled'    => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($user) {
            $user->nombre_completo = trim($user->nombre . ' ' . $user->apellido);
        });
    }

    public function getNombreCompletoAttribute($value): string
    {
        return $value ?: trim($this->nombre . ' ' . $this->apellido);
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'usuario_id');
    }

    public function horarioAdministrativo()
    {
        return $this->hasOne(HorarioAdministrativo::class, 'usuario_id');
    }

    public function cargaAcademica()
    {
        return $this->hasMany(CargaAcademicaDocente::class, 'usuario_id');
    }

    public function horariosDocente()
    {
        return $this->belongsToMany(Horario::class, 'docente_horario')
            ->withPivot(['tipo_actividad', 'coordinacion_id', 'salon_id', 'carga_academica_id', 'seccion'])
            ->withTimestamps();
    }

    public function totalHorasSemanalesDocente(): float
    {
        return $this->horariosDocente()
            ->get()
            ->sum(fn($h) => $h->duracion_en_horas);
    }

    public function tienePermiso(string $codigoPermiso): bool
    {
        return $this->rol->permisos()->where('codigo', $codigoPermiso)->exists();
    }

    /**
     * Requisito #15: genera un código de 6 dígitos, lo guarda hasheado con
     * vencimiento y lo envía por correo. Devuelve los minutos de validez.
     */
    public function generarCodigoDosFactores(int $minutosValidez = 10): int
    {
        $codigo = (string) random_int(100000, 999999);

        $this->forceFill([
            'two_factor_code'       => Hash::make($codigo),
            'two_factor_expires_at' => now()->addMinutes($minutosValidez),
        ])->saveQuietly();

        $this->notify(new \App\Notifications\TwoFactorCodeNotification($codigo, $minutosValidez));

        return $minutosValidez;
    }

    /**
     * Valida el código ingresado contra el hash almacenado y su vencimiento.
     */
    public function codigoDosFactoresValido(?string $codigo): bool
    {
        if (!$codigo || !$this->two_factor_code || !$this->two_factor_expires_at) {
            return false;
        }

        if ($this->two_factor_expires_at->isPast()) {
            return false;
        }

        return Hash::check($codigo, $this->two_factor_code);
    }

    /**
     * Limpia el código tras un uso correcto (evita reutilización).
     */
    public function limpiarCodigoDosFactores(): void
    {
        $this->forceFill([
            'two_factor_code'       => null,
            'two_factor_expires_at' => null,
        ])->saveQuietly();
    }

    /**
     * Fase C: invalida las demás sesiones del usuario tras un cambio de contraseña.
     * - Rota remember_token (mata cookies "recuérdame" robadas).
     * - Sesión activa actual: si se pasa $sesionActualId se conserva.
     * - Si no, se eliminan TODAS las sesiones del usuario (reset por correo, etc.).
     * Soporta drivers database y file (file: payload puede estar encriptado con
     * SESSION_ENCRYPT, en cuyo caso se descifra con Crypt antes de inspeccionar).
     */
    public function invalidarOtrasSesiones(?string $sesionActualId = null): void
    {
        $this->forceFill(['remember_token' => Str::random(60)])->saveQuietly();

        $driver = config('session.driver');

        if ($driver === 'database') {
            $query = DB::table('sessions')->where('user_id', $this->id);
            if ($sesionActualId !== null) {
                $query->where('id', '!=', $sesionActualId);
            }
            $query->delete();

            return;
        }

        if ($driver !== 'file') {
            return;
        }

        $carpeta  = config('session.files') ?: storage_path('framework/sessions');
        $id       = $this->id;
        $marcaPhp = 'login_web";i:' . $id . ';';

        foreach (glob($carpeta . '/*') ?: [] as $archivo) {
            if ($sesionActualId !== null && basename($archivo) === $sesionActualId) {
                continue;
            }

            $bruto = @file_get_contents($archivo);
            if ($bruto === false) {
                continue;
            }

            $contenido = $bruto;
            if (config('session.encrypt')) {
                try {
                    $contenido = Crypt::decrypt($bruto);
                } catch (\Throwable $e) {
                    continue;
                }
            }

            $pertenece = str_contains($contenido, $marcaPhp)
                || str_contains($contenido, '"login_web":' . $id . ',')
                || str_contains($contenido, '"login_web":' . $id . '}');

            if ($pertenece) {
                @unlink($archivo);
            }
        }
    }

    public function obtenerSesionesDelDia(string $fecha): array
    {
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;

        $bloques = $this->horariosDocente()
            ->where('horarios.dia_semana', $diaSemana)
            ->orderBy('horarios.hora_inicio')
            ->get();

        if ($bloques->isEmpty()) {
            return [];
        }

        $sesiones = [];
        $sesionActual = null;

        foreach ($bloques as $bloque) {
            $datos = [
                'horario_id_inicio'  => $bloque->id,
                'hora_inicio'        => $bloque->hora_inicio,
                'hora_fin'           => $bloque->hora_fin,
                'salon_id'           => $bloque->pivot->salon_id,
                'seccion'            => $bloque->pivot->seccion,
                'carga_academica_id' => $bloque->pivot->carga_academica_id,
                'tipo_actividad'     => $bloque->pivot->tipo_actividad,
                'bloques'            => collect([$bloque]),
            ];

            if ($sesionActual === null) {
                $sesionActual = $datos;
                continue;
            }

            $anterior = $sesionActual['bloques']->last();

            // Una clase puede ocupar varios bloques consecutivos (por ejemplo,
            // 2 horas académicas = dos bloques de 45 min). Solo se unen si son
            // contiguos y pertenecen a la misma materia, sección, salón y tipo.
            $contiguos = Horario::normalizarHora($anterior->hora_fin)
                === Horario::normalizarHora($bloque->hora_inicio);

            $mismaClase = (int) $sesionActual['salon_id'] === (int) $bloque->pivot->salon_id
                && (string) $sesionActual['seccion'] === (string) $bloque->pivot->seccion
                && (int) $sesionActual['carga_academica_id'] === (int) $bloque->pivot->carga_academica_id
                && (string) $sesionActual['tipo_actividad'] === (string) $bloque->pivot->tipo_actividad;

            if ($contiguos && $mismaClase) {
                $sesionActual['hora_fin'] = $bloque->hora_fin;
                $sesionActual['bloques']->push($bloque);
            } else {
                $sesiones[] = $sesionActual;
                $sesionActual = $datos;
            }
        }

        if ($sesionActual) {
            $sesiones[] = $sesionActual;
        }

        return $sesiones;
    }

    public function obtenerSesionActiva(string $fecha, string $hora): ?array
    {
        $sesiones = $this->obtenerSesionesDelDia($fecha);

        foreach ($sesiones as $sesion) {
            if ($hora >= $sesion['hora_inicio'] && $hora < $sesion['hora_fin']) {
                return $sesion;
            }
        }

        return null;
    }

    /**
     * Clase (sesión) activa que corresponde al salón escaneado. Si la clase
     * dura dos o más bloques consecutivos, se devuelve completa para que la
     * asistencia se registre una sola vez en todo el rango.
     */
    public function obtenerSesionActivaEnSalon(string $fecha, string $hora, int $salonId): ?array
    {
        foreach ($this->obtenerSesionesDelDia($fecha) as $sesion) {
            if ((int) $sesion['salon_id'] !== $salonId) {
                continue;
            }

            if ($hora >= $sesion['hora_inicio'] && $hora < $sesion['hora_fin']) {
                return $sesion;
            }
        }

        return null;
    }

    /**
     * Explica por qué no hay sesión activa. Evita el mensaje genérico
     * "no tiene bloques asignados" cuando en realidad la clase ya terminó,
     * todavía no empieza o el docente solo tiene bloques en otro día.
     *
     * @return array{titulo: string, mensaje: string, caso: string, contexto: array}
     */
    public function explicarSinSesionActiva(string $fecha, string $hora): array
    {
        $diaIso   = Carbon::parse($fecha)->dayOfWeekIso;
        $sesiones = $this->obtenerSesionesDelDia($fecha);
        $rango    = fn (array $s) => substr($s['hora_inicio'], 0, 5) . ' – ' . substr($s['hora_fin'], 0, 5);

        $contexto = [
            'hora_servidor' => $hora,
            'fecha'         => $fecha,
            'dia'           => Horario::DIAS[$diaIso] ?? (string) $diaIso,
            'sesiones_hoy'  => array_map($rango, $sesiones),
        ];

        if ($sesiones === []) {
            $dias = $this->horariosDocente()
                ->pluck('horarios.dia_semana')
                ->unique()
                ->sort()
                ->values();

            $contexto['dias_con_bloques'] = $dias
                ->map(fn ($d) => Horario::DIAS[(int) $d] ?? (string) $d)
                ->all();

            if ($dias->isNotEmpty()) {
                return [
                    'titulo'   => 'Hoy no tiene bloques',
                    'mensaje'  => "Hoy es {$contexto['dia']} y no tiene bloques asignados. Sus bloques están en: "
                        . implode(', ', $contexto['dias_con_bloques']) . '.',
                    'caso'     => 'otro_dia',
                    'contexto' => $contexto,
                ];
            }

            return [
                'titulo'   => 'Sin bloques asignados',
                'mensaje'  => 'No tiene bloques horarios asignados. Asígnelos en Horarios del Personal Docente.',
                'caso'     => 'sin_asignaciones',
                'contexto' => $contexto,
            ];
        }

        $ultima = $sesiones[count($sesiones) - 1];

        foreach ($sesiones as $sesion) {
            if ($hora < $sesion['hora_inicio']) {
                return [
                    'titulo'   => 'Fuera de bloque',
                    'mensaje'  => 'No hay bloque activo. Su próxima clase inicia a las '
                        . substr($sesion['hora_inicio'], 0, 5) . '.',
                    'caso'     => 'proxima',
                    'contexto' => $contexto,
                ];
            }
        }

        return [
            'titulo'   => 'Bloque ya finalizado',
            'mensaje'  => 'Su bloque de las ' . $rango($ultima) . " ya finalizó (hora del servidor: {$hora}).",
            'caso'     => 'finalizadas',
            'contexto' => $contexto,
        ];
    }

    public function obtenerBloqueActivoEnSalon(string $fecha, string $hora, int $salonId): ?array
    {
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;

        $bloque = $this->horariosDocente()
            ->where('horarios.dia_semana', $diaSemana)
            ->where('docente_horario.salon_id', $salonId)
            ->where('horarios.hora_inicio', '<=', $hora)
            ->where('horarios.hora_fin', '>', $hora)
            ->first();

        if (!$bloque) {
            return null;
        }

        return [
            'horario_id' => $bloque->id,
            'hora_inicio' => $bloque->hora_inicio,
            'hora_fin'    => $bloque->hora_fin,
            'salon_id'    => $salonId,
        ];
    }

    public function scopeActivos($query)
    {
        return $query->where('estatus', 'activo');
    }

    public function scopeDocentes($query)
    {
        return $query->where('rol_id', 3);
    }

    public function scopeAdministrativos($query)
    {
        return $query->where('rol_id', 2);
    }
}
