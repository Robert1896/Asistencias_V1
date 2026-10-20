@extends('layouts.auth')

@section('title', 'Verificación en dos pasos')

@section('content')
<div class="card shadow-lg border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="bi bi-shield-lock" style="font-size:2.5rem;color:var(--u-orange);"></i>
            <h1 class="h4 mt-2 mb-0">Verificación en dos pasos</h1>
            <p class="text-muted small mb-0">Ingresa el código de 6 dígitos que enviamos a tu correo electrónico.</p>
        </div>

        <form method="POST" action="{{ route('login.twofactor') }}" data-validate>
            @csrf

            <div class="mb-3">
                <label class="form-label" for="codigo">Código de verificación</label>
                <input type="text" name="codigo" id="codigo"
                       class="form-control text-center @error('codigo') is-invalid @enderror"
                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6"
                       required autofocus autocomplete="one-time-code"
                       placeholder="123456" title="Debe contener 6 dígitos.">
                @error('codigo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mb-2">Verificar e ingresar</button>
        </form>

        <form method="POST" action="{{ route('login.twofactor.resend') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0">Reenviar código</button>
        </form>
        <form method="POST" action="{{ route('login.twofactor.cancel') }}" class="d-inline float-end">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0 text-muted">Cancelar</button>
        </form>
    </div>
</div>
@endsection
