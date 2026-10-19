@php
    $user = auth()->user();
    $titulo = 'Gestión de Salones';
@endphp

@extends('layouts.app')

@section('title', $titulo)
@section('tone-level', '1')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold"><i class="bi bi-door-open me-2"></i>{{ $titulo }}</h4>
        <div>
            <a href="{{ route('admin.salones.ocupacion') }}" class="btn btn-outline-info btn-sm me-1">
                <i class="bi bi-calendar3 me-1"></i>Salones Ocupados
            </a>
            @can('salones_eliminar')
                <button class="btn btn-outline-danger btn-sm me-1" id="btnLimpiarHorarios" aria-label="Limpiar todos los horarios">
                    <i class="bi bi-trash me-1"></i>Limpiar Horarios
                </button>
            @endcan
            @can('salones_editar')
                <button class="btn btn-outline-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#importarModal" aria-label="Importar asignaciones de horarios">
                    <i class="bi bi-upload me-1"></i>Importar Asignaciones
                </button>
            @endcan
            @can('salones_crear')
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#salonModal"
                        data-action="crear">
                    <i class="bi bi-plus me-1"></i>Nuevo Salón
                </button>
            @endcan
        </div>
    </div>

    <form id="formLimpiar" method="POST" action="{{ route('admin.salones.limpiar') }}" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="confirmar_limpiar" value="1">
        <input type="hidden" name="confirmacion_texto" id="confirmacionTextoLimpiar" value="">
    </form>

    {{-- Alertas --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('errores_importar') && count(session('errores_importar')))
        <div class="alert alert-warning alert-dismissible fade show">
            <strong>Errores de importación:</strong>
            <ul class="mb-0 mt-1 ps-3 small">
                @foreach (session('errores_importar') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('usuarios_creados') && count(session('usuarios_creados')))
        <div class="alert alert-info alert-dismissible fade show">
            <strong><i class="bi bi-person-plus me-1"></i>Usuarios creados por importación:</strong>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered mb-0 small" role="table" aria-label="Usuarios creados por importación">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Rol</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (session('usuarios_creados') as $u)
                            <tr>
                                <td>{{ $u['nombre'] }}</td>
                                <td>{{ $u['cedula'] }}</td>
                                <td>{{ $u['rol'] }}</td>
                                <td><span class="badge text-bg-warning">Inactivo</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <small class="text-muted d-block mt-1">
                Las contraseñas generadas no se muestran por seguridad. Activa cada usuario y pide que use
                <em>«Olvidé mi contraseña»</em> para establecer la suya con el enlace enviado a su correo.
            </small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('salones_creados') && count(session('salones_creados')))
        <div class="alert alert-secondary alert-dismissible fade show">
            <strong><i class="bi bi-door-open me-1"></i>Salones creados automáticamente:</strong>
            <span class="small ms-1">{{ implode(', ', session('salones_creados')) }}</span>
            <small class="text-muted d-block mt-1">Se crearon con capacidad por defecto (30). Edítalos para agregar capacidad y ubicación.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('horarios_creados') && count(session('horarios_creados')))
        <div class="alert alert-info alert-dismissible fade show">
            <strong><i class="bi bi-clock me-1"></i>Bloques horarios creados automáticamente:</strong>
            <span class="small ms-1">{{ implode(', ', session('horarios_creados')) }}</span>
            <small class="text-muted d-block mt-1">Estos bloques ahora están disponibles en el sistema.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('coordinaciones_creadas') && count(session('coordinaciones_creadas')))
        <div class="alert alert-info alert-dismissible fade show">
            <strong><i class="bi bi-building me-1"></i>Coordinaciones creadas automáticamente:</strong>
            <span class="small ms-1">{{ implode(', ', session('coordinaciones_creadas')) }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" role="table" aria-label="Salones disponibles">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th class="text-center">Capacidad</th>
                            <th>Ubicación</th>
                            <th class="text-center">Asignaciones</th>
                            <th class="text-center">QR</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salones as $salon)
                            <tr>
                                <td>{{ $salon->nombre }}</td>
                                <td><span class="badge bg-secondary">{{ $salon->codigo }}</span></td>
                                <td class="text-center">{{ $salon->capacidad }}</td>
                                <td>{{ $salon->ubicacion ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $salon->asignaciones->count() ? 'warning' : 'light' }} text-dark">
                                        {{ $salon->asignaciones->count() }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-dark ver-qr-salon"
                                            data-id="{{ $salon->id }}"
                                            data-nombre="{{ $salon->nombre }}"
                                            data-codigo="{{ $salon->codigo }}"
                                            aria-label="Ver QR de {{ $salon->nombre }}">
                                        <i class="bi bi-qr-code"></i>
                                    </button>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.salones.ocupacion') }}?salon={{ $salon->id }}"
                                       class="btn btn-sm btn-outline-info" title="Ver horario del salón" aria-label="Ver horario de {{ $salon->nombre }}">
                                        <i class="bi bi-calendar3"></i>
                                    </a>
                                    @can('salones_editar')
                                        <button class="btn btn-sm btn-outline-primary editar-salon"
                                                data-id="{{ $salon->id }}"
                                                data-nombre="{{ $salon->nombre }}"
                                                data-codigo="{{ $salon->codigo }}"
                                                data-capacidad="{{ $salon->capacidad }}"
                                                data-ubicacion="{{ $salon->ubicacion }}"
                                                data-descripcion="{{ $salon->descripcion }}"
                                                aria-label="Editar {{ $salon->nombre }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    @endcan
                                    @if ($salon->asignaciones->isEmpty())
                                        @can('salones_eliminar')
                                            <form action="{{ route('admin.salones.destroy', $salon) }}" method="POST"
                                                  class="d-inline"
                                                  data-confirm="¿Eliminar este salón?">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Eliminar" aria-label="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-door-open fa-2x mb-2 d-block"></i>
                                    No hay salones registrados. ¡Crea el primero!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Crear / Editar --}}
<div class="modal fade" id="salonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="salonForm" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" id="salonId" name="salonId" value="">

            <div class="modal-header">
                <h5 class="modal-title" id="salonModalTitle">Nuevo Salón</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if ($errors->any())
                    <div class="alert alert-danger py-1 px-3 small mb-2">
                        @foreach ($errors->all() as $error)
                            <span class="me-2">{{ $error }}</span>@if(!$loop->last)&middot;@endif
                        @endforeach
                    </div>
                @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="salonNombre" name="nombre" required maxlength="80">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Código *</label>
                        <input type="text" class="form-control" id="salonCodigo" name="codigo" required maxlength="30"
                               placeholder="Ej: A-101">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Capacidad *</label>
                        <input type="number" class="form-control" id="salonCapacidad" name="capacidad" required min="1">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Ubicación</label>
                        <input type="text" class="form-control" id="salonUbicacion" name="ubicacion" maxlength="150"
                               placeholder="Ej: Edificio A, Planta 1">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" id="salonDescripcion" name="descripcion" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="salonSubmitBtn">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Importar Asignaciones --}}
<div class="modal fade" id="importarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.salones.importar') }}" method="POST" enctype="multipart/form-data" class="modal-content" data-show-overlay>
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Importar Asignaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Sube un archivo Excel (.xlsx) o CSV con las asignaciones de horarios a salones.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Archivo *</label>
                    <input type="file" name="archivo" class="form-control" accept=".xlsx,.csv" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="reemplazar" id="reemplazarCheckbox" value="1">
                    <label class="form-check-label small fw-semibold" for="reemplazarCheckbox">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                        Reemplazar todas las asignaciones anteriores
                    </label>
                    <div class="form-text small text-muted">
                        Se eliminarán TODAS las asignaciones y cargas académicas actuales antes de importar.
                    </div>
                </div>
                <div class="alert alert-info small mb-0">
                    <strong>Columnas requeridas:</strong> cedula, dia, hora_inicio, hora_fin, salon_codigo<br>
                    <strong>opcionales:</strong> tipo_actividad, coordinacion, materia, seccion
                    <br><span class="text-success fw-semibold">Si la materia no existe para el docente, se crea automáticamente con las horas calculadas.</span>
                    <br><a href="{{ route('admin.salones.plantilla') }}" class="text-decoration-underline mt-1 d-inline-block">
                        <i class="bi bi-download me-1"></i>Descargar plantilla de ejemplo
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Importar</button>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="qrSalonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR del Salón</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrSalonContainer" class="mb-2"></div>
                <p class="fw-bold mb-0" id="qrSalonNombre"></p>
                <small class="text-muted" id="qrSalonCodigo"></small>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button class="btn btn-outline-secondary btn-sm" data-imprimir>
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
                <button class="btn btn-outline-warning btn-sm" id="btnRegenerarQr" title="Generar un nuevo código QR. El anterior dejará de funcionar.">
                    <i class="bi bi-arrow-clockwise me-1"></i>Regenerar QR
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style nonce="{{ $cspNonce ?? '' }}">
    @media print {
        body * { visibility: hidden; }
        #qrSalonModal .modal-body, #qrSalonModal .modal-body * { visibility: visible; }
        #qrSalonModal .modal-header, #qrSalonModal .modal-footer { display: none !important; }
        #qrSalonModal { position: static !important; }
        #qrSalonModal .modal-dialog { margin: 0; max-width: 100%; }
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('salonModal');
    const form = document.getElementById('salonForm');
    const methodInput = form.querySelector('input[name="_method"]');

    // Botones "Nuevo"
    document.querySelectorAll('[data-bs-target="#salonModal"][data-action="crear"]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('salonModalTitle').textContent = 'Nuevo Salón';
            document.getElementById('salonSubmitBtn').textContent = 'Guardar';
            form.action = '{{ route("admin.salones.store") }}';
            methodInput.value = 'POST';
            form.querySelector('#salonId').value = '';
            form.querySelector('#salonNombre').value = '';
            form.querySelector('#salonCodigo').value = '';
            form.querySelector('#salonCapacidad').value = '30';
            form.querySelector('#salonUbicacion').value = '';
            form.querySelector('#salonDescripcion').value = '';
        });
    });

    // Botones "Editar"
    document.querySelectorAll('.editar-salon').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            document.getElementById('salonModalTitle').textContent = 'Editar Salón';
            document.getElementById('salonSubmitBtn').textContent = 'Actualizar';
            form.action = '{{ url("admin/salones") }}/' + id;
            methodInput.value = 'PUT';
            form.querySelector('#salonId').value = id;
            form.querySelector('#salonNombre').value = this.dataset.nombre;
            form.querySelector('#salonCodigo').value = this.dataset.codigo;
            form.querySelector('#salonCapacidad').value = this.dataset.capacidad;
            form.querySelector('#salonUbicacion').value = this.dataset.ubicacion;
            form.querySelector('#salonDescripcion').value = this.dataset.descripcion;
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        });
    });
});
</script>
<script nonce="{{ $cspNonce ?? '' }}">
document.querySelectorAll('.ver-qr-salon').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const nombre = this.dataset.nombre;
        const codigo = this.dataset.codigo;

        document.getElementById('qrSalonNombre').textContent = nombre;
        document.getElementById('qrSalonCodigo').textContent = 'Código: ' + codigo;
        document.getElementById('qrSalonContainer').innerHTML =
            '<img src="{{ url("admin/salones") }}/' + id + '/qr?v=' + Date.now() + '" alt="QR ' + esc(codigo) + '" style="max-width:250px;">';

        document.getElementById('btnRegenerarQr').dataset.salonId = id;
        document.getElementById('btnRegenerarQr').dataset.salonNombre = nombre;

        const modal = new bootstrap.Modal(document.getElementById('qrSalonModal'));
        modal.show();
    });
});

document.getElementById('btnRegenerarQr').addEventListener('click', function() {
    const salonId = this.dataset.salonId;
    const salonNombre = this.dataset.salonNombre || 'este salón';
    if (!confirm('¿Regenerar el QR de ' + salonNombre + '?\n\nEl código anterior dejará de funcionar inmediatamente.')) return;

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Regenerando...';

    fetch('{{ url("admin/salones") }}/' + salonId + '/qr/regenerar', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            document.getElementById('qrSalonContainer').innerHTML =
                '<img src="{{ url("admin/salones") }}/' + salonId + '/qr?v=' + Date.now() + '" alt="QR regenerado" style="max-width:250px;">';
            document.getElementById('qrSalonCodigo').textContent = 'Código: ' + data.nuevo_qr;
            showToast(data.mensaje, 'success');
        } else {
            showToast(data.mensaje || 'Error al regenerar', 'danger');
        }
    })
    .catch(() => showToast('Error de conexión', 'danger'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Regenerar QR';
    });
});

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}

// Limpiar horarios (acción destructiva: exige confirmación escrita)
document.getElementById('btnLimpiarHorarios').addEventListener('click', function() {
    const confirmacion = window.prompt(
        'Se eliminarán TODAS las asignaciones y cargas académicas actuales.\n' +
        'Esta acción no se puede deshacer.\n\n' +
        'Escribe ELIMINAR TODO (en mayúsculas) para confirmar:'
    );

    if (confirmacion === null) {
        return;
    }

    if (confirmacion.trim() !== 'ELIMINAR TODO') {
        window.alert('Confirmación incorrecta. No se eliminó nada.');
        return;
    }

    document.getElementById('confirmacionTextoLimpiar').value = 'ELIMINAR TODO';
    document.getElementById('formLimpiar').submit();
});

// Importar con reemplazo
document.querySelector('#importarModal form').addEventListener('submit', function(e) {
    if (document.getElementById('reemplazarCheckbox').checked) {
        if (!confirm('Esto eliminará TODAS las asignaciones y cargas académicas actuales y las reemplazará con las del archivo.\n\n¿Continuar?')) {
            e.preventDefault();
        }
    }
});
</script>
@endpush
