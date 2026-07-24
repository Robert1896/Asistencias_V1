@extends('layouts.app')

@section('title', 'Editar Horario: ' . $usuario->nombre_completo)
@section('tone-level', '2')

@section('content')
<nav aria-label="breadcrumb" class="breadcrumb-admin">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.horarios') }}">Horarios</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $usuario->nombre_completo }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="bi bi-calendar-range me-2"></i>
        Horario de: <strong>{{ $usuario->nombre_completo }}</strong>
    </h4>
    <a href="{{ route('admin.horarios', ['tab' => 'administrativos']) }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><h6 class="mb-0">Editar Turno Administrativo</h6></div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger py-1 px-3 small mb-2">
                        @foreach ($errors->all() as $error)
                            <span class="me-2">{{ $error }}</span>@if(!$loop->last)&middot;@endif
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.horarios.guardar-admin') }}">
                    @csrf
                    <input type="hidden" name="usuario_id" value="{{ $usuario->id }}">

                    <div class="mb-3">
                        <label class="form-label">Turno</label>
                        <select name="turno" class="form-select" required>
                            <option value="Matutino" {{ ($horario->turno ?? '') === 'Matutino' ? 'selected' : '' }}>
                                Matutino (8:00 — 13:30)
                            </option>
                            <option value="Vespertino" {{ ($horario->turno ?? '') === 'Vespertino' ? 'selected' : '' }}>
                                Vespertino (13:00 — 18:00)
                            </option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Entrada</label>
                            <input type="time" name="hora_entrada" class="form-control"
                                   value="{{ $horario->hora_entrada ?? '08:00' }}">
                        </div>
                        <div class="col">
                            <label class="form-label">Salida</label>
                            <input type="time" name="hora_salida" class="form-control"
                                   value="{{ $horario->hora_salida ?? '13:30' }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Días de la semana</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach(['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'Sábado'] as $clave => $etiqueta)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="{{ $clave }}" value="1"
                                       id="dia_{{ $clave }}" {{ ($horario->$clave ?? ($clave !== 'sabado')) ? 'checked' : '' }}>
                                <label class="form-check-label" for="dia_{{ $clave }}">{{ $etiqueta }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
