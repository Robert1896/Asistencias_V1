<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CargaAcademicaDocente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CargaAcademicaController extends Controller
{
    public function index(Request $request): View
    {
        $query = CargaAcademicaDocente::with('usuario')->orderBy('usuario_id');

        if ($busqueda = $request->input('busqueda')) {
            $escaped = escape_like($busqueda);

            $query->where(function ($q) use ($escaped) {
                $q->whereHas('usuario', fn($u) => $u->where('nombre_completo', 'like', $escaped))
                  ->orWhere('materia', 'like', $escaped);
            });
        }

        $cargas = $query->get();
        $docentes = User::whereHas('rol', fn($q) => $q->where('nombre', 'Docente'))
            ->where('estatus', 'activo')
            ->orderBy('nombre_completo')
            ->get();

        return view('admin.carga_academica.index', compact('cargas', 'docentes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario_id'       => 'required|exists:users,id',
            'materia'          => 'required|string|max:255',
            'tipo'             => 'required|in:Clase,Asesoría,Otra',
            'horas_semanales'  => 'required|numeric|min:0.5',
        ]);

        CargaAcademicaDocente::create($data);

        return redirect()->route('admin.materias')->with('success', 'Materia creada correctamente.');
    }

    public function update(Request $request, CargaAcademicaDocente $carga)
    {
        $data = $request->validate([
            'materia'          => 'required|string|max:255',
            'tipo'             => 'required|in:Clase,Asesoría,Otra',
            'horas_semanales'  => 'required|numeric|min:0.5',
        ]);

        $carga->update($data);

        return redirect()->route('admin.materias')->with('success', 'Materia actualizada correctamente.');
    }

    public function destroy(CargaAcademicaDocente $carga)
    {
        $carga->delete();

        return redirect()->route('admin.materias')->with('success', 'Materia eliminada correctamente.');
    }
}
