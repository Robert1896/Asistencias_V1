@php
    $titulo = 'Configuración de Correo';
@endphp

@extends('layouts.app')

@section('title', $titulo)
@section('tone-level', '1')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 fw-bold"><i class="bi bi-envelope-gear me-2"></i>{{ $titulo }}</h4>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-server me-2"></i>Servidor SMTP</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.correo.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Host SMTP *</label>
                            <input type="text" class="form-control @error('host') is-invalid @enderror"
                                   name="host" value="{{ old('host', $config->host) }}" required
                                   placeholder="smtp.gmail.com">
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Puerto *</label>
                            <input type="number" class="form-control @error('puerto') is-invalid @enderror"
                                   name="puerto" value="{{ old('puerto', $config->puerto) }}" required min="1" max="65535">
                            @error('puerto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Encriptación *</label>
                            <select class="form-select @error('encriptacion') is-invalid @enderror" name="encriptacion" required>
                                <option value="tls" {{ old('encriptacion', $config->encriptacion) === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ old('encriptacion', $config->encriptacion) === 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="none" {{ old('encriptacion', $config->encriptacion) === 'none' ? 'selected' : '' }}>Ninguna</option>
                            </select>
                            @error('encriptacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Usuario SMTP *</label>
                            <input type="text" class="form-control @error('usuario') is-invalid @enderror"
                                   name="usuario" value="{{ old('usuario', $config->usuario) }}" required
                                   placeholder="correo@gmail.com">
                            @error('usuario')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contraseña SMTP *</label>
                            <input type="password" class="form-control @error('contrasena') is-invalid @enderror"
                                   name="contrasena" value="{{ old('contrasena', $config->contrasena) }}"
                                   placeholder="{{ $config->contrasena ? '••••••••' : '' }}">
                            @error('contrasena')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <hr class="my-2">
                        <div class="col-md-6">
                            <label class="form-label">Dirección "From" *</label>
                            <input type="email" class="form-control @error('direccion_from') is-invalid @enderror"
                                   name="direccion_from" value="{{ old('direccion_from', $config->direccion_from) }}" required
                                   placeholder="no-reply@universidad.edu">
                            @error('direccion_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre "From" *</label>
                            <input type="text" class="form-control @error('nombre_from') is-invalid @enderror"
                                   name="nombre_from" value="{{ old('nombre_from', $config->nombre_from) }}" required>
                            @error('nombre_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="activo" value="1" id="activoCheck"
                                       {{ old('activo', $config->activo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activoCheck">
                                    <strong>Activar envío de correos</strong>
                                    <br><small class="text-muted">Si está desactivado, los correos no se enviarán (se guardan en log)</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Guardar configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-send me-2"></i>Enviar correo de prueba</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.correo.probar') }}" method="POST" class="d-flex gap-2 align-items-end">
                    @csrf
                    <div class="flex-grow-1">
                        <label class="form-label">Correo de destino</label>
                        <input type="email" class="form-control @error('email_prueba') is-invalid @enderror"
                               name="email_prueba" placeholder="tu@email.com" required>
                        @error('email_prueba')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-outline-success btn-sm text-nowrap">
                        <i class="bi bi-send me-1"></i>Enviar prueba
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
