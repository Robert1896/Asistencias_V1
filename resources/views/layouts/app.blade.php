<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Control de Asistencia') - Universidad</title>
    <meta name="description" content="@yield('meta_description', 'Sistema de control de asistencia universitaria con códigos QR dinámicos')">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <a href="#main-content" class="visually-hidden-focusable">Ir al contenido principal</a>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-box">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p id="loadingText">Procesando...</p>
        </div>
    </div>
    @auth
    @php $esDocente = auth()->user()->rol && auth()->user()->rol->nombre === 'Docente'; @endphp
    @php $esAsistente = auth()->user()->rol && auth()->user()->rol->nombre === 'Asistente'; @endphp

    {{-- ═══ NAVBAR ═══ --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="{{ $esDocente ? route('empleado.dashboard') : route('dashboard') }}">
                <img src="{{ asset('img/logo-navbar.png') }}" alt="Logo" width="28" height="28" class="me-2 rounded">
                <span>Control de Asistencia</span>
            </a>
            <button class="theme-toggle me-2" id="themeToggle" title="Cambiar tema">
                <i class="bi bi-moon-fill" id="themeIcon"></i>
            </button>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            {{-- Desktop: collapse normal --}}
            <div class="collapse navbar-collapse d-none d-lg-block" id="navbarMain">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    @if ($esDocente)
                        <li class="nav-item"><a class="nav-link" href="{{ route('qr.dinamico.page') }}"><i class="bi bi-qr-code me-1"></i>Mi QR</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('docente.escanear.salon') }}"><i class="bi bi-upc-scan me-1"></i>Escanear</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('empleado.horario') }}"><i class="bi bi-calendar-week me-1"></i>Horario</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('empleado.estadisticas') }}"><i class="bi bi-bar-chart-line me-1"></i>Estadísticas</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('justificaciones') }}"><i class="bi bi-clipboard-check me-1"></i>Justificaciones</a></li>
                    @elseif ($esAsistente)
                        <li class="nav-item"><a class="nav-link" href="{{ route('estacion.index') }}"><i class="bi bi-qr-code-scan me-1"></i>Estación</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('estacion.manual') }}"><i class="bi bi-pencil-square me-1"></i>Registro Manual</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.asistencias') }}"><i class="bi bi-calendar-check me-1"></i>Asistencias</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.salones.ocupacion') }}"><i class="bi bi-grid me-1"></i>Salones</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.horarios') }}"><i class="bi bi-calendar me-1"></i>Horarios</a></li>
                    @else
                        @can('estacion_operar')
                            <li class="nav-item"><a class="nav-link" href="{{ route('estacion.index') }}"><i class="bi bi-qr-code-scan me-1"></i>Estación</a></li>
                        @endcan
                        <li class="nav-item"><a class="nav-link" href="{{ route('justificaciones') }}"><i class="bi bi-clipboard-check me-1"></i>Justificaciones</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-gear me-1"></i>Admin</a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-scrollable">
                                <li><small class="dropdown-header text-uppercase">Gestión</small></li>
                                @can('usuarios_ver')
                                    <li><a class="dropdown-item" href="{{ route('admin.usuarios') }}"><i class="bi bi-people me-2"></i>Usuarios</a></li>
                                @endcan
                                @can('roles_ver')
                                    <li><a class="dropdown-item" href="{{ route('admin.roles') }}"><i class="bi bi-shield me-2"></i>Roles</a></li>
                                @endcan
                                @can('horarios_ver')
                                    <li><a class="dropdown-item" href="{{ route('admin.horarios') }}"><i class="bi bi-calendar me-2"></i>Horarios Base</a></li>
                                @endcan
                                @can('horarios_editar')
                                    <li><a class="dropdown-item" href="{{ route('admin.docentes.horarios') }}"><i class="bi bi-calendar-week me-2"></i>Horarios Docentes</a></li>
                                @endcan
                                @can('materias_editar')
                                    <li><a class="dropdown-item" href="{{ route('admin.materias') }}"><i class="bi bi-journal-bookmark me-2"></i>Materias</a></li>
                                @endcan
                                <li><small class="dropdown-header text-uppercase">Espacios</small></li>
                                @can('salones_ver')
                                    <li><a class="dropdown-item" href="{{ route('admin.salones.index') }}"><i class="bi bi-door-open me-2"></i>Salones</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.salones.ocupacion') }}"><i class="bi bi-grid me-2"></i>Ocupación Salones</a></li>
                                @endcan
                                <li><small class="dropdown-header text-uppercase">Sistema</small></li>
                                @can('feriados_editar')
                                    <li><a class="dropdown-item" href="{{ route('admin.feriados') }}"><i class="bi bi-calendar-event me-2"></i>Feriados</a></li>
                                @endcan
                                @can('asistencias_ver')
                                    <li><a class="dropdown-item" href="{{ route('admin.asistencias') }}"><i class="bi bi-calendar-check me-2"></i>Asistencias</a></li>
                                @endcan
                                @can('reportes_ver')
                                    <li><a class="dropdown-item" href="{{ route('admin.reportes') }}"><i class="bi bi-bar-chart me-2"></i>Reportes</a></li>
                                @endcan
                                @can('bitacora_ver')
                                    <li><a class="dropdown-item" href="{{ route('admin.bitacora') }}"><i class="bi bi-journal-text me-2"></i>Bitácora</a></li>
                                @endcan
                                @can('correo_editar')
                                    <li><a class="dropdown-item" href="{{ route('admin.correo') }}"><i class="bi bi-envelope-gear me-2"></i>Correo</a></li>
                                @endcan
                                @can('estacion_configurar')
                                    <li><a class="dropdown-item" href="{{ route('admin.estacion.central') }}"><i class="bi bi-geo-alt me-2"></i>Estación Central</a></li>
                                @endcan
                                <li><hr class="dropdown-divider"></li>
                                <li><small class="dropdown-header text-uppercase">Cuenta</small></li>
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-pencil-square me-2"></i>Mi Perfil</a></li>
                            </ul>
                        </li>
                    @endif
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="badge bg-secondary me-1">{{ strtoupper(substr(auth()->user()->nombre ?? 'U', 0, 1) . substr(auth()->user()->apellido ?? 'U', 0, 1)) }}</span>
                            <span class="d-none d-lg-inline">{{ auth()->user()->nombre_completo }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->rol->nombre ?? '' }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-pencil-square me-2"></i>Modificar Usuario</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- ═══ MOBILE: Offcanvas derecho ═══ --}}
    <div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center">
                <img src="{{ asset('img/logo-navbar.png') }}" alt="Logo" width="22" height="22" class="me-2 rounded">
                <h6 class="offcanvas-title mb-0 fw-semibold" id="mobileNavLabel" style="font-size:0.875rem;">Menú</h6>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-0">
            <nav class="flex-column flex-grow-1">
                @if ($esDocente)
                    <a class="offcanvas-nav-link" href="{{ route('qr.dinamico.page') }}"><i class="bi bi-qr-code"></i>Mi QR</a>
                    <a class="offcanvas-nav-link" href="{{ route('docente.escanear.salon') }}"><i class="bi bi-upc-scan"></i>Escanear</a>
                    <a class="offcanvas-nav-link" href="{{ route('empleado.horario') }}"><i class="bi bi-calendar-week"></i>Horario</a>
                    <a class="offcanvas-nav-link" href="{{ route('empleado.estadisticas') }}"><i class="bi bi-bar-chart-line"></i>Estadísticas</a>
                    <a class="offcanvas-nav-link" href="{{ route('justificaciones') }}"><i class="bi bi-clipboard-check"></i>Justificaciones</a>
                @elseif ($esAsistente)
                    <a class="offcanvas-nav-link" href="{{ route('estacion.index') }}"><i class="bi bi-qr-code-scan"></i>Estación</a>
                    <a class="offcanvas-nav-link" href="{{ route('estacion.manual') }}"><i class="bi bi-pencil-square"></i>Registro Manual</a>
                    <a class="offcanvas-nav-link" href="{{ route('admin.asistencias') }}"><i class="bi bi-calendar-check"></i>Asistencias</a>
                    <a class="offcanvas-nav-link" href="{{ route('admin.salones.ocupacion') }}"><i class="bi bi-grid"></i>Salones</a>
                    <a class="offcanvas-nav-link" href="{{ route('admin.horarios') }}"><i class="bi bi-calendar"></i>Horarios</a>
                @else
                    @can('estacion_operar')
                        <a class="offcanvas-nav-link" href="{{ route('estacion.index') }}"><i class="bi bi-qr-code-scan"></i>Estación</a>
                    @endcan
                    <a class="offcanvas-nav-link" href="{{ route('justificaciones') }}"><i class="bi bi-clipboard-check"></i>Justificaciones</a>
                    <div class="offcanvas-nav-divider"><small>Gestión</small></div>
                    @can('usuarios_ver')
                        <a class="offcanvas-nav-link" href="{{ route('admin.usuarios') }}"><i class="bi bi-people"></i>Usuarios</a>
                    @endcan
                    @can('roles_ver')
                        <a class="offcanvas-nav-link" href="{{ route('admin.roles') }}"><i class="bi bi-shield"></i>Roles</a>
                    @endcan
                    @can('horarios_ver')
                        <a class="offcanvas-nav-link" href="{{ route('admin.horarios') }}"><i class="bi bi-calendar"></i>Horarios Base</a>
                    @endcan
                    @can('horarios_editar')
                        <a class="offcanvas-nav-link" href="{{ route('admin.docentes.horarios') }}"><i class="bi bi-calendar-week"></i>Horarios Docentes</a>
                    @endcan
                    @can('materias_editar')
                        <a class="offcanvas-nav-link" href="{{ route('admin.materias') }}"><i class="bi bi-journal-bookmark"></i>Materias</a>
                    @endcan
                    <div class="offcanvas-nav-divider"><small>Espacios</small></div>
                    @can('salones_ver')
                        <a class="offcanvas-nav-link" href="{{ route('admin.salones.index') }}"><i class="bi bi-door-open"></i>Salones</a>
                        <a class="offcanvas-nav-link" href="{{ route('admin.salones.ocupacion') }}"><i class="bi bi-grid"></i>Ocupación Salones</a>
                    @endcan
                    <div class="offcanvas-nav-divider"><small>Sistema</small></div>
                    @can('feriados_editar')
                        <a class="offcanvas-nav-link" href="{{ route('admin.feriados') }}"><i class="bi bi-calendar-event"></i>Feriados</a>
                    @endcan
                    @can('asistencias_ver')
                        <a class="offcanvas-nav-link" href="{{ route('admin.asistencias') }}"><i class="bi bi-calendar-check"></i>Asistencias</a>
                    @endcan
                    @can('reportes_ver')
                        <a class="offcanvas-nav-link" href="{{ route('admin.reportes') }}"><i class="bi bi-bar-chart"></i>Reportes</a>
                    @endcan
                    @can('bitacora_ver')
                        <a class="offcanvas-nav-link" href="{{ route('admin.bitacora') }}"><i class="bi bi-journal-text"></i>Bitácora</a>
                    @endcan
                    @can('correo_editar')
                        <a class="offcanvas-nav-link" href="{{ route('admin.correo') }}"><i class="bi bi-envelope-gear"></i>Correo</a>
                    @endcan
                    @can('estacion_configurar')
                        <a class="offcanvas-nav-link" href="{{ route('admin.estacion.central') }}"><i class="bi bi-geo-alt"></i>Estación Central</a>
                    @endcan
                @endif
            </nav>
            <div class="offcanvas-user-box mt-auto">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-secondary me-2" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;font-size:0.78rem;">{{ strtoupper(substr(auth()->user()->nombre ?? 'U', 0, 1) . substr(auth()->user()->apellido ?? 'U', 0, 1)) }}</span>
                    <div>
                        <div class="offcanvas-user-name fw-semibold">{{ auth()->user()->nombre_completo }}</div>
                        <div class="offcanvas-user-role">{{ auth()->user()->rol->nombre ?? '' }}</div>
                    </div>
                </div>
                <a class="offcanvas-nav-link" href="{{ route('profile.edit') }}"><i class="bi bi-pencil-square"></i>Mi Perfil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="offcanvas-nav-link offcanvas-logout-btn w-100 text-start"><i class="bi bi-box-arrow-right"></i>Cerrar Sesión</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══ MAIN CONTENT ═══ --}}
    <main class="app-main tone-@yield('tone-level', '0')" id="main-content">
        <div class="wave-bg-top">
            <svg viewBox="0 0 1400 220" preserveAspectRatio="none">
                <path d="M0,0 L1400,0 L1400,90 Q1200,160 1000,110 Q800,60 600,120 Q400,180 200,110 Q100,75 0,110 Z" fill="rgba(230,81,0,0.18)"/>
                <path d="M0,0 L1400,0 L1400,55 Q1200,120 1000,80 Q800,40 600,90 Q400,140 200,80 Q100,50 0,80 Z" fill="rgba(230,81,0,0.1)"/>
            </svg>
        </div>
        <div class="wave-bg-bottom">
            <svg viewBox="0 0 1400 150" preserveAspectRatio="none">
                <path d="M0,150 L1400,150 L1400,50 Q1200,0 1000,60 Q800,120 600,50 Q400,0 200,70 Q100,120 0,70 Z" fill="rgba(13,33,55,0.12)"/>
            </svg>
        </div>
        <img src="{{ asset('img/logo.png') }}" alt="" class="logo-bg-watermark" loading="lazy">
        <div class="content-inner">
            <x-alertas />
            @auth
                @if(auth()->user()->email === null)
                    <div class="alert alert-warning alert-dismissible fade show py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Tu cuenta fue creada por importación. Debes
                        <a href="{{ route('profile.edit') }}" class="fw-bold">agregar tu correo electrónico</a>
                        para recibir notificaciones y recuperar tu contraseña.
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            @endauth
            @yield('content')
        </div>
    </main>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        /* Global AJAX error handler */
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[data-ajax]').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var btn = form.querySelector('button[type="submit"]');
                    var data = new FormData(form);
                    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando...'; }

                    fetch(form.action, {
                        method: form.method || 'POST',
                        body: data,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) {
                        if (!r.ok) throw r;
                        return r.json ? r.json() : r.text();
                    })
                    .then(function(resp) {
                        if (form.dataset.onSuccess) { window[form.dataset.onSuccess](resp); }
                        else { location.reload(); }
                    })
                    .catch(function(err) {
                        var msg = 'Ocurrió un error. Intenta de nuevo.';
                        if (err && err.json) {
                            err.json().then(function(body) { if (body.message) msg = body.message; showToast(msg, 'danger'); });
                        } else { showToast(msg, 'danger'); }
                        if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.originalText || 'Enviar'; }
                    });
                });
            });
        });

        window.showToast = function showToast(msg, type) {
            var container = document.getElementById('main-content');
            if (!container) return;
            var inner = container.querySelector('.content-inner');
            if (!inner) return;
            var alert = document.createElement('div');
            alert.className = 'alert alert-' + (type || 'danger') + ' alert-dismissible fade show py-2 small';
            alert.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' + msg + '<button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>';
            inner.prepend(alert);
        }

        /* Form loading state */
        document.querySelectorAll('form:not([data-ajax])').forEach(function(form) {
            form.addEventListener('submit', function() {
                var btn = form.querySelector('button[type="submit"]');
                    if (btn && !btn.dataset.loading) {
                    btn.dataset.loading = '1';
                    btn.dataset.originalText = btn.innerHTML;
                    btn.disabled = true;
                    var loadMsg = form.dataset.loadingText || 'Guardando...';
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + loadMsg;
                    if (form.dataset.showOverlay) { showLoading(loadMsg); }
                }
            });
        });

        /* Loading overlay helpers */
        window.showLoading = function showLoading(msg) {
            var o = document.getElementById('loadingOverlay');
            var t = document.getElementById('loadingText');
            if (o) { o.classList.add('active'); }
            if (t && msg) { t.textContent = msg; }
        }
        window.hideLoading = function hideLoading() {
            var o = document.getElementById('loadingOverlay');
            if (o) { o.classList.remove('active'); }
        }

        /* Tooltips */
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(el) { new bootstrap.Tooltip(el); });

        /* ═══ REAL-TIME FORM VALIDATION ═══ */
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
                function showValid() {
                    if (!field.value.trim() && !field.required) return;
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                    var g = getGroup();
                    if (g) { var old = g.querySelector('.invalid-feedback'); if (old) old.remove(); }
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
                    if (field.maxLength > 0 && val.length > field.maxLength) {
                        showError('Máximo ' + field.maxLength + ' caracteres.'); return false;
                    }
                    if (field.pattern && val) {
                        var re = new RegExp('^' + field.pattern + '$');
                        if (!re.test(val)) {
                            var titles = {
                                '[A-Za-záéíóúñÁÉÍÓÚÑ\\s]+': 'Solo se permiten letras y espacios.',
                                '[0-9]+': 'Solo se permiten números.',
                                '[0-9]{7,8}': 'Debe tener entre 7 y 8 dígitos.',
                                '(?=.*\\d)(?=.*[a-z])(?=.*[A-Z]).{16,}': 'Mínimo 16 caracteres con mayúscula, minúscula y número.',
                                '(?=.*\\d)(?=.*[a-z])(?=.*[A-Z]).{8,}': 'Mínimo 8 caracteres con mayúscula, minúscula y número.'
                            };
                            showError(titles[field.pattern] || field.title || 'Formato no válido.'); return false;
                        }
                    }
                    if (field.type === 'number' && val) {
                        var minVal = field.getAttribute('min');
                        var maxVal = field.getAttribute('max');
                        if (minVal && Number(val) < Number(minVal)) {
                            showError('El valor mínimo es ' + minVal + '.'); return false;
                        }
                        if (maxVal && Number(val) > Number(maxVal)) {
                            showError('El valor máximo es ' + maxVal + '.'); return false;
                        }
                    }
                    showValid();
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

        /* ═══ DARK MODE ═══ */
        (function() {
            var toggle = document.getElementById('themeToggle');
            var icon = document.getElementById('themeIcon');
            var html = document.documentElement;
            if (!toggle || !icon) return;

            var saved = localStorage.getItem('theme');
            if (saved) {
                html.setAttribute('data-theme', saved);
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                html.setAttribute('data-theme', 'dark');
            }
            updateIcon(html.getAttribute('data-theme') || 'light');

            toggle.addEventListener('click', function() {
                toggle.style.transform = 'rotate(360deg)';
                setTimeout(function() { toggle.style.transform = ''; }, 400);
                var current = html.getAttribute('data-theme') || 'light';
                var next = current === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                updateIcon(next);
            });

            function updateIcon(theme) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>