<?php

namespace App\Http\Controllers;

use App\Models\QrToken;
use App\Models\User;
use App\Traits\GeneratesQrSvg;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QrController extends Controller
{
    use GeneratesQrSvg;

    public function showPage(): View
    {
        $user = Auth::user();
        return view('auth.qr_dinamico', compact('user'));
    }

    public function show(User $user): Response
    {
        $esPropio = auth()->check() && auth()->id() === $user->id;
        $authUser = auth()->user();
        $puedeVerGestion = $authUser ? method_exists($authUser, 'can') ? (bool) $authUser->can('usuarios_ver') : false : false;

        abort_unless($esPropio || $puedeVerGestion, 403, 'No autorizado para ver el QR de otro usuario.');

        if (blank($user->codigo_qr_estatico)) {
            abort(404, 'Este usuario no tiene QR estático (usa QR dinámico).');
        }

        $svg = $this->renderSvg((string) $user->codigo_qr_estatico);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function generarToken(User $user): JsonResponse
    {
        abort_unless($user->id === auth()->id(), 403, 'No autorizado para generar tokens de otro usuario.');

        QrToken::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->delete();

        $token = bin2hex(random_bytes(20));
        $expiresAt = now()->addSeconds(60);

        QrToken::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => $expiresAt,
        ]);

        $payload = "DynQR:{$user->id}:{$token}:{$expiresAt->timestamp}";

        return response()->json([
            'ok'         => true,
            'token'      => $payload,
            'expires_at' => $expiresAt->format('H:i:s'),
        ]);
    }

    public function qrDinamico(User $user): Response
    {
        abort_unless($user->id === auth()->id(), 403, 'No autorizado para ver el QR dinámico de otro usuario.');

        QrToken::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->delete();

        $token = bin2hex(random_bytes(20));
        $expiresAt = now()->addSeconds(60);

        QrToken::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => $expiresAt,
        ]);

        $payload = "DynQR:{$user->id}:{$token}:{$expiresAt->timestamp}";

        $svg = $this->renderSvg($payload);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function validarToken(string $dynQr): ?User
    {
        if (!str_starts_with($dynQr, 'DynQR:')) {
            return null;
        }

        $parts = explode(':', $dynQr);
        if (count($parts) !== 4) {
            return null;
        }

        [, $userId, $token, $expiresAt] = $parts;

        $qrToken = QrToken::where('user_id', $userId)
            ->where('token', $token)
            ->first();

        if (!$qrToken || $qrToken->isExpired()) {
            if ($qrToken) {
                $qrToken->delete();
            }
            return null;
        }

        $qrToken->delete();

        return User::where('id', $userId)
            ->where('estatus', 'activo')
            ->first();
    }
}
