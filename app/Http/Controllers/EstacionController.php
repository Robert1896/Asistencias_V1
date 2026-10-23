<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\DiaFeriado;
use App\Models\Salon;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class EstacionController extends Controller
{
    private QrController $qrController;
    private AttendanceService $attendanceService;

    public function __construct(QrController $qrController, AttendanceService $attendanceService)
    {
        $this->qrController = $qrController;
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $estacionCentral = \App\Models\EstacionCentral::obtener();
        return view('estacion.index', compact('estacionCentral'));
    }

    private function verificarDiaHabil(): ?JsonResponse
    {
        $hoy = today()->toDateString();

        if (now()->dayOfWeek === \Carbon\Carbon::SUNDAY) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Hoy es domingo — no hay labores.',
                'tipo'    => 'error',
            ]);
        }

        if (DiaFeriado::esFeriado($hoy)) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Hoy es día feriado — no hay labores.',
                'tipo'    => 'error',
            ]);
        }

        return null;
    }

    public function procesarLectura(Request $request): JsonResponse
    {
        $ip = $request->ip();

        if (RateLimiter::tooManyAttempts('scan:' . $ip, 30)) {
            $seconds = RateLimiter::availableIn('scan:' . $ip);

            ActivityLog::log('scan_bloqueado', "Rate limit en escaneo desde IP: {$ip}", 'seguridad', [
                'ip' => $ip,
            ]);

            return response()->json([
                'ok'      => false,
                'mensaje' => "Demasiadas solicitudes. Espera {$seconds} segundos.",
                'tipo'    => 'error',
            ], 429);
        }

        RateLimiter::hit('scan:' . $ip, 60);

        $qrHash = trim($request->input('qr_hash', ''));

        if (empty($qrHash)) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Código QR vacío.',
                'tipo'    => 'error',
            ], 400);
        }

        if (strlen($qrHash) > 255) {
            ActivityLog::log('scan_sospechoso', "QR excesivamente largo desde IP: {$ip}", 'seguridad', [
                'ip'        => $ip,
                'longitud'  => strlen($qrHash),
            ]);

            Log::warning('Escaneo sospechoso', ['ip' => $ip, 'razon' => 'QR excesivamente largo', 'longitud' => strlen($qrHash)]);

            return response()->json([
                'ok'      => false,
                'mensaje' => 'Código QR inválido.',
                'tipo'    => 'error',
            ], 400);
        }

        $usuario = null;

        if (str_starts_with($qrHash, 'DynQR:')) {
            $usuario = $this->qrController->validarToken($qrHash);
        }

        if (!$usuario) {
            $usuario = User::where('codigo_qr_estatico', $qrHash)
                ->where('estatus', 'activo')
                ->where('rol_id', '!=', 3)
                ->with('rol')
                ->first();
        }

        if (!$usuario && str_starts_with($qrHash, 'DynQR:')) {
            $parts = explode(':', $qrHash);
            if (count($parts) === 4) {
                $userId = (int) $parts[1];
                $usuario = User::where('id', $userId)
                    ->where('estatus', 'activo')
                    ->with('rol')
                    ->first();
            }
        }

        if (!$usuario && str_starts_with($qrHash, 'DynQR:')) {
            $parts = explode(':', $qrHash);
            if (count($parts) === 4) {
                $userId = (int) $parts[1];
                $token = $parts[2];

                $qrToken = \App\Models\QrToken::where('user_id', $userId)
                    ->where('token', $token)
                    ->first();

                if ($qrToken && $qrToken->expires_at && $qrToken->expires_at->timestamp >= now()->timestamp) {
                    $qrToken->delete();
                    $usuario = User::where('id', $userId)
                        ->where('estatus', 'activo')
                        ->with('rol')
                        ->first();
                } elseif ($qrToken) {
                    $qrToken->delete();
                }
            }
        }

        if ($usuario && (int) $usuario->rol_id === 1) {
            // Permitir marcaje con QR dinámico para superadmin
        }

        if ($usuario && (int) $usuario->rol_id === 1 && blank($usuario->codigo_qr_estatico)) {
            // Permitir superadmin con solo QR dinámico
        }

        if (!$usuario) {
            ActivityLog::log('scan_fallido', "QR no reconocido desde IP: {$ip}", 'seguridad', [
                'ip'     => $ip,
                'qr_hash' => substr($qrHash, 0, 50),
            ]);

            Log::warning('Escaneo sospechoso', ['ip' => $ip, 'razon' => 'QR no reconocido']);

            return response()->json([
                'ok'      => false,
                'mensaje' => 'QR no reconocido, expirado o usuario inactivo.',
                'tipo'    => 'error',
            ]);
        }

        $hoy      = today()->toDateString();
        $horaAhora = now()->format('H:i:s');
        $diaSemana = now()->dayOfWeek;

        $rolId            = (int) $usuario->rol_id;
        $esDocente        = $rolId === 3;
        $esAdministrativo = $rolId === 2;
        $esAsistente      = $rolId === 4;
        $esSuperadmin     = $rolId === 1;

        if ($respuesta = $this->verificarDiaHabil()) return $respuesta;

        if ($esAdministrativo || $esAsistente || $esSuperadmin) {
            $resultado = $this->attendanceService->procesarAdministrativo($usuario, $qrHash, $ip);
            return response()->json($resultado);
        }

        if ($esDocente) {
            $sesion = $usuario->obtenerSesionActiva($hoy, $horaAhora);
            if (!$sesion) {
                $departamento = Departamento::where('codigo', 'INFO')->first();

                $salonesOcupados = Asistencia::where('fecha', $hoy)
                    ->whereNull('hora_salida')
                    ->whereNotNull('salon_id')
                    ->pluck('salon_id');

                $salonEspecial = Salon::where('codigo', 'ADHOC-001')->first(['id', 'nombre', 'codigo']);

                $salonesDisponibles = Salon::where('codigo', '!=', 'ADHOC-001')
                    ->whereNotIn('id', $salonesOcupados)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre', 'codigo']);

                return response()->json([
                    'ok'                   => false,
                    'requires_department'  => true,
                    'mensaje'              => 'No tiene bloque activo. Seleccione un salón.',
                    'tipo'                 => 'info',
                    'nombre'               => $usuario->nombre_completo,
                    'user_id'              => $usuario->id,
                    'departamento_id'      => $departamento?->id,
                    'salon_especial'       => $salonEspecial,
                    'salones_disponibles'  => $salonesDisponibles,
                ]);
            }
            $resultado = $this->attendanceService->procesarDocentePorSesion($usuario, $hoy, $horaAhora, $ip);
            return response()->json($resultado);
        }

        return response()->json([
            'ok'      => false,
            'mensaje' => 'Rol no reconocido.',
            'tipo'    => 'error',
        ]);
    }

    public function escanearSalon()
    {
        $user = Auth::user();
        $hoy = today()->toDateString();
        $horaAhora = now()->format('H:i:s');
        $bloqueActual = $user->obtenerSesionActiva($hoy, $horaAhora);

        return view('empleado.escanear_salon', compact('user', 'bloqueActual'));
    }

    public function procesarSalonQr(Request $request): JsonResponse
    {
        $ip = $request->ip();

        if (RateLimiter::tooManyAttempts('salon-scan:' . $ip, 30)) {
            $seconds = RateLimiter::availableIn('salon-scan:' . $ip);
            return response()->json([
                'ok'      => false,
                'mensaje' => "Demasiadas solicitudes. Espera {$seconds} segundos.",
                'tipo'    => 'error',
            ], 429);
        }

        RateLimiter::hit('salon-scan:' . $ip, 60);

        $usuario = Auth::user();

        if (!$usuario || $usuario->rol_id !== 3) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Solo los docentes pueden escanear QR de salón.',
                'tipo'    => 'error',
            ], 403);
        }

        $salonQr = trim($request->input('salon_qr', ''));

        if (empty($salonQr)) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Código QR de salón vacío.',
                'tipo'    => 'error',
            ], 400);
        }

        $salon = Salon::where('codigo_qr', $salonQr)->first();

        if (!$salon) {
            ActivityLog::log('scan_salon_fallido', "QR de salón no reconocido desde IP: {$ip}", 'seguridad', [
                'user_id' => $usuario->id,
                'ip'      => $ip,
            ]);

            Log::warning('Escaneo sospechoso', ['user' => $usuario->email, 'ip' => $ip, 'razon' => 'QR de salón no reconocido']);

            return response()->json([
                'ok'      => false,
                'mensaje' => 'Salón no reconocido.',
                'tipo'    => 'error',
            ]);
        }

        $hoy      = today()->toDateString();
        $horaAhora = now()->format('H:i:s');
        $diaSemana = now()->dayOfWeek;

        if ($respuesta = $this->verificarDiaHabil()) return $respuesta;

        $resultado = $this->attendanceService->procesarDocente($usuario, $salonQr, $ip);
        return response()->json($resultado);
    }

    public function procesarAdHoc(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'departamento_id' => 'required|exists:departamentos,id',
            'salon_id'       => 'nullable|exists:salones,id',
        ]);

        $usuario = User::with('rol')->findOrFail($request->user_id);

        // Fase B: asistencia ad-hoc solo aplica a roles Administrativo o Docente
        // (nunca Superadmin ni Asistente, para no auto-registrarse desde la estación)
        if (!in_array((int) $usuario->rol_id, [2, 3], true)) {
            return response()->json([
                'ok'      => false,
                'tipo'    => 'error',
                'mensaje' => 'La asistencia ad-hoc solo aplica a roles Administrativo o Docente.',
            ], 422);
        }

        $ip = $request->ip();

        $resultado = $this->attendanceService->procesarDocenteAdHoc(
            $usuario,
            $request->departamento_id,
            $ip,
            $request->salon_id
        );
        return response()->json($resultado);
    }

    public function registroManual()
    {
        $departamentos = Departamento::orderBy('nombre')->get();
        $estacionCentral = \App\Models\EstacionCentral::obtener();
        return view('estacion.manual', compact('departamentos', 'estacionCentral'));
    }

    public function buscarEmpleado(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $empleados = User::where('estatus', 'activo')
            ->whereIn('rol_id', [2, 3])
            ->where(function ($q) use ($query) {
                $escaped = escape_like($query);
                $q->where('nombre', 'like', $escaped)
                  ->orWhere('apellido', 'like', $escaped)
                  ->orWhere('nombre_completo', 'like', $escaped)
                  ->orWhere('cedula', 'like', $escaped);
            })
            ->with('rol')
            ->limit(10)
            ->get(['id', 'nombre', 'apellido', 'nombre_completo', 'cedula', 'rol_id']);

        return response()->json($empleados);
    }

    public function registrarManual(Request $request): JsonResponse
    {
        $request->validate([
            'empleado_id' => 'required|exists:users,id',
            'tipo'        => 'required|in:entrada,salida',
        ]);

        $operador = Auth::user();
        $empleado = User::with('rol')->findOrFail($request->empleado_id);

        $resultado = $this->attendanceService->registrarManual($operador, $empleado, $request->tipo, $request->ip());
        return response()->json($resultado);
    }
}
