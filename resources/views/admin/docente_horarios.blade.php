@extends('layouts.app')

@section('title', 'Horarios Docentes')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Horarios del Personal Docente</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.docentes.horarios') }}" class="row g-2 align-items-end" role="search">
            <div class="col-12 col-md-8 col-lg-6">
                <label class="form-label small mb-1" for="busquedaDocente">Buscar docente</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="busqueda" id="busquedaDocente" class="form-control"
                           placeholder="Nombre, cédula, correo o materia..."
                           value="{{ $busqueda ?? '' }}" autocomplete="off">
                    @if (($busqueda ?? '') !== '')
                        <a href="{{ route('admin.docentes.horarios') }}" class="btn btn-outline-secondary" aria-label="Limpiar búsqueda">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-6 text-md-end">
                <span class="text-muted small">
                    {{ $docentes->count() }}
                    {{ $docentes->count() === 1 ? 'docente' : 'docentes' }}
                    @if (($busqueda ?? '') !== '')
                        para «{{ $busqueda }}»
                    @endif
                </span>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Horarios de docentes">
                <thead>
                    <tr>
                        <th>Docente</th>
                        <th>Correo</th>
                        <th>Materias</th>
                        <th>Bloques Asignados</th>
                        <th>Total Horas/Sem</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($docentes as $d)
                        @php $horas = $d->totalHorasSemanalesDocente(); @endphp
                        <tr>
                            <td>{{ $d->nombre_completo }}</td>
                            <td>{{ $d->email }}</td>
                            <td>
                                @if ($d->cargaAcademica->isNotEmpty())
                                    @foreach ($d->cargaAcademica as $c)
                                        <span class="badge bg-light text-dark border me-1">{{ $c->materia }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $d->horariosDocente->count() }} bloques</span>
                            </td>
                            <td>
                                <strong>{{ number_format($horas, 1) }} h</strong>
                            </td>
                            <td>
                                <a href="{{ route('admin.docentes.horarios.edit', $d) }}"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i> Asignar Horarios
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-3">
                                @if (($busqueda ?? '') !== '')
                                    No se encontraron docentes para «{{ $busqueda }}».
                                    <a href="{{ route('admin.docentes.horarios') }}" class="ms-1">Ver todos</a>
                                @else
                                    No hay docentes activos.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
