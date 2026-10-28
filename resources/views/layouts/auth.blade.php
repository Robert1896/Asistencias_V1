<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Control de Asistencia') - Universidad</title>
    <meta name="description" content="Sistema de control de asistencia universitaria - Inicio de sesión">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <style nonce="{{ $cspNonce ?? '' }}">
        :root {
            --u-orange: #bf4c00;
            --u-black: #1a1a1a;
            --u-navy: #0d2137;
            --bg-primary: #f2f2f2;
            --bg-card: #ffffff;
            --bg-input: #ffffff;
            --bg-body: linear-gradient(180deg, #fff5eb 0%, #f2f2f2 100%);
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
        }
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #0f172a;
            --bg-body: linear-gradient(180deg, #1a1a2e 0%, #0f172a 100%);
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --u-orange: #e67e22;
        }
        body { background: var(--bg-body); color: var(--text-primary); transition: background 0.3s ease, color 0.3s ease; min-height: 100vh; position: relative; overflow-x: hidden; }
        [data-theme="dark"] .card { background: var(--bg-card); border-color: var(--border-color); }
        [data-theme="dark"] .form-control, [data-theme="dark"] .form-select {
            background: var(--bg-input); border-color: var(--border-color); color: var(--text-primary);
        }
        [data-theme="dark"] .form-label { color: var(--text-secondary); }
        .auth-theme-toggle {
            position: fixed; top: 16px; right: 16px; background: var(--bg-card); border: 1px solid var(--border-color);
            color: var(--text-primary); width: 38px; height: 38px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;
            font-size: 1rem; z-index: 100;
        }
        .auth-theme-toggle:hover { transform: rotate(20deg); border-color: var(--u-orange); }
        [data-theme="dark"] .alert-success { background: rgba(102,187,106,0.15); color: #66bb6a; }
        [data-theme="dark"] .alert-danger { background: rgba(239,83,80,0.15); color: #ef5350; }
        [data-theme="dark"] h5,
        [data-theme="dark"] p,
        [data-theme="dark"] .form-check-label,
        [data-theme="dark"] a:not(.alert a) {
            color: var(--text-primary);
        }
        [data-theme="dark"] .text-muted {
            color: var(--text-secondary) !important;
        }
        [data-theme="dark"] .btn-primary {
            background: var(--u-orange);
            border-color: var(--u-orange);
        }
        .auth-wave-top, .auth-wave-bottom {
            position: fixed; left: 0; right: 0; z-index: 0; pointer-events: none;
        }
        .auth-wave-top { top: 0; height: 220px; }
        .auth-wave-bottom { bottom: 0; height: 150px; }
        .container { position: relative; z-index: 1; }

        /* ═══ HIDE NATIVE EDGE PASSWORD EYE ═══ */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-webkit-toggle-password-visibility {
            display: none !important;
        }

        /* ═══ CUSTOM PASSWORD EYE (cross-browser) ═══ */
        .pw-toggle-btn {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 4px 6px;
            color: var(--text-secondary); font-size: 0.95rem; line-height: 1;
            opacity: 0; pointer-events: none; transition: opacity 0.2s ease;
            z-index: 5;
        }
        .pw-toggle-btn.visible {
            opacity: 1; pointer-events: auto;
        }
        .pw-toggle-btn:hover { color: var(--u-orange); }
        .pw-toggle-btn:focus-visible { outline: 2px solid var(--u-orange); outline-offset: 2px; border-radius: 2px; }
        .pw-field-wrap { position: relative; }
        .pw-field-wrap input[type="password"],
        .pw-field-wrap input[type="text"] { padding-right: 2.5rem; }
        .auth-footer {
            color: var(--text-secondary);
        }
        [data-theme="dark"] .auth-footer {
            color: var(--text-secondary) !important;
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
    <a href="#auth-content" class="visually-hidden-focusable">Ir al contenido principal</a>
    <div class="auth-wave-top">
        <svg viewBox="0 0 1400 220" preserveAspectRatio="none" style="width:100%;height:100%;">
            <path d="M0,0 L1400,0 L1400,90 Q1200,160 1000,110 Q800,60 600,120 Q400,180 200,110 Q100,75 0,110 Z" fill="rgba(230,81,0,0.18)"/>
            <path d="M0,0 L1400,0 L1400,55 Q1200,120 1000,80 Q800,40 600,90 Q400,140 200,80 Q100,50 0,80 Z" fill="rgba(230,81,0,0.1)"/>
        </svg>
    </div>
    <div class="auth-wave-bottom">
        <svg viewBox="0 0 1400 150" preserveAspectRatio="none" style="width:100%;height:100%;">
            <path d="M0,150 L1400,150 L1400,50 Q1200,0 1000,60 Q800,120 600,50 Q400,0 200,70 Q100,120 0,70 Z" fill="rgba(13,33,55,0.12)"/>
        </svg>
    </div>
    <button class="auth-theme-toggle" id="authThemeToggle" title="Cambiar tema" aria-label="Cambiar entre tema claro y oscuro">
        <i class="bi bi-moon-fill" id="authThemeIcon"></i>
    </button>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <x-alertas />
                <main id="auth-content">
                    @yield('content')
                </main>
                <div class="text-center mt-3 small auth-footer">
                    &copy; {{ date('Y') }} {{ config('app.domain') ?: 'UPTAG' }} - Todos los derechos reservados.
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        document.querySelectorAll('form[data-validate]').forEach(function(form) {
            var fields = form.querySelectorAll('[required], [type="email"], [minlength], [maxlength], [pattern]');
            fields.forEach(function(field) {
                function getGroup() {
                    var col = field.closest('[class*="col-"]');
                    if (col) return col;
                    return field.closest('.mb-3') || field.parentElement;
                }
                function clearError() {
                    field.classList.remove('is-invalid');
                    var g = getGroup();
                    if (g) { var old = g.querySelector('.invalid-feedback'); if (old) old.remove(); }
                }
                function showError(msg) {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                    var g = getGroup();
                    if (!g) return;
                    var old = g.querySelector('.invalid-feedback');
                    if (old) old.remove();
                    var fb = document.createElement('div');
                    fb.className = 'invalid-feedback d-block';
                    fb.style.fontSize = '0.75rem';
                    fb.textContent = msg;
                    g.appendChild(fb);
                }
                function validate() {
                    var val = field.value.trim();
                    if (field.required && !val) { showError('Este campo es obligatorio.'); return false; }
                    if (!val && !field.required) { clearError(); return true; }
                    if (field.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                        showError('Ingrese un correo válido (ejemplo@dominio.com).'); return false;
                    }
                    if (field.minLength > 0 && val.length < field.minLength) {
                        showError('Mínimo ' + field.minLength + ' caracteres.'); return false;
                    }
                    if (field.pattern && val) {
                        var re = new RegExp('^' + field.pattern + '$');
                        if (!re.test(val)) {
                            showError(field.title || 'Formato no válido.'); return false;
                        }
                    }
                    field.classList.remove('is-invalid');
                    var g = getGroup();
                    if (g) { var old = g.querySelector('.invalid-feedback'); if (old) old.remove(); }
                    return true;
                }
                field.addEventListener('blur', validate);
                field.addEventListener('input', function() {
                    if (field.classList.contains('is-invalid')) {
                        clearError();
                        validate();
                    }
                });
            });
        });
    </script>
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('toggle-login-pw');
            var input = document.getElementById('login-password');
            if (!btn || !input) return;

            input.addEventListener('input', function() {
                if (input.value.length > 0) {
                    btn.classList.add('visible');
                } else {
                    btn.classList.remove('visible');
                    input.type = 'password';
                    btn.querySelector('i').className = 'bi bi-eye';
                }
            });

            btn.addEventListener('click', function() {
                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.querySelector('i').className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });
    </script>
    <script nonce="{{ $cspNonce ?? '' }}">
        (function() {
            var toggle = document.getElementById('authThemeToggle');
            var icon = document.getElementById('authThemeIcon');
            var html = document.documentElement;
            if (!toggle || !icon) return;

            var saved = localStorage.getItem('theme');
            if (saved) { html.setAttribute('data-theme', saved); updateIcon(saved); }
            else if (window.matchMedia('(prefers-color-scheme: dark)').matches) { html.setAttribute('data-theme', 'dark'); updateIcon('dark'); }

            toggle.addEventListener('click', function() {
                var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                updateIcon(next);
            });

            function updateIcon(theme) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
        })();
    </script>
</body>
</html>
