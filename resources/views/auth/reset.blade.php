@extends('layouts.auth')

@section('title', 'Restablecer Contraseña')

@section('content')
<div class="card shadow-lg border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <img src="{{ asset('img/logo.png') }}" alt="Logo de la universidad" width="56" height="56">
            <h5 class="mt-2">Restablecer Contraseña</h5>
        </div>

        <form method="POST" action="{{ route('password.update') }}" data-validate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Nueva Contraseña</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required
                       minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                       title="Mínimo 8 caracteres con mayúscula, minúscula y número">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Restablecer</button>
        </form>
    </div>
</div>
@endsection
