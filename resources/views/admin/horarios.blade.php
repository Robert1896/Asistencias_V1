@extends('layouts.app')

@section('title', 'Gestión de Horarios')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-calendar-range me-2"></i>Gestión de Horarios</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'administrativos' ? 'active' : '' }}"
               href="{{ route('admin.horarios', ['tab' => 'administrativos']) }}">Horarios Fijos (Administrativos y Asistentes)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'docentes' ? 'active' : '' }}"
           href="{{ route('admin.horarios', ['tab' => 'docentes']) }}">Horarios Personal Docente</a>
    </li>
</ul>

@if ($tab === 'administrativos')
<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><h6 class="mb-0">Asignar / Editar Turno (Administrativo / Asistente)</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.horarios.guardar-admin') }}">                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-danger py-1 px-3 small mb-2">
                            @foreach ($errors->all() as $error)
                                <span class="me-2">{{ $error }}</span>@if(!$loop->last)&middot;@endif
                            @endforeach
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Empleado</label>
                        <select name="usuario_id" class="form-select @error('usuario_id') is-invalid @enderror" required>
                            <option value="">Seleccione...</option>
                            @foreach ($administrativos as $a)
                                <option value="{{ $a->id }}">{{ $a->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Turno</label>
                        <select name="turno" class="form-select" required>
                            <option value="Matutino">Matutino (8:00 — 13:30)</option>
                            <option value="Vespertino">Vespertino (13:00 — 18:00)</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Entrada</label>
                            <input type="time" name="hora_entrada" class="form-control" value="08:00">
                        </div>
                        <div class="col">
                            <label class="form-label">Salida</label>
                            <input type="time" name="hora_salida" class="form-control" value="13:30">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Días de la semana</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['lunes'=>'Lun','martes'=>'Mar','miercoles'=>'Mié','jueves'=>'Jue','viernes'=>'Vie','sabado'=>'Sáb'] as $clave => $etiqueta)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="{{ $clave }}" value="1"
                                       id="dia_{{ $clave }}" {{ $clave !== 'sabado' ? 'checked' : '' }}>
                                <label class="form-check-label" for="dia_{{ $clave }}">{{ $etiqueta }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0" role="table" aria-label="Horarios fijos">
                    <thead>
                        <tr><th>Empleado</th><th>Turno</th><th>Entrada</th><th>Salida</th><th>Días</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($horariosAdmin as $h)
                        <tr>
                            <td>{{ $h->usuario->nombre_completo }}</td>
                            <td><span class="badge bg-info">{{ $h->turno }}</span></td>
                            <td>{{ $h->hora_entrada }}</td>
                            <td>{{ $h->hora_salida }}</td>
                            <td>
                                @foreach(['lunes'=>'Lun','martes'=>'Mar','miercoles'=>'Mié','jueves'=>'Jue','viernes'=>'Vie','sabado'=>'Sáb'] as $k => $l)
                                    @if($h->$k)
                                        <span class="badge bg-success">{{ $l }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('admin.horarios.edit', $h->usuario_id) }}"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-muted text-center py-3">Sin horarios registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Horarios del Personal Docente</h6>
                <a href="{{ route('admin.docentes.horarios') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-range me-1"></i>Ver / Modificar horarios completos</a>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0" role="table" aria-label="Horarios docentes">
                    <tbody>
                        @forelse ($docentes as $d)
                        <tr>
                            <td>{{ $d->nombre_completo }}</td>
                            <td>{{ $d->email }}</td>
                            <td><span class="badge bg-info">{{ $d->horariosDocente->count() }} bloques</span></td>
                            <td>
                                @if ($d->horariosDocente->isEmpty())
                                    <span class="text-muted small">Sin horarios asignados</span>
                                @else
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($d->horariosDocente->sortBy(fn($h)=>$h->dia_semana.(string)$h->hora_inicio) as $hd)
                                            <span class="badge bg-light text-dark border">
                                                {{ \App\Models\Horario::DIAS[$hd->dia_semana] ?? $hd->dia_semana }} {{ substr($hd->hora_inicio,0,5) }}-{{ substr($hd->hora_fin,0,5) }}
                                                @if(isset($hd->salon) && $hd->salon) - {{ $hd->salon->nombre }} @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.docentes.horarios.edit', $d) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i> Modificar</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No hay docentes activos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
