@php
    $dias = \App\Models\Horario::DIAS;
    $bloques = (new \App\Models\Horario)->todosLosBloques();

    $asignaciones = \App\Models\DocenteHorarioPivot::with('docente.cargaAcademica', 'horario')
        ->where('salon_id', $salon->id)
        ->get()
        ->keyBy(fn($p) => $p->horario_id);

    $index = [];
    foreach ($asignaciones as $pid => $pivot) {
        $h = $pivot->horario;
        if (!$h) continue;
        $key = $h->dia_semana . '|' . $h->hora_inicio . '|' . $h->hora_fin;
        $index[$key][] = $pivot;
    }

    $consumed = [];
@endphp

<table style="width:100%;border-collapse:collapse;font-size:11px;text-align:center;">
    <thead>
        <tr>
            <th style="background:#212529;color:#fff;padding:6px 8px;border:1px solid #dee2e6;">Horario</th>
            @foreach ($dias as $num => $nom)
                <th style="background:#212529;color:#fff;padding:6px 8px;border:1px solid #dee2e6;min-width:100px;">{{ $nom }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($bloques as $rowIdx => $bloque)
            @php
                $horaInicio = $bloque['hora_inicio'];
                $horaFin = $bloque['hora_fin'];
            @endphp
            <tr>
                <td style="padding:4px 6px;border:1px solid #dee2e6;font-weight:bold;white-space:nowrap;">
                    {{ substr($horaInicio, 0, 5) }} – {{ substr($horaFin, 0, 5) }}
                </td>
                @foreach ($dias as $diaNum => $diaNom)
                    @php
                        $key = $diaNum . '|' . $horaInicio . '|' . $horaFin;
                        $consumeKey = $diaNum . '|' . $horaInicio;
                    @endphp
                    @if (isset($consumed[$consumeKey]))
                        @continue
                    @endif

                    @php
                        $asignados = $index[$key] ?? [];
                    @endphp

                    @if (count($asignados) > 0)
                        @php
                            $pivot = $asignados[0];
                            $userId = $pivot->user_id;
                            $spanCount = 1;
                            $nextInicio = $horaFin;

                            for ($look = $rowIdx + 1; $look < count($bloques); $look++) {
                                $next = $bloques[$look];
                                if ($next['hora_inicio'] !== $nextInicio) break;
                                $nextKey = $diaNum . '|' . $next['hora_inicio'] . '|' . $next['hora_fin'];
                                $nextAsignados = $index[$nextKey] ?? [];
                                $found = false;
                                foreach ($nextAsignados as $na) {
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

                            $materias = $pivot->docente?->cargaAcademica?->pluck('materia')->implode(', ') ?? '';
                            $rowSpan = $spanCount > 1 ? $spanCount : 1;

                            $tipoBadge = match($pivot->tipo_actividad) {
                                'carga_academica' => '<span style="display:inline-block;background:#0d6efd;color:#fff;font-size:9px;padding:1px 4px;border-radius:3px;">C.A.</span>',
                                'asesoria' => '<span style="display:inline-block;background:#0dcaf0;color:#000;font-size:9px;padding:1px 4px;border-radius:3px;">Ases.</span>',
                                'administrativa' => '<span style="display:inline-block;background:#198754;color:#fff;font-size:9px;padding:1px 4px;border-radius:3px;">Adm.</span>',
                                default => '<span style="display:inline-block;background:#6c757d;color:#fff;font-size:9px;padding:1px 4px;border-radius:3px;">' . e($pivot->tipo_actividad) . '</span>',
                            };
                        @endphp
                        <td rowspan="{{ $rowSpan }}" style="padding:4px;border:1px solid #dee2e6;vertical-align:top;text-align:left;background:#e3f2fd;">
                            <strong style="font-size:10px;">{{ $pivot->docente->nombre_completo ?? '?' }}</strong><br>
                            @if ($spanCount > 1)
                                <span style="font-size:8px;color:#666;">{{ substr($horaInicio, 0, 5) }} – {{ substr($bloques[$rowIdx + $spanCount - 1]['hora_fin'], 0, 5) }}</span><br>
                            @endif
                            @if ($materias)
                                <span style="font-size:9px;color:#888;">{{ $materias }}</span><br>
                            @endif
                            {!! $tipoBadge !!}
                        </td>
                    @else
                        <td style="padding:4px;border:1px solid #dee2e6;background:#f8f9fa;color:#aaa;">—</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
