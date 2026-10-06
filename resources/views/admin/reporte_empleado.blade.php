@extends('layouts.app')

@section('title', 'Reporte — ' . $empleado->nombre_completo)
@section('tone-level', '2')

@section('content')
<nav aria-label="breadcrumb" class="breadcrumb-admin">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.reportes') }}">Reportes</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $empleado->nombre_completo }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="bi bi-person-lines-fill me-2"></i>
        Reporte: {{ $empleado->nombre_completo }}
    </h4>
    <a href="{{ route('admin.reportes') }}" class="btn btn-outline-secondary btn-sm">← Reportes</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reportes.empleado', $empleado) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="filtroFechaInicio">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" id="filtroFechaInicio" class="form-control" value="{{ $fechaInicio }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filtroFechaFin">Fecha Fin</label>
                <input type="date" name="fecha_fin" id="filtroFechaFin" class="form-control" value="{{ $fechaFin }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.reportes.empleado.exportar', ['empleado' => $empleado, 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'formato' => 'pdf']) }}" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white text-center py-3">
            <h3 class="mb-0">{{ $estadisticas->dias_asistidos }}</h3>
            <small>Días Asistidos</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white text-center py-3">
            <h3 class="mb-0">{{ number_format($estadisticas->horas_reales, 1) }}h</h3>
            <small>Horas Reales</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white text-center py-3">
            <h3 class="mb-0">{{ number_format($estadisticas->horas_teoricas, 1) }}h</h3>
            <small>Horas Teóricas</small>
        </div>
    </div>
    <div class="col-md-3">
        @php
            $colorProd = $estadisticas->productividad >= 80 ? 'success' : ($estadisticas->productividad >= 50 ? 'warning' : 'danger');
        @endphp
        <div class="card border-0 shadow-sm bg-{{ $colorProd }} text-white text-center py-3">
            <h3 class="mb-0">{{ $estadisticas->productividad }}%</h3>
            <small>Productividad</small>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <span class="badge bg-success fs-6 mb-1">{{ $estadisticas->presentes }}</span>
                <p class="small text-muted mb-0">Presentes</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <span class="badge bg-warning text-dark fs-6 mb-1">{{ $estadisticas->tardes }}</span>
                <p class="small text-muted mb-0">Tardes</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <span class="badge bg-danger fs-6 mb-1">{{ $estadisticas->inasistentes }}</span>
                <p class="small text-muted mb-0">Inasistentes</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h6 class="mb-0">Detalle de Asistencias</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Reporte de empleado">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Estado</th>
                        <th>Justificado</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($asistencias as $a)
                    <tr>
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
                        <td class="small">{{ Str::limit($a->texto_justificacion, 50) ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center py-3">Sin registros en el período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
