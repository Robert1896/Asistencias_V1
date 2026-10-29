@php
    $titulo = 'Mi Horario';
    $bloques = (new \App\Models\Horario)->todosLosBloques();
@endphp

@extends('layouts.app')

@section('title', $titulo)
@section('tone-level', '2')

@push('styles')
<style nonce="{{ $cspNonce ?? '' }}">
    .horario-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .horario-table th { white-space: nowrap; }
    .horario-table td.horario-cell { background: #e3f2fd; vertical-align: middle; }
    .horario-table .cell-inner { border-radius: 8px; padding: 8px; background: #e3f2fd; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-3"><i class="bi bi-calendar-week me-2"></i>{{ $titulo }}</h4>
    <a href="{{ route('empleado.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Mi Panel</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        {{-- Desktop table --}}
        <div class="horario-wrap">
            <table class="table table-bordered table-sm text-center align-middle mb-0 horario-table" style="font-size:13px;">
                <thead>
                    <tr>
                        <th style="min-width:90px;">Horario</th>
                        @foreach ($dias as $num => $nom)
                            <th style="min-width:120px;">{{ $nom }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $consumed = []; @endphp
                    @foreach ($bloques as $rowIdx => $bloque)
                        <tr>
                            <td class="fw-bold text-nowrap" style="font-size:12px;">
                                {{ substr($bloque['hora_inicio'], 0, 5) }} – {{ substr($bloque['hora_fin'], 0, 5) }}
                            </td>
                            @foreach ($dias as $diaNum => $diaNom)
                                @php $consumeKey = $diaNum . '|' . $bloque['hora_inicio']; @endphp
                                @if (isset($consumed[$consumeKey])) @continue @endif

                                @php
                                    $match = null;
                                    $spanCount = 1;
                                    if (isset($agrupado[$diaNum])) {
                                        foreach ($agrupado[$diaNum] as $a) {
                                            if (\App\Models\Horario::normalizarHora($a->horario->hora_inicio) === $bloque['hora_inicio']
                                                && \App\Models\Horario::normalizarHora($a->horario->hora_fin) === $bloque['hora_fin']) {
                                                $match = $a;
                                                break;
                                            }
                                        }
                                    }
                                    if ($match) {
                                        $nextInicio = $bloque['hora_fin'];
                                        for ($look = $rowIdx + 1; $look < count($bloques); $look++) {
                                            $next = $bloques[$look];
                                            if ($next['hora_inicio'] !== $nextInicio) break;
                                            $foundMatch = false;
                                            if (isset($agrupado[$diaNum])) {
                                                foreach ($agrupado[$diaNum] as $a) {
                                                    if (\App\Models\Horario::normalizarHora($a->horario->hora_inicio) === $next['hora_inicio']
                                                        && \App\Models\Horario::normalizarHora($a->horario->hora_fin) === $next['hora_fin']) {
                                                        $foundMatch = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$foundMatch) break;
                                            $spanCount++;
                                            $nextInicio = $next['hora_fin'];
                                        }
                                        if ($spanCount > 1) {
                                            $markInicio = $bloques[$rowIdx]['hora_fin'];
                                            for ($s = 1; $s < $spanCount; $s++) {
                                                $consumed[$diaNum . '|' . $markInicio] = true;
                                                if (isset($bloques[$rowIdx + $s])) $markInicio = $bloques[$rowIdx + $s]['hora_fin'];
                                            }
                                        }
                                    }
                                @endphp

                                @if ($match)
                                    @php
                                        $horaInicioMerge = $match->horario->hora_inicio;
                                        $horaFinMerge = $spanCount > 1 ? $bloques[$rowIdx + $spanCount - 1]['hora_fin'] : $match->horario->hora_fin;
                                    @endphp
                                    <td rowspan="{{ $spanCount }}" class="p-1 align-middle horario-cell">
                                        <div class="cell-inner">
                                            <div class="fw-bold small">{{ substr($horaInicioMerge, 0, 5) }} – {{ substr($horaFinMerge, 0, 5) }}</div>
                                            @switch($match->tipo_actividad)
                                                @case('carga_academica') <span class="badge bg-primary">Clase</span> @break
                                                @case('asesoria') <span class="badge bg-info">Asesoría</span> @break
                                                @case('administrativa') <span class="badge bg-success">Administrativa</span> @break
                                                @default <span class="badge bg-secondary">{{ $match->tipo_actividad }}</span>
                                            @endswitch
                                            @if ($match->cargaAcademica)
                                                <div class="fw-semibold mt-1 hcell-materia">{{ $match->cargaAcademica->materia }}</div>
                                                <div class="text-muted hcell-meta">{{ $match->cargaAcademica->horas_semanales }} h/sem</div>
                                            @endif
                                            @if ($match->seccion)
                                                <div class="text-muted hcell-meta">Sec: {{ $match->seccion }}</div>
                                            @endif
                                            @if ($match->salon)
                                                <div><span class="badge bg-secondary">{{ $match->salon->codigo }}</span></div>
                                            @endif
                                            @if ($match->coordinacion)
                                                <div class="text-muted hcell-meta">{{ $match->coordinacion->nombre }}</div>
                                            @endif
                                        </div>
                                    </td>
                                @else
                                    <td class="bg-light text-muted small py-3">—</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection