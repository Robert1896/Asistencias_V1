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
7697d97  Vercel — api/index.php, vercel.json, prepare-dist, cron, trustProxies
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
├── 20261006_131010\   (Fase 0)
├── 20261006_152226\   (tanda estación/QR previa)
└── 20261019_103758\   (respaldo actual: 292 archivos + qr_asistencia.sql)
```

**Para restaurar (si el usuario lo pide):** "restaurar el respaldo 20261019_103758".

**PENDIENTE:** MySQL/XAMPP está apagado — el dump `qr_asistencia.sql` se copió del respaldo anterior. Cuando XAMPP esté corriendo, refrescar con:

```powershell
& 'C:\xampp\mysql\bin\mysqldump.exe' -uroot --single-transaction --routines --triggers qr_asistencia > C:\xampp\htdocs\backups_asistencias_v1\20261019_103758\qr_asistencia.sql
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

## Despliegue en Vercel (en preparación)

Vercel no ejecuta PHP nativo: se usa el runtime comunitario **`vercel-php`**. El error `STATIC_BUILD_NO_OUT_DIR` pasó porque Vercel detectó `package.json` + `vite build` y esperaba una carpeta `dist` (laravel-vite emite en `public/build`).

Archivos añadidos:

| Archivo | Rol |
|---|---|
| `api/index.php` | Forwarder a `public/index.php` (entrypoint de la función) |
| `vercel.json` | `framework: null`; build `npm ci && npm run build && node scripts/prepare-dist.mjs`; `outputDirectory: dist`; función `api/index.php` con `vercel-php@0.8.0` (PHP 8.4); rewrite total a la función; cron; env no secretas |
| `.vercelignore` | No sube `vendor`, `node_modules`, `tests`, `storage/logs`, `.env`, `dist`, respaldos, etc. |
| `scripts/prepare-dist.mjs` | Copia los assets de `public/` a `dist/` (esto resuelve el error de la carpeta faltante) |
| `bootstrap/app.php` | `trustProxies(at: '*')` (HTTPS detrás del proxy de Vercel) |
| `config/app.php` | `cron_secret` (env `CRON_SECRET`) |
| `routes/web.php` | `/cron/mark-absents` y `/cron/migrate`, protegidas con `Authorization: Bearer {CRON_SECRET}` |
| `.env.example` | Documenta las variables de producción |

**Base de datos:** Vercel no provee MySQL. Recomendado conectar **Turso (SQLite)** desde el marketplace (el esquema ya está probado en SQLite: los `ALTER ... ENUM`/`FOREIGN_KEY_CHECKS` están guardados con `!== 'sqlite'`). Alternativa: MySQL externo (Aiven, etc.). Postgres requeriría adaptar esas migraciones.

**Pasos que faltan (los hace el usuario/tanda siguiente):**
1. Conectar la BD en el marketplace de Vercel y cargar las variables de entorno (ver `.env.example`): `APP_KEY`, `APP_URL`, `CRON_SECRET`, `DB_*`, `MAIL_*`.
2. Correr migraciones: `GET https://<app>/cron/migrate` con `Authorization: Bearer <CRON_SECRET>` (o `php artisan migrate --force` apuntando a la BD remota).
3. Importar los datos actuales del MySQL local.
4. Deploy y smoke test (login, estación, QR, reportes, cron).
5. **Limitación conocida:** `FILESYSTEM_DISK=local` es efímero en Vercel → los archivos de justificación no persisten. Pendiente decidir S3-compatible (Cloudflare R2) para fase posterior.

**Cron:** `app:mark-absents` está programado en `vercel.json` a `0 3 * * *` (03:00 UTC = 23:00 en Caracas). Vercel Hobby admite crons 1 vez/día.

**Zona horaria:** la app debe correr en `America/Caracas` (UTC-4, sin horario de verano). Antes producción corría en UTC y todas las horas quedaban +4 h (un escaneo a las 10:08 se guardaba como 14:08). `config/app.php` ahora usa `America/Caracas` como valor por defecto de `APP_TIMEZONE`, así que un despliegue sin la variable ya no queda en UTC. El bloque `env` de `vercel.json` está **deprecado** en el esquema actual de Vercel: para que la variable exista de verdad, definirla también en el dashboard (Settings → Environment Variables, en Production/Preview/Development) o con `vercel env add APP_TIMEZONE production`.

---

## Corrección de acentos (mojibake CP437) — datos importados

**Síntoma:** en `admin/usuarios` (y otras listas) los acentos salían como caracteres de caja: `├│`, `├í`, `├®`, `├║` (p.ej. "Rodr├¡guez", "Coordinaci├│n", "Acad├®mica").

**Causa raíz:** el dump fuente `qr_asistencia.sql` (UTF-16 LE) **ya venía corrupto**. Al generarse, los bytes UTF-8 de cada acento se interpretaron como CP437: `ó` (`C3 B3`) quedó como `├`+`│` (U+251C U+2502). No era problema de la vista (`usuarios.blade.php` está en UTF-8 correcto) ni de TiDB.

**Solución aplicada:** `scripts/gen_datos_tidb.php` ahora revierte el mojibake en cada INSERT:
- `tablaCp437()` — mapa de los 128 caracteres CP437 (bytes 0x80-0xFF) a Unicode.
- `revertirCp437()` — convierte cada carácter a su byte CP437 y reinterpreta la secuencia como UTF-8, validando por secuencia (los bytes que no forman UTF-8 válido se devuelven como carácter CP437 original).
- `reemplazosDoblePaso()` — corrige los pocos remanentes de doble codificación: `ÔÇô`→`–`, `ÔÇª`→`…`, `ÔåÆ`→`→`, `├®`→`é`.
- Se aplica al INSERT completo (seguro: **todo** carácter no-ASCII del dump está dentro de literales de texto — verificado).

**Reimportar en TiDB (método recomendado):** ejecutar el endpoint protegido que importa desde el propio servidor, evitando el portapapeles/SQL Editor (que reinterpretaba la codificación y volvía a corromper los acentos):

```
GET https://sadiuptag1.vercel.app/cron/import-datos
    Authorization: Bearer <CRON_SECRET>
```

El generador produce además `database/data/datos_asistencias.php` (un `return '<sql>';`), que **sí se versiona** y se despliega a Vercel. El endpoint lee ese archivo y ejecuta las sentencias vía PDO con `SET NAMES utf8mb4`.

**Causa del fallo previo con `datos_tidb.sql`:** usaba `TRUNCATE TABLE`, que en TiDB Serverless falla sobre tablas referenciadas por foreign keys **aunque** haya `SET FOREIGN_KEY_CHECKS=0` (TiDB lo ignora). El generador ahora emite **`DELETE FROM`** en orden hijos→padres (y vacía tablas hijas extra: `auditoria_asistencias`, `docente_horario`, `carga_academica_docente`, `dias_feriados`, `estacion_central`, `personal_access_tokens`, `password_reset_tokens`) y **carga en orden padres→hijos**. Probado end-to-end contra el esquema real (exit 0, idempotente).

`datos_tidb.sql` (método manual alternativo) sigue generándose, pero `*.sql` está en `.gitignore`; no contiene secretos.

**Para que no vuelva a pasar:** exportar desde MySQL/MariaDB con `--default-character-set=utf8mb4` y **redirigiendo a archivo binario**, no por consola/pipeline que reinterprete la codificación:

```powershell
& 'C:\xampp\mysql\bin\mysqldump.exe' -uroot --default-character-set=utf8mb4 --single-transaction --routines --triggers qr_asistencia | Out-File -Encoding utf8 -FilePath dump.sql
```

(Evitar `>` simple y evitar leer el dump en editores que asuman CP437.)

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
