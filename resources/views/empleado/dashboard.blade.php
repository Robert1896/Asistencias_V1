@php
    $titulo = 'Mi Panel';
@endphp

@extends('layouts.app')

@section('title', $titulo)
@section('tone-level', '1')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2"></i>{{ $user->nombre }}</h5>
        <small class="text-muted">{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</small>
    </div>
</div>

{{-- Resumen del mes --}}
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="stat-card stat-green p-3">
            <div class="num">{{ $totalMes->presentes ?? 0 }}</div>
            <div class="label">Presente</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card stat-yellow p-3">
            <div class="num">{{ $totalMes->tardes ?? 0 }}</div>
            <div class="label">Tardes</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card stat-red p-3">
            <div class="num">{{ $totalMes->inasistentes ?? 0 }}</div>
            <div class="label">Inasistente</div>
        </div>
    </div>
</div>

@if (($totalMes->presentes ?? 0) == 0 && ($totalMes->tardes ?? 0) == 0 && ($totalMes->inasistentes ?? 0) == 0)
<div class="alert alert-info small mb-3 py-2">
    <i class="bi bi-info-circle me-1"></i>
    <strong>¿Cómo funciona?</strong> Muestra tu QR en la Estación de Chequeo para entrada, y escanea el QR de tu salón en cada bloque.
</div>
@endif

<small class="text-muted fw-bold text-uppercase mb-2 d-block acciones-label" style="font-size:0.62rem;letter-spacing:1px;">Acciones</small>

<div class="row g-3">
    <div class="col-4">
        <a href="{{ route('qr.dinamico.page') }}" class="text-decoration-none">
            <div class="card nav-btn grad-qr h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                    <div class="icon-wrap"><i class="bi bi-qr-code"></i></div>
                    <h6 class="fw-bold mb-0">Mi QR</h6>
                    <small class="text-muted d-none d-sm-block">Código dinámico</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-4">
        <a href="{{ route('docente.escanear.salon') }}" class="text-decoration-none">
            <div class="card nav-btn grad-escanear h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                    <div class="icon-wrap"><i class="bi bi-upc-scan"></i></div>
                    <h6 class="fw-bold mb-0">Escanear</h6>
                    <small class="text-muted d-none d-sm-block">QR del salón</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-4">
        <a href="{{ route('empleado.horario') }}" class="text-decoration-none">
            <div class="card nav-btn grad-horario h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                    <div class="icon-wrap"><i class="bi bi-calendar-week"></i></div>
                    <h6 class="fw-bold mb-0">Horario</h6>
                    <small class="text-muted d-none d-sm-block">Mi agenda</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-4">
        <a href="{{ route('empleado.estadisticas') }}" class="text-decoration-none">
            <div class="card nav-btn grad-stats h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                    <div class="icon-wrap"><i class="bi bi-bar-chart-line"></i></div>
                    <h6 class="fw-bold mb-0">Estadísticas</h6>
                    <small class="text-muted d-none d-sm-block">Mis gráficos</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-4">
        <a href="{{ route('justificaciones') }}" class="text-decoration-none">
            <div class="card nav-btn grad-justificar h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                    <div class="icon-wrap"><i class="bi bi-clipboard-check"></i></div>
                    <h6 class="fw-bold mb-0">Justificar</h6>
                    <small class="text-muted d-none d-sm-block">Mis justificaciones</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-4">
        <a href="{{ route('profile.edit') }}" class="text-decoration-none">
            <div class="card nav-btn grad-perfil h-100">
                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                    <div class="icon-wrap"><i class="bi bi-person-gear"></i></div>
                    <h6 class="fw-bold mb-0">Perfil</h6>
                    <small class="text-muted d-none d-sm-block">Mi cuenta</small>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection