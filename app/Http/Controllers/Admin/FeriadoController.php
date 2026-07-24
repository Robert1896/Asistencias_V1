<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeriadoRequest;
use App\Models\ActivityLog;
use App\Models\DiaFeriado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeriadoController extends Controller
{
    public function index(Request $request): View
    {
        $anio = $request->input('anio', now()->year);

        $feriados = DiaFeriado::whereYear('fecha', $anio)
            ->orderBy('fecha')
            ->get();

        return view('admin.feriados', compact('feriados', 'anio'));
    }

    public function store(StoreFeriadoRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DiaFeriado::create([
            'fecha'       => $data['fecha'],
            'nombre'      => strip_tags($data['nombre']),
            'recurrente'  => $data['recurrente'] ?? false,
        ]);

        ActivityLog::log('feriado_crear', "Agregó día feriado: {$data['nombre']} ({$data['fecha']})", 'sistema');

        return redirect()->route('admin.feriados')->with('success', 'Día feriado agregado.');
    }

    public function destroy(DiaFeriado $feriado): RedirectResponse
    {
        $nombre = $feriado->nombre;
        $feriado->delete();

        ActivityLog::log('feriado_eliminar', "Eliminó día feriado: {$nombre}", 'sistema');

        return redirect()->route('admin.feriados')->with('success', 'Día feriado eliminado.');
    }
}
