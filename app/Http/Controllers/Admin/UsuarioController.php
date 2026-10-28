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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('rol');

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('apellido', 'like', '%' . $buscar . '%')
                    ->orWhere('cedula', 'like', '%' . $buscar . '%')
                    ->orWhere('email', 'like', '%' . $buscar . '%')
                    ->orWhereRaw("CONCAT(COALESCE(nombre,''), ' ', COALESCE(apellido,'')) LIKE ?", ['%' . $buscar . '%']);
            });
        }

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->input('rol_id'));
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        $usuarios = $query->orderBy('id')->paginate(15)->withQueryString();
        $roles    = Rol::orderBy('id')->get();

        // Asegurar que el rol Técnico esté disponible en el formulario
        if (!$roles->contains('id', 5)) {
            try {
                $tecnico = Rol::firstOrCreate(
                    ['id' => 5],
                    ['nombre' => 'Técnico', 'descripcion' => 'Soporte técnico con funciones operativas']
                );
                if (!isset($tecnico->id)) {
                    $tecnico->id = 5;
                    $tecnico->nombre = 'Técnico';
                    $tecnico->descripcion = 'Soporte técnico con funciones operativas';
                }
                $roles = $roles->push($tecnico)->sortBy('id')->values();
            } catch (\Throwable $e) {
                $roles = $roles->push((object)['id' => 5, 'nombre' => 'Técnico'])->sortBy('id')->values();
            }
        }

        return view('admin.usuarios', compact('usuarios', 'roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
        } catch (\Throwable $e) {
            $data = $request->all();
        }

        if (isset($data['rol_id']) && (int)$data['rol_id'] === 5) {
        try {
            \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(
                ['id' => 5],
                ['nombre' => 'Técnico', 'descripcion' => 'Soporte técnico con funciones operativas']
            );
        } catch (\Throwable $e) {
        }
        }

        try {
            User::create([
                'password'           => $data['password'],
                'nombre'             => isset($data['nombre']) ? strip_tags($data['nombre']) : '',
                'apellido'           => isset($data['apellido']) ? strip_tags($data['apellido']) : '',
                'cedula'             => $data['cedula'] ?? null,
                'email'              => $data['email'] ?? null,
                'rol_id'             => isset($data['rol_id']) ? (int)$data['rol_id'] : 2,
                'estatus'            => 'activo',
            ]);
        } catch (\Throwable $e) {
            try {
                \Illuminate\Support\Facades\DB::statement("INSERT IGNORE INTO roles (id, nombre, descripcion, created_at, updated_at) VALUES (5, 'Técnico', 'Soporte técnico con funciones operativas', NOW(), NOW())");
                User::create([
                    'password'           => $data['password'],
                    'nombre'             => isset($data['nombre']) ? strip_tags($data['nombre']) : '',
                    'apellido'           => isset($data['apellido']) ? strip_tags($data['apellido']) : '',
                    'cedula'             => $data['cedula'] ?? null,
                    'email'              => $data['email'] ?? null,
                    'rol_id'             => isset($data['rol_id']) ? (int)$data['rol_id'] : 2,
                    'estatus'            => 'activo',
                ]);
            } catch (\Throwable $e2) {
                return back()->with('error', 'No se pudo crear el usuario.')->withInput();
            }
        }

        try {
            ActivityLog::log('usuario_crear', "Creó el usuario {$data['email']}", 'usuarios', ['email' => $data['email']]);
        } catch (\Throwable $e) {
        }

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

        $cambioPassword = !empty($data['password']);

        if ($cambioPassword) {
            $payload['password'] = $data['password'];
        }

        $usuario->update($payload);

        if ($cambioPassword) {
            // Fase C: la contraseña nueva invalida recordatorios/sesiones del editado
            $usuario->invalidarOtrasSesiones();
        }

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

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        // Una cuenta Superadmin solo puede ser modificada por otra Superadmin (CWE-269)
        abort_if((int) $usuario->rol_id === 1 && (int) auth()->user()->rol_id !== 1, 403);

        // Requisito #19: step-up authentication. El operador debe reingresar su
        // contraseña para confirmar una acción destructiva.
        $request->validate([
            'password_confirmacion' => 'required|string',
        ], [
            'password_confirmacion.required' => 'Debes ingresar tu contraseña para confirmar la eliminación.',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->input('password_confirmacion'), auth()->user()->password)) {
            ActivityLog::log('usuario_eliminar_denegado', "Contraseña incorrecta al intentar eliminar a {$usuario->email}", 'usuarios', [
                'email'   => $usuario->email,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors(['password_confirmacion' => 'La contraseña no es correcta. No se eliminó el usuario.']);
        }

        $email = $usuario->email;
        $usuario->delete();

        ActivityLog::log('usuario_eliminar', "Eliminó al usuario {$email}", 'usuarios', ['email' => $email]);

        return redirect()->route('admin.usuarios')->with('success', 'Usuario eliminado.');
    }
}
