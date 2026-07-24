@extends('layouts.app')

@section('title', 'Materias por Docente')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-journal-bookmark me-2"></i>Materias por Docente</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Buscar</label>
                <form method="GET" action="{{ route('admin.materias') }}">
                    <div class="input-group">
                        <input type="text" name="busqueda" class="form-control" placeholder="Nombre del docente o materia..." value="{{ request('busqueda') }}">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                        @if (request('busqueda'))
                            <a href="{{ route('admin.materias') }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#agregarModal">
                    <i class="bi bi-plus-lg me-1"></i>Agregar Materia
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Materias asignadas">
                <thead>
                    <tr>
                        <th>Docente</th>
                        <th>Materia</th>
                        <th>Tipo</th>
                        <th>Horas/Sem</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cargas as $c)
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $c->usuario->nombre_completo ?? 'N/A' }}</span>
                        </td>
                        <td>{{ $c->materia }}</td>
                        <td>
                            @switch($c->tipo)
                                @case('Clase') <span class="badge bg-primary">Clase</span> @break
                                @case('Asesoría') <span class="badge bg-info">Asesoría</span> @break
                                @default <span class="badge bg-secondary">{{ $c->tipo }}</span>
                            @endswitch
                        </td>
                        <td>{{ $c->horas_semanales }}h</td>
                        <td>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#editarModal{{ $c->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.materias.destroy', $c) }}" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar esta materia?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Modal Editar -->
                    <div class="modal fade" id="editarModal{{ $c->id }}" tabindex="-1" aria-labelledby="editarModalLabel{{ $c->id }}">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.materias.update', $c) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header border-0">
                                        <h6 class="modal-title" id="editarModalLabel{{ $c->id }}">Editar Materia</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="small text-muted mb-3">
                                            <strong>{{ $c->usuario->nombre_completo ?? 'N/A' }}</strong>
                                        </p>
                                        <div class="mb-3">
                                            <label class="form-label">Materia</label>
                                            <input type="text" name="materia" class="form-control" value="{{ $c->materia }}" required>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Tipo</label>
                                                <select name="tipo" class="form-select" required>
                                                    <option value="Clase" {{ $c->tipo === 'Clase' ? 'selected' : '' }}>Clase</option>
                                                    <option value="Asesoría" {{ $c->tipo === 'Asesoría' ? 'selected' : '' }}>Asesoría</option>
                                                    <option value="Otra" {{ $c->tipo === 'Otra' ? 'selected' : '' }}>Otra</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Horas/Semana</label>
                                                <input type="number" name="horas_semanales" class="form-control" value="{{ $c->horas_semanales }}" step="0.5" min="0.5" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="5" class="text-muted text-center py-4">
                            <i class="bi bi-journal-x fa-2x mb-2 d-block"></i>No hay materias registradas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="agregarModal" tabindex="-1" aria-labelledby="agregarModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.materias.store') }}">
                @csrf
                <div class="modal-header border-0">
                    <h6 class="modal-title" id="agregarModalLabel">Agregar Materia</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Docente</label>
                        <select name="usuario_id" class="form-select" required>
                            <option value="">-- Seleccionar docente --</option>
                            @foreach ($docentes as $d)
                                <option value="{{ $d->id }}">{{ $d->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Materia</label>
                        <input type="text" name="materia" class="form-control" placeholder="Ej: Lógica Matemática" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select" required>
                                <option value="Clase">Clase</option>
                                <option value="Asesoría">Asesoría</option>
                                <option value="Otra">Otra</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Horas/Semana</label>
                            <input type="number" name="horas_semanales" class="form-control" value="1.5" step="0.5" min="0.5" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
