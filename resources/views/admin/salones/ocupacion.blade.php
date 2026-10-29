@php
    $user = auth()->user();
    $titulo = 'Salones Ocupados';
@endphp

@extends('layouts.app')

@section('title', $titulo)
@section('tone-level', '2')

@section('content')
<div class="container-fluid py-3">
    <nav aria-label="breadcrumb" class="breadcrumb-admin">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.salones.index') }}">Salones</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ocupación</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold"><i class="bi bi-calendar3 me-2"></i>{{ $titulo }}</h4>
        <a href="{{ route('admin.salones.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver a Salones
        </a>
    </div>

    {{-- Pestañas --}}
    <ul class="nav nav-tabs mb-3" id="ocupacionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="por-salon-tab" data-bs-toggle="tab"
                    data-bs-target="#por-salon" type="button" role="tab">
                <i class="bi bi-door-open me-1"></i>Ocupación por Salón
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="lista-asignaciones-tab" data-bs-toggle="tab"
                    data-bs-target="#lista-asignaciones" type="button" role="tab">
                <i class="bi bi-list-ul me-1"></i>Todas las Asignaciones
            </button>
        </li>
    </ul>

    <div class="tab-content" id="ocupacionTabsContent">
        {{-- ─── TAB 1: CUADRÍCULA POR SALÓN ──────────────── --}}
        <div class="tab-pane fade show active" id="por-salon" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Seleccionar Salón</label>
                            <select id="selectorSalon" class="form-select">
                                <option value="">-- Elija un salón --</option>
                                @foreach ($salones as $salon)
                                    <option value="{{ $salon->id }}">
                                        {{ $salon->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="cuadricula-wrapper" class="table-responsive" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 fw-semibold" id="salon-title"></h6>
                            <div class="btn-group btn-group-sm" id="export-buttons">
                                <button type="button" class="btn btn-outline-danger" id="btn-export-pdf" title="Descargar PDF" aria-label="Descargar PDF">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="btn-export-jpg" title="Ver vista de impresión (Guardar como PDF/imagen)" aria-label="Imprimir horario">
                                    <i class="bi bi-printer me-1"></i>Imprimir
                                </button>
                                <button type="button" class="btn btn-outline-success" id="btn-export-csv" title="Descargar CSV" aria-label="Descargar CSV">
                                    <i class="bi bi-filetype-csv me-1"></i>CSV
                                </button>
                            </div>
                        </div>
                        <table class="table table-bordered table-sm text-center align-middle ocupacion-table" id="cuadricula-table" role="table" aria-label="Cuadrícula de ocupación">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width:90px;">Horario</th>
                                     @foreach ($dias as $num => $nom)
                                         <th style="min-width:130px;">{{ $nom }}</th>
                                     @endforeach
                                </tr>
                            </thead>
                            <tbody id="cuadricula-body">
                                {{-- Skeleton loading --}}
                                @for ($i = 0; $i < 8; $i++)
                                <tr class="skeleton-row">
                                    <td><span class="skeleton" style="width:80px;"></span></td>
                                    <td><span class="skeleton" style="width:120px;"></span></td>
                                    <td><span class="skeleton" style="width:120px;"></span></td>
                                    <td><span class="skeleton" style="width:120px;"></span></td>
                                    <td><span class="skeleton" style="width:120px;"></span></td>
                                    <td><span class="skeleton" style="width:120px;"></span></td>
                                    <td><span class="skeleton" style="width:120px;"></span></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <div id="sin-seleccion" class="text-center text-muted py-5">
                        <i class="bi bi-hand-index fa-3x mb-2 d-block"></i>
                        Selecciona un salón para ver su ocupación semanal.
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── TAB 2: LISTA DE TODAS LAS ASIGNACIONES ──── --}}
        <div class="tab-pane fade" id="lista-asignaciones" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" role="table" aria-label="Asignaciones por salón">
                            <thead class="table-light">
                                <tr>
                                    <th>Salón</th>
                                    <th>Profesor</th>
                                    <th>Materia</th>
                                    <th>Día</th>
                                    <th>Horario</th>
                                    <th>Actividad</th>
                                    <th>Coordinación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $asignacionesGlobal = \App\Models\DocenteHorarioPivot::with('salon','docente.cargaAcademica','horario','coordinacion')
                                        ->whereNotNull('salon_id')
                                        ->orderBy('salon_id')
                                        ->get();
                                @endphp
                                @forelse ($asignacionesGlobal as $a)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $a->salon?->codigo ?? '—' }}</span></td>
                                        <td>{{ $a->docente?->nombre_completo ?? '—' }}</td>
                                        <td>
                                            @if ($a->docente && $a->docente->cargaAcademica->isNotEmpty())
                                                @foreach ($a->docente->cargaAcademica as $c)
                                                    <span class="badge bg-light text-dark border me-1">{{ $c->materia }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>{{ \App\Models\Horario::DIAS[$a->horario->dia_semana] ?? '—' }}</td>
                                        <td>{{ substr($a->horario->hora_inicio,0,5) }} – {{ substr($a->horario->hora_fin,0,5) }}</td>
                                        <td>
                                            @switch($a->tipo_actividad)
                                                @case('carga_academica')  <span class="badge bg-primary">Carga Académica</span> @break
                                                @case('asesoria')         <span class="badge bg-info">Asesoría</span> @break
                                                @case('administrativa')   <span class="badge bg-success">Administrativa</span> @break
                                                @default <span class="badge bg-secondary">{{ $a->tipo_actividad }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $a->coordinacion?->nombre ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fa-2x mb-2 d-block"></i>No hay asignaciones con salón.
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function () {
    const selector = document.getElementById('selectorSalon');
    const wrapper = document.getElementById('cuadricula-wrapper');
    const sinSel  = document.getElementById('sin-seleccion');
    const tbody   = document.getElementById('cuadricula-body');
    const titleEl = document.getElementById('salon-title');
    const btnPdf  = document.getElementById('btn-export-pdf');
    const btnPrint = document.getElementById('btn-export-jpg');
    const btnCsv  = document.getElementById('btn-export-csv');

    const DIAS = @json($dias);
    const BLOQUES = @json($bloques);

    // SQLite guarda 'HH:MM' y MySQL 'HH:MM:SS'; se compara siempre normalizado.
    const normHora = (h) => (h && h.length === 5 ? h + ':00' : h);

    let currentSalonId = '';
    let currentSalonName = '';
    let currentData = {};

    selector.addEventListener('change', function () {
        const salonId = this.value;
        if (!salonId) {
            wrapper.style.display = 'none';
            sinSel.style.display = 'block';
            return;
        }
        currentSalonId = salonId;
        currentSalonName = this.options[this.selectedIndex].text;
        titleEl.textContent = 'Horario de ' + currentSalonName;
        cargarCuadricula(salonId);
    });

    const params = new URLSearchParams(window.location.search);
    const salonParam = params.get('salon');
    if (salonParam) {
        selector.value = salonParam;
        selector.dispatchEvent(new Event('change'));
    }

    function cargarCuadricula(salonId) {
        fetch('{{ route("admin.salones.ocupacion.data") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ salon_id: salonId })
        })
        .then(r => r.json())
        .then(data => {
            currentData = data;
            renderizarCuadricula(data);
            wrapper.style.display = '';
            sinSel.style.display = 'none';
        });
    }

    function exportar(formato) {
        if (!currentSalonId) return;
        window.location.href = '{{ route("admin.salones.exportar", "__ID__") }}'.replace('__ID__', currentSalonId) + '?formato=' + formato;
    }

    btnPdf.addEventListener('click', function() { exportar('pdf'); });
    btnPrint.addEventListener('click', function() { exportar('imagen'); });
    btnCsv.addEventListener('click', function() { exportar('csv'); });

    function renderizarCuadricula(data) {
        const index = {};
        for (const [horarioId, pivots] of Object.entries(data)) {
            for (const p of pivots) {
                if (!p.horario) continue;
                const key = p.horario.dia_semana + '|' + normHora(p.horario.hora_inicio) + '|' + normHora(p.horario.hora_fin);
                if (!index[key]) index[key] = [];
                index[key].push(p);
            }
        }

        const consumed = {};

        let html = '';
        BLOQUES.forEach((rango, rowIdx) => {
            html += '<tr>';
            html += `<td class="small text-nowrap fw-bold">${rango.hora_inicio.substring(0,5)} – ${rango.hora_fin.substring(0,5)}</td>`;

            Object.keys(DIAS).forEach(diaNum => {
                const key = diaNum + '|' + rango.hora_inicio + '|' + rango.hora_fin;
                const asignados = index[key] || [];

                const consumeKey = diaNum + '|' + rango.hora_inicio;
                if (consumed[consumeKey]) {
                    return;
                }

                if (asignados.length > 0) {
                    const pivot = asignados[0];
                    const userId = pivot.user_id;

                    let spanCount = 1;
                    let nextInicio = rango.hora_fin;

                    for (let look = rowIdx + 1; look < BLOQUES.length; look++) {
                        const next = BLOQUES[look];
                        if (next.hora_inicio !== nextInicio) break;

                        const nextKey = diaNum + '|' + next.hora_inicio + '|' + next.hora_fin;
                        const nextAsignados = index[nextKey] || [];
                        const nextPivot = nextAsignados.find(a => a.user_id === userId);

                        if (!nextPivot) break;

                        spanCount++;
                        nextInicio = next.hora_fin;
                    }

                    if (spanCount > 1) {
                        let markInicio = BLOQUES[rowIdx].hora_fin;
                        for (let s = 1; s < spanCount; s++) {
                            const mk = diaNum + '|' + markInicio;
                            consumed[mk] = true;
                            const nextBlock = BLOQUES[rowIdx + s];
                            if (nextBlock) markInicio = nextBlock.hora_fin;
                        }
                    }

                    const badges = {
                        'carga_academica': 'primary',
                        'asesoria': 'info',
                        'administrativa': 'success'
                    };
                    const actividadText = {
                        'carga_academica': 'C.A.',
                        'asesoria': 'Ases.',
                        'administrativa': 'Adm.'
                    };
                    const materias = pivot.carga_academica_id
                        ? (pivot.docente?.carga_academica?.find(c => c.id === pivot.carga_academica_id)?.materia || '')
                        : '';

                    const colorMap = {
                        'verde':    { bg: '#28a745', title: 'Presente en clase' },
                        'amarillo': { bg: '#ffc107', title: 'Bloque activo — Esperando profesor' },
                        'gris':     { bg: '#6c757d', title: 'Sin bloque activo' },
                    };
                    const colorInfo = colorMap[pivot.color_estado] || colorMap['gris'];
                    const dotColor = `<span class="d-inline-block rounded-circle ms-1" style="width:8px;height:8px;background:${colorInfo.bg};" title="${colorInfo.title}"></span>`;

                    const horaLlegada = pivot.hora_llegada
                        ? `<small class="d-block text-success" style="font-size:8px;">Entró: ${pivot.hora_llegada.substring(0,5)}</small>`
                        : '';

                    const rowSpan = spanCount > 1 ? ` rowspan="${spanCount}"` : '';
                    const cellStyle = spanCount > 1
                        ? 'vertical-align:middle;'
                        : 'vertical-align:top;';

                    const rangeLabel = spanCount > 1
                        ? `<small class="d-block text-muted" style="font-size:8px;">${rango.hora_inicio.substring(0,5)} – ${BLOQUES[rowIdx + spanCount - 1].hora_fin.substring(0,5)}</small>`
                        : '';

                    const cards = `<div class="border rounded p-1 mb-1" style="background:#e3f2fd;">
                         <small class="fw-bold d-block">${pivot.docente?.nombre_completo || '?'}${dotColor}</small>
                         ${rangeLabel}
                         ${materias ? `<small class="d-block text-muted" style="font-size:9px;line-height:1.2;">${materias}</small>` : ''}
                         ${horaLlegada}
                         <span class="badge bg-${badges[pivot.tipo_actividad] || 'secondary'}" style="font-size:9px;">${actividadText[pivot.tipo_actividad] || pivot.tipo_actividad}</span>
                     </div>`;

                    html += `<td class="p-1 align-top" style="min-width:130px;${cellStyle}"${rowSpan}>${cards}</td>`;
                } else {
                    html += `<td class="bg-light text-muted small py-3">—</td>`;
                }
            });
            html += '</tr>';
        });
        tbody.innerHTML = html;
    }
});
</script>
@endpush
