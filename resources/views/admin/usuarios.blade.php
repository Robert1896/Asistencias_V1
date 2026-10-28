@extends('layouts.app')

@section('title', 'Gestión de Usuarios')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-people me-2"></i>Gestión de Usuarios</h1>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

{{-- ═══ CREAR USUARIO ═══ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-person-plus me-1"></i> Nuevo Usuario</h6>
        <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#formCrear">
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse show" id="formCrear">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger py-1 px-3 small mb-3">
                    @foreach ($errors->all() as $error)
                        <span class="me-2">{{ $error }}</span>@if(!$loop->last)&middot;@endif
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('admin.usuarios.store') }}" data-validate>
                @csrf
                {{-- Sección 1: Datos Personales --}}
                <div class="mb-3">
                    <small class="text-muted fw-bold text-uppercase" style="font-size:0.62rem;letter-spacing:1px;">Datos Personales</small>
                    <hr class="mt-1 mb-2">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small" for="nuevoNombre">Nombre *</label>
                            <input type="text" name="nombre" id="nuevoNombre" class="form-control form-control-sm"
                                   placeholder="Nombre" value="{{ old('nombre') }}" required
                                   pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios"
                                   data-solo-letras>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small" for="nuevoApellido">Apellido *</label>
                            <input type="text" name="apellido" id="nuevoApellido" class="form-control form-control-sm"
                                   placeholder="Apellido" value="{{ old('apellido') }}" required
                                   pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios"
                                   data-solo-letras>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small" for="nuevoCedula">Cédula *</label>
                            <input type="number" name="cedula" id="nuevoCedula" class="form-control form-control-sm"
                                   placeholder="Cédula" value="{{ old('cedula') }}" required min="1000000" max="99999999"
                                   data-max-len="8">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small" for="nuevoEmail">Correo *</label>
                            <input type="email" name="email" id="nuevoEmail" class="form-control form-control-sm"
                                   placeholder="correo@universidad.edu" value="{{ old('email') }}" required>
                        </div>
                    </div>
                </div>

                {{-- Sección 2: Credenciales y Rol --}}
                <div class="mb-3">
                    <small class="text-muted fw-bold text-uppercase" style="font-size:0.62rem;letter-spacing:1px;">Acceso y Rol</small>
                    <hr class="mt-1 mb-2">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small" for="nuevoRol">Rol *</label>
                            <select name="rol_id" id="nuevoRol" class="form-select form-select-sm" required>
                                <option value="">Seleccionar rol...</option>
                                @php
                                    $rolesLista = $roles;
                                    if (!$rolesLista->contains('id', 5)) {
                                        $rolesLista = $rolesLista->push((object)['id' => 5, 'nombre' => 'Técnico'])->sortBy('id')->values();
                                    }
                                @endphp
                                @foreach ($rolesLista as $rol)
                                    @if ($rol->id !== 1 || auth()->user()->rol_id === 1)
                                        <option value="{{ $rol->id }}" {{ old('rol_id') == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div style="height:22px;"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small" for="new-user-password">Contraseña *</label>
                            <div class="input-group input-group-sm pw-field-wrap">
                                <input type="password" name="password" id="new-user-password"
                                       class="form-control"
                                       placeholder="Mín. 16: Mayús + Minús + Núm"
                                       required minlength="16"
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{16,}"
                                       title="Mínimo 16 caracteres con al menos una mayúscula, una minúscula y un número"
                                       data-strength autocomplete="new-password">
                                <button class="pw-toggle-btn" type="button" id="toggle-pw-new" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div id="pw-strength-box" class="mt-1" style="display:none;">
                                <div class="progress" style="height:4px;">
                                    <div id="pw-bar" class="progress-bar" role="progressbar" style="width:0%"></div>
                                </div>
                                <small id="pw-label" class="text-muted" style="font-size:0.65rem;"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small" for="new-user-password-confirm">Confirmar contraseña *</label>
                            <div class="input-group input-group-sm pw-field-wrap">
                                <input type="password" name="password_confirmation" id="new-user-password-confirm"
                                       class="form-control"
                                       placeholder="Repetir contraseña"
                                       required minlength="16"
                                       autocomplete="new-password">
                                <button class="pw-toggle-btn" type="button" id="toggle-pw-new-confirm" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small d-none d-md-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm w-100">+ Agregar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ IMPORTAR ═══ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-upload me-1"></i> Importar Usuarios (CSV/Excel)</h6>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#importarForm">
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse" id="importarForm">
        <div class="card-body">
            <p class="small text-muted mb-2">
                Columnas: <code>nombre</code>, <code>apellido</code>, <code>email</code>.
                Opcionales: <code>cedula</code>, <code>rol</code>, <code>password</code>.
                Separador punto y coma (;) para CSV.
            </p>
            <form method="POST" action="{{ route('admin.usuarios.importar') }}" enctype="multipart/form-data" class="row g-2 align-items-end" data-show-overlay>
                @csrf
                <div class="col-md-6">
                    <input type="file" name="archivo" class="form-control form-control-sm" accept=".csv,.txt,.xlsx" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-info btn-sm w-100">
                        <i class="bi bi-upload me-1"></i>Importar
                    </button>
                </div>
            </form>
            @if (session('errores_importar'))
                <div class="mt-2">
                    <small class="text-danger">Errores:</small>
                    <ul class="small text-danger mb-0">
                        @foreach (session('errores_importar') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card shadow border mb-3">
    <div class="card-header bg-primary text-white py-2"><strong><i class="bi bi-search me-2"></i>Búsqueda y filtros</strong></div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.usuarios') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1" for="filtroBuscar">Buscar</label>
                <input type="text" name="buscar" id="filtroBuscar" class="form-control form-control-sm" value="{{ request('buscar') }}" placeholder="Nombre, apellido, cédula, correo o docente">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1" for="filtroRol">Rol</label>
                <select name="rol_id" id="filtroRol" class="form-select form-select-sm">
                    <option value="">Todos los roles</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->id }}" {{ request('rol_id') == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="filtroEstatus">Estatus</label>
                <select name="estatus" id="filtroEstatus" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="activo" {{ request('estatus') === 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="inactivo" {{ request('estatus') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Buscar</button>
                <a href="{{ route('admin.usuarios') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i>Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- ═══ TABLA ═══ --}}
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Usuarios del sistema">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Cédula</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estatus</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $u)
                    <tr>
                        <td>{{ $u->id }}</td>
                        <td>{{ $u->nombre_completo }}</td>
                        <td>{{ $u->cedula ?? '—' }}</td>
                        <td>{{ $u->email }}</td>
                        <td><span class="badge bg-secondary">{{ $u->rol->nombre }}</span></td>
                        <td>
                            @if ($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.usuarios.toggle', $u) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="badge bg-{{ $u->estatus === 'activo' ? 'success' : 'danger' }} text-decoration-none border-0"
                                        style="cursor:pointer;"
                                        data-bs-toggle="tooltip" title="Cambiar a {{ $u->estatus === 'activo' ? 'inactivo' : 'activo' }}"
                                        data-confirm="¿Cambiar estado de {{ $u->nombre_completo }}?">
                                    {{ $u->estatus }}
                                </button>
                            </form>
                            @else
                            <span class="badge bg-{{ $u->estatus === 'activo' ? 'success' : 'danger' }}">{{ $u->estatus }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="#" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse"
                               data-bs-target="#edit-{{ $u->id }}" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                            @if ($u->id !== auth()->id())
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                    data-delete-action="{{ route('admin.usuarios.destroy', $u) }}"
                                    data-delete-name="{{ $u->nombre_completo }}"
                                    aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                            @endif
                        </td>
                    </tr>
                    <tr class="collapse" id="edit-{{ $u->id }}">
                        <td colspan="7" class="bg-light">
                            <form method="POST" action="{{ route('admin.usuarios.update', $u) }}" class="row g-2 p-2 align-items-end" data-validate>
                                @csrf @method('PUT')
                                <div class="col-md-2">
                                    <label class="form-label small text-muted" for="editNombre{{ $u->id }}">Nombre</label>
                                    <input type="text" name="nombre" id="editNombre{{ $u->id }}" class="form-control form-control-sm"
                                           value="{{ $u->nombre }}" required
                                           pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios"
                                           data-solo-letras>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted" for="editApellido{{ $u->id }}">Apellido</label>
                                    <input type="text" name="apellido" id="editApellido{{ $u->id }}" class="form-control form-control-sm"
                                           value="{{ $u->apellido }}" required
                                           pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios"
                                           data-solo-letras>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small text-muted" for="editCedula{{ $u->id }}">Cédula</label>
                                    <input type="number" name="cedula" id="editCedula{{ $u->id }}" class="form-control form-control-sm"
                                           value="{{ $u->cedula }}" required min="1000000" max="99999999"
                                           data-max-len="8">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted" for="editEmail{{ $u->id }}">Correo</label>
                                    <input type="email" name="email" id="editEmail{{ $u->id }}" class="form-control form-control-sm"
                                           value="{{ $u->email }}" required>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small text-muted" for="editRol{{ $u->id }}">Rol</label>
                                    <select name="rol_id" id="editRol{{ $u->id }}" class="form-select form-select-sm" required>
                                        @php
                                            $rolesListaEdit = $roles;
                                            if (!$rolesListaEdit->contains('id', 5)) {
                                                $rolesListaEdit = $rolesListaEdit->push((object)['id' => 5, 'nombre' => 'Técnico'])->sortBy('id')->values();
                                            }
                                        @endphp
                                        @foreach ($rolesListaEdit as $rol)
                                            @if ($rol->id !== 1 || auth()->user()->rol_id === 1)
                                                <option value="{{ $rol->id }}" {{ $rol->id === $u->rol_id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted" for="editPassword{{ $u->id }}">Nueva contraseña</label>
                                    <div class="pw-field-wrap">
                                        <input type="password" name="password" id="editPassword{{ $u->id }}" class="form-control form-control-sm"
                                               placeholder="Mín. 16 caracteres" minlength="16">
                                        <button class="pw-toggle-btn" type="button"
                                                data-pw-toggle="editPassword{{ $u->id }}"
                                                title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted" for="editPasswordConfirm{{ $u->id }}">Confirmar contraseña</label>
                                    <div class="pw-field-wrap">
                                        <input type="password" name="password_confirmation" id="editPasswordConfirm{{ $u->id }}" class="form-control form-control-sm"
                                               placeholder="Repetir contraseña" minlength="16">
                                        <button class="pw-toggle-btn" type="button"
                                                data-pw-toggle="editPasswordConfirm{{ $u->id }}"
                                                title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Guardar</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-center mt-3">
                {{ $usuarios->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Modal de confirmación de eliminación (step-up auth #19) --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="deleteUserForm" action="">
            @csrf @method('DELETE')
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h1 class="modal-title h5" id="deleteUserModalLabel"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Confirmar eliminación</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Vas a eliminar al usuario <strong id="deleteUserName"></strong>. Esta acción no se puede deshacer.</p>
                    <label class="form-label" for="deletePassword">Confirma tu contraseña de administrador</label>
                    <input type="password" name="password_confirmacion" id="deletePassword"
                           class="form-control" required autocomplete="current-password">
                    @error('password_confirmacion')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Se requiere tu contraseña para autorizar una acción destructiva.</div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash me-1"></i>Eliminar usuario</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-pw-toggle]').forEach(function(btn) {
            var input = document.getElementById(btn.getAttribute('data-pw-toggle'));
            if (!input) return;
            input.addEventListener('input', function() {
                btn.classList.toggle('visible', input.value.length > 0);
            });
            btn.addEventListener('click', function() {
                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.querySelector('i').className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });

        var toggleBtn = document.getElementById('toggle-pw-new');
        var pwInput = document.getElementById('new-user-password');
        if (toggleBtn && pwInput) {
            pwInput.addEventListener('input', function() {
                if (pwInput.value.length > 0) {
                    toggleBtn.classList.add('visible');
                } else {
                    toggleBtn.classList.remove('visible');
                    pwInput.type = 'password';
                    toggleBtn.querySelector('i').className = 'bi bi-eye';
                }
            });
            toggleBtn.addEventListener('click', function() {
                var isPassword = pwInput.type === 'password';
                pwInput.type = isPassword ? 'text' : 'password';
                toggleBtn.querySelector('i').className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        var toggleBtnConfirm = document.getElementById('toggle-pw-new-confirm');
        var pwInputConfirm = document.getElementById('new-user-password-confirm');
        if (toggleBtnConfirm && pwInputConfirm) {
            pwInputConfirm.addEventListener('input', function() {
                if (pwInputConfirm.value.length > 0) {
                    toggleBtnConfirm.classList.add('visible');
                } else {
                    toggleBtnConfirm.classList.remove('visible');
                    pwInputConfirm.type = 'password';
                    toggleBtnConfirm.querySelector('i').className = 'bi bi-eye';
                }
            });
            toggleBtnConfirm.addEventListener('click', function() {
                var isPassword = pwInputConfirm.type === 'password';
                pwInputConfirm.type = isPassword ? 'text' : 'password';
                toggleBtnConfirm.querySelector('i').className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        var pwBox = document.getElementById('pw-strength-box');
        var pwBar = document.getElementById('pw-bar');
        var pwLabel = document.getElementById('pw-label');
        if (pwInput && pwBox) {
            pwInput.addEventListener('input', function() {
                var v = pwInput.value;
                if (!v) { pwBox.style.display = 'none'; return; }
                pwBox.style.display = 'block';
                var s = 0;
                if (v.length >= 16) s++;
                if (v.length >= 20) s++;
                if (/[A-Z]/.test(v)) s++;
                if (/[a-z]/.test(v)) s++;
                if (/[0-9]/.test(v)) s++;
                if (/[^A-Za-z0-9]/.test(v)) s++;
                var levels = [
                    { min: 0, cls: 'bg-danger',  label: 'Muy débil' },
                    { min: 2, cls: 'bg-warning', label: 'Débil' },
                    { min: 3, cls: 'bg-info',    label: 'Regular' },
                    { min: 4, cls: 'bg-primary', label: 'Buena' },
                    { min: 5, cls: 'bg-success', label: 'Fuerte' }
                ];
                var lvl = levels.filter(function(l){ return s >= l.min; }).pop();
                if (pwBar) { pwBar.style.width = (s/6*100)+'%'; pwBar.className = 'progress-bar '+lvl.cls; }
                if (pwLabel) { pwLabel.textContent = lvl.label; pwLabel.className = 'small '+lvl.cls.replace('bg-','text-'); }
            });
        }

    });
</script>
@endpush