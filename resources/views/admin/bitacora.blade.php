@extends('layouts.app')

@section('title', 'Bitácora de Actividad')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Bitácora de Actividad</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.bitacora') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="filtroTipo">Tipo</label>
                <select name="tipo" id="filtroTipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="auth" {{ request('tipo') === 'auth' ? 'selected' : '' }}>Autenticación</option>
                    <option value="usuarios" {{ request('tipo') === 'usuarios' ? 'selected' : '' }}>Usuarios</option>
                    <option value="asistencias" {{ request('tipo') === 'asistencias' ? 'selected' : '' }}>Asistencias</option>
                    <option value="sistema" {{ request('tipo') === 'sistema' ? 'selected' : '' }}>Sistema</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filtroFecha">Fecha</label>
                <input type="date" name="fecha" id="filtroFecha" class="form-control" value="{{ request('fecha') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Bitácora del sistema">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($actividades as $a)
                    <tr>
                        <td class="small">{{ $a->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $a->user->nombre_completo ?? 'Sistema' }}</td>
                        <td><code class="small">{{ $a->accion }}</code></td>
                        <td>{{ $a->descripcion }}</td>
                        <td>
                            @php
                                $tipoCls = match($a->tipo) {
                                    'auth' => 'primary',
                                    'usuarios' => 'info',
                                    'asistencias' => 'warning',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $tipoCls }}">{{ $a->tipo }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-muted text-center py-3">No se encontraron registros de actividad para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if (method_exists($actividades, 'links'))
    <div class="card-footer bg-white">
        {{ $actividades->links() }}
    </div>
    @endif
</div>
@endsection
