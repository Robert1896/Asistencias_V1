@extends('layouts.auth')

@section('title', 'Sesión expirada')

@section('content')
<div class="card shadow-lg border-0 text-center">
    <div class="card-body p-5">
        <div class="fs-1 fw-bold text-muted">419</div>
        <h5 class="mt-2">Sesión expirada</h5>
        <p class="text-muted small">Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.</p>
        <a href="{{ url('/login') }}" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
        </a>
    </div>
</div>
@endsection
