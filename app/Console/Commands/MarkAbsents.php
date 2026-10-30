<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\DiaFeriado;
use App\Models\User;
use App\Notifications\AsistenciaNotificacion;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsents extends Command
{
    protected $signature = 'app:mark-absents';
    protected $description = 'Marca ausencias: 1 por día (admin) o 1 por bloque (docente)';

    public function handle(): int
    {
        $hoy = today();
        $diaSemana = $hoy->dayOfWeek;
        $fechaStr = $hoy->toDateString();

        if ($diaSemana === Carbon::SUNDAY) {
            $this->info('Domingo — no se procesa.');
            return self::SUCCESS;
        }

        if (DiaFeriado::esFeriado($hoy)) {
            $this->info('Día feriado — no se procesa.');
            return self::SUCCESS;
        }

        $usuarios = User::where('estatus', 'activo')
            ->whereIn('rol_id', [2, 3])
            ->get();

        $marcados = 0;

        foreach ($usuarios as $user) {
            if ($user->rol_id === 2) {
                $marcados += $this->procesarAdministrativo($user, $hoy, $diaSemana, $fechaStr);
            } elseif ($user->rol_id === 3) {
                $marcados += $this->procesarDocente($user, $hoy, $fechaStr);
            }
        }

        $this->info("Se marcaron {$marcados} ausencias.");
        return self::SUCCESS;
    }

    private function procesarAdministrativo(User $user, Carbon $hoy, int $diaSemana, string $fechaStr): int
    {
        $horario = $user->horarioAdministrativo;
        if (!$horario) {
            return 0;
        }

        $diasMap = [
            Carbon::MONDAY    => 'lunes',
            Carbon::TUESDAY   => 'martes',
            Carbon::WEDNESDAY => 'miercoles',
            Carbon::THURSDAY  => 'jueves',
            Carbon::FRIDAY    => 'viernes',
            Carbon::SATURDAY  => 'sabado',
        ];

        $diaCampo = $diasMap[$diaSemana] ?? null;
        if (!$diaCampo || !$horario->{$diaCampo}) {
            return 0;
        }

        $yaRegistro = Asistencia::where('usuario_id', $user->id)
            ->where('fecha', $fechaStr)
            ->whereNull('horario_id')
            ->exists();

        if ($yaRegistro) {
            return 0;
        }

        Asistencia::create([
            'usuario_id' => $user->id,
            'fecha'      => $fechaStr,
            'estado'     => 'Inasistente',
        ]);

        $user->notify(new AsistenciaNotificacion('inasistente', $fechaStr, null, 'Inasistente'));

        return 1;
    }

    private function procesarDocente(User $user, Carbon $hoy, string $fechaStr): int
    {
        $sesiones = $user->obtenerSesionesDelDia($fechaStr);
        if (empty($sesiones)) {
            return 0;
        }

        $marcados = 0;

        foreach ($sesiones as $sesion) {
            $horarioIdInicio = $sesion['horario_id_inicio'];

            $yaRegistro = Asistencia::where('usuario_id', $user->id)
                ->where('fecha', $fechaStr)
                ->whereIn('horario_id', collect($sesion['bloques'])->pluck('id')->all())
                ->exists();

            if ($yaRegistro) {
                continue;
            }

            $bloqueTexto = substr($sesion['hora_inicio'], 0, 5) . ' – ' . substr($sesion['hora_fin'], 0, 5);

            Asistencia::create([
                'usuario_id'    => $user->id,
                'fecha'         => $fechaStr,
                'horario_id'    => $horarioIdInicio,
                'bloque_inicio' => $sesion['hora_inicio'],
                'bloque_fin'    => $sesion['hora_fin'],
                'estado'        => 'Inasistente',
            ]);

            $user->notify(new AsistenciaNotificacion('inasistente', $fechaStr, null, 'Inasistente'));

            $marcados++;
        }

        return $marcados;
    }
}
