@extends('layouts.auth')

@section('title', 'Acceso denegado')

@section('content')
<div class="card shadow-lg border-0 text-center">
    <div class="card-body p-5">
        <div class="fs-1 fw-bold text-muted">403</div>
        <h5 class="mt-2">Acceso denegado</h5>
        <p class="text-muted small">No tiene permisos para acceder a esta sección.</p>
        <a href="{{ url('/login') }}" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-house me-1"></i>Volver al Inicio
        </a>
    </div>
</div>
@endsection
