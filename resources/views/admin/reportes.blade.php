@extends('layouts.app')

@section('title', 'Reportes de Productividad')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Reportes de Productividad</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reportes') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="general" {{ $tipoReporte === 'general' ? 'selected' : '' }}>General</option>
                    <option value="docentes" {{ $tipoReporte === 'docentes' ? 'selected' : '' }}>Solo Docentes</option>
                    <option value="administrativos" {{ $tipoReporte === 'administrativos' ? 'selected' : '' }}>Solo Administrativos</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Mostrar Reporte</button>
                </div>
            </div>
        </form>
    </div>
</div>

@php
    $exportParams = http_build_query([
        'fecha_inicio' => $fechaInicio,
        'fecha_fin'    => $fechaFin,
        'tipo'         => $tipoReporte,
    ]);
@endphp

<div class="mb-4">
    <span class="me-2 small text-muted">Exportar como:</span>
    <a href="{{ route('admin.reportes.exportar', ['formato' => 'pdf'] + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'tipo' => $tipoReporte]) }}" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
    </a>
    <a href="{{ route('admin.reportes.exportar', ['formato' => 'excel'] + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'tipo' => $tipoReporte]) }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-file-earmark-excel me-1"></i>Excel
    </a>
    <a href="{{ route('admin.reportes.exportar', ['formato' => 'csv'] + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'tipo' => $tipoReporte]) }}" class="btn btn-outline-info btn-sm">
        <i class="bi bi-filetype-csv me-1"></i>CSV
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white"><h6 class="mb-0">Productividad Individual</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Reporte general">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Rol</th>
                        <th>Días</th>
                        <th>Horas Reales</th>
                        <th>Horas Teóricas</th>
                        <th>Productividad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte as $r)
                    <tr>
                        <td>
                            <a href="{{ route('admin.reportes.empleado', ['empleado' => $r->id ?? 0, 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" class="text-decoration-none">
                                {{ $r->nombre }}
                            </a>
                        </td>
                        <td><span class="badge bg-secondary">{{ $r->rol_nombre }}</span></td>
                        <td>{{ $r->dias_asistidos }}</td>
                        <td>{{ number_format($r->horas_reales, 1) }} h</td>
                        <td>{{ number_format($r->horas_teoricas, 1) }} h</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px;">
                                     <div class="progress-bar {{ $r->productividad >= 80 ? 'bg-success' : ($r->productividad >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                          style="width: {{ min($r->productividad, 100) }}%"></div>
                                 </div>
                                <span class="small fw-bold">{{ $r->productividad }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center py-3">No hay datos de productividad para el período seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white"><h6 class="mb-0">Resumen Diario</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Reporte detallado">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Presentes</th>
                        <th>Tardes</th>
                        <th>Inasistentes</th>
                        <th>Apoyo Sábado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resumenDiario as $d)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
                        <td>{{ $d->total_registros }}</td>
                        <td class="text-success">{{ $d->presentes }}</td>
                        <td class="text-warning">{{ $d->tardes }}</td>
                        <td class="text-danger">{{ $d->inasistentes }}</td>
                        <td class="text-info">{{ $d->apoyos_sabado }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center py-3">No se encontraron registros diarios para el período seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
