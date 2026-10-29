<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalonRequest;
use App\Http\Requests\UpdateSalonRequest;
use App\Models\ActivityLog;
use App\Models\Asistencia;
use App\Models\CargaAcademicaDocente;
use App\Models\Coordinacion;
use App\Models\DocenteHorarioPivot;
use App\Models\Horario;
use App\Models\Salon;
use App\Models\User;
use App\Services\ImportService;
use App\Traits\GeneratesQrSvg;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Str;

class SalonController extends Controller
{
    use GeneratesQrSvg;
    public function index()
    {
        $salones = Salon::orderBy('nombre')->get();
        return view('admin.salones.index', compact('salones'));
    }

    public function store(StoreSalonRequest $request)
    {
        $data = $request->validated();

        $salon = Salon::create($data);

        Log::info('Salon creado', ['salon_id' => $salon->id, 'nombre' => $salon->nombre]);

        ActivityLog::log('salon_crear', "Salón creado: {$request->nombre}", 'usuarios', ['nombre' => $request->nombre]);

        return redirect()->route('admin.salones.index')
            ->with('success', 'Salón creado correctamente.');
    }

    public function update(UpdateSalonRequest $request, Salon $salon)
    {
        $data = $request->validated();

        $salon->update($data);

        Log::info('Salon actualizado', ['salon_id' => $salon->id, 'nombre' => $salon->nombre]);

        ActivityLog::log('salon_editar', "Salón actualizado: {$salon->nombre}", 'usuarios', ['salon_id' => $salon->id]);

        return redirect()->route('admin.salones.index')
            ->with('success', 'Salón actualizado correctamente.');
    }

    public function destroy(Salon $salon)
    {
        if ($salon->asignaciones()->exists()) {
            return redirect()->route('admin.salones.index')
                ->with('error', 'No se puede eliminar el salón porque tiene horarios asignados.');
        }

        $salon->delete();

        Log::info('Salon eliminado', ['salon_id' => $salon->id, 'nombre' => $salon->nombre]);

        ActivityLog::log('salon_eliminar', "Salón eliminado: {$salon->nombre}", 'usuarios', ['salon_id' => $salon->id]);

        return redirect()->route('admin.salones.index')
            ->with('success', 'Salón eliminado correctamente.');
    }

    public function qrCode(Salon $salon): Response
    {
        $svg = $this->renderSvg($salon->codigo_qr);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function regenerarQr(Salon $salon): JsonResponse
    {
        $salon->codigo_qr = Str::uuid()->toString();
        $salon->save();

        ActivityLog::log('salon_qr_regenerar', "QR regenerado para salón: {$salon->nombre}", 'usuarios', [
            'salon_id' => $salon->id,
        ]);

        return response()->json([
            'ok'      => true,
            'mensaje' => "QR de {$salon->nombre} regenerado. El código anterior ya no funcionará.",
            'nuevo_qr' => $salon->codigo_qr,
        ]);
    }

    public function ocupacion()
    {
        $salones  = Salon::orderBy('nombre')->get();
        $dias     = Horario::DIAS;
        $horarioModel = new Horario;
        $bloques = $horarioModel->todosLosBloques();

        return view('admin.salones.ocupacion', compact('salones', 'dias', 'bloques'));
    }

    public function ocupacionData(Request $request): JsonResponse
    {
        $request->validate(['salon_id' => 'required|exists:salones,id']);

        $hoy       = today()->toDateString();
        $horaAhora = now()->format('H:i:s');
        $diaSemana = now()->dayOfWeekIso;

        $pivots = DocenteHorarioPivot::with(['docente.cargaAcademica', 'docente.rol', 'horario', 'salon', 'coordinacion', 'cargaAcademica'])
            ->where('salon_id', $request->salon_id)
            ->get();

        $userIds = $pivots->pluck('user_id')->filter()->values();
        $todasAsistencias = Asistencia::where('fecha', $hoy)
            ->whereIn('usuario_id', $userIds)
            ->get()
            ->groupBy('usuario_id');

        $asignaciones = $pivots->groupBy(fn ($p) => $p->horario_id);

        foreach ($asignaciones as $horarioId => $pivotsGrupo) {
            foreach ($pivotsGrupo as $pivot) {
                $bloque = $pivot->horario;

                $bloqueActivo = (
                    $bloque &&
                    $bloque->dia_semana == $diaSemana &&
                    $horaAhora >= $bloque->hora_inicio &&
                    $horaAhora < $bloque->hora_fin
                );

                $pivot->bloque_activo = $bloqueActivo;

                if ($pivot->docente) {
                    $asistenciasUser = $todasAsistencias->get($pivot->user_id, collect());
                    $asistencia = $asistenciasUser->first(function ($a) use ($bloque) {
                        return $a->horario_id === $bloque->id
                            || ($a->horario_id === null
                                && $a->bloque_inicio <= $bloque->hora_inicio
                                && $a->bloque_fin >= $bloque->hora_fin);
                    });

                    $pivot->asistio_hoy   = $asistencia !== null;
                    $pivot->hora_llegada  = $asistencia?->hora_entrada;
                    $pivot->color_estado  = $bloqueActivo
                        ? ($asistencia && $asistencia->hora_entrada ? 'verde' : 'amarillo')
                        : 'gris';
                } else {
                    $pivot->asistio_hoy  = false;
                    $pivot->hora_llegada = null;
                    $pivot->color_estado = 'gris';
                }
            }
        }

        return response()->json($asignaciones);
    }

    public function disponibilidad(Request $request): JsonResponse
    {
        $request->validate([
            'salon_id'  => 'required|exists:salones,id',
            'horario_id' => 'required|exists:horarios,id',
            'excluir_docente_id' => 'nullable|exists:users,id',
        ]);

        $query = DocenteHorarioPivot::where('salon_id', $request->salon_id)
            ->where('horario_id', $request->horario_id);

        if ($request->filled('excluir_docente_id')) {
            $query->where('user_id', '!=', $request->excluir_docente_id);
        }

        $ocupado = $query->exists();

        return response()->json([
            'disponible' => !$ocupado,
            'ocupado_por' => $ocupado
                ? DocenteHorarioPivot::with('docente')
                    ->where('salon_id', $request->salon_id)
                    ->where('horario_id', $request->horario_id)
                    ->first()?->docente?->nombre_completo
                : null,
        ]);
    }

    public function exportar(Request $request, Salon $salon): Response|ResponseFacade
    {
        $formato = $request->query('formato', 'pdf');

        $dias = Horario::DIAS;
        $bloques = (new Horario)->todosLosBloques();

        $asignaciones = DocenteHorarioPivot::with(['docente.cargaAcademica', 'docente.rol', 'horario', 'salon', 'coordinacion', 'cargaAcademica'])
            ->where('salon_id', $salon->id)
            ->get()
            ->keyBy(fn($p) => $p->horario_id);

        $index = [];
        foreach ($asignaciones as $pid => $pivot) {
            $h = $pivot->horario;
            if (!$h) continue;
            $key = $h->dia_semana . '|'
                . Horario::normalizarHora($h->hora_inicio) . '|'
                . Horario::normalizarHora($h->hora_fin);
            $index[$key][] = $pivot;
        }

        $grid = [];
        $consumed = [];
        foreach ($bloques as $rowIdx => $bloque) {
            $horaInicio = $bloque['hora_inicio'];
            $horaFin = $bloque['hora_fin'];
            $row = ['hora' => substr($horaInicio, 0, 5) . ' – ' . substr($horaFin, 0, 5)];

            foreach ($dias as $diaNum => $diaNom) {
                $key = $diaNum . '|' . $horaInicio . '|' . $horaFin;
                $consumeKey = $diaNum . '|' . $horaInicio;

                if (isset($consumed[$consumeKey])) {
                    $row[$diaNum] = ['skip' => true];
                    continue;
                }

                $asignados = $index[$key] ?? [];

                if (count($asignados) > 0) {
                    $pivot = $asignados[0];
                    $userId = $pivot->user_id;
                    $spanCount = 1;
                    $nextInicio = $horaFin;

                    for ($look = $rowIdx + 1; $look < count($bloques); $look++) {
                        $next = $bloques[$look];
                        if ($next['hora_inicio'] !== $nextInicio) break;
                        $nextKey = $diaNum . '|' . $next['hora_inicio'] . '|' . $next['hora_fin'];
                        $found = false;
                        foreach ($index[$nextKey] ?? [] as $na) {
                            if ($na->user_id == $userId) { $found = true; break; }
                        }
                        if (!$found) break;
                        $spanCount++;
                        $nextInicio = $next['hora_fin'];
                    }

                    if ($spanCount > 1) {
                        $markInicio = $bloques[$rowIdx]['hora_fin'];
                        for ($s = 1; $s < $spanCount; $s++) {
                            $consumed[$diaNum . '|' . $markInicio] = true;
                            if (isset($bloques[$rowIdx + $s])) {
                                $markInicio = $bloques[$rowIdx + $s]['hora_fin'];
                            }
                        }
                    }

                    $row[$diaNum] = [
                        'skip' => false,
                        'docente' => $pivot->docente?->nombre_completo ?? '?',
                        'materias' => $pivot->docente?->cargaAcademica?->pluck('materia')->implode(', ') ?: '',
                        'tipo' => $pivot->tipo_actividad,
                        'rowspan' => $spanCount,
                    ];
                } else {
                    $row[$diaNum] = ['skip' => false, 'vacio' => true];
                }
            }

            $grid[] = $row;
        }

        $nombreArchivo = 'horario_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($salon->codigo));

        switch ($formato) {
            case 'pdf':
                $html = view('admin.salones.exportar_horario', compact('salon', 'dias', 'grid'))->render();
                $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
                return $pdf->download($nombreArchivo . '.pdf');

            case 'csv':
                $csvRows = [];
                $header = array_merge(['Horario'], array_values($dias));
                $csvRows[] = $header;

                foreach ($grid as $row) {
                    $csvRow = [$row['hora']];
                    foreach ($dias as $diaNum => $diaNom) {
                        $cell = $row[$diaNum] ?? ['vacio' => true];
                        if ($cell['skip'] ?? false) {
                            $csvRow[] = '';
                        } elseif ($cell['vacio'] ?? false) {
                            $csvRow[] = '';
                        } else {
                            $tipo = match($cell['tipo']) {
                                'carga_academica' => 'C.A.',
                                'asesoria' => 'Ases.',
                                'administrativa' => 'Adm.',
                                default => $cell['tipo'],
                            };
                            $csvRow[] = $cell['docente'] . ' [' . $tipo . ']' . ($cell['materias'] ? ' - ' . $cell['materias'] : '');
                        }
                    }
                    $csvRows[] = $csvRow;
                }

                $output = fopen('php://temp', 'r+');
                fwrite($output, "\xEF\xBB\xBF");
                foreach ($csvRows as $csvRow) {
                    fputcsv($output, \App\Support\Csv::fila($csvRow), ';');
                }
                rewind($output);
                $csvContent = stream_get_contents($output);
                fclose($output);

                return ResponseFacade::make($csvContent, 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment;filename=\"{$nombreArchivo}.csv\"",
                ]);

            case 'imagen':
                $html = view('admin.salones.exportar_horario', compact('salon', 'dias', 'grid'))->render();
                return ResponseFacade::make($html, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ]);

            default:
                return back()->with('error', 'Formato no soportado: ' . $formato);
        }
    }

    public function plantilla(): Response|ResponseFacade
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['cedula', 'nombre', 'apellido', 'dia', 'hora_inicio', 'hora_fin', 'salon_codigo', 'tipo_actividad', 'coordinacion', 'materia', 'seccion'];
        foreach ($headers as $col => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $cell = $sheet->getCell($colLetter . '1');
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
        }

        $ejemplo = ['12345678', 'Juan', 'Pérez', 'Lunes', '07:00:00', '07:45:00', 'A-101', 'carga_academica', 'Coordinación Académica', 'Matemáticas', 'Secc-A'];
        foreach ($ejemplo as $col => $valor) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->getCell($colLetter . '2')->setValue($valor);
        }

        foreach ($headers as $col => $header) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1))->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $temp = tempnam(sys_get_temp_dir(), 'plantilla_');
        $writer->save($temp);

        $content = file_get_contents($temp);
        @unlink($temp);

        return ResponseFacade::make($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="plantilla_asignaciones.xlsx"',
        ]);
    }

    public function importar(Request $request, ImportService $importService): RedirectResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        if ($request->boolean('reemplazar')) {
            DocenteHorarioPivot::query()->delete();
            CargaAcademicaDocente::query()->delete();
        }

        $resultado = $importService->importarAsignaciones($request->file('archivo'));

        $mensaje = "Se crearon {$resultado['created']} asignaciones.";
        if (!empty($resultado['usuarios_creados'])) {
            $mensaje .= ' Se crearon ' . count($resultado['usuarios_creados']) . ' usuarios nuevos.';
        }
        if (!empty($resultado['salones_creados'])) {
            $mensaje .= ' Se crearon ' . count($resultado['salones_creados']) . ' salones nuevos.';
        }
        if (!empty($resultado['horarios_creados'])) {
            $mensaje .= ' Se crearon ' . count($resultado['horarios_creados']) . ' bloques horarios nuevos.';
        }
        if (!empty($resultado['coordinaciones_creadas'])) {
            $mensaje .= ' Se crearon ' . count($resultado['coordinaciones_creadas']) . ' coordinaciones nuevas.';
        }
        if (!empty($resultado['errors'])) {
            $erroresInfo = array_filter($resultado['errors'], fn($e) => str_starts_with($e, 'Se crearon'));
            $erroresReales = array_diff_key($resultado['errors'], $erroresInfo);

            if (!empty($erroresReales)) {
                $mensaje .= ' ' . count($erroresReales) . ' filas con errores.';
            }
        }

        ActivityLog::log('salon_importar', "Importación de asignaciones: {$resultado['created']} creadas", 'usuarios', ['creados' => $resultado['created'], 'errores' => count($resultado['errors'])]);

        return redirect()->route('admin.salones.index')
            ->with('success', $mensaje)
            ->with('errores_importar', $resultado['errors'])
            ->with('usuarios_creados', $resultado['usuarios_creados'] ?? [])
            ->with('salones_creados', $resultado['salones_creados'])
            ->with('horarios_creados', $resultado['horarios_creados'])
            ->with('coordinaciones_creadas', $resultado['coordinaciones_creadas']);
    }

    public function limpiarAsignaciones(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmar_limpiar' => ['required', 'accepted'],
            'confirmacion_texto' => ['required', 'string', 'in:ELIMINAR TODO'],
        ]);

        $asignaciones = DocenteHorarioPivot::query()->count();
        $cargas       = CargaAcademicaDocente::query()->count();

        DB::transaction(function () {
            DocenteHorarioPivot::query()->delete();
            CargaAcademicaDocente::query()->delete();
        });

        ActivityLog::log(
            'salon_limpiar',
            "Se eliminaron {$asignaciones} asignaciones y {$cargas} cargas académicas",
            'usuarios',
            [
                'asignaciones_eliminadas' => $asignaciones,
                'cargas_eliminadas'       => $cargas,
                'user_id'                 => auth()->id(),
                'ip'                      => $request->ip(),
            ]
        );

        return redirect()->route('admin.salones.index')
            ->with('success', "Se eliminaron {$asignaciones} asignaciones y {$cargas} cargas académicas. Ahora puedes importar el nuevo horario.");
    }
}
