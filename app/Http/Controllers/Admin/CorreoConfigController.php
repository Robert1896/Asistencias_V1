<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCorreoConfigRequest;
use App\Models\ActivityLog;
use App\Models\CorreoConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CorreoConfigController extends Controller
{
    public function index()
    {
        $config = CorreoConfig::firstOrCreate([], [
            'host'         => 'smtp.gmail.com',
            'puerto'       => 587,
            'encriptacion' => 'tls',
            'nombre_from'  => 'Sistema de Asistencia',
            'activo'       => false,
        ]);

        return view('admin.correo_config', compact('config'));
    }

    public function update(UpdateCorreoConfigRequest $request)
    {
        $data = $request->validated();
        $data['activo'] = $request->boolean('activo');
        $data['contrasena'] = Crypt::encryptString($data['contrasena']);

        $config = CorreoConfig::firstOrCreate([], []);
        $config->update($data);

        ActivityLog::log('correo_config', "Configuración de correo actualizada", 'sistema');

        Log::info('Config correo actualizada');

        return redirect()->route('admin.correo')
            ->with('success', 'Configuración de correo actualizada correctamente.');
    }

    public function probar(Request $request)
    {
        $request->validate([
            'email_prueba' => 'required|email',
        ]);

        $config = CorreoConfig::where('activo', true)->first();

        if (!$config) {
            return back()->with('error', 'No hay configuración de correo activa. Guarda la configuración primero.');
        }

        $password = $this->decryptPassword($config->contrasena);

        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => $config->host]);
        config(['mail.mailers.smtp.port' => $config->puerto]);
        config(['mail.mailers.smtp.encryption' => $config->encriptacion]);
        config(['mail.mailers.smtp.username' => $config->usuario]);
        config(['mail.mailers.smtp.password' => $password]);
        config(['mail.from.address' => $config->direccion_from]);
        config(['mail.from.name' => $config->nombre_from]);

        try {
            Mail::raw('Este es un correo de prueba del Sistema de Asistencia. Si lo recibes, la configuración es correcta.', function ($message) use ($request) {
                $message->to($request->email_prueba)
                    ->subject('Prueba de correo - Sistema de Asistencia');
            });

            ActivityLog::log('correo_prueba', "Correo de prueba enviado a: {$request->email_prueba}", 'sistema');

            Log::info('Correo de prueba enviado a: ' . $request->email_prueba);

            return back()->with('success', "Correo de prueba enviado a {$request->email_prueba}. Revisa tu bandeja de entrada (y spam).");
        } catch (\Exception $e) {
            Log::error('Error enviando correo de prueba: ' . $e->getMessage());

            return back()->with('error', "Error al enviar: {$e->getMessage()}");
        }
    }

    private function decryptPassword(string $encrypted): string
    {
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            Log::error('Error desencriptando contraseña de correo: ' . $e->getMessage());
            throw new \RuntimeException('No se pudo desencriptar la contraseña del correo configurado.');
        }
    }
}
