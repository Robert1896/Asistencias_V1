@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="card shadow-lg border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <img src="{{ asset('img/logo.png') }}" alt="Logo de la universidad" width="146" height="auto">
            <h5 class="mt-2">Control de Asistencia</h5>
            <p class="text-muted small">Universidad - Sistema QR</p>
        </div>

        <form method="POST" action="{{ route('login') }}" data-validate>
            @csrf

            <div class="mb-3">
                <label class="form-label">Correo electrónico o Cédula</label>
                <input type="text" name="login" class="form-control @error('login') is-invalid @enderror"
                       value="{{ old('login') }}" required autofocus autocomplete="username">
                @error('login')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <div class="input-group pw-field-wrap">
                    <input type="password" name="password" id="login-password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    <button class="pw-toggle-btn" type="button"
                            id="toggle-login-pw" title="Mostrar contraseña"
                            aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small" for="remember">Recordarme</label>
                </div>
                <a href="{{ route('password.request') }}" class="small">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Ingresar</button>
        </form>


    </div>
</div>
@endsection
