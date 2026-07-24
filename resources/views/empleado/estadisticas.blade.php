@php
    $titulo = 'Mis Estadísticas';
@endphp

@extends('layouts.app')

@section('title', $titulo)
@section('tone-level', '2')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>{{ $titulo }}</h4>
    <a href="{{ route('empleado.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Mi Panel</a>
</div>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-green p-3">
            <div class="num">{{ $totalMes->presentes ?? 0 }}</div>
            <div class="label">Presente</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-yellow p-3">
            <div class="num">{{ $totalMes->tardes ?? 0 }}</div>
            <div class="label">Tarde</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-red p-3">
            <div class="num">{{ $totalMes->inasistentes ?? 0 }}</div>
            <div class="label">Inasistente</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:#0dcaf0;border-color:#0a58ca;">
            <div class="num" style="color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.15);">{{ $totalMes->apoyos ?? 0 }}</div>
            <div class="label" style="color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.15);">Apoyo Sáb.</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">Últimos 30 días</h6>
            </div>
            <div class="card-body">
                <canvas id="chartBarras" height="120"></canvas>
                <noscript>
                    <table class="table table-sm small">
                        <thead><tr><th>Día</th><th>Presente</th><th>Tarde</th><th>Inasistente</th></tr></thead>
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

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">Resumen del Mes</h6>
            </div>
            <div class="card-body text-center">
                <canvas id="chartDoughnut" height="200"></canvas>
                <noscript>
                    <p class="small text-muted mt-2">
                        Presente: {{ $totalMes->presentes ?? 0 }} |
                        Tarde: {{ $totalMes->tardes ?? 0 }} |
                        Inasistente: {{ $totalMes->inasistentes ?? 0 }}
                    </p>
                </noscript>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = @json($chartLabels);
    const presentes = @json($chartPresentes);
    const tardes = @json($chartTardes);
    const inasistentes = @json($chartInasistentes);

    const totalP = {{ $totalMes->presentes ?? 0 }};
    const totalT = {{ $totalMes->tardes ?? 0 }};
    const totalI = {{ $totalMes->inasistentes ?? 0 }};

    new Chart(document.getElementById('chartBarras'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Presente', data: presentes, backgroundColor: '#198754' },
                { label: 'Tarde', data: tardes, backgroundColor: '#ffc107' },
                { label: 'Inasistente', data: inasistentes, backgroundColor: '#dc3545' },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    new Chart(document.getElementById('chartDoughnut'), {
        type: 'doughnut',
        data: {
            labels: ['Presente', 'Tarde', 'Inasistente'],
            datasets: [{
                data: [totalP, totalT, totalI],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
@endpush