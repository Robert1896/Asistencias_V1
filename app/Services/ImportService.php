<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CargaAcademicaDocente;
use App\Models\Coordinacion;
use App\Models\DocenteHorarioPivot;
use App\Models\Horario;
use App\Models\Rol;
use App\Models\Salon;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportService
{
    /**
     * Genera una contraseña aleatoria criptográficamente segura.
     *
     * Garantiza al menos una minúscula, una mayúscula, un número y un símbolo,
     * y rellena el resto usando random_int() (CSPRNG). El orden se baraja con
     * Fisher-Yates usando entropía criptográfica para no filtrar el patrón.
     */
    private static function generarPasswordSegura(int $length = 16): string
    {
        $length = max($length, PasswordPolicy::MIN_LENGTH);

        $min = 'abcdefghijkmnpqrstuvwxyz';
        $may = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $num = '23456789';
        $sim = '!@#$%&*-_=+?';

        $pw = [
            $min[random_int(0, strlen($min) - 1)],
            $may[random_int(0, strlen($may) - 1)],
            $num[random_int(0, strlen($num) - 1)],
            $sim[random_int(0, strlen($sim) - 1)],
        ];

        $all = $min . $may . $num . $sim;
        $max = strlen($all) - 1;

        for ($i = count($pw); $i < $length; $i++) {
            $pw[] = $all[random_int(0, $max)];
        }

        for ($i = count($pw) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$pw[$i], $pw[$j]] = [$pw[$j], $pw[$i]];
        }

        return implode('', $pw);
    }
    /**
     * Normaliza un valor de hora a formato HH:MM:SS.
     * Maneja: strings, objetos DateTime, números decimales de Excel (serial time).
     */
    private function normalizarHora(mixed $valor): string
    {
        if ($valor instanceof \DateTime) {
            return $valor->format('H:i:s');
        }

        $str = trim((string) $valor);

        if ($str === '') {
            return '';
        }

        if (preg_match('/^\d+(\.\d+)?$/', $str)) {
            $decimal = (float) $str;
            $totalSegundos = (int) round($decimal * 86400);
            $h = intdiv($totalSegundos, 3600);
            $m = intdiv($totalSegundos % 3600, 60);
            $s = $totalSegundos % 60;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $str, $m1)) {
            return sprintf('%02d:%02d:00', (int) $m1[1], (int) $m1[2]);
        }

        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $str, $m2)) {
            return sprintf('%02d:%02d:%02d', (int) $m2[1], (int) $m2[2], (int) $m2[3]);
        }

        return $str;
    }

    /**
     * Parsea un archivo CSV/XLSX y devuelve encabezados y filas de datos.
     *
     * Soporta archivos CSV con separador punto y coma (;) y archivos XLSX
     * mediante PhpSpreadsheet. Los encabezados se normalizan a minúsculas
     * y se recortan espacios en blanco.
     *
     * @param UploadedFile $file Archivo subido por el usuario (CSV, TXT o XLSX).
     * @return array{headers: array<int,string>, rows: array<int,array>}
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($file->getRealPath(), 'r');
            $headers = fgetcsv($handle, 0, ';');
            $rows = [];
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        } else {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $reader->getActiveSheet();
            $rows = [];
            $headers = null;
            foreach ($sheet->getRowIterator() as $i => $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = $cell->getValue();
                }
                if ($i === 1) {
                    $headers = array_map(fn ($h) => strtolower(trim($h)), $cells);
                } else {
                    $rows[] = $cells;
                }
            }
        }

        return [
            'headers' => $headers ?? [],
            'rows'    => $rows,
        ];
    }

    /**
     * Importa usuarios desde un archivo CSV o XLSX.
     *
     * Valida campos requeridos (nombre, apellido, email), cédula única,
     * formato de contraseña y existencia de rol. Genera contraseña aleatoria
     * si no se proporciona. Crea registros con estatus activo.
     *
     * @param UploadedFile $file Archivo con los datos de usuarios.
     * @return array{created: int, errors: array<int,string>}
     */
    public function importarUsuarios(UploadedFile $file): array
    {
        $parsed = $this->parseFile($file);
        $headers = $parsed['headers'];
        $rows = $parsed['rows'];

        if (!$headers) {
            return ['created' => 0, 'errors' => ['El archivo no tiene encabezados válidos.']];
        }

        $required = ['nombre', 'apellido', 'email'];
        $missing = array_diff($required, $headers);
        if ($missing) {
            return ['created' => 0, 'errors' => ['Faltan columnas requeridas: ' . implode(', ', $missing)]];
        }

        $nombreIdx    = array_search('nombre', $headers);
        $apellidoIdx  = array_search('apellido', $headers);
        $emailIdx     = array_search('email', $headers);
        $cedulaIdx    = array_search('cedula', $headers);
        $rolIdx       = array_search('rol', $headers);
        $passwordIdx  = array_search('password', $headers);

        $creados = 0;
        $errores = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $i => $row) {
            $nombre    = trim($row[$nombreIdx] ?? '');
            $apellido  = trim($row[$apellidoIdx] ?? '');
            $email     = trim($row[$emailIdx] ?? '');
            $cedula    = $cedulaIdx !== false ? trim((string) ($row[$cedulaIdx] ?? '')) : '';
            $rolNombre = ($rolIdx !== false && trim((string) ($row[$rolIdx] ?? '')) !== '')
                ? trim($row[$rolIdx])
                : 'Docente';

            if (!$nombre || mb_strlen($nombre) > 80) {
                $errores[] = "Fila " . ($i + 2) . ": nombre vacío o excede 80 caracteres.";
                continue;
            }

            if (mb_strlen($apellido) > 80) {
                $errores[] = "Fila " . ($i + 2) . ": apellido excede 80 caracteres.";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
                $errores[] = "Fila " . ($i + 2) . ": email '{$email}' no es válido.";
                continue;
            }

            if (!$cedula || !ctype_digit($cedula) || strlen($cedula) < 7 || strlen($cedula) > 8 || (int) $cedula < 1000000) {
                $errores[] = "Fila " . ($i + 2) . ": cédula inválida '{$cedula}' (debe ser numérica, 7-8 dígitos, mín. 1.000.000).";
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $errores[] = "Fila " . ($i + 2) . ": email '{$email}' ya existe.";
                continue;
            }

            if (User::where('cedula', $cedula)->exists()) {
                $errores[] = "Fila " . ($i + 2) . ": cédula '{$cedula}' ya existe.";
                continue;
            }

            $finalPassword = ($passwordIdx !== false && trim((string) ($row[$passwordIdx] ?? '')) !== '')
                ? trim($row[$passwordIdx])
                : self::generarPasswordSegura(16);

            if (mb_strlen($finalPassword) < PasswordPolicy::MIN_LENGTH || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $finalPassword)) {
                $errores[] = "Fila " . ($i + 2) . ": contraseña no cumple mínimo " . PasswordPolicy::MIN_LENGTH . " caracteres, mayúscula, minúscula y número.";
                continue;
            }

            $rol = Rol::where('nombre', $rolNombre)->first() ?? Rol::where('id', 3)->first();

            // El rol Superadmin no puede asignarse vía importación (CWE-269)
            if ((int) $rol->id === 1) {
                $errores[] = "Fila " . ($i + 2) . ": el rol 'Superadmin' no puede asignarse por importación.";
                continue;
            }

                User::create([
                    'nombre'             => strip_tags($nombre),
                    'apellido'           => strip_tags($apellido),
                    'email'              => $email,
                    'cedula'             => $cedula,
                    'rol_id'             => $rol->id,
                    'password'           => $finalPassword,
                    'estatus'            => 'activo',
                ]);

                $creados++;
            }

            DB::commit();

            ActivityLog::log('usuarios_importar', "Importó {$creados} usuarios desde archivo", 'usuarios', [
                'creados' => $creados,
                'errores' => count($errores),
            ]);

            return ['created' => $creados, 'errors' => $errores];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Importa asignaciones docente-horario-salón desde un archivo CSV o XLSX.
     *
     * Valida cédula de usuario, día de la semana, horario, salón y tipo de actividad.
     * Detecta colisiones de salón y duplicados de asignación. Carga automáticamente
     * registros de CargaAcademicaDocente cuando la materia es nueva para el docente.
     *
     * @param UploadedFile $file Archivo con los datos de asignaciones.
     * @return array{created: int, errors: array<int,string>}
     */
    public function importarAsignaciones(UploadedFile $file): array
    {
        DB::beginTransaction();

        try {
            $parsed = $this->parseFile($file);
            $headers = $parsed['headers'];
            $rows = $parsed['rows'];

        if (!$headers) {
            return ['created' => 0, 'errors' => ['El archivo no tiene encabezados válidos.']];
        }

        $required = ['cedula', 'dia', 'hora_inicio', 'hora_fin', 'salon_codigo'];
        $missing = array_diff($required, $headers);
        if ($missing) {
            return ['created' => 0, 'errors' => ['Faltan columnas requeridas: ' . implode(', ', $missing)]];
        }

        $cedulaIdx      = array_search('cedula', $headers);
        $nombreIdx      = array_search('nombre', $headers);
        $apellidoIdx    = array_search('apellido', $headers);
        $diaIdx         = array_search('dia', $headers);
        $horaInicioIdx  = array_search('hora_inicio', $headers);
        $horaFinIdx     = array_search('hora_fin', $headers);
        $salonCodigoIdx = array_search('salon_codigo', $headers);
        $tipoIdx        = array_search('tipo_actividad', $headers);
        $coordIdx       = array_search('coordinacion', $headers);
        $materiaIdx     = array_search('materia', $headers);
        $seccionIdx     = array_search('seccion', $headers);

        $diasMap = [
            'lunes' => 1, 'martes' => 2, 'miércoles' => 3, 'miercoles' => 3,
            'jueves' => 4, 'viernes' => 5, 'sábado' => 6, 'sabado' => 6,
        ];

        $filasValidas = [];
        $errores = [];
        $bloquesPorMateria = [];
        $usuariosCreados = [];
        $salonesCreados = [];
        $horariosCreados = [];
        $coordinacionesCreadas = [];

        foreach ($rows as $i => $row) {
            $cedulaRaw  = $row[$cedulaIdx] ?? '';
            $cedula     = is_numeric($cedulaRaw) ? (string) (int) $cedulaRaw : trim((string) $cedulaRaw);
            $dia         = trim((string) ($row[$diaIdx] ?? ''));
            $horaInicio  = $this->normalizarHora($row[$horaInicioIdx] ?? '');
            $horaFin     = $this->normalizarHora($row[$horaFinIdx] ?? '');
            $salonCodigo = trim((string) ($row[$salonCodigoIdx] ?? ''));
            $tipo        = trim((string) ($row[$tipoIdx] ?? '')) ?: 'carga_academica';
            $coordNombre = trim((string) ($row[$coordIdx] ?? '')) ?: null;
            $materia     = trim((string) ($row[$materiaIdx] ?? ''));
            $seccion     = trim((string) ($row[$seccionIdx] ?? ''));
            $fila = $i + 2;

            if (!$cedula || !ctype_digit($cedula) || strlen($cedula) < 7 || strlen($cedula) > 8) {
                $errores[] = "Fila {$fila}: cédula inválida '{$cedula}'.";
                continue;
            }
            $user = User::where('cedula', $cedula)->first();
            if (!$user) {
                $nombre   = $nombreIdx !== false ? trim($row[$nombreIdx] ?? '') : '';
                $apellido = $apellidoIdx !== false ? trim($row[$apellidoIdx] ?? '') : '';
                $nombre   = $nombre ?: 'Docente';
                $apellido = $apellido ?: $cedula;

                $passwordGenerada = self::generarPasswordSegura(16);
                $rolId = ($tipo === 'administrativa') ? 2 : 3;

                $user = User::create([
                    'cedula'             => $cedula,
                    'nombre'             => strip_tags($nombre),
                    'apellido'           => strip_tags($apellido),
                    'email'              => null,
                    'password'           => $passwordGenerada,
                    'rol_id'             => $rolId,
                    'estatus'            => 'inactivo',
                ]);

                unset($passwordGenerada);

                $usuariosCreados[] = [
                    'nombre' => $user->nombre_completo,
                    'cedula' => $cedula,
                    'rol'    => $rolId === 3 ? 'Docente' : 'Administrativo',
                ];
            }
            $diaNum = $diasMap[strtolower($dia)] ?? null;
            if (!$diaNum) {
                $errores[] = "Fila {$fila}: día inválido '{$dia}'. Use: Lunes, Martes, Miércoles, Jueves, Viernes, Sábado (sin importar mayúsculas).";
                continue;
            }
            $horario = Horario::buscarBloque($diaNum, $horaInicio, $horaFin);
            if (!$horario) {
                // Un rango nuevo se crea para los seis días: así el grid de
                // asignación docente lo ofrece en cualquier día y no quedan
                // celdas "sin bloque".
                Horario::asegurarRango($horaInicio, $horaFin);

                $horario = Horario::buscarBloque($diaNum, $horaInicio, $horaFin);

                $label = "{$horaInicio}-{$horaFin}";
                if (!in_array($label, $horariosCreados)) {
                    $horariosCreados[] = $label;
                }
            }
            $salon = Salon::where('codigo', $salonCodigo)->first();
            if (!$salon) {
                $salon = Salon::create([
                    'codigo'     => $salonCodigo,
                    'nombre'     => 'Salón ' . $salonCodigo,
                    'capacidad'  => 30,
                    'ubicacion'  => null,
                ]);
                $salonesCreados[] = $salonCodigo;
            }
            if (!in_array($tipo, ['carga_academica', 'asesoria', 'administrativa'])) {
                $errores[] = "Fila {$fila}: tipo de actividad inválido '{$tipo}'.";
                continue;
            }
            if ($tipo === 'carga_academica' && !$materia) {
                $errores[] = "Fila {$fila}: para tipo 'carga_academica' debe indicar la materia.";
                continue;
            }
            $colision = DocenteHorarioPivot::where('salon_id', $salon->id)
                ->where('horario_id', $horario->id)
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($colision) {
                $errores[] = "Fila {$fila}: el salón '{$salonCodigo}' ya está ocupado el {$dia} de {$horaInicio} a {$horaFin}.";
                continue;
            }
            $duplicado = DocenteHorarioPivot::where('user_id', $user->id)
                ->where('horario_id', $horario->id)
                ->where('tipo_actividad', $tipo)
                ->exists();
            if ($duplicado) {
                $errores[] = "Fila {$fila}: el docente ya tiene asignado este bloque horario con tipo '{$tipo}'.";
                continue;
            }
            $coordId = null;
            if ($coordNombre) {
                $coord = Coordinacion::where('nombre', $coordNombre)->first();
                if (!$coord) {
                    $coord = Coordinacion::create([
                        'nombre'      => $coordNombre,
                        'descripcion' => null,
                    ]);
                    if (!in_array($coordNombre, $coordinacionesCreadas)) {
                        $coordinacionesCreadas[] = $coordNombre;
                    }
                }
                $coordId = $coord->id;
            }

            $filasValidas[] = [
                'user_id'        => $user->id,
                'horario_id'     => $horario->id,
                'tipo_actividad' => $tipo,
                'salon_id'       => $salon->id,
                'coord_id'       => $coordId,
                'materia'        => $materia,
                'seccion'        => $seccion,
            ];

            if ($materia) {
                $key = $user->id . '|' . $materia;
                $bloquesPorMateria[$key] = ($bloquesPorMateria[$key] ?? 0) + 1;
            }
        }

        $cargaMap = [];
        $cargasCreadas = 0;

        foreach ($bloquesPorMateria as $key => $numBloques) {
            [$userId, $materia] = explode('|', $key, 2);

            $carga = CargaAcademicaDocente::where('usuario_id', $userId)
                ->where('materia', $materia)
                ->first();

            $horasSemanales = $numBloques;

            if (!$carga) {
                $carga = CargaAcademicaDocente::create([
                    'usuario_id'      => $userId,
                    'materia'         => $materia,
                    'tipo'            => 'Clase',
                    'horas_semanales' => $horasSemanales,
                ]);
                $cargasCreadas++;
            } else {
                $carga->update(['horas_semanales' => $horasSemanales]);
                $cargasCreadas++;
            }

            $cargaMap[$key] = $carga->id;
        }

        $creados = 0;

        foreach ($filasValidas as $fila) {
            $cargaId = null;
            if ($fila['materia']) {
                $cargaId = $cargaMap[$fila['user_id'] . '|' . $fila['materia']] ?? null;
            }

            DocenteHorarioPivot::create([
                'user_id'            => $fila['user_id'],
                'horario_id'         => $fila['horario_id'],
                'tipo_actividad'     => $fila['tipo_actividad'],
                'salon_id'           => $fila['salon_id'],
                'coordinacion_id'    => $fila['coord_id'],
                'carga_academica_id' => $cargaId,
                'seccion'            => $fila['seccion'] ?: null,
            ]);

            $creados++;
        }

        if ($cargasCreadas > 0) {
            $errores[] = "Se crearon {$cargasCreadas} cargas académicas automáticamente.";
        }
        if (!empty($salonesCreados)) {
            $errores[] = "Se crearon " . count($salonesCreados) . " salones nuevos: " . implode(', ', $salonesCreados) . ".";
        }
        if (!empty($horariosCreados)) {
            $errores[] = "Se crearon " . count($horariosCreados) . " bloques horarios nuevos: " . implode(', ', $horariosCreados) . ".";
        }
        if (!empty($coordinacionesCreadas)) {
            $errores[] = "Se crearon " . count($coordinacionesCreadas) . " coordinaciones nuevas: " . implode(', ', $coordinacionesCreadas) . ".";
        }

            DB::commit();

            ActivityLog::log('salon_importar', "Importación de asignaciones: {$creados} creadas", 'usuarios', [
                'creados' => $creados,
                'errores' => count($errores),
            ]);

            return [
                'created'              => $creados,
                'errors'               => $errores,
                'usuarios_creados'     => $usuariosCreados,
                'salones_creados'      => $salonesCreados,
                'horarios_creados'     => $horariosCreados,
                'coordinaciones_creadas' => $coordinacionesCreadas,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
