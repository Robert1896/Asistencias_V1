@extends('layouts.app')

@section('title', 'Mi Código QR Dinámico')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route($backRoute ?? 'dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Mi Panel</a>
</div>

<div class="row justify-content-center g-4">
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white text-center py-3">
                <h5 class="mb-0"><i class="bi bi-qr-code me-2"></i>Mi Código QR Dinámico</h5>
                <small class="text-muted">Se renueva automáticamente cada 60 segundos</small>
            </div>
            <div class="card-body text-center">
                <div id="qrContainer" class="py-3 text-center">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
                </div>

                <div class="mt-3">
                    <div class="progress" style="height: 6px;">
                        <div id="qrTimer" class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                    </div>
                    <small class="text-muted" id="qrExpiry">Expira en: 60s</small>
                </div>

                <div class="mt-3">
                    <button class="btn btn-outline-primary btn-sm" type="button" data-refrescar-qr>
                        <i class="bi bi-arrow-clockwise me-1"></i>Renovar ahora
                    </button>
                </div>
            </div>
            <div class="card-footer bg-white text-center py-2">
                <a href="{{ route($backRoute ?? 'dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver a Mi Panel</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i>¿Cómo funciona?</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="bi bi-1-circle-fill text-primary me-2"></i>Apunte la cámara de la estación hacia este código QR</li>
                    <li class="mb-2"><i class="bi bi-2-circle-fill text-primary me-2"></i>El código se renueva cada 60 segundos por seguridad</li>
                    <li class="mb-2"><i class="bi bi-3-circle-fill text-primary me-2"></i>Si el código expiró, se mostrará un error al escanear</li>
                    <li class="mb-0"><i class="bi bi-shield-lock-fill text-success me-2"></i>Úselo en la Estación de Chequeo para registrar entrada o salida</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    const qrUrl = '{{ route("qr.dinamico", $user) }}';
    let countdownInterval = null;
    let tiempoRestante = 60;

    window.refrescarQR = function refrescarQR() {
        const container = document.getElementById('qrContainer');
        if (!container) return;
        container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';

        fetch(qrUrl, { cache: 'no-store' })
            .then(r => {
                if (!r.ok) throw new Error('No se pudo generar el QR');
                return r.blob();
            })
            .then(blob => {
                const url = URL.createObjectURL(blob);
                container.innerHTML = '';
                const img = document.createElement('img');
                img.src = url;
                img.alt = 'QR Dinámico';
                img.className = 'img-fluid border rounded p-2 bg-white';
                img.style.maxWidth = '280px';
                container.appendChild(img);
                iniciarCuentaRegresiva();
            })
            .catch(() => {
                container.innerHTML = '<div class="alert alert-danger py-2 mb-0">Error al generar QR</div>';
            });
    };

    function iniciarCuentaRegresiva() {
        tiempoRestante = 60;
        const timer = document.getElementById('qrTimer');
        const expiry = document.getElementById('qrExpiry');
        if (!timer || !expiry) return;
        if (countdownInterval) clearInterval(countdownInterval);
        timer.style.width = '100%';
        timer.className = 'progress-bar bg-primary';
        expiry.textContent = 'Expira en: 60s';
        countdownInterval = setInterval(() => {
            tiempoRestante--;
            timer.style.width = ((tiempoRestante / 60) * 100) + '%';
            expiry.textContent = 'Expira en: ' + tiempoRestante + 's';
            timer.className = tiempoRestante <= 10 ? 'progress-bar bg-danger' : 'progress-bar bg-primary';
            if (tiempoRestante <= 0) {
                clearInterval(countdownInterval);
                window.refrescarQR();
            }
        }, 1000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        window.refrescarQR();
    });
</script>
@endpush
