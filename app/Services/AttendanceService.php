<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\AsistenciaNotificacion;
use App\Support\EstadoAsistencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public function procesarAdministrativo(User $usuario, ?string $qrHash, ?string $ip = null): array
    {
        $hoy      = today()->toDateString();
        $horaAhora = now()->format('H:i:s');
        $diaSemana = now()->dayOfWeek;

        $asistencia = Asistencia::where('usuario_id', $usuario->id)
            ->where('fecha', $hoy)
            ->whereNull('horario_id')
            ->first();

        if (!$asistencia) {
            $estado = EstadoAsistencia::desdeHorarioFijo(
                $horaAhora,
                $usuario->horarioAdministrativo?->hora_entrada,
                $diaSemana === Carbon::SATURDAY,
                $diaSemana
            );

            Asistencia::create([
                'usuario_id'   => $usuario->id,
                'fecha'        => $hoy,
                'hora_entrada' => $horaAhora,
                'estado'       => $estado,
            ]);

            $logData = ['user_id' => $usuario->id, 'estado' => $estado];
            if ($ip) $logData['ip'] = $ip;
            ActivityLog::log('asistencia_entrada', "Entrada registrada: {$usuario->email}", 'asistencia', $logData);

            Log::info('Asistencia registrada', ['user' => $usuario->email, 'tipo' => 'entrada', 'estado' => $estado]);

            if ($estado === 'Tarde') {
                $usuario->notify(new AsistenciaNotificacion('tarde', $hoy, $horaAhora, 'Tarde'));
            }

            $msg = $estado === 'Tarde'
                ? "Bienvenido {$usuario->nombre_completo} — Entrada registrada (Tarde)"
                : "Bienvenido {$usuario->nombre_completo} — Entrada registrada";

            return $this->resultado(true, $msg, $estado === 'Tarde' ? 'warning' : 'success', $usuario, 'entrada', $horaAhora, $estado);
        }

        if ($asistencia->hora_entrada && !$asistencia->hora_salida) {
            $asistencia->update(['hora_salida' => $horaAhora]);

            $logData = ['user_id' => $usuario->id];
            if ($ip) $logData['ip'] = $ip;
            ActivityLog::log('asistencia_salida', "Salida registrada: {$usuario->email}", 'asistencia', $logData);

            return $this->resultado(true, "Hasta luego {$usuario->nombre_completo} — Salida registrada", 'success', $usuario, 'salida', $horaAhora, $asistencia->estado);
        }

        return $this->resultado(true, "{$usuario->nombre_completo} ya registró entrada y salida hoy.", 'info', $usuario);
    }

    public function procesarDocente(User $usuario, ?string $salonCodigo, ?string $ip = null): array
    {
        $hoy      = today()->toDateString();
        $horaAhora = now()->format('H:i:s');

        $this->autoCerrarSalidaAnterior($usuario);

        $salon = Salon::where('codigo_qr', $salonCodigo)->orWhere('codigo', $salonCodigo)->first();

        if (!$salon) {
            return [
                'ok'      => false,
                'mensaje' => 'Salón no reconocido.',
                'tipo'    => 'error',
            ];
        }

        $bloque = $usuario->obtenerBloqueActivoEnSalon($hoy, $horaAhora, $salon->id);

        $sinAsignacion = false;

        if (!$bloque) {
            $sesionActiva = $usuario->obtenerSesionActiva($hoy, $horaAhora);

            $sinAsignacion = true;

            if ($sesionActiva) {
                $bloque = [
                    'horario_id'  => $sesionActiva['horario_id_inicio'],
                    'hora_inicio' => $sesionActiva['hora_inicio'],
                    'hora_fin'    => $sesionActiva['hora_fin'],
                    'salon_id'    => $salon->id,
                ];
            } else {
                $todasLasSesiones = $usuario->obtenerSesionesDelDia($hoy);
                $proxima = null;
                foreach ($todasLasSesiones as $s) {
                    if ($horaAhora < $s['hora_inicio']) {
                        $proxima = $s;
                        break;
                    }
                }

                if ($proxima) {
                    $horaInicio = substr($proxima['hora_inicio'], 0, 5);
                    return [
                        'ok'      => false,
                        'mensaje' => "No tiene bloque activo en este salón. Su próxima clase inicia a las {$horaInicio}.",
                        'tipo'    => 'info',
                    ];
                }

                return [
                    'ok'      => false,
                    'mensaje' => 'No tiene bloques horarios asignados para hoy.',
                    'tipo'    => 'error',
                ];
            }
        }

        $horarioId = $bloque['horario_id'];

        $yaRegistro = Asistencia::where('usuario_id', $usuario->id)
            ->where('fecha', $hoy)
            ->where('horario_id', $horarioId)
            ->first();

        if ($yaRegistro) {
            return [
                'ok'      => true,
                'mensaje' => "{$usuario->nombre_completo} ya registró asistencia para este bloque.",
                'tipo'    => 'info',
                'nombre'  => $usuario->nombre_completo,
                'rol'     => $usuario->rol->nombre,
                'bloque'  => substr($bloque['hora_inicio'], 0, 5) . ' – ' . substr($bloque['hora_fin'], 0, 5),
            ];
        }

        $estado = $sinAsignacion
            ? EstadoAsistencia::SIN_ASIGNACION
            : EstadoAsistencia::desdeLimite($horaAhora, $bloque['hora_inicio']);

        Asistencia::create([
            'usuario_id'    => $usuario->id,
            'fecha'         => $hoy,
            'horario_id'    => $horarioId,
            'salon_id'      => $salon->id,
            'bloque_inicio' => $bloque['hora_inicio'],
            'bloque_fin'    => $bloque['hora_fin'],
            'hora_entrada'  => $horaAhora,
            'estado'        => $estado,
        ]);

        $logData = [
            'user_id'        => $usuario->id,
            'horario_id'     => $horarioId,
            'salon_id'       => $salon->id,
            'estado'         => $estado,
            'sin_asignacion' => $sinAsignacion,
        ];
        if ($ip) $logData['ip'] = $ip;
        ActivityLog::log('asistencia_entrada', "Entrada docente (salon QR): {$usuario->email} → {$salon->codigo}", 'asistencia', $logData);

        Log::info('Asistencia registrada', ['user' => $usuario->email, 'tipo' => 'entrada_salon_qr', 'estado' => $estado, 'salon' => $salon->codigo]);

        if ($estado === 'Tarde') {
            $usuario->notify(new AsistenciaNotificacion('tarde', $hoy, $horaAhora, 'Tarde'));
        }

        $bloqueTexto = substr($bloque['hora_inicio'], 0, 5) . ' – ' . substr($bloque['hora_fin'], 0, 5);

        $mensajeExtra = $sinAsignacion
            ? " (Sin asignación registrada en este salón)"
            : '';

        $mensajeEstado = $estado === 'Tarde' ? ' (Tarde)' : '';

        return [
            'ok'      => true,
            'mensaje' => "{$usuario->nombre_completo} — Asistencia registrada [Bloque {$bloqueTexto}]{$mensajeEstado}{$mensajeExtra}",
            'tipo'    => $estado === 'Tarde' ? 'warning' : ($sinAsignacion ? 'info' : 'success'),
            'nombre'  => $usuario->nombre_completo,
            'rol'     => $usuario->rol->nombre,
            'marca'   => 'entrada',
            'hora'    => $horaAhora,
            'estado'  => $estado,
            'bloque'  => $bloqueTexto,
            'salon'   => $salon->codigo,
        ];
    }

    public function procesarDocentePorSesion(User $usuario, string $hoy, string $horaAhora, ?string $ip = null): array
    {
        $asistenciaAbierta = Asistencia::where('usuario_id', $usuario->id)
            ->where('fecha', $hoy)
            ->whereNull('hora_salida')
            ->first();

        if ($asistenciaAbierta) {
            $asistenciaAbierta->update(['hora_salida' => $horaAhora]);

            $logData = [
                'user_id'    => $usuario->id,
                'horario_id' => $asistenciaAbierta->horario_id,
            ];
            if ($ip) $logData['ip'] = $ip;
            ActivityLog::log('asistencia_salida', "Salida automática docente: {$usuario->email}", 'asistencia', $logData);
        }

        $sesion = $usuario->obtenerSesionActiva($hoy, $horaAhora);

        if (!$sesion) {
            $todasLasSesiones = $usuario->obtenerSesionesDelDia($hoy);

            $proximaSesion = null;
            foreach ($todasLasSesiones as $s) {
                if ($horaAhora < $s['hora_inicio']) {
                    $proximaSesion = $s;
                    break;
                }
            }

            if ($proximaSesion) {
                $horaInicio = substr($proximaSesion['hora_inicio'], 0, 5);
                return [
                    'ok'      => false,
                    'mensaje' => "No hay bloque activo. Su próxima clase inicia a las {$horaInicio}.",
                    'tipo'    => 'info',
                ];
            }

            return [
                'ok'      => false,
                'mensaje' => 'No tiene bloques horarios asignados para hoy.',
                'tipo'    => 'error',
            ];
        }

        $bloqueInicio = $sesion['horario_id_inicio'];

        $asistencia = Asistencia::where('usuario_id', $usuario->id)
            ->where('fecha', $hoy)
            ->where('horario_id', $bloqueInicio)
            ->first();

        if (!$asistencia) {
$estado = EstadoAsistencia::desdeLimite($horaAhora, $sesion['hora_inicio']);

            Asistencia::create([
                'usuario_id'    => $usuario->id,
                'fecha'         => $hoy,
                'horario_id'    => $bloqueInicio,
                'salon_id'      => $sesion['salon_id'],
                'bloque_inicio' => $sesion['hora_inicio'],
                'bloque_fin'    => $sesion['hora_fin'],
                'hora_entrada'  => $horaAhora,
                'estado'        => $estado,
            ]);

            $logData = [
                'user_id'        => $usuario->id,
                'horario_id'     => $bloqueInicio,
                'salon_id'   => $sesion['salon_id'],
                'estado'     => $estado,
            ];
            if ($ip) $logData['ip'] = $ip;
            ActivityLog::log('asistencia_entrada', "Entrada docente registrada: {$usuario->email}", 'asistencia', $logData);

            Log::info('Asistencia registrada', ['user' => $usuario->email, 'tipo' => 'entrada_docente', 'estado' => $estado]);

            if ($estado === 'Tarde') {
                $usuario->notify(new AsistenciaNotificacion('tarde', $hoy, $horaAhora, 'Tarde'));
            }

            $bloqueTexto = substr($sesion['hora_inicio'], 0, 5) . ' – ' . substr($sesion['hora_fin'], 0, 5);
            $mensajeBase = $estado === 'Tarde'
                ? "Bienvenido {$usuario->nombre_completo} — Entrada registrada (Tarde) [Bloque {$bloqueTexto}]"
                : "Bienvenido {$usuario->nombre_completo} — Entrada registrada [Bloque {$bloqueTexto}]";

            return [
                'ok'           => true,
                'mensaje'      => $mensajeBase,
                'tipo'         => $estado === 'Tarde' ? 'warning' : 'success',
                'nombre'       => $usuario->nombre_completo,
                'rol'          => $usuario->rol->nombre,
                'marca'        => 'entrada',
                'hora'         => $horaAhora,
                'estado'       => $estado,
                'bloque'       => $bloqueTexto,
            ];
        }

        if ($asistencia->hora_entrada && !$asistencia->hora_salida) {
            $asistencia->update(['hora_salida' => $horaAhora]);

            $logData = [
                'user_id'    => $usuario->id,
                'horario_id' => $bloqueInicio,
            ];
            if ($ip) $logData['ip'] = $ip;
            ActivityLog::log('asistencia_salida', "Salida docente registrada: {$usuario->email}", 'asistencia', $logData);

            $bloqueTexto = substr($sesion['hora_inicio'], 0, 5) . ' – ' . substr($sesion['hora_fin'], 0, 5);

            return [
                'ok'      => true,
                'mensaje' => "Hasta luego {$usuario->nombre_completo} — Salida registrada [Bloque {$bloqueTexto}]",
                'tipo'    => 'success',
                'nombre'  => $usuario->nombre_completo,
                'rol'     => $usuario->rol->nombre,
                'marca'   => 'salida',
                'hora'    => $horaAhora,
                'estado'  => $asistencia->estado,
                'bloque'  => $bloqueTexto,
            ];
        }

        $bloqueTexto = substr($sesion['hora_inicio'], 0, 5) . ' – ' . substr($sesion['hora_fin'], 0, 5);

        return [
            'ok'      => true,
            'mensaje' => "{$usuario->nombre_completo} ya registró entrada y salida para el bloque {$bloqueTexto}.",
            'tipo'    => 'info',
            'nombre'  => $usuario->nombre_completo,
            'rol'     => $usuario->rol->nombre,
            'bloque'  => $bloqueTexto,
        ];
    }

    public function procesarDocenteAdHoc(User $usuario, int $departamentoId, ?string $ip = null, ?int $salonId = null): array
    {
        $hoy      = today()->toDateString();
        $horaAhora = now()->format('H:i:s');

        $this->autoCerrarSalidaAnterior($usuario);

        $departamento = Departamento::find($departamentoId);
        if (!$departamento) {
            return $this->resultado(false, 'Departamento no válido.', 'error');
        }

        if ($salonId) {
            $salon = Salon::find($salonId);
        }
        if (!$salon) {
            $salon = Salon::where('nombre', 'Asistencia Especial')->first();
        }
        if (!$salon) {
            return $this->resultado(false, 'Salón virtual no configurado.', 'error');
        }

        $asistenciaAbierta = Asistencia::where('usuario_id', $usuario->id)
            ->where('fecha', $hoy)
            ->whereNull('hora_salida')
            ->first();

        if ($asistenciaAbierta) {
            $asistenciaAbierta->update(['hora_salida' => $horaAhora]);

            ActivityLog::log('asistencia_salida', "Salida ad-hoc registrada: {$usuario->email} → {$salon->nombre}", 'asistencia', [
                'user_id'   => $usuario->id,
                'salon'     => $salon->nombre,
            ]);

            return $this->resultado(true, "Hasta luego {$usuario->nombre_completo} — Salida registrada [{$salon->nombre}]", 'success', $usuario, 'salida', $horaAhora, 'Presente');
        }

        Asistencia::create([
            'usuario_id'      => $usuario->id,
            'fecha'           => $hoy,
            'salon_id'        => $salon->id,
            'departamento_id' => $departamento->id,
            'hora_entrada'    => $horaAhora,
            'estado'          => 'Presente',
        ]);

        ActivityLog::log('asistencia_entrada', "Entrada ad-hoc registrada: {$usuario->email} → {$salon->nombre}", 'asistencia', [
            'user_id'   => $usuario->id,
            'salon'     => $salon->nombre,
        ]);

        Log::info('Asistencia ad-hoc registrada', ['user' => $usuario->email, 'salon' => $salon->nombre]);

        return $this->resultado(true, "Bienvenido {$usuario->nombre_completo} — Entrada registrada [{$salon->nombre}]", 'success', $usuario, 'entrada', $horaAhora, 'Presente');
    }

    public function registrarManual(User $operador, User $empleado, string $tipo, ?string $ip = null): array
    {
        $hoy      = today()->toDateString();
        $horaAhora = now()->format('H:i:s');

        if ($empleado->rol_id === 3) {
            return $this->registrarManualDocente($operador, $empleado, $tipo, $hoy, $horaAhora, $ip);
        }

        if ($empleado->rol_id === 2) {
            return $this->registrarManualAdministrativo($operador, $empleado, $tipo, $hoy, $horaAhora, $ip);
        }

        return $this->resultado(false, 'Solo se puede registrar asistencia para Administrativos y Docentes.', 'error');
    }

    private function registrarManualDocente(User $operador, User $empleado, string $tipo, string $hoy, string $horaAhora, ?string $ip = null): array
    {
        if ($tipo === 'entrada') {
            $sesion = $empleado->obtenerSesionActiva($hoy, $horaAhora);

            if (!$sesion) {
                return $this->resultado(false, "{$empleado->nombre_completo} no tiene bloque activo en este momento.", 'info');
            }

            $bloqueInicio = $sesion['horario_id_inicio'];
            $yaRegistro = Asistencia::where('usuario_id', $empleado->id)
                ->where('fecha', $hoy)
                ->where('horario_id', $bloqueInicio)
                ->first();

            if ($yaRegistro) {
                return $this->resultado(false, "{$empleado->nombre_completo} ya tiene asistencia registrada para este bloque.", 'info');
            }

            $estado = EstadoAsistencia::desdeLimite($horaAhora, $sesion['hora_inicio']);

            Asistencia::create([
                'usuario_id'    => $empleado->id,
                'fecha'         => $hoy,
                'horario_id'    => $bloqueInicio,
                'salon_id'      => $sesion['salon_id'],
                'bloque_inicio' => $sesion['hora_inicio'],
                'bloque_fin'    => $sesion['hora_fin'],
                'hora_entrada'  => $horaAhora,
                'estado'        => $estado,
            ]);

            ActivityLog::log('asistencia_entrada', "Entrada manual (operador {$operador->email}): {$empleado->email}", 'asistencia', [
                'user_id'    => $empleado->id,
                'operador'   => $operador->id,
                'horario_id' => $bloqueInicio,
                'estado'     => $estado,
            ]);

            $bloqueTexto = substr($sesion['hora_inicio'], 0, 5) . ' – ' . substr($sesion['hora_fin'], 0, 5);
            $msgEstado = $estado === 'Tarde' ? ' (Tarde)' : '';

            return $this->resultado(true, "{$empleado->nombre_completo} — Entrada manual registrada [Bloque {$bloqueTexto}]{$msgEstado}", $estado === 'Tarde' ? 'warning' : 'success', $empleado, 'entrada', $horaAhora, $estado);
        }

        if ($tipo === 'salida') {
            $abierta = Asistencia::where('usuario_id', $empleado->id)
                ->where('fecha', $hoy)
                ->whereNull('hora_salida')
                ->first();

            if (!$abierta) {
                return $this->resultado(false, "{$empleado->nombre_completo} no tiene registro de entrada abierto hoy.", 'info');
            }

            $abierta->update(['hora_salida' => $horaAhora]);

            ActivityLog::log('asistencia_salida', "Salida manual (operador {$operador->email}): {$empleado->email}", 'asistencia', [
                'user_id'  => $empleado->id,
                'operador' => $operador->id,
            ]);

            return $this->resultado(true, "{$empleado->nombre_completo} — Salida manual registrada", 'success', $empleado, 'salida', $horaAhora);
        }

        return $this->resultado(false, 'Tipo de registro no válido.', 'error');
    }

    private function registrarManualAdministrativo(User $operador, User $empleado, string $tipo, string $hoy, string $horaAhora, ?string $ip = null): array
    {
        if ($tipo === 'entrada') {
            $yaRegistro = Asistencia::where('usuario_id', $empleado->id)
                ->where('fecha', $hoy)
                ->whereNull('horario_id')
                ->first();

            if ($yaRegistro) {
                return $this->resultado(false, "{$empleado->nombre_completo} ya tiene registro de asistencia hoy.", 'info');
            }

            $estado = EstadoAsistencia::desdeHorarioFijo(
                $horaAhora,
                $empleado->horarioAdministrativo?->hora_entrada,
                now()->dayOfWeek === Carbon::SATURDAY
            );

            Asistencia::create([
                'usuario_id'   => $empleado->id,
                'fecha'        => $hoy,
                'hora_entrada' => $horaAhora,
                'estado'       => $estado,
            ]);

            ActivityLog::log('asistencia_entrada', "Entrada manual (operador {$operador->email}): {$empleado->email}", 'asistencia', [
                'user_id'  => $empleado->id,
                'operador' => $operador->id,
                'estado'   => $estado,
            ]);

            return $this->resultado(true, "{$empleado->nombre_completo} — Entrada manual registrada [{$estado}]", $estado === 'Tarde' ? 'warning' : 'success', $empleado, 'entrada', $horaAhora, $estado);
        }

        if ($tipo === 'salida') {
            $abierta = Asistencia::where('usuario_id', $empleado->id)
                ->where('fecha', $hoy)
                ->whereNull('horario_id')
                ->whereNull('hora_salida')
                ->first();

            if (!$abierta) {
                return $this->resultado(false, "{$empleado->nombre_completo} no tiene registro de entrada abierto hoy.", 'info');
            }

            $abierta->update(['hora_salida' => $horaAhora]);

            ActivityLog::log('asistencia_salida', "Salida manual (operador {$operador->email}): {$empleado->email}", 'asistencia', [
                'user_id'  => $empleado->id,
                'operador' => $operador->id,
            ]);

            return $this->resultado(true, "{$empleado->nombre_completo} — Salida manual registrada", 'success', $empleado, 'salida', $horaAhora);
        }

        return $this->resultado(false, 'Tipo de registro no válido.', 'error');
    }

    public function autoCerrarSalidaAnterior(User $usuario): void
    {
        $hoy      = today()->toDateString();
        $horaAhora = now()->format('H:i:s');

        $asistenciaAbierta = Asistencia::where('usuario_id', $usuario->id)
            ->where('fecha', $hoy)
            ->whereNull('hora_salida')
            ->first();

        if ($asistenciaAbierta) {
            $asistenciaAbierta->update(['hora_salida' => $horaAhora]);

            ActivityLog::log('asistencia_salida', "Salida automática: {$usuario->email}", 'asistencia', [
                'user_id'    => $usuario->id,
                'horario_id' => $asistenciaAbierta->horario_id,
                'salon_id'   => $asistenciaAbierta->salon_id,
            ]);
        }
    }

    private function resultado(bool $ok, string $mensaje, string $tipo, ?User $user = null, ?string $marca = null, ?string $hora = null, ?string $estado = null): array
    {
        $r = ['ok' => $ok, 'mensaje' => $mensaje, 'tipo' => $tipo];
        if ($user) {
            $r['nombre'] = $user->nombre_completo;
            $r['rol']    = $user->rol->nombre;
        }
        if ($marca) $r['marca'] = $marca;
        if ($hora)  $r['hora']  = $hora;
        if ($estado) $r['estado'] = $estado;
        return $r;
    }
}
