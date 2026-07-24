@extends('layouts.app')

@section('title', 'Estación de Chequeo')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
    #reader { width: 100%; max-width: 480px; margin: 0 auto; border: 3px dashed #dee2e6; border-radius: 12px; overflow: hidden; background: #000; }
    #reader video { width: 100% !important; height: auto !important; }
    .estado-indicador { transition: all 0.3s ease; min-height: 100px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
    .modal-dept .form-check-label { cursor: pointer; padding: 10px 15px; border: 2px solid #dee2e6; border-radius: 8px; display: block; transition: all 0.2s; }
    .modal-dept .form-check-input:checked + .form-check-label { border-color: #0d6efd; background: #e7f1ff; }
    .salon-especial .form-check-label { border-color: #bf4c00 !important; }
    .salon-especial .form-check-input:checked + .form-check-label { border-color: #bf4c00 !important; background: #fff5eb !important; color: #bf4c00; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">
                        <span class="badge bg-secondary me-2" id="estadoCamara">●</span>
                        Escáner QR
                    </h5>
                    <button class="btn btn-outline-success btn-sm" id="btnToggleCamara">
                        <i class="bi bi-camera-video" id="iconoCamara"></i>
                        <span id="textoCamara">Iniciar Cámara</span>
                    </button>
                </div>
            </div>
            <div class="card-body text-center">
                <div id="reader"></div>
                <p class="text-muted small mt-3 mb-0" id="msgCamara" aria-live="polite">Presione "Iniciar Cámara" para comenzar. Escanee el QR del empleado para registrar entrada; escanee nuevamente para registrar salida.</p>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Registro en Vivo</h5>
            </div>
            <div class="card-body">
                <div id="estadoActual" class="estado-indicador bg-light text-muted" aria-live="polite">
                    <span>Esperando lectura QR...</span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0">Últimos registros</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="listaUltimos" aria-live="polite">
                    <li class="list-group-item text-muted small text-center">Sin registros aún</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Modal selección de salón (ad-hoc) --}}
<div class="modal fade modal-dept" id="modalDepartamento" tabindex="-1" aria-labelledby="modalDepartamentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDepartamentoLabel"><i class="bi bi-building me-2"></i>Asistencia Especial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2">No tiene bloque activo.</p>
                <p class="fw-bold mb-3" id="deptEmpleadoNombre"></p>
                <div class="mb-3">
                    <span class="text-muted small">Departamento:</span>
                    <span class="fw-semibold">Informática</span>
                </div>
                <div id="salonEspecial" class="mb-2"></div>
                <hr class="my-2">
                <p class="text-muted small mb-2">O seleccione un salón disponible:</p>
                <div id="salonLista" class="mb-3"></div>
                <div id="salonSinDisponibles" class="text-center text-muted d-none">
                    <i class="bi bi-exclamation-circle me-1"></i>No hay salones disponibles en este momento.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnDeptConfirmar" disabled>
                    <i class="bi bi-check-circle me-1"></i>Registrar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function() {
    var sonidoExito = new Audio('https://cdn.soundjay.com/button/sounds/button-09.mp3');
    var sonidoError = new Audio('https://cdn.soundjay.com/button/sounds/button-10.mp3');
    var readerEl = document.getElementById('reader');
    var estadoActual = document.getElementById('estadoActual');
    var listaUltimos = document.getElementById('listaUltimos');
    var estadoCamara = document.getElementById('estadoCamara');
    var btnToggle = document.getElementById('btnToggleCamara');
    var iconoCamara = document.getElementById('iconoCamara');
    var textoCamara = document.getElementById('textoCamara');
    var msgCamara = document.getElementById('msgCamara');
    var html5QrCode = null, bloqueado = false, camaraActiva = false;
    var MAX_ULTIMOS = 10;

    function actualizarUI(encendida) {
        camaraActiva = encendida;
        if (encendida) {
            estadoCamara.className = 'badge bg-success me-2';
            estadoCamara.textContent = '●';
            iconoCamara.className = 'bi bi-camera-video-off';
            textoCamara.textContent = 'Detener Cámara';
            btnToggle.className = 'btn btn-outline-danger btn-sm';
            msgCamara.textContent = 'Apunte el código QR del empleado frente a la cámara';
        } else {
            estadoCamara.className = 'badge bg-secondary me-2';
            estadoCamara.textContent = '●';
            iconoCamara.className = 'bi bi-camera-video';
            textoCamara.textContent = 'Iniciar Cámara';
            btnToggle.className = 'btn btn-outline-success btn-sm';
            msgCamara.textContent = 'Presione "Iniciar Cámara" para comenzar a escanear';
        }
    }

    function limpiarReader() { while (readerEl.firstChild) readerEl.removeChild(readerEl.firstChild); }

    function crearEscanner() {
        html5QrCode = new Html5Qrcode('reader');
        html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 15, qrbox: { width: 250, height: 250 } },
            onScanSuccess, function() {}
        ).then(function() {
            actualizarUI(true);
            mostrarEstado('Cámara activa — Listo para escanear', 'success');
        }).catch(function() {
            actualizarUI(false);
            var isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            if (!isSecure) {
                mostrarEstado('Cámara bloqueada. Acceda por <strong>https://localhost/asistencias_v1/public</strong> para habilitar la cámara.', 'danger');
            } else {
                mostrarEstado('Error al acceder a la cámara. Verifique los permisos del navegador.', 'danger');
            }
        });
    }

    function iniciarEscaner() {
        if (html5QrCode) {
            html5QrCode.stop().then(function() { html5QrCode.clear(); limpiarReader(); crearEscanner(); })
                .catch(function() { html5QrCode.clear(); limpiarReader(); crearEscanner(); });
        } else { limpiarReader(); crearEscanner(); }
    }

    function detenerEscaner() {
        if (html5QrCode) {
            html5QrCode.stop().then(function() { html5QrCode.clear(); html5QrCode = null; limpiarReader(); actualizarUI(false); mostrarEstado('Cámara detenida.', 'info'); })
                .catch(function() { html5QrCode.clear(); html5QrCode = null; limpiarReader(); actualizarUI(false); mostrarEstado('Cámara detenida.', 'info'); });
        } else actualizarUI(false);
    }

    function toggleCamara() { camaraActiva ? detenerEscaner() : iniciarEscaner(); }

    function onScanSuccess(decodedText) {
        if (bloqueado) return;
        bloqueado = true;
        mostrarEstado('Procesando...', 'info');

        fetch('{{ route("estacion.procesar") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ qr_hash: decodedText })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.requires_department) {
                sonidoError.play().catch(function(){});
                mostrarEstado(data.mensaje, 'info');
                mostrarDepartamentoModal(data);
                return;
            }
            if (data.ok) {
                sonidoExito.play().catch(function(){});
                mostrarAlerta(data.tipo === 'warning' ? 'warning' : 'success', data.mensaje);
                mostrarEstado(data.mensaje, data.tipo === 'warning' ? 'warning' : 'success');
                if (data.nombre) agregarUltimo(data);
            } else {
                sonidoError.play().catch(function(){});
                mostrarAlerta('error', data.mensaje);
                mostrarEstado(data.mensaje, 'danger');
            }
        })
        .catch(function() { mostrarAlerta('error', 'Error de conexión'); mostrarEstado('Error de conexión', 'danger'); })
        .finally(function() { setTimeout(function() { bloqueado = false; }, 1500); });
    }

    function mostrarAlerta(tipo, mensaje) {
        Swal.fire({ icon: tipo, title: mensaje, showConfirmButton: false, timer: 2500, toast: true, position: 'top-end',
            background: { success: '#28a745', warning: '#ffc107', error: '#dc3545', info: '#0dcaf0' }[tipo] || '#dc3545',
            color: '#fff', iconColor: '#fff' });
    }

    function mostrarEstado(texto, tipo) {
        var bg = { success: 'bg-success text-white', danger: 'bg-danger text-white', info: 'bg-info text-white', warning: 'bg-warning text-dark' }[tipo] || 'bg-light text-muted';
        estadoActual.className = 'estado-indicador ' + bg;
        estadoActual.innerHTML = '<span class="fw-bold">' + texto + '</span>';
    }

    function agregarUltimo(data) {
        var icono = data.marca === 'entrada' ? '<i class="bi bi-box-arrow-in-down text-success"></i>' : '<i class="bi bi-box-arrow-up text-primary"></i>';
        var badge = data.estado === 'Tarde' ? ' <span class="badge bg-warning text-dark">Tarde</span>'
                    : data.estado === 'Apoyo Sabado' ? ' <span class="badge bg-info">Apoyo Sábado</span>' : '';
        var bloqueInfo = data.bloque ? ' <span class="badge bg-secondary">' + esc(data.bloque) + '</span>' : '';
        var li = document.createElement('li');
        li.className = 'list-group-item py-2 small';
        li.innerHTML = icono + ' <strong>' + esc(data.nombre) + '</strong>' + badge + bloqueInfo + '<br>'
                     + '<span class="text-muted">' + esc(data.rol) + ' — ' + esc(data.marca) + ' ' + esc(data.hora) + '</span>';
        listaUltimos.prepend(li);
        while (listaUltimos.children.length > MAX_ULTIMOS) listaUltimos.removeChild(listaUltimos.lastChild);
        var placeholder = listaUltimos.querySelector('.text-center');
        if (placeholder) placeholder.remove();
    }

    function esc(str) { var d = document.createElement('div'); d.appendChild(document.createTextNode(str || '')); return d.innerHTML; }

    var deptUserId = null;
    var deptSelectedId = null;
    var salonSelectedId = null;
    var modalDeptEl = document.getElementById('modalDepartamento');
    var modalDept = new bootstrap.Modal(modalDeptEl);
    var btnDeptConfirmar = document.getElementById('btnDeptConfirmar');

    function mostrarDepartamentoModal(data) {
        deptUserId = data.user_id;
        deptSelectedId = data.departamento_id;
        salonSelectedId = null;
        document.getElementById('deptEmpleadoNombre').textContent = data.nombre;
        var salonEspecial = document.getElementById('salonEspecial');
        var lista = document.getElementById('salonLista');
        var sinDisponibles = document.getElementById('salonSinDisponibles');
        salonEspecial.innerHTML = '';
        lista.innerHTML = '';

        if (data.salon_especial) {
            var div = document.createElement('div');
            div.className = 'form-check mb-2 salon-especial';
            div.innerHTML = '<input class="form-check-input" type="radio" name="salonRadio" id="especial" value="' + data.salon_especial.id + '">'
                + '<label class="form-check-label fw-semibold" for="especial">'
                + '<i class="bi bi-star-fill me-2 text-warning"></i>' + esc(data.salon_especial.nombre)
                + ' <span class="text-muted small">(Departamento de Informática)</span>'
                + '</label>';
            div.querySelector('input').addEventListener('change', function() {
                salonSelectedId = data.salon_especial.id;
                btnDeptConfirmar.disabled = false;
            });
            salonEspecial.appendChild(div);
        }

        if (!data.salones_disponibles || data.salones_disponibles.length === 0) {
            sinDisponibles.classList.remove('d-none');
            btnDeptConfirmar.disabled = true;
        } else {
            sinDisponibles.classList.add('d-none');
            data.salones_disponibles.forEach(function(salon) {
                var id = 'salon_' + salon.id;
                var div = document.createElement('div');
                div.className = 'form-check mb-2';
                div.innerHTML = '<input class="form-check-input" type="radio" name="salonRadio" id="' + id + '" value="' + salon.id + '">'
                    + '<label class="form-check-label fw-semibold" for="' + id + '">'
                    + '<i class="bi bi-door-open me-2"></i>' + esc(salon.nombre)
                    + '</label>';
                div.querySelector('input').addEventListener('change', function() {
                    salonSelectedId = salon.id;
                    btnDeptConfirmar.disabled = false;
                });
                lista.appendChild(div);
            });
            btnDeptConfirmar.disabled = true;
        }
        modalDept.show();
    }

    btnDeptConfirmar.addEventListener('click', function() {
        if (!deptUserId || !deptSelectedId) return;
        btnDeptConfirmar.disabled = true;
        btnDeptConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Registrando...';

        var body = { user_id: deptUserId, departamento_id: deptSelectedId };
        if (salonSelectedId) body.salon_id = salonSelectedId;

        fetch('{{ route("estacion.adhoc") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            modalDept.hide();
            if (data.ok) {
                sonidoExito.play().catch(function(){});
                mostrarAlerta(data.tipo === 'warning' ? 'warning' : 'success', data.mensaje);
                mostrarEstado(data.mensaje, data.tipo === 'warning' ? 'warning' : 'success');
                if (data.nombre) agregarUltimo(data);
            } else {
                sonidoError.play().catch(function(){});
                mostrarAlerta('error', data.mensaje);
                mostrarEstado(data.mensaje, 'danger');
            }
        })
        .catch(function() { modalDept.hide(); mostrarAlerta('error', 'Error de conexión'); mostrarEstado('Error de conexión', 'danger'); })
        .finally(function() {
            btnDeptConfirmar.disabled = false;
            btnDeptConfirmar.innerHTML = '<i class="bi bi-check-circle me-1"></i>Registrar';
        });
    });

    modalDeptEl.addEventListener('hidden.bs.modal', function() { btnDeptConfirmar.disabled = true; });

    btnToggle.addEventListener('click', toggleCamara);
})();
</script>
@endpush