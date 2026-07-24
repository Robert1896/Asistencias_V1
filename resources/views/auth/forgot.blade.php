@extends('layouts.auth')

@section('title', 'Recuperar Contraseña')

@section('content')
<div class="card shadow-lg border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <img src="{{ asset('img/logo.png') }}" alt="Logo de la universidad" width="56" height="56">
            <h5 class="mt-2">Recuperar Contraseña</h5>
            <p class="text-muted small">Ingresa tu correo para recibir el enlace de restablecimiento</p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" data-validate>
            @csrf

            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Enviar enlace</button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="small">Volver al inicio de sesión</a>
        </div>
    </div>
</div>
@endsection
