<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\AsistenciaNotificacion;
use App\Services\ImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        $usuarios = User::with('rol')->orderBy('id')->paginate(15);
        $roles    = Rol::orderBy('id')->get();
        return view('admin.usuarios', compact('usuarios', 'roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'password'           => $data['password'],
            'nombre'             => strip_tags($data['nombre']),
            'apellido'           => strip_tags($data['apellido']),
            'cedula'             => $data['cedula'],
            'email'              => $data['email'],
            'rol_id'             => $data['rol_id'],
            'estatus'            => 'activo',
            'codigo_qr_estatico' => ($data['rol_id'] == 3 && DB::getDriverName() === 'mysql') ? null : Str::uuid()->toString(),
        ]);

        ActivityLog::log('usuario_crear', "Creó el usuario {$data['email']}", 'usuarios', ['email' => $data['email']]);

        return redirect()->route('admin.usuarios')->with('success', 'Usuario creado correctamente.');
    }

    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        $data = $request->validated();

        $payload = [
            'nombre'   => strip_tags($data['nombre']),
            'apellido' => strip_tags($data['apellido']),
            'cedula'   => $data['cedula'],
            'email'    => $data['email'],
            'rol_id'   => $data['rol_id'],
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $usuario->update($payload);

        ActivityLog::log('usuario_editar', "Editó el usuario {$usuario->email}", 'usuarios', ['email' => $usuario->email]);

        return redirect()->route('admin.usuarios')->with('success', 'Usuario actualizado.');
    }

    public function importar(Request $request, ImportService $importService): RedirectResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        $resultado = $importService->importarUsuarios($request->file('archivo'));

        $mensaje = "Se crearon {$resultado['created']} usuarios.";
        if (!empty($resultado['errors'])) {
            $mensaje .= ' ' . count($resultado['errors']) . ' filas con errores.';
        }

        return redirect()->route('admin.usuarios')->with('success', $mensaje)
            ->with('errores_importar', $resultado['errors']);
    }

    public function toggleEstatus(User $usuario): RedirectResponse
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar tu propio estatus.');
        }

        // Una cuenta Superadmin solo puede ser modificada por otra Superadmin (CWE-269)
        abort_if((int) $usuario->rol_id === 1 && (int) auth()->user()->rol_id !== 1, 403);

        $nuevoEstatus = $usuario->estatus === 'activo' ? 'inactivo' : 'activo';
        $usuario->update(['estatus' => $nuevoEstatus]);

        $tipoNotif = $nuevoEstatus === 'activo' ? 'activo' : 'inactivo';
        $usuario->notify(new AsistenciaNotificacion($tipoNotif, now()->toDateString()));

        $accion = $nuevoEstatus === 'activo' ? 'usuario_activar' : 'usuario_desactivar';
        $desc = $nuevoEstatus === 'activo'
            ? "Activó al usuario {$usuario->email}"
            : "Desactivó al usuario {$usuario->email}";
        ActivityLog::log($accion, $desc, 'usuarios', ['email' => $usuario->email, 'nuevo_estatus' => $nuevoEstatus]);

        $mensaje = $nuevoEstatus === 'activo'
            ? "Usuario {$usuario->nombre_completo} activado correctamente."
            : "Usuario {$usuario->nombre_completo} desactivado.";

        return redirect()->route('admin.usuarios')->with('success', $mensaje);
    }

    public function destroy(User $usuario): RedirectResponse
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        // Una cuenta Superadmin solo puede ser modificada por otra Superadmin (CWE-269)
        abort_if((int) $usuario->rol_id === 1 && (int) auth()->user()->rol_id !== 1, 403);

        $email = $usuario->email;
        $usuario->delete();

        ActivityLog::log('usuario_eliminar', "Eliminó al usuario {$email}", 'usuarios', ['email' => $email]);

        return redirect()->route('admin.usuarios')->with('success', 'Usuario eliminado.');
    }
}
