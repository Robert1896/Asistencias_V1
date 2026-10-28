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
        <form method="GET" action="{{ route('admin.bitacora') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small" for="filtroTipo">Tipo</label>
                <select name="tipo" id="filtroTipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="auth" {{ request('tipo') === 'auth' ? 'selected' : '' }}>Autenticación</option>
                    <option value="usuarios" {{ request('tipo') === 'usuarios' ? 'selected' : '' }}>Usuarios</option>
                    <option value="asistencias" {{ request('tipo') === 'asistencias' ? 'selected' : '' }}>Asistencias</option>
                    <option value="horarios" {{ request('tipo') === 'horarios' ? 'selected' : '' }}>Horarios</option>
                    <option value="salones" {{ request('tipo') === 'salones' ? 'selected' : '' }}>Salones</option>
                    <option value="sistema" {{ request('tipo') === 'sistema' ? 'selected' : '' }}>Sistema</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small" for="filtroDispositivo">Dispositivo</label>
                <select name="dispositivo" id="filtroDispositivo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="pc" {{ request('dispositivo') === 'pc' ? 'selected' : '' }}>PC</option>
                    <option value="movil" {{ request('dispositivo') === 'movil' ? 'selected' : '' }}>Móvil</option>
                    <option value="tablet" {{ request('dispositivo') === 'tablet' ? 'selected' : '' }}>Tablet</option>
                    <option value="desconocido" {{ request('dispositivo') === 'desconocido' ? 'selected' : '' }}>Desconocido</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small" for="filtroUsuario">Usuario</label>
                <input type="text" name="usuario" id="filtroUsuario" class="form-control form-control-sm" value="{{ request('usuario') }}" placeholder="Nombre o correo">
            </div>
            <div class="col-md-2">
                <label class="form-label small" for="filtroFecha">Fecha</label>
                <input type="date" name="fecha" id="filtroFecha" class="form-control form-control-sm" value="{{ request('fecha') }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="{{ route('admin.bitacora') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" role="table" aria-label="Bitácora del sistema">
                <thead class="table-light">
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Dispositivo</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($actividades as $a)
                    <tr>
                        <td class="small text-nowrap">{{ $a->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="small">{{ $a->user->nombre_completo ?? 'Sistema' }}</td>
                        <td class="small"><span class="fw-semibold">{{ \App\Support\ActivityActionTranslator::translate($a->accion) }}</span></td>
                        <td class="small">{{ $a->descripcion }}</td>
                        <td>
                            @php
                                $tipoCls = match($a->tipo) {
                                    'auth' => 'primary',
                                    'usuarios' => 'info',
                                    'asistencias' => 'warning',
                                    'horarios' => 'success',
                                    'salones' => 'dark',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $tipoCls }}">{{ $a->tipo }}</span>
                        </td>
                        <td class="small text-nowrap">
                            @php
                                $disp = $a->dispositivo ?? 'desconocido';
                                $dispIcon = match($disp) {
                                    'pc' => 'bi-laptop',
                                    'movil' => 'bi-phone',
                                    'tablet' => 'bi-tablet',
                                    default => 'bi-question-circle'
                                };
                                $dispTxt = match($disp) {
                                    'pc' => 'PC',
                                    'movil' => 'Móvil',
                                    'tablet' => 'Tablet',
                                    default => 'Desconocido'
                                };
                            @endphp
                            <i class="bi {{ $dispIcon }} me-1"></i>{{ $dispTxt }}
                        </td>
                        <td class="small">{{ $a->datos['ip'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted text-center py-3">No se encontraron registros de actividad para los filtros seleccionados.</td></tr>
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
