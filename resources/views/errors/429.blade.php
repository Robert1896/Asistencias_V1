@extends('layouts.auth')

@section('title', 'Demasiadas solicitudes')

@section('content')
<div class="card shadow-lg border-0 text-center">
    <div class="card-body p-5">
        <div class="fs-1 fw-bold text-muted">429</div>
        <h5 class="mt-2">Demasiadas solicitudes</h5>
        <p class="text-muted small">Ha realizado demasiadas solicitudes en poco tiempo. Por favor, espere unos minutos e intente de nuevo.</p>
        <a href="{{ url('/login') }}" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-house me-1"></i>Volver al Inicio
        </a>
    </div>
</div>
@endsection
