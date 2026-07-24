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
            'tipo'  => 'nullable|string|max:50',
            'fecha' => 'nullable|date',
        ]);

        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($tipo = $request->input('tipo')) {
            $query->where('tipo', $tipo);
        }

        if ($fecha = $request->input('fecha')) {
            $query->whereDate('created_at', $fecha);
        }

        $actividades = $query->paginate(15)->withQueryString();

        return view('admin.bitacora', compact('actividades'));
    }
}
