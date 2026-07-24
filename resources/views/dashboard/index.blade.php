@extends('layouts.app')

@section('title', 'Control de Asistencia')

@section('content')
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-green p-3">
            <div class="num">{{ $totalUsuarios }}</div>
            <div class="label">Usuarios</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-green p-3">
            <div class="num">{{ $asistenciasHoy }}</div>
            <div class="label">Asistencias Hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-yellow p-3">
            <div class="num">{{ $tardesHoy }}</div>
            <div class="label">Tardes Hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-red p-3">
            <div class="num">{{ $inasistentesHoy }}</div>
            <div class="label">Inasist. Hoy</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-graph-up me-1"></i>Asistencias — Últimos 7 días</h6>
            </div>
            <div class="card-body">
                <canvas id="chartAsistenciaSemanal" height="100"></canvas>
                <noscript>
                    <table class="table table-sm small">
                        <thead><tr><th>Día</th><th>Presentes</th><th>Tardes</th><th>Inasistentes</th></tr></thead>
                        <tbody>
                            @foreach ($chartLabels as $i => $label)
                            <tr><td>{{ $label }}</td><td>{{ $chartPresentes[$i] ?? 0 }}</td><td>{{ $chartTardes[$i] ?? 0 }}</td><td>{{ $chartInasistentes[$i] ?? 0 }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </noscript>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-pie-chart me-1"></i>Resumen del Mes</h6>
            </div>
            <div class="card-body">
                <canvas id="chartResumenMes" height="200"></canvas>
                <noscript>
                    <p class="small text-muted mt-2">
                        Presentes: {{ $totalMes->presentes ?? 0 }} |
                        Tardes: {{ $totalMes->tardes ?? 0 }} |
                        Inasistentes: {{ $totalMes->inasistentes ?? 0 }} |
                        Apoyo Sáb.: {{ $totalMes->apoyos ?? 0 }}
                    </p>
                </noscript>
            </div>
        </div>
    </div>
</div>

<small class="text-muted fw-bold text-uppercase mb-2 d-block acciones-label" style="font-size:0.62rem;letter-spacing:1px;">Acciones</small>

<div class="row g-3">
    <div class="col-6 col-md-3">
        <a href="{{ route('estacion.index') }}" class="text-decoration-none">
            <div class="card nav-btn grad-escanear h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="icon-wrap"><i class="bi bi-qr-code-scan"></i></div>
                    <h6 class="fw-bold mb-0">Estación</h6>
                    <small class="text-muted">Registrar entrada/salida</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.asistencias') }}" class="text-decoration-none">
            <div class="card nav-btn grad-qr h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="icon-wrap"><i class="bi bi-calendar-check"></i></div>
                    <h6 class="fw-bold mb-0">Asistencias</h6>
                    <small class="text-muted">Consultar registros</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.usuarios') }}" class="text-decoration-none">
            <div class="card nav-btn grad-stats h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="icon-wrap"><i class="bi bi-people"></i></div>
                    <h6 class="fw-bold mb-0">Usuarios</h6>
                    <small class="text-muted">Gestión de usuarios</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.reportes') }}" class="text-decoration-none">
            <div class="card nav-btn grad-justificar h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="icon-wrap"><i class="bi bi-bar-chart"></i></div>
                    <h6 class="fw-bold mb-0">Reportes</h6>
                    <small class="text-muted">Productividad</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.docentes.horarios') }}" class="text-decoration-none">
            <div class="card nav-btn grad-horario h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="icon-wrap"><i class="bi bi-calendar-week"></i></div>
                    <h6 class="fw-bold mb-0">Horarios</h6>
                    <small class="text-muted">Asignar bloques</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.roles') }}" class="text-decoration-none">
            <div class="card nav-btn grad-perfil h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="icon-wrap"><i class="bi bi-shield"></i></div>
                    <h6 class="fw-bold mb-0">Roles</h6>
                    <small class="text-muted">Permisos</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.salones.index') }}" class="text-decoration-none">
            <div class="card nav-btn grad-escanear h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="icon-wrap"><i class="bi bi-door-open"></i></div>
                    <h6 class="fw-bold mb-0">Salones</h6>
                    <small class="text-muted">Aulas y ocupación</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('qr.dinamico.page') }}" class="text-decoration-none">
            <div class="card nav-btn grad-qr h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="icon-wrap"><i class="bi bi-qr-code"></i></div>
                    <h6 class="fw-bold mb-0">Mi QR</h6>
                    <small class="text-muted">Código dinámico</small>
                </div>
            </div>
        </a>
    </div>
</div>

@endsection

@push('styles')
<style>
    .hover-card:hover {
        transform: translateY(-3px);
        transition: all 0.2s ease;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    const labels = {!! json_encode($chartLabels) !!};
    const presentes = {!! json_encode($chartPresentes) !!};
    const tardes = {!! json_encode($chartTardes) !!};
    const inasistentes = {!! json_encode($chartInasistentes) !!};
    const mesData = {!! json_encode([
        'presentes' => $totalMes->presentes ?? 0,
        'tardes' => $totalMes->tardes ?? 0,
        'inasistentes' => $totalMes->inasistentes ?? 0,
        'apoyos' => $totalMes->apoyos ?? 0,
    ]) !!};

    new Chart(document.getElementById('chartAsistenciaSemanal'), {
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

    const totalMes = mesData.presentes + mesData.tardes + mesData.inasistentes + mesData.apoyos;
    new Chart(document.getElementById('chartResumenMes'), {
        type: 'doughnut',
        data: {
            labels: ['Presentes', 'Tardes', 'Inasistentes', 'Apoyo Sáb.'],
            datasets: [{
                data: [mesData.presentes, mesData.tardes, mesData.inasistentes, mesData.apoyos],
                backgroundColor: ['#198754', '#ffc107', '#dc3545', '#0dcaf0'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
            }
        }
    });
</script>
@endpush