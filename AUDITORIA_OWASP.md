# Auditoría de seguridad — Asistencias v1 (Laravel 12)

**Fecha:** 2026-10-06
**Alcance:** OWASP Top 10 (2021) y CWE Top 25 sobre `C:\xampp\htdocs\asistencias_v1`.
**Metodología:** revisión estática de código, rutas, middlewares y configuración + suite de tests de regresión por fase.
**Estado final:** remediación completa por fases (0, A, B, C, D, E, F) — commiteada; `composer audit` en 0 avisos; suite **128 tests / 606 assertions OK**.

---

## Resumen por fase

| Fase | Contenido | Commit |
|------|-----------|--------|
| 0 | Respaldo + línea base + suite verde (74 tests) | `27f6da4` |
| A | Críticos de escalada de privilegios (CWE-798/269/284) | `fd0d375` |
| B | Autorización por módulo, fin de llave maestra (CWE-862) | `99f1d49` |
| C | Auth: throttling, enumeración, sesiones, SMTP (CWE-307/204/620/613/312) | `1c4e553` |
| D | Dependencias, SRI, CORS, cache QR, demos (A08/A05) | `fa5c109` |
| E | Archivos privados, CSV injection, throttles, sesión absoluta, logs 403 | `214bb1e` |
| F | Verificación final + documentación | este commit |

---

## Hallazgos remediados

### Fase A — Escalada de privilegios (críticos)

| CWE/OWASP | Hallazgo | Remedio |
|-----------|----------|---------|
| CWE-798 (A07) | Credenciales hardcodeadas en README y seeder predecible | Seeder con password aleatoria `bin2hex(random_bytes(16))`; README sin credenciales |
| CWE-269 (A01) | Rutas de roles protegidas con `can:usuarios_ver` (llave maestra) | `can:roles_ver` / `can:roles_editar` + guards en `updatePermisos` |
| CWE-263 (A01) | `rol_id` sin allow-list en store/update de usuarios | `rolesAsignables()` (no Superadmin) en `StoreUserRequest`/`UpdateUserRequest` |
| CWE-285 (A01) | `toggleEstatus`/`destroy` sin guard específico | Guards `usuarios_editar`/`usuarios_eliminar` |
| CWE-732 (A01) | Importación CSV podía asignar rol Superadmin | Import rechaza filas con rol no permitido |
| — | Bug real: `array_search` devuelve `false` (no `null`) | Passwords tomaban valores erróneos en CSV sin columna password — corregido en `ImportService` |

### Fase B — Autorización por módulo

| CWE/OWASP | Hallazgo | Remedio |
|-----------|----------|---------|
| CWE-862 (A01) | `usuarios_ver` era llave maestra de ~40 rutas | Cada ruta con su `can:` (roles, horarios, reportes, salones, bitácora, correo, feriados, materias, asistencias) |
| CWE-862 (A01) | 7 permisos mencionados en vistas pero inexistentes en BD | +7 permisos (ids 15-21); rol 1 recibe la matriz completa |
| CWE-284 (A01) | Usuarios inactivos retenían permisos | `Gate::before` deniega si `estatus !== 'activo'` + middleware global `VerificarEstatusActivo` (logout + invalidar sesión) |
| CWE-862 (A01) | Dashboard global visible sin `asistencias_ver` | Redirect a estación/profile según permisos |
| CWE-284 (A01) | Registro ad-hoc aceptaba cualquier rol (incl. Superadmin) | `procesarAdHoc` rechaza roles ∉ [2,3] (422) |
| CWE-89 (A03) | Menús y tarjetas con permisos solo en cliente | `@can` en menús desktop/offcanvas y tarjetas del dashboard |
| — | Seeder roto en BD fresca (nombre/apellido NOT NULL) | Corregido en `DatabaseSeeder` |

### Fase C — Autenticación y sesiones

| CWE/OWASP | Hallazgo | Remedio |
|-----------|----------|---------|
| CWE-307 (A07) | Login sin límite de intentos | Throttle por identificador (5/15min) y por IP (30/15min); log de bloqueos |
| CWE-204 (A01) | `forgot-password` revelaba existencia de correo | Respuesta idéntica exista o no el usuario; `throttle:5,1` y reset `throttle:10,1` |
| CWE-620 (A01) | Cambio de correo/contraseña sin contraseña actual | `current_password` obligatorio en perfil con mensajes propios |
| CWE-613 (A01) | Contraseña cambiada = sesiones y recordatorios vigentes | `User::invalidarOtrasSesiones()` rota `remember_token` y borra sesiones (driver database y file, con `SESSION_ENCRYPT`) en perfil, reset por correo y cambio por admin |
| CWE-312 (A05) | Contraseña SMTP en texto plano en BD | `CorreoConfig::descifrarContrasena()` con `Crypt` + migración automática de textos heredados |

### Fase D — Datos y configuración

| CWE/OWASP | Hallazgo | Remedio |
|-----------|----------|---------|
| A08 (CWE-1104) | 29 avisos de seguridad en 6 paquetes | `composer update` completo → **0 avisos** (dompdf 3.1.6, laravel 12.69.3, guzzle 7.15.5, commonmark 2.10.3, flysystem 3.36.0, phpspreadsheet 5.10.0) |
| CWE-829 (A08) | 8 recursos CDN sin SRI ni versión fija (`sweetalert2@11`, `chart.js@4` flotantes) | `integrity="sha384-..." crossorigin` + versiones exactas en todas las vistas |
| A05 (CWE-942) | CORS por defecto `allowed_origins/methods/headers = '*'` | `config/cors.php` propio: origen = `APP_URL`, métodos y headers explícitos, `max_age=3600` |
| — | CSP bloqueaba recursos usados por las vistas | `unpkg` (escáner QR), `fonts.bunny.net`, `cdn.soundjay.com` añadidos a las directivas correspondientes |
| CWE-524 (A05) | QR estático con `Cache-Control: public` (recurso autenticado) | `private, max-age=86400` |
| CWE-200 (A05) | `.env.example` con `SESSION_ENCRYPT=false` | `SESSION_ENCRYPT=true` + `SESSION_ABSOLUTE_TIMEOUT=480` |
| — | 5 archivos demo HTML servibles desde `public/` | Eliminados |

### Fase E — Datos sensibles y abuso

| CWE/OWASP | Hallazgo | Remedio |
|-----------|----------|---------|
| CWE-552 (A01) | Adjuntos de justificación en disco público (`/storage/justificaciones` accesible sin auth) | Guardado en disco privado + ruta `justificaciones/archivo/{asistencia}` (dueño o `asistencias_ver`) con migración lazy de archivos heredados |
| CWE-1236 (A03) | CSV/Excel exports sin sanitizar fórmulas (`=`, `+`, `-`, `@`) | Helper `App\Support\Csv` aplicado en reportes y horarios (CSV y XLSX) |
| CWE-770 (A04) | Importaciones y estación sin límite de tasa | `throttle:10,1` importaciones, `60,1` estación, `30,1` manual/token QR |
| CWE-613 (A01) | Sesión válida indefinidamente con actividad | Sesión absoluta 8h (`SESSION_ABSOLUTE_TIMEOUT`) — `SesionAbsoluta` + marca `sesion_iniciada_en` al login |
| CWE-778 (A09) | Denegaciones 403 sin registro | `RegistrarAccesoDenegado`: fila en `activity_log` + `Log::warning` con ruta, usuario e IP |

---

## Hallazgos conocidos pendientes (decisión del usuario)

1. **API**: el rol 2 perdió acceso a `/api/v1/*` (P7); no existe emisión de tokens — decidir si se habilita o se retira la API.
2. **Enum `asistencias.estado`** no contempla `SIN_ASIGNACION` (posible error al guardar ese estado).
3. **Dump de MySQL**: XAMPP apagado — generar `qr_asistencia.sql` fresco en Fase F operativa.
4. **Operativo SRI**: al cambiar/versionar recursos CDN recalcular hashes (`sha384`) o el navegador bloqueará la carga.

---

## Verificación final

```powershell
composer audit          # 0 avisos
& "vendor\bin\phpunit"  # OK (128 tests, 606 assertions)
```
