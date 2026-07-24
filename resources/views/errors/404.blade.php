@extends('layouts.auth')

@section('title', 'Página no encontrada')

@section('content')
<div class="card shadow-lg border-0 text-center">
    <div class="card-body p-5">
        <div class="fs-1 fw-bold text-muted">404</div>
        <h5 class="mt-2">Página no encontrada</h5>
        <p class="text-muted small">La página que busca no existe o fue movida.</p>
        <a href="{{ url('/login') }}" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-house me-1"></i>Volver al Inicio
        </a>
    </div>
</div>
@endsection
