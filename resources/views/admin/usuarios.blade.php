@extends('layouts.app')

@section('title', 'Gestión de Usuarios')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-3"><i class="bi bi-people me-2"></i>Gestión de Usuarios</h4>
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
                            <label class="form-label small">Nombre *</label>
                            <input type="text" name="nombre" class="form-control form-control-sm"
                                   placeholder="Nombre" value="{{ old('nombre') }}" required
                                   pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios"
                                   oninput="this.value=this.value.replace(/[^A-Za-záéíóúñÁÉÍÓÚÑ\s]/g,'')">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Apellido *</label>
                            <input type="text" name="apellido" class="form-control form-control-sm"
                                   placeholder="Apellido" value="{{ old('apellido') }}" required
                                   pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios"
                                   oninput="this.value=this.value.replace(/[^A-Za-záéíóúñÁÉÍÓÚÑ\s]/g,'')">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Cédula *</label>
                            <input type="number" name="cedula" class="form-control form-control-sm"
                                   placeholder="Cédula" value="{{ old('cedula') }}" required min="1000000" max="99999999"
                                   oninput="this.value=this.value.slice(0,8)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Correo *</label>
                            <input type="email" name="email" class="form-control form-control-sm"
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
                            <label class="form-label small">Rol *</label>
                            <select name="rol_id" class="form-select form-select-sm" required>
                                <option value="">Seleccionar rol...</option>
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->id }}" {{ old('rol_id') == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                                @endforeach
                            </select>
                            <div style="height:22px;"></div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">Contraseña *</label>
                            <div class="input-group input-group-sm pw-field-wrap">
                                <input type="password" name="password" id="new-user-password"
                                       class="form-control"
                                       placeholder="Mín. 8: Mayús + Minús + Núm"
                                       required minlength="8"
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                       title="Mínimo 8 caracteres con al menos una mayúscula, una minúscula y un número"
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

{{-- ═══ TABLA ═══ --}}
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Usuarios del sistema">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estatus</th>
                        <th>QR</th>
                        <th>Acciones</th>
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
                                        onclick="return confirm('¿Cambiar estado de {{ $u->nombre_completo }}?')">
                                    {{ $u->estatus }}
                                </button>
                            </form>
                            @else
                            <span class="badge bg-{{ $u->estatus === 'activo' ? 'success' : 'danger' }}">{{ $u->estatus }}</span>
                            @endif
                        </td>
                        <td><code style="font-size:0.65rem;">{{ $u->codigo_qr_estatico ?? '—' }}</code></td>
                        <td>
                            <button type="button" class="btn btn-outline-success btn-sm"
                                    onclick="verQR({{ $u->id }}, '{{ $u->nombre_completo }}')">
                                <i class="bi bi-qr-code"></i>
                            </button>
                            <a href="#" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse"
                               data-bs-target="#edit-{{ $u->id }}" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                            @if ($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.usuarios.destroy', $u) }}" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar a {{ $u->nombre_completo }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    <tr class="collapse" id="edit-{{ $u->id }}">
                        <td colspan="8" class="bg-light">
                            <form method="POST" action="{{ route('admin.usuarios.update', $u) }}" class="row g-2 p-2 align-items-end" data-validate>
                                @csrf @method('PUT')
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Nombre</label>
                                    <input type="text" name="nombre" class="form-control form-control-sm"
                                           value="{{ $u->nombre }}" required
                                           pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios"
                                           oninput="this.value=this.value.replace(/[^A-Za-záéíóúñÁÉÍÓÚÑ\s]/g,'')">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Apellido</label>
                                    <input type="text" name="apellido" class="form-control form-control-sm"
                                           value="{{ $u->apellido }}" required
                                           pattern="[A-Za-záéíóúñÁÉÍÓÚÑ\s]+" title="Solo letras y espacios"
                                           oninput="this.value=this.value.replace(/[^A-Za-záéíóúñÁÉÍÓÚÑ\s]/g,'')">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small text-muted">Cédula</label>
                                    <input type="number" name="cedula" class="form-control form-control-sm"
                                           value="{{ $u->cedula }}" required min="1000000" max="99999999"
                                           oninput="this.value=this.value.slice(0,8)">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Correo</label>
                                    <input type="email" name="email" class="form-control form-control-sm"
                                           value="{{ $u->email }}" required>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small text-muted">Rol</label>
                                    <select name="rol_id" class="form-select form-select-sm" required>
                                        @foreach ($roles as $rol)
                                            <option value="{{ $rol->id }}" {{ $rol->id === $u->rol_id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Nueva contraseña</label>
                                    <input type="password" name="password" class="form-control form-control-sm"
                                           placeholder="Mín. 8 caracteres" minlength="8">
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

{{-- Modal QR --}}
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="qrModalLabel"><i class="bi bi-qr-code me-2"></i>Código QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div id="qrSpinner" class="py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Generando...</span>
                    </div>
                    <p class="text-muted small mt-2">Generando código QR...</p>
                </div>
                <div id="qrContent" style="display:none;">
                    <div id="qrImageContainer" class="mb-3"></div>
                    <p class="fw-bold mb-0" id="qrUserName"></p>
                    <code class="small text-muted" id="qrHash"></code>
                    <div class="mt-3">
                        <a id="qrDownloadLink" href="#" download="qr.png" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-download"></i> Descargar PNG
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        var pwBox = document.getElementById('pw-strength-box');
        var pwBar = document.getElementById('pw-bar');
        var pwLabel = document.getElementById('pw-label');
        if (pwInput && pwBox) {
            pwInput.addEventListener('input', function() {
                var v = pwInput.value;
                if (!v) { pwBox.style.display = 'none'; return; }
                pwBox.style.display = 'block';
                var s = 0;
                if (v.length >= 8) s++;
                if (v.length >= 12) s++;
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

        var qrUrlBase = '{{ url("qr") }}';
        window.verQR = function(userId, userName) {
            document.getElementById('qrModalLabel').textContent = 'Código QR - ' + userName;
            document.getElementById('qrSpinner').style.display = 'block';
            document.getElementById('qrContent').style.display = 'none';
            document.getElementById('qrImageContainer').innerHTML = '';
            var modal = new bootstrap.Modal(document.getElementById('qrModal'));
            modal.show();
            var img = new Image();
            img.onload = function () {
                document.getElementById('qrSpinner').style.display = 'none';
                document.getElementById('qrContent').style.display = 'block';
                document.getElementById('qrImageContainer').innerHTML = '';
                document.getElementById('qrImageContainer').appendChild(img);
                document.getElementById('qrUserName').textContent = userName;
            };
            img.onerror = function () {
                document.getElementById('qrSpinner').style.display = 'none';
                document.getElementById('qrContent').style.display = 'block';
                document.getElementById('qrImageContainer').innerHTML = '<div class="alert alert-danger py-2">Error al generar el QR</div>';
            };
            img.src = qrUrlBase + '/' + userId;
            img.alt = 'QR - ' + userName;
            img.className = 'img-fluid border rounded p-2 bg-white';
            img.style.maxWidth = '280px';
            fetch(img.src).then(function(r){ return r.blob(); }).then(function(blob) {
                document.getElementById('qrDownloadLink').href = URL.createObjectURL(blob);
                document.getElementById('qrDownloadLink').download = 'qr-' + userId + '.svg';
            }).catch(function () {});
            document.getElementById('qrHash').textContent = 'Cargando...';
        };
    });
</script>
@endpush