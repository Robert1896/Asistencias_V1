<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'tipo'        => 'nullable|string|max:50',
            'fecha'       => 'nullable|date',
            'dispositivo' => 'nullable|string|in:pc,movil,tablet,desconocido',
            'usuario'     => 'nullable|string|max:100',
        ]);

        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($tipo = $request->input('tipo')) {
            $query->where('tipo', $tipo);
        }

        if ($fecha = $request->input('fecha')) {
            $query->whereDate('created_at', $fecha);
        }

        if ($dispositivo = $request->input('dispositivo')) {
            $query->where('dispositivo', $dispositivo);
        }

        if ($usuario = $request->input('usuario')) {
            $query->whereHas('user', function ($q) use ($usuario) {
                $q->where('nombre_completo', 'like', '%' . $usuario . '%')
                    ->orWhere('email', 'like', '%' . $usuario . '%');
            });
        }

        $actividades = $query->paginate(20)->withQueryString();

        return view('admin.bitacora', compact('actividades'));
    }
}
