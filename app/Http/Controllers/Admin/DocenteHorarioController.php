<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDocenteHorarioRequest;
use App\Models\ActivityLog;
use App\Models\CargaAcademicaDocente;
use App\Models\Coordinacion;
use App\Models\DocenteHorarioPivot;
use App\Models\Horario;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DocenteHorarioController extends Controller
{
    public function index(): View
    {
        $docentes = User::where('rol_id', 3)->where('estatus', 'activo')
            ->orderBy('nombre_completo')
            ->with(['horariosDocente', 'cargaAcademica'])
            ->get();

        return view('admin.docente_horarios', compact('docentes'));
    }

    public function edit(User $user): View
    {
        $agrupado       = Horario::agrupadosPorDia();
        $coordinaciones = Coordinacion::orderBy('nombre')->get();
        $salones        = Salon::orderBy('nombre')->get();
        $cargas         = $user->cargaAcademica;

        $asignados = $user->horariosDocente()
            ->get()
            ->keyBy(fn($h) => $h->pivot->horario_id . '-' . $h->pivot->tipo_actividad);

        // Mapa de colisiones: [horario_id => [salon_id, ...]] ocupados por OTROS docentes
        $colisiones = DocenteHorarioPivot::whereNotNull('salon_id')
            ->where('user_id', '!=', $user->id)
            ->get()
            ->groupBy('horario_id')
            ->map(fn($items) => $items->pluck('salon_id')->unique()->values()->toArray())
            ->toArray();

        return view('admin.docente_horario_edit', compact(
            'user', 'agrupado', 'coordinaciones', 'salones', 'asignados', 'colisiones', 'cargas'
        ));
    }

    public function update(UpdateDocenteHorarioRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $syncData = [];
        $errores = [];

        // Sincronizar cargas académicas con la importación (crear/actualizar por materia)
        $bloquesPorMateria = [];
        foreach (($data['bloques'] ?? []) as $bloque) {
            $tipo = $bloque['tipo'] ?? 'carga_academica';
            if ($tipo === 'carga_academica') {
                $cargaId = !empty($bloque['carga_academica_id']) ? (int) $bloque['carga_academica_id'] : null;
                if ($cargaId) {
                    $carga = CargaAcademicaDocente::find($cargaId);
                    if ($carga && $carga->usuario_id == $user->id) {
                        $key = $user->id . '|' . $carga->materia;
                        $bloquesPorMateria[$key] = ($bloquesPorMateria[$key] ?? 0) + 1;
                    }
                }
            }
        }

        $cargaMap = [];
        foreach ($bloquesPorMateria as $key => $numBloques) {
            [$userId, $materia] = explode('|', $key, 2);
            $carga = CargaAcademicaDocente::where('usuario_id', $userId)
                ->where('materia', $materia)
                ->first();
            if ($carga) {
                $carga->update(['horas_semanales' => $numBloques]);
                $cargaMap[$key] = $carga->id;
            }
        }

        foreach (($data['bloques'] ?? []) as $key => $bloque) {
            $horarioId = $bloque['horario_id'] ?? null;
            if (!$horarioId) {
                continue;
            }

            $tipo    = $bloque['tipo'] ?? 'carga_academica';
            $coordId = !empty($bloque['coord']) ? (int) $bloque['coord'] : null;
            $salonId = !empty($bloque['salon_id']) ? (int) $bloque['salon_id'] : null;
            $cargaId = !empty($bloque['carga_academica_id']) ? (int) $bloque['carga_academica_id'] : null;
            $seccion = !empty($bloque['seccion']) ? $bloque['seccion'] : null;
            $pivotKey = $horarioId . '-' . $tipo . '-' . ($seccion ?? '');

            if ($tipo === 'carga_academica' && $cargaId) {
                $carga = CargaAcademicaDocente::find($cargaId);
                if ($carga && $carga->usuario_id == $user->id) {
                    $mapKey = $user->id . '|' . $carga->materia;
                    if (isset($cargaMap[$mapKey])) {
                        $cargaId = $cargaMap[$mapKey];
                    }
                }
            }

            if ($salonId) {
                $colision = DocenteHorarioPivot::where('salon_id', $salonId)
                    ->where('horario_id', $horarioId)
                    ->where('user_id', '!=', $user->id)
                    ->exists();

                if ($colision) {
                    $horario = Horario::find($horarioId);
                    $dia     = $horario ? (Horario::DIAS[$horario->dia_semana] ?? '?') : '?';
                    $inicio  = $horario ? substr($horario->hora_inicio, 0, 5) : '';
                    $fin     = $horario ? substr($horario->hora_fin, 0, 5) : '';
                    $salon   = Salon::find($salonId);
                    $errores[] = "El salón «{$salon->nombre}» ya está ocupado el {$dia} de {$inicio} a {$fin} por otro profesor.";
                }
            }

            $syncData[$pivotKey] = [
                'horario_id'         => $horarioId,
                'tipo_actividad'     => $tipo,
                'coordinacion_id'    => $coordId,
                'salon_id'           => $salonId,
                'carga_academica_id' => $cargaId,
                'seccion'            => $seccion,
            ];
        }

        if (!empty($errores)) {
            return redirect()->back()->withErrors($errores)->withInput();
        }

        $user->horariosDocente()->detach();

        foreach ($syncData as $d) {
            $user->horariosDocente()->attach($d['horario_id'], [
                'tipo_actividad'     => $d['tipo_actividad'],
                'coordinacion_id'    => $d['coordinacion_id'],
                'salon_id'           => $d['salon_id'],
                'carga_academica_id' => $d['carga_academica_id'],
                'seccion'            => $d['seccion'],
            ]);
        }

        ActivityLog::log('horario_docente', "Horarios actualizados para: {$user->nombre_completo}", 'usuarios', ['user_id' => $user->id]);

        Log::info('Horarios actualizados para: ' . $user->nombre_completo, ['user_id' => $user->id]);

        return redirect()->route('admin.docentes.horarios')
            ->with('success', "Horarios actualizados para {$user->nombre_completo}.");
    }
}
