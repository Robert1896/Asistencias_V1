# Continuación — Proyecto Asistencias v1

> Archivo de traspaso. Para retomar: **lee CONTINUACION.md y sigue**.

---

## Cómo retomar (instrucciones para el asistente)

1. Leer este archivo completo.
2. Leer `AUDITORIA_OWASP.md` (auditoría OWASP/CWE y estado de remediación) — `AUDIT_TODO.md` y `AUDITORIA_V2.md` son contexto histórico.
3. Antes de tocar código: crear respaldo nuevo. Ver sección **Respaldo**.
4. No tocar la matriz de roles/permisos sin confirmación explícita del usuario.

---

## Estado global

**Auditoría OWASP/CWE + remediación por fases: COMPLETADA (fases 0, A, B, C, D, E, F).**

Suite actual: **OK (134 tests, 615 assertions)**. `composer audit`: **0 avisos**.

Commits:

```
27f6da4  Fase 0  — respaldo + línea base (74 tests)
fd0d375  Fase A  — críticos: credenciales, rutas roles, allow-list rol_id
99f1d49  Fase B  — permisos por módulo (40 rutas con can:), +7 permisos, estatus inactivo
1c4e553  Fase C  — throttles login, forgot genérico, current_password, invalidar sesiones, SMTP cifrado
fa5c109  Fase D  — composer update (29→0 avisos), SRI, CORS, cache QR privada, demos eliminados
214bb1e  Fase E  — justificaciones en disco privado, CSV injection, throttles, sesión absoluta, logs 403
30bfd19  Fase F  — auditoría final (AUDITORIA_OWASP.md) + esta documentación
9400e11  Estación Central — verificación red/GPS, middleware, panel admin, rechazo primer registro
b403072  QR admin + nonce CSP + dropdown scroll + test QrShowTest
```

### Trabajo posterior a la auditoría

- **Estación Central (`9400e11`):** middleware `VerificarEstacionCentral` en todas las rutas de estación con modos `ninguno/red/gps/ambos`, IP/rango (`IpUtils`) y distancia Haversine contra radio configurable; headers `X-Geo-Lat/Lng` desde la vista; panel admin Configuración > Estación Central (`estacion_configurar`, id 22, solo rol 1); `AttendanceService` rechaza el primer registro del día de un docente si no existe asistencia previa.
- **Tanda QR/CSP (este commit):** `QrController::show` permite al admin con `usuarios_ver` ver el QR de otro usuario y devuelve **404** (antes 500) cuando el usuario no tiene QR estático; badge "Sin QR" en `admin/usuarios`; `nonce` CSP en los `<style>` inline de todas las vistas (faltaban 7 + 2 de salones); dropdown del menú Admin con scroll; test `tests/Feature/QrShowTest.php` (5 casos).

---

## Respaldo

Regla: crear un respaldo nuevo antes de cada tanda de cambios; no reutilizar.

```
C:\xampp\htdocs\backups_asistencias_v1\
├── 20261005_135949\
├── 20261006_002201\   (Fase anterior: 256 archivos + qr_asistencia.sql)
└── 20261006_131010\   (Fase 0 — respaldo actual; SQL copiado del anterior)
```

**Para restaurar (si el usuario lo pide):** "restaurar el respaldo 20261006_002201".

**PENDIENTE:** MySQL/XAMPP está apagado desde antes de la Fase 0 — el dump `qr_asistencia.sql` del respaldo actual es el de ayer. Cuando XAMPP esté corriendo, refrescar con:

```powershell
& 'C:\xampp\mysql\bin\mysqldump.exe' -uroot --single-transaction --routines --triggers qr_asistencia > C:\xampp\htdocs\backups_asistencias_v1\20261006_131010\qr_asistencia.sql
```

---

## Lo que hizo la auditoría (resumen)

Ver `AUDITORIA_OWASP.md` para la matriz completa. En corto:

- **A (críticos):** fin de credenciales hardcodeadas, fin de `usuarios_ver` como llave maestra (40 rutas con `can:` específico), allow-list de `rol_id`, +7 permisos (21 total), rol 1 = Superadmin con bypass completo, `Gate::before` deniega inactivos.
- **B:** middleware `VerificarEstatusActivo` (logout de inactivos), ad-hoc solo roles 2/3, `@can` en menús/tarjetas.
- **C:** throttle login por identificador e IP, forgot-password sin enumeración, `current_password` para cambiar correo/contraseña, invalidación de sesiones y `remember_token` al cambiar contraseña (drivers database/file/encrypt), contraseña SMTP cifrada con migración de plaintext.
- **D:** dependencias actualizadas (dompdf 3.1.6, laravel 12.69.3, phpspreadsheet 5.10, guzzle, commonmark, flysystem), SRI `sha384` en los 8 recursos CDN con versiones fijadas, `config/cors.php` sin wildcards, CSP habilita unpkg/fuentes/sonidos que bloqueaba, QR estático `Cache-Control: private`, demos HTML eliminados de `public/`.
- **E:** adjuntos de justificación en disco privado (ruta autorizada + migración lazy de los heredados en `public`), sanitizado CSV/Excel (`App\Support\Csv`), throttles (import 10/min, estación 60/min, manual/token 30/min), sesión absoluta 8h (`SESSION_ABSOLUTE_TIMEOUT`), cada 403 queda en `activity_log`.

---

## PENDIENTES BLOQUEADOS (requieren decisión del usuario)

1. **Administrativo (rol 2) sin acceso a `/api/v1/*`** — consecuencia de P7. No hay emisión de tokens (`createToken` sin uso), la API es inalcanzable en la práctica. Decidir: dar permisos, retirar la API, o implementar emisión de tokens.
2. **Enum `asistencias.estado` vs `SIN_ASIGNACION`** — el enum solo admite `Presente/Tarde/Inasistente/Apoyo Sabado`; `EstadoAsistencia::SIN_ASIGNACION` no es válido. Decidir: nullable, migrar enum, o eliminar la constante.

## Pendientes operativos

- Refrescar dump de MySQL (arriba).
- **SRI:** si se cambia/versiona un recurso CDN en las vistas, recalcular `sha384` o el navegador bloqueará la carga. Ver `tests/Feature/FaseDHardeningTest.php` (exige SRI a todo script/link CDN remoto).

---

## Nota de entorno

- `EstadoAsistencia::MINUTOS_TOLERANCIA` lanzaba `Undefined constant` intermitente desde PHPUnit; resuelto eliminando el test que leía la constante directamente. No reintroducirlo.
- PowerShell `Set-Content -Encoding UTF8` añade BOM y rompe PHP (fallo de namespace) — usar la herramienta de escritura de archivos o `[System.IO.File]::WriteAllText($p,$t,(New-Object System.Text.UTF8Encoding($false)))`.
- Rutas `/admin/usuarios*` exigen `can:usuarios_ver` (grupo) + permiso específico anidado → tests con permisos `[1,2]` o `[1,3]`.
- `tienePermiso()` NO hace bypass para rol 1; en vistas/controladores que necesitan bypass usar `Gate::allows()`/`@can`.
- Frontend estación usa `data.ok` (no `exito`) en JSON de `estacion.adhoc`.

---

## Comandos de verificación

```powershell
# Suite completa (actual: 134 tests, 615 assertions, OK)
php artisan view:clear; php artisan route:clear; php artisan config:clear
& "vendor\bin\phpunit"

# Por fase
& "vendor\bin\phpunit" --filter FaseDHardeningTest
& "vendor\bin\phpunit" --filter FaseEHardeningTest

# Dependencias
composer audit            # debe decir: No security vulnerability advisories found
```

Entorno: PHP 8.2.12, PHPUnit 11.5.57, Laravel 12.69.3. Tests con SQLite `:memory:` (`phpunit.xml`), `SESSION_DRIVER=array`, `BCRYPT_ROUNDS=4`.
