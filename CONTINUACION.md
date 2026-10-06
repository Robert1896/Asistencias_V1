# Continuación — Proyecto Asistencias v1

> Archivo de traspaso. Mañana basta con decir: **"lee CONTINUACION.md y sigue"**.

---

## Cómo retomar (instrucciones para el asistente)

1. Leer este archivo completo.
2. Leer `AUDIT_TODO.md` y `AUDITORIA_V2.md` (auditorías previas, contexto de los hallazgos originales).
3. Antes de tocar código: crear respaldo nuevo. Ver sección **Respaldo**.
4. No tocar la matriz de roles/permisos sin confirmación explícita del usuario (ver **PENDIENTES BLOQUEADOS**).

---

## Estado global

**Fase 1 — completada.**
**Fase 2 — completada al 100% (P6, P7, P8, P9, P10).**
**Fase 3 (rediseño de roles/permisos) — APLAZADA por el usuario, no iniciada.**

Suite de tests actual: **OK (74 tests, 311 assertions)** — línea base verde.

---

## Respaldo

Respaldo verificado de la Fase 2:

```
C:\xampp\htdocs\backups_asistencias_v1\20261006_002201\
```
(contiene copia de `asistencias_v1\` + `qr_asistencia.sql` de 189874 bytes; 21 tablas, 15 INSERT, 256 archivos)

**Regla:** crear un respaldo nuevo antes de cada tanda de cambios. No reutilizar este.

---

## Lo que se hizo en esta sesión

### P10 — Refactor del cálculo de estado (COMPLETADA)
Lógica de estado de asistencia duplicada y dispersa; se unificó.

- Nuevo `app/Support/EstadoAsistencia.php`: constantes `PRESENTE`, `TARDE`, `INASISTENTE`, `APOYO_SABADO`, `SIN_ASIGNACION`, `MINUTOS_TOLERANCIA = 15`, métodos `desdeHorarioFijo()`, `desdeLimite()`, `esTarde()`.
- `app/Services/AttendanceService.php`: eliminados `calcularEstado()` y `estadoDesdeLimite()`. Todos los puntos de cálculo usan `EstadoAsistencia`.
- `tests/Unit/EstadoAsistenciaTest.php`: 14 tests de bordes (tolerancia exacta, configurable, sábado, domingo conserva comportamiento original).

### P9 — Iconos (COMPLETADA)
Único FA restante corregido: `fas fa-arrow-left` → `bi bi-arrow-left` en `resources/views/estacion/manual.blade.php:19`. 0 coincidencias de Font Awesome restantes.

### P8 — Accesibilidad (COMPLETADA)
- `resources/views/layouts/auth.blade.php`: skip link `#auth-content`, `<main id="auth-content">`, `aria-label` en el toggle de tema.
- Labels asociados (`for`/`id`) en filtros de: `bitacora`, `asistencias`, `reportes`, `reporte_empleado`, `carga_academica/index`, `usuarios`.

### P6 — CSP sin `unsafe-inline` (COMPLETADA)
- `app/Http/Middleware/SecurityHeaders.php` reescrito: nonce por request (`base64_encode(random_bytes(16))`), `app('view')->share('cspNonce', $nonce)`, `buildCsp()` sin `'unsafe-inline'`/`'unsafe-eval'` en `script-src`; añade `object-src 'none'`, `base-uri 'self'`, `frame-ancestors 'none'`; se eliminó la cabecera obsoleta `X-XSS-Protection`.
- 15 `<script>` inline migrados a `nonce="{{ $cspNonce ?? '' }}"` en 14 vistas.
- Nuevo `public/js/app.js` con el comportamiento global que antes vivía en manejadores inline: `data-confirm`, `data-solo-letras`, `data-max-len`, `data-preview-archivo`, `data-preview-imagen`, `data-marcar-checkbox`, `data-imprimir`, `data-refrescar-qr`, `data-ver-qr`.
- `public/js/app.js` se carga con `defer` en `layouts/app.blade.php` y `layouts/auth.blade.php`.
- Globals expuestos explícitamente: `window.showToast`, `window.showLoading`, `window.hideLoading` en `layouts/app.blade.php`; `window.verQR` en `usuarios.blade.php`; `window.refrescarQR` en `auth/qr_dinamico.blade.php`.
- `tests/Feature/SecurityHeadersTest.php`: 6 tests (CSP presente, sin unsafe-inline/eval, nonce presente, object-src/frame-ancestors/base-uri, cabeceras básicas, nonce único por request).
- **0 manejadores inline restantes.** Los 44 atributos `style=""` siguen funcionando porque `style-src` conserva `'unsafe-inline'`.

### P7 — Autorización granular por endpoint en la API (COMPLETADA)
- `app/Http/Middleware/ApiAdminOnly.php`: ahora acepta permisos como parámetros (`api.admin:usuarios_ver`). Orden de validación: autenticado → `estatus === 'activo'` → rol 1 (Superadmin, bypass) → lista blanca de roles `[1,2]` → **cualquiera** de los permisos indicados. Sin permisos pasados devuelve 403.
- `routes/api.php` mapea permiso por endpoint: `usuarios`/`usuarios/{usuario}` → `usuarios_ver`; `asistencias`/`asistencias/{usuario}` → `asistencias_ver`.
- **Bug encontrado y corregido de paso:** `app/Providers/AppServiceProvider.php` — `throttle:api` fallaba en runtime con `Rate limiter [App\Models\User::api] is not defined` porque el limiter nunca estuvo registrado. Ahora: 60 req/min autenticado, 20/min por IP anónimo.
- `tests/Feature/ApiPermisosTest.php` (nuevo, 7 tests): Superadmin sin permisos, Administrativo sin/con `usuarios_ver`, `asistencias_ver` no abre `usuarios`, Docente rechazado incluso con permisos, usuario inactivo rechazado, rol fuera de lista blanca rechazado.

---

## PENDIENTES BLOQUEADOS (requieren decisión del usuario)

### 1. Matriz de roles/permisos — APLAZADA explícitamente
Hallazgos del rediseño, **no tocar sin confirmación**:

| # | Hallazgo | Ubicación |
|---|----------|-----------|
| 1 | `Gate::before` da bypass total a `rol_id === 1` | `app\Providers\AuthServiceProvider.php` |
| 2 | Edición de roles protegida con `can:usuarios_ver` en vez de `roles_editar` | `routes\web.php` |
| 3 | `UsuarioController::update` acepta `rol_id` sin validar | `app\Http\Controllers\Admin\UsuarioController.php` |
| 4 | `salones/index.blade.php` usa el código de permiso inexistente `ver_usuarios` | `resources\views\admin\salones\index.blade.php` |

Roles actuales en BD:

| id | Rol | Permisos |
|----|-----|----------|
| 1 | Superadmin | 14 (todos) |
| 2 | Administrativo | `estacion_operar`, `horarios_ver` |
| 3 | Docente | ninguno |
| 4 | Asistente | 6 |

### 2. Administrativo perdió acceso a la API
Consecuencia directa de P7: el rol 2 solo tiene `estacion_operar` y `horarios_ver`, así que ya **no** puede entrar a `/api/v1/*` (antes entraba por el chequeo `rol_id in [1,2]`).

**Impacto real ≈ cero**: no existe ningún Issuance de tokens — `createToken()` no aparece en ningún controlador ni vista. La API es inalcanzable en la práctica hoy.

**Decisión pendiente:** ¿se le agregan `usuarios_ver`/`asistencias_ver` al rol 2, se deja así, o se implementa el flujo de emisión de tokens?

### 3. Enum `asistencias.estado` vs `SIN_ASIGNACION`
El enum solo permite `Presente`, `Tarde`, `Inasistente`, `Apoyo Sabado`. Pero `EstadoAsistencia::SIN_ASIGNACION = 'Sin asignación'` **no** es un valor válido de ese enum.

**Decisión pendiente:** ¿columna nullable? ¿migrar el enum para incluirlo? ¿eliminar la constante?

---

## Nota de entorno

Problema raro del entorno (ya resuelto): `EstadoAsistencia::MINUTOS_TOLERANCIA` lanzaba `Undefined constant` de forma intermitente desde PHPUnit. Se resolvió **eliminando el test que accedía a la constante directamente** (el valor se verifica vía comportamiento, no leyendo la constante). Los archivos de diagnóstico `_diag*.php` y `tests/Unit/Z*Test.php` ya fueron eliminados. Si reaparece el síntoma, no reintroducir tests que lean constantes directamente.

---

## Archivos clave de esta sesión

```
app/Http/Middleware/ApiAdminOnly.php          ← objetivo de P7, ahora con permisos
app/Http/Middleware/SecurityHeaders.php       ← CSP con nonce por request
app/Providers/AppServiceProvider.php          ← RateLimiter 'api' (bug corregido)
app/Support/EstadoAsistencia.php              ← cálculo unificado de estados
app/Services/AttendanceService.php            ← usa EstadoAsistencia, sin lógica duplicada
public/js/app.js                              ← comportamiento global (ex inline handlers)
routes/api.php                                ← endpoints con api.admin:<permiso>
resources/views/layouts/app.blade.php         ← carga js/app.js, expone window.*, nonce
resources/views/layouts/auth.blade.php        ← skip link #auth-content, js/app.js, nonce
resources/views/admin/usuarios.blade.php      ← data-* attrs, window.verQR
resources/views/admin/asistencias.blade.php   ← filtros con for/id, preview por data-*
resources/views/admin/docente_horario_edit.blade.php ← data-marcar-checkbox
resources/views/estacion/manual.blade.php     ← último icono FA corregido (L19)

tests/Unit/EstadoAsistenciaTest.php           ← 14 tests del cálculo de estado
tests/Feature/SecurityHeadersTest.php        ← 6 tests de CSP
tests/Feature/ApiPermisosTest.php             ← 7 tests de autorización API (nuevo)
tests/Feature/ApiTest.php                     ← tests preexistentes de API
```

---

## Estado de git

**No se ha hecho ningún commit.** Hay 34 archivos modificados y 12 sin trackear respecto al commit inicial `4c8007d`.

```
M  app/Http/Controllers/Admin/CargaAcademicaController.php
M  app/Http/Controllers/Admin/SalonController.php
M  app/Http/Controllers/Api/V1/UsuarioApiController.php
M  app/Http/Controllers/EstacionController.php
M  app/Http/Middleware/ApiAdminOnly.php
M  app/Http/Middleware/SecurityHeaders.php
M  app/Providers/AppServiceProvider.php
M  app/Services/AttendanceService.php
M  app/Services/ImportService.php
M  composer.json
M  database/seeders/DatabaseSeeder.php
M  resources/views/... (20 vistas)
M  routes/api.php
M  routes/web.php
?? app/Support/
?? database/migrations/2026_07_30_092636_remove_estacion_operar_permission_from_docentes.php
?? database/migrations/2026_10_05_140000_cleanup_and_harden_asistencias_integrity.php
?? public/js/
?? tests/Feature/ApiPermisosTest.php
?? tests/Feature/ImportacionPasswordsTest.php
?? tests/Feature/LimpiarAsignacionesTest.php
?? tests/Feature/SecurityHeadersTest.php
?? tests/Unit/EstadoAsistenciaTest.php
?? tests/Unit/LikeQueryTest.php
```

**Punto de decisión:** ¿se commitea el trabajo de Fase 2 o se sigue acumulando sin commit? Recordar: nunca hacer commit sin que el usuario lo pida explícitamente.

---

## Comandos de verificación

```powershell
# Suite completa (línea base: 74 tests, 311 assertions, OK)
& "vendor\bin\phpunit"

# Solo una parte
& "vendor\bin\phpunit" --filter ApiPermisosTest
& "vendor\bin\phpunit" --filter SecurityHeadersTest

# Cachés (hacer tras tocar vistas)
php artisan view:clear
php artisan config:clear
php artisan view:cache
```

Entorno: PHP 8.2.12, PHPUnit 11.5.56, Laravel con Sanctum. Tests con SQLite `:memory:` (`phpunit.xml`).

---

## Recomendación de próximos pasos

1. Commitear Fase 2 (con confirmación del usuario) — deja la línea base consolidada.
2. Resolver el enum `asistencias.estado` vs `SIN_ASIGNACION`.
3. Iniciar **Fase 3**: rediseño de roles/permisos, empezando por los 4 hallazgos bloqueados de arriba.
4. Considerar emitir tokens de API (hoy no hay forma de crear uno).