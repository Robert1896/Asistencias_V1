<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCargaRequest;
use App\Models\ActivityLog;
use App\Models\CargaAcademicaDocente;
use App\Models\HorarioAdministrativo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HorarioController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'administrativos');

        $administrativos = User::whereIn('rol_id', [2, 4, 5])->where('estatus', 'activo')
            ->orderBy('nombre_completo')
            ->get(['id', 'nombre_completo']);

        $docentes = User::query()
            ->where('rol_id', 3)
            ->where('estatus', 'activo')
            ->orderBy('nombre_completo')
            ->get();

        $docentes = $docentes->load(['horariosDocente.horario', 'horariosDocente.salon', 'cargaAcademica']);

        $horariosAdmin = HorarioAdministrativo::with('usuario')->whereHas('usuario', function ($q) {
            $q->whereIn('rol_id', [2, 4, 5])->where('estatus', 'activo');
        })->get();
        $cargasDocentes = CargaAcademicaDocente::with('usuario')->get();

        return view('admin.horarios', compact(
            'tab', 'administrativos', 'docentes', 'horariosAdmin', 'cargasDocentes'
        ));
    }

    public function edit(User $usuario): View
    {
        $horario = HorarioAdministrativo::where('usuario_id', $usuario->id)->first();

        return view('admin.horario_edit', compact('usuario', 'horario'));
    }

    public function guardarAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'usuario_id'   => ['required', 'integer', Rule::exists('users', 'id')->where(function ($q) {
                $q->whereIn('rol_id', [2, 4, 5])->where('estatus', 'activo');
            })],
            'turno'        => 'required|in:Matutino,Vespertino',
            'hora_entrada' => 'required|date_format:H:i',
            'hora_salida'  => 'required|date_format:H:i',
            'lunes'        => 'nullable|boolean',
            'martes'       => 'nullable|boolean',
            'miercoles'    => 'nullable|boolean',
            'jueves'       => 'nullable|boolean',
            'viernes'      => 'nullable|boolean',
            'sabado'       => 'nullable|boolean',
        ]);

        $dias = [
            'lunes'     => $request->boolean('lunes'),
            'martes'    => $request->boolean('martes'),
            'miercoles' => $request->boolean('miercoles'),
            'jueves'    => $request->boolean('jueves'),
            'viernes'   => $request->boolean('viernes'),
            'sabado'    => $request->boolean('sabado'),
        ];

        HorarioAdministrativo::updateOrCreate(
            ['usuario_id' => $data['usuario_id']],
            array_merge($data, $dias)
        );

        try {
            ActivityLog::log('horario_admin', "Horario administrativo guardado para usuario ID: {$request->usuario_id}", 'usuarios');
            Log::info('Horario administrativo guardado', ['user_id' => $request->usuario_id]);
        } catch (\Throwable $e) {
        }

        return redirect()->route('admin.horarios', ['tab' => 'administrativos'])
            ->with('success', 'Horario administrativo guardado.');
    }

    public function guardarCarga(StoreCargaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        CargaAcademicaDocente::create($data);

        ActivityLog::log('carga_agregar', "Carga académica agregada: {$request->materia}", 'usuarios');

        Log::info('Carga académica agregada', ['user_id' => $data['usuario_id'], 'materia' => $data['materia']]);

        return redirect()->route('admin.horarios', ['tab' => 'docentes'])
            ->with('success', 'Carga académica agregada.');
    }

    public function eliminarCarga(CargaAcademicaDocente $carga): RedirectResponse
    {
        ActivityLog::log('carga_eliminar', "Carga académica eliminada", 'usuarios', ['carga_id' => $carga->id]);

        Log::info('Carga académica eliminada', ['carga_id' => $carga->id, 'materia' => $carga->materia]);

        $carga->delete();
        return redirect()->route('admin.horarios', ['tab' => 'docentes'])
            ->with('success', 'Carga eliminada.');
    }
}
