<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = Auth::user();
        return view('auth.profile', compact('user'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validated();

        $cambiandoPassword = !empty($data['password']);
        $cambiandoEmail    = ($data['email'] ?? '') !== $user->email;

        // Fase C: cambios sensibles de la cuenta requieren la contraseña actual (CWE-620)
        if ($cambiandoPassword || $cambiandoEmail) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
            ], [
                'current_password.required'          => 'Confirma tu contraseña actual para cambiar el correo o la contraseña.',
                'current_password.current_password'  => 'La contraseña actual es incorrecta.',
            ]);
        }

        $payload = [
            'nombre'   => strip_tags($data['nombre']),
            'apellido' => strip_tags($data['apellido']),
            'cedula'   => $data['cedula'],
            'email'    => $data['email'],
        ];

        if ($cambiandoPassword) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        if ($cambiandoPassword) {
            $user->invalidarOtrasSesiones($request->session()->getId());
        }

        ActivityLog::log('perfil_editar', "Perfil actualizado: {$user->email}", 'usuarios', ['user_id' => $user->id]);

        Log::info('Perfil actualizado', ['user_id' => $user->id]);

        return redirect()->route('profile.edit')
            ->with('success', 'Perfil actualizado correctamente.');
    }
}
