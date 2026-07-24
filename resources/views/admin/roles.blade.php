@extends('layouts.app')

@section('title', 'Roles y Permisos')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Roles y Permisos</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger py-1 px-3 small mb-2">
        @foreach ($errors->all() as $error)
            <span class="me-2">{{ $error }}</span>@if(!$loop->last)&middot;@endif
        @endforeach
    </div>
@endif

@foreach ($roles as $rol)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">
            {{ $rol->nombre }}
            <small class="text-muted fw-normal ms-2">{{ $rol->descripcion }}</small>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.roles.permisos', $rol) }}">
            @csrf @method('PUT')
            <div class="row">
                @foreach ($agrupados as $modulo => $modPermisos)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="fw-bold border-bottom pb-1 mb-2 text-capitalize">{{ $modulo }}</div>
                    @foreach ($modPermisos as $permiso)
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="permisos[]"
                               value="{{ $permiso->id }}" id="perm_{{ $rol->id }}_{{ $permiso->id }}"
                               {{ $rol->permisos->contains($permiso->id) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="perm_{{ $rol->id }}_{{ $permiso->id }}">
                            {{ $permiso->nombre }}
                            <br><code class="text-muted" style="font-size:0.65rem;">{{ $permiso->codigo }}</code>
                        </label>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary mt-2">Guardar Permisos para {{ $rol->nombre }}</button>
        </form>
    </div>
</div>
@endforeach
@endsection
