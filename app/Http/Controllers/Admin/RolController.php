<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RolController extends Controller
{
    public function index(): View
    {
        $roles         = Rol::with('permisos')->orderBy('id')->get();
        $permisos      = Permiso::orderBy('modulo')->orderBy('id')->get();
        $agrupados     = $permisos->groupBy('modulo');

        return view('admin.roles', compact('roles', 'permisos', 'agrupados'));
    }

    public function updatePermisos(Request $request, Rol $rol): RedirectResponse
    {
        $actor = auth()->user();

        // Solo el Superadmin puede modificar el rol Superadmin, y nadie edita su propio rol
        // (evita autopromoción de permisos: CWE-269/863)
        abort_if($rol->id === 1 && $actor->rol_id !== 1, 403);
        abort_if($rol->id === $actor->rol_id, 403);

        $data = $request->validate([
            'permisos'   => 'nullable|array',
            'permisos.*' => 'integer|exists:permisos,id',
        ]);

        $rol->permisos()->sync($data['permisos'] ?? []);

        Log::info('Permisos actualizados para rol: ' . $rol->nombre, ['rol_id' => $rol->id]);

        ActivityLog::log('rol_permisos', "Permisos actualizados para rol: {$rol->nombre}", 'usuarios', ['rol_id' => $rol->id]);

        return redirect()->route('admin.roles')->with('success', "Permisos actualizados para {$rol->nombre}.");
    }
}
