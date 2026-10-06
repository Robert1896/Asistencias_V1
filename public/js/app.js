/**
 * Comportamiento global de la interfaz.
 *
 * Sustituye a los manejadores inline (onclick, onsubmit, onchange, oninput)
 * para permitir una Content-Security-Policy estricta sin 'unsafe-inline'.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initConfirmaciones();
        initSoloLetras();
        initMaxLength();
        initPreviewArchivo();
        initAccionesGlobales();
    });

    function initConfirmaciones() {
        document.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-confirm]');
            if (!form) return;
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-confirm-click]');
            if (!trigger) return;
            if (!window.confirm(trigger.getAttribute('data-confirm-click'))) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }

    function initSoloLetras() {
        document.addEventListener('input', function (event) {
            var input = event.target.closest('[data-solo-letras]');
            if (!input) return;
            var valor = input.value.replace(/[^A-Za-záéíóúñÁÉÍÓÚÑ\s]/g, '');
            if (valor !== input.value) {
                input.value = valor;
            }
        });
    }

    function initMaxLength() {
        document.addEventListener('input', function (event) {
            var input = event.target.closest('[data-max-len]');
            if (!input) return;
            var max = parseInt(input.getAttribute('data-max-len'), 10);
            if (Number.isNaN(max)) return;
            if (input.value.length > max) {
                input.value = input.value.slice(0, max);
            }
        });
    }

    function initPreviewArchivo() {
        document.addEventListener('change', function (event) {
            var input = event.target.closest('[data-preview-archivo]');
            if (!input) return;

            var nombre = document.getElementById(input.getAttribute('data-preview-archivo'));
            var preview = document.getElementById(input.getAttribute('data-preview-imagen'));

            if (!input.files || !input.files[0]) {
                if (nombre) nombre.textContent = '';
                if (preview) preview.classList.add('d-none');
                return;
            }

            var file = input.files[0];
            if (nombre) {
                nombre.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
            }
            if (!preview) return;

            if (file.type && file.type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('d-none');
            }
        });
    }

    function initAccionesGlobales() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-imprimir]');
            if (trigger) {
                window.print();
                return;
            }

            var refrescar = event.target.closest('[data-refrescar-qr]');
            if (refrescar && typeof window.refrescarQR === 'function') {
                window.refrescarQR();
                return;
            }

            var verQr = event.target.closest('[data-ver-qr]');
            if (verQr && typeof window.verQR === 'function') {
                var id = verQr.getAttribute('data-ver-qr');
                var nombre = verQr.getAttribute('data-ver-qr-nombre') || '';
                window.verQR(id, nombre);
            }
        });

        document.addEventListener('change', function (event) {
            var campo = event.target.closest('[data-marcar-checkbox]');
            if (!campo) return;
            var destino = document.getElementById(campo.getAttribute('data-marcar-checkbox'));
            if (destino) destino.checked = true;
        });
    }
})();