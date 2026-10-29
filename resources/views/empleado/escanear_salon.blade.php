@extends('layouts.app')

@section('title', 'Escanear Salón')
@section('tone-level', '2')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.min.css" integrity="sha384-dCW5imOdApH6OwpFau8cZNKjqVbJYnCA5q+8YsMYP3XwXKsV6Jfz1u6MZLnXaBsS" crossorigin="anonymous" rel="stylesheet">
<style nonce="{{ $cspNonce ?? '' }}">
    #reader { width: 100%; max-width: 400px; margin: 0 auto; border: 3px dashed #dee2e6; border-radius: 12px; overflow: hidden; background: #000; }
    #reader video { width: 100% !important; height: auto !important; }
    .estado-indicador { transition: all 0.3s ease; min-height: 80px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
    .bloque-info { background: #f8f9fa; border-radius: 12px; padding: 12px 16px; border-left: 4px solid #0d6efd; }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                @if ($bloqueActual)
                    <div class="bloque-info">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clock-history fs-4 text-primary me-3"></i>
                            <div>
                                <small class="text-muted">Bloque actual</small>
                                <div class="fw-bold">{{ substr($bloqueActual['hora_inicio'], 0, 5) }} – {{ substr($bloqueActual['hora_fin'], 0, 5) }}</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bloque-info" style="border-left-color: #fd7e14; background: #fff4e6;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle fs-4 text-warning me-3"></i>
                            <div>
                                <small class="text-muted">{{ $explicacion['titulo'] ?? 'Sin bloque activo' }}</small>
                                <div class="fw-bold text-warning">{{ $explicacion['mensaje'] ?? 'No tiene un bloque horario activo en este momento.' }}</div>
                                @if (!empty($explicacion['contexto']['sesiones_hoy']))
                                    <small class="text-muted">Bloques de hoy: {{ implode(', ', $explicacion['contexto']['sesiones_hoy']) }}</small><br>
                                @endif
                                <small class="text-muted">También puede escanear el QR del salón de asistencia especial (ADHOC-001).</small>
                            </div>
                        </div>
                    </div>
                @endif

                <small class="text-muted d-block mt-2">
                    Hora del servidor: {{ now()->format('H:i:s') }} · {{ ucfirst(now()->locale('es')->dayName) }} {{ now()->format('d/m/Y') }}
                </small>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">
                        <span class="badge bg-secondary me-2" id="estadoCamara">●</span>
                        Escáner de Salón
                    </h5>
                    <button class="btn btn-outline-success btn-sm" id="btnToggleCamara">
                        <i class="bi bi-camera-video" id="iconoCamara"></i>
                        <span id="textoCamara">Iniciar Cámara</span>
                    </button>
                </div>
            </div>
            <div class="card-body text-center">
                <div id="reader"></div>
                <p class="text-muted small mt-3 mb-0" id="msgCamara" aria-live="polite">Presione "Iniciar Cámara" para escanear el QR del salón</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-3">
            <div class="card-body">
                <div id="estadoActual" class="estado-indicador bg-light text-muted" aria-live="polite">
                    <span>Esperando escaneo del salón...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.min.js" integrity="sha384-hW8ZCQHtRH+nVOAkHZ4amZvYsAtKn1ZOvMV6dNag1Rb1thWmLZMBKTRxFV0cOxiK" crossorigin="anonymous"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+" crossorigin="anonymous"></script>
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    var sonidoExito = new Audio('https://cdn.soundjay.com/button/sounds/button-09.mp3');
    var sonidoError = new Audio('https://cdn.soundjay.com/button/sounds/button-10.mp3');
    var readerEl = document.getElementById('reader');
    var estadoActual = document.getElementById('estadoActual');
    var estadoCamara = document.getElementById('estadoCamara');
    var btnToggle = document.getElementById('btnToggleCamara');
    var iconoCamara = document.getElementById('iconoCamara');
    var textoCamara = document.getElementById('textoCamara');
    var msgCamara = document.getElementById('msgCamara');
    var html5QrCode = null, bloqueado = false, camaraActiva = false;

    function actualizarUI(encendida) {
        camaraActiva = encendida;
        if (encendida) {
            estadoCamara.className = 'badge bg-success me-2';
            estadoCamara.textContent = '●';
            iconoCamara.className = 'bi bi-camera-video-off';
            textoCamara.textContent = 'Detener Cámara';
            btnToggle.className = 'btn btn-outline-danger btn-sm';
            msgCamara.textContent = 'Apunte al código QR del salón frente a la cámara';
        } else {
            estadoCamara.className = 'badge bg-secondary me-2';
            estadoCamara.textContent = '●';
            iconoCamara.className = 'bi bi-camera-video';
            textoCamara.textContent = 'Iniciar Cámara';
            btnToggle.className = 'btn btn-outline-success btn-sm';
            msgCamara.textContent = 'Presione "Iniciar Cámara" para escanear el QR del salón';
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
                mostrarEstado('Cámara bloqueada. Acceda por https://localhost/asistencias_v1/public para habilitar la cámara.', 'danger');
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
        detenerEscaner();

        fetch('{{ route("estacion.salon-qr") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ salon_qr: decodedText })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                sonidoExito.play().catch(function(){});
                mostrarAlerta(data.tipo === 'warning' ? 'warning' : 'success', data.mensaje);
                mostrarEstado(data.mensaje, data.tipo === 'warning' ? 'warning' : 'success');
            } else {
                sonidoError.play().catch(function(){});
                mostrarAlerta('error', data.mensaje);
                mostrarEstado(data.mensaje, 'danger', data.diagnostico);
            }
        })
        .catch(function() { mostrarAlerta('error', 'Error de conexión'); mostrarEstado('Error de conexión', 'danger'); })
        .finally(function() { setTimeout(function() { bloqueado = false; iniciarEscaner(); }, 2500); });
    }

    function mostrarAlerta(tipo, mensaje) {
        Swal.fire({ icon: tipo, title: mensaje, showConfirmButton: false, timer: 3000, toast: true, position: 'top-end',
            background: { success: '#28a745', warning: '#ffc107', error: '#dc3545', info: '#0dcaf0' }[tipo] || '#dc3545',
            color: '#fff', iconColor: '#fff' });
    }

    function mostrarEstado(texto, tipo, diagnostico) {
        var bg = { success: 'bg-success text-white', danger: 'bg-danger text-white', info: 'bg-info text-white', warning: 'bg-warning text-dark' }[tipo] || 'bg-light text-muted';
        estadoActual.className = 'estado-indicador ' + bg;
        while (estadoActual.firstChild) estadoActual.removeChild(estadoActual.firstChild);

        var caja = document.createElement('div');
        caja.className = 'text-center w-100 px-2';

        var principal = document.createElement('span');
        principal.className = 'fw-bold small d-block';
        principal.textContent = texto;
        caja.appendChild(principal);

        if (diagnostico && diagnostico.hora_servidor) {
            var partes = ['Hora del servidor: ' + diagnostico.hora_servidor];
            if (diagnostico.dia) partes.push(diagnostico.dia + ' ' + (diagnostico.fecha || ''));
            if (diagnostico.sesiones_hoy && diagnostico.sesiones_hoy.length) {
                partes.push('Bloques de hoy: ' + diagnostico.sesiones_hoy.join(', '));
            } else {
                partes.push('Sin bloques hoy');
            }
            if (diagnostico.dias_con_bloques && diagnostico.dias_con_bloques.length) {
                partes.push('Días con bloques: ' + diagnostico.dias_con_bloques.join(', '));
            }

            var detalle = document.createElement('span');
            detalle.className = 'small d-block mt-1';
            detalle.style.opacity = '0.9';
            detalle.textContent = partes.join(' · ');
            caja.appendChild(detalle);
        }

        estadoActual.appendChild(caja);
    }

    btnToggle.addEventListener('click', toggleCamara);
})();
</script>
@endpush