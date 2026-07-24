@extends('layouts.app')

@section('title', 'Registro de Asistencias')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Registro de Asistencias</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-info p-3">
            <div class="num">{{ $totalAsistencias }}</div>
            <div class="label">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-green p-3">
            <div class="num">{{ $presentes }}</div>
            <div class="label">Presentes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-yellow p-3">
            <div class="num">{{ $tardes }}</div>
            <div class="label">Tardes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-red p-3">
            <div class="num">{{ $inasistentes }}</div>
            <div class="label">Inasistentes</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="{{ $esAsistente ? 'col-md-12' : 'col-md-8' }}">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-graph-up me-1"></i>Asistencias por Día</h6>
            </div>
            <div class="card-body">
                <canvas id="chartDistribucion" height="100"></canvas>
            </div>
        </div>
    </div>
    @if (!$esAsistente)
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-pie-chart me-1"></i>Resumen del Período</h6>
            </div>
            <div class="card-body">
                <canvas id="chartResumen" height="200"></canvas>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.asistencias') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio', now()->startOfMonth()->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Hasta</label>
                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin', now()->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="Presente" {{ request('estado') === 'Presente' ? 'selected' : '' }}>Presente</option>
                    <option value="Tarde" {{ request('estado') === 'Tarde' ? 'selected' : '' }}>Tarde</option>
                    <option value="Inasistente" {{ request('estado') === 'Inasistente' ? 'selected' : '' }}>Inasistente</option>
                    <option value="Apoyo Sabado" {{ request('estado') === 'Apoyo Sabado' ? 'selected' : '' }}>Apoyo Sábado</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Registro de asistencias">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Estado</th>
                        <th>Justificado</th>
                        <th>Justificación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($asistencias as $a)
                    <tr>
                        <td>{{ $a->usuario->nombre_completo ?? 'N/A' }}</td>
                        <td>{{ $a->fecha->format('d/m/Y') }}</td>
                        <td>{{ $a->hora_entrada ?? '—' }}</td>
                        <td>{{ $a->hora_salida ?? '—' }}</td>
                        <td>
                            @php
                                $cls = match($a->estado) {
                                    'Presente' => 'success',
                                    'Tarde' => 'warning',
                                    'Inasistente' => 'danger',
                                    'Apoyo Sabado' => 'info',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $cls }}">{{ $a->estado }}</span>
                        </td>
                        <td>
                            @if ($a->justificado === 'Si')
                                <span class="badge bg-success">Sí</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td class="small">
                            {{ Str::limit($a->texto_justificacion, 40) ?? '—' }}
                            @if ($a->archivo_justificacion)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($a->archivo_justificacion) }}"
                                       target="_blank" class="ms-1" data-bs-toggle="tooltip" title="Ver justificativo adjunto">
                                    <i class="bi bi-paperclip text-primary"></i>
                                </a>
                            @endif
                        </td>
                        <td>
                            @if ($a->justificado !== 'Si' && ($a->estado === 'Inasistente' || $a->estado === 'Tarde'))
                            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#justificarModal{{ $a->id }}">
                                <i class="bi bi-check-circle"></i>
                            </button>
                            <div class="modal fade" id="justificarModal{{ $a->id }}" tabindex="-1" aria-labelledby="justificarModalLabel{{ $a->id }}">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.asistencias.justificar', $a) }}" enctype="multipart/form-data">
                                            @csrf @method('PUT')
                                            <div class="modal-header border-0">
                                                <h6 class="modal-title" id="justificarModalLabel{{ $a->id }}">Justificar asistencia</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="small text-muted mb-2">
                                                    <strong>{{ $a->usuario->nombre_completo ?? 'N/A' }}</strong> —
                                                    {{ $a->fecha->format('d/m/Y') }} — {{ $a->estado }}
                                                </p>
                                                <input type="hidden" name="justificado" value="Si">
                                                <textarea name="texto_justificacion" class="form-control form-control-sm @error('texto_justificacion') is-invalid @enderror"
                                                          placeholder="Motivo de la justificación (opcional)" rows="3"></textarea>
                                                @error('texto_justificacion')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="mt-2">
                                                    <label class="form-label small fw-semibold">Justificativo (opcional)</label>
                                                    <input type="file" name="archivo" id="archivoJustificacion{{ $a->id }}" class="form-control form-control-sm @error('archivo') is-invalid @enderror"
                                                           accept=".pdf,.jpg,.jpeg,.png" onchange="previewArchivo(this, 'archivoNombre{{ $a->id }}', 'archivoPreview{{ $a->id }}')">
                                                    <div id="archivoNombre{{ $a->id }}" class="form-text text-primary"></div>
                                                    <img id="archivoPreview{{ $a->id }}" class="mt-1 rounded d-none" style="max-height:100px;">
                                                    <div class="form-text">PDF o imagen, máximo 5MB.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-warning btn-sm">Justificar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @elseif ($a->justificado === 'Si')
                            <form method="POST" action="{{ route('admin.asistencias.justificar', $a) }}" class="d-inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="justificado" value="No">
                                <button type="submit" class="btn btn-outline-secondary btn-sm"
                                        data-bs-toggle="tooltip" title="Remover justificación" onclick="return confirm('¿Remover justificación?')">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-muted text-center py-3">No se encontraron asistencias para el período y filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if (method_exists($asistencias, 'links'))
    <div class="card-footer bg-white">
        {{ $asistencias->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<noscript>
    <div class="card mt-3"><div class="card-body">
        <h6>Distribución diaria (datos):</h6>
        <table class="table table-sm"><thead><tr><th>Fecha</th><th>Presentes</th><th>Tardes</th><th>Inasistentes</th></tr></thead><tbody>
        @foreach($chartLabels as $i => $f)
            <tr><td>{{ $f }}</td><td>{{ $chartPresentes[$i] }}</td><td>{{ $chartTardes[$i] }}</td><td>{{ $chartInasistentes[$i] }}</td></tr>
        @endforeach
        </tbody></table>
    </div></div>
</noscript>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    const labels = {!! json_encode($chartLabels) !!};
    const presentes = {!! json_encode($chartPresentes) !!};
    const tardes = {!! json_encode($chartTardes) !!};
    const inasistentes = {!! json_encode($chartInasistentes) !!};

    new Chart(document.getElementById('chartDistribucion'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Presentes', data: presentes, backgroundColor: '#198754' },
                { label: 'Tardes', data: tardes, backgroundColor: '#ffc107' },
                { label: 'Inasistentes', data: inasistentes, backgroundColor: '#dc3545' },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    @if (!$esAsistente)
    new Chart(document.getElementById('chartResumen'), {
        type: 'doughnut',
        data: {
            labels: ['Presentes', 'Tardes', 'Inasistentes'],
            datasets: [{
                data: [{!! $presentes !!}, {!! $tardes !!}, {!! $inasistentes !!}],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
            }
        }
    });
    @endif
</script>
<script>
function previewArchivo(input, nombreId, previewId) {
    const nombre = document.getElementById(nombreId);
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        nombre.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) { preview.src = e.target.result; preview.classList.remove('d-none'); };
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('d-none');
        }
    } else {
        nombre.textContent = '';
        preview.classList.add('d-none');
    }
}
</script>
@endpush
