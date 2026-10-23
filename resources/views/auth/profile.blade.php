@extends('layouts.app')

@section('title', 'Modificar Perfil')
@section('tone-level', '2')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Modificar Perfil</h5>
            </div>
            <div class="card-body">
                @if ($user->email === null)
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Tu cuenta no tiene correo electrónico registrado. Debes agregar uno para poder recibir notificaciones y recuperar tu contraseña.
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-1 px-3 small mb-3">
                        @foreach ($errors->all() as $error)
                            <span class="me-2">{{ $error }}</span>@if(!$loop->last)&middot;@endif
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('profile.update') }}" data-validate>
                    @csrf
                    @method('PUT')

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre', $user->nombre) }}" required
                                   pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido *</label>
                            <input type="text" name="apellido" class="form-control @error('apellido') is-invalid @enderror"
                                   value="{{ old('apellido', $user->apellido) }}" required
                                   pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cédula *</label>
                        <input type="number" name="cedula" class="form-control @error('cedula') is-invalid @enderror"
                               value="{{ old('cedula', $user->cedula) }}" required min="1000000" max="99999999"
                               data-max-len="8">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña Actual <small class="text-muted">(obligatoria para cambiar el correo o la contraseña)</small></label>
                        <div class="pw-field-wrap">
                            <input type="password" name="current_password" id="current-password-input" autocomplete="current-password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   placeholder="Tu contraseña actual">
                            <button class="pw-toggle-btn" type="button"
                                    data-pw-toggle="current-password-input"
                                    title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                        <div class="pw-field-wrap">
                            <input type="password" name="password" id="password-input" class="form-control @error('password') is-invalid @enderror"
                                   minlength="16" placeholder="Mín. 16: mayúscula + minúscula + número"
                                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{16,}"
                                   title="Mínimo 16 caracteres con mayúscula, minúscula y número">
                            <button class="pw-toggle-btn" type="button"
                                    data-pw-toggle="password-input"
                                    title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="mt-1" id="password-strength" style="display:none;">
                            <div class="progress" style="height:5px;">
                                <div id="strength-bar" class="progress-bar" style="width:0%"></div>
                            </div>
                            <small id="strength-text" class="text-muted"></small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <div class="pw-field-wrap">
                            <input type="password" name="password_confirmation" id="password-confirm-input" class="form-control"
                                   placeholder="Repite la contraseña">
                            <button class="pw-toggle-btn" type="button"
                                    data-pw-toggle="password-confirm-input"
                                    title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    @if ($user->rol_id !== 3)
                    <div class="mb-3">
                        <label class="form-label">Código QR estático</label>
                        <input type="text" class="form-control" value="{{ $user->codigo_qr_estatico }}" readonly disabled>
                        <small class="text-muted">Este código es permanente y no se puede modificar.</small>
                    </div>
                    @endif

                    <div class="mb-3">
                        <a href="{{ route('qr.dinamico.page') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-qr-code me-2"></i>Ver mi QR Dinámico (seguro)
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('password-input')?.addEventListener('input', function() {
    var val = this.value;
    var bar = document.getElementById('strength-bar');
    var text = document.getElementById('strength-text');
    var wrapper = document.getElementById('password-strength');
    if (!val) { wrapper.style.display = 'none'; return; }
    wrapper.style.display = 'block';
    var score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[a-z]/.test(val)) score++;
    if (/\d/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var levels = [
        { pct: 20, cls: 'bg-danger', label: 'Muy débil' },
        { pct: 40, cls: 'bg-warning', label: 'Débil' },
        { pct: 60, cls: 'bg-info', label: 'Regular' },
        { pct: 80, cls: 'bg-primary', label: 'Buena' },
        { pct: 100, cls: 'bg-success', label: 'Fuerte' }
    ];
    var lvl = levels[Math.min(score, levels.length) - 1] || levels[0];
    bar.style.width = lvl.pct + '%';
    bar.className = 'progress-bar ' + lvl.cls;
    text.textContent = lvl.label;
    text.className = 'small ' + lvl.cls.replace('bg-', 'text-');
});
</script>
@endpush
@endsection
