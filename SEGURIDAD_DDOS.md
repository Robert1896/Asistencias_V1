# Protección anti-DDoS — Asistencias v1

**Fecha:** 2026-10-30
**Alcance:** capa de borde (Vercel), capa de aplicación (Laravel) y guardarraíles de base de datos.
**Plan de hosting:** Vercel Hobby (sin *spend management*).

---

## 1. Por qué importa en este sistema

| Recurso Hobby | Incluido | Lectura |
|---|---|---|
| Function Invocations | 1.000.000 / mes | holgado |
| **Active CPU** | **4 h / mes** | ~140.000 requests a 100 ms de CPU |
| Provisioned Memory | 360 GB-h | ~1,3 M request-segundos a 1 GB |
| Reglas WAF personalizadas | **3** | hay que consolidar |
| IPs bloqueadas | 10 | solo atacantes puntuales |
| Al exceder | esperar 30 días | el sitio queda pausado |

El tráfico bloqueado, desafiado o limitado por el firewall **no consume cuota**.
Por eso el borde es la primera línea: un flood de 100 rps durante 30 minutos
(~180.000 requests) podría agotar el CPU del mes y dejar la aplicación pausada.

---

## 2. Capa A — Vercel Firewall (panel, sin código)

Ruta: **Dashboard → proyecto → Firewall → Rules**.

### A1. Bloquear escáneres (Deny) — gratis, no consume cuota

- Condición: `Path` *matches* `^/(\.env|\.git|wp-|xmlrpc|vendor/|phpmyadmin|admin\.php|config\.)`
- Acción: **Deny** (403)

### A2. Solo Venezuela (Challenge)

- Condición: `Country` *is not* `VE`
- Acción: **Challenge**

Se usa *challenge* y no *deny* para no romper un acceso legítimo desde el exterior.
Los navegadores lo resuelven solos; los scripts automatizados no.

### A3. Login y estación (Rate limit)

- Condición: `Path` *equals* `/login` **OR** `Path` *starts with* `/estacion`
- Acción: **Rate limit**, 30 requests / 60 s por IP
- Empezar en modo **log** 24–48 h y luego activar.

> Advertencia: el campus suele salir por una sola IP (NAT). Si se activa un
> límite demasiado bajo, se bloquea a usuarios legítimos. Ajustar con datos.

### Complementos

- **Attack Mode** (Firewall → Bot Management): activarlo solo durante un
  incidente. Es gratis, ilimitado y deja pasar cron y bots verificados.
- **IP Blocking** (máx. 10): reservar para el atacante del momento.
- **Deployment Protection** → Vercel Authentication **solo en previews**, para
  que una URL de preview no sirva la app completa contra la misma base.
- **Alerta de uso**: revisar semanalmente Active CPU e invocaciones.

---

## 3. Capa B — Aplicación (implementado en este commit)

| Cambio | Archivo | Efecto |
|---|---|---|
| Se eliminan las consultas de esquema por request | `app/Providers/AppServiceProvider.php` | −2 consultas en **cada** request (incluidos 404 y bots) |
| La configuración SMTP cuesta 1 consulta memoizada | `app/Providers/AppServiceProvider.php` | −1 consulta y sin `hasTable` |
| `throttle:publico` (60/min por IP **y ruta**) | `routes/web.php` | login, QR, archivos de justificación |
| `throttle:20,1` en `POST /login` | `routes/web.php` | frena credential stuffing distribuido |
| `throttle:exportaciones` (6/min) | `routes/web.php` | Excel/plantillas y QR de salón |
| `throttle:10,1` en justificaciones | `routes/web.php` | abuso de subida de archivos |
| `MAIL_TIMEOUT=5` | `config/mail.php` | un SMTP lento no retiene 60 s de función |
| `DB_CONNECT_TIMEOUT=5` | `config/database.php` | falla rápido si TiDB está saturado |
| Poda diaria de `sessions` y `cache` | `app/Console/Commands/PruneEphemeral.php` | evita crecimiento y presión de escritura |
| `robots.txt` + `X-Robots-Tag` | `public/robots.txt`, `vercel.json` | menos crawlers y scanners |
| `APP_DB_BOOTSTRAP_CHECKS=false` | `vercel.json` | apaga la autocreación de tablas en producción |

### Variables nuevas

| Variable | Producción | Descripción |
|---|---|---|
| `APP_DB_BOOTSTRAP_CHECKS` | `false` | `true` solo si se recrea la base y hay que autocrear `cache`/`cache_locks` |
| `APP_MAIL_FROM_DB` | `true` | mantiene el SMTP editable desde la interfaz |
| `MAIL_TIMEOUT` | `5` | segundos máximos por envío |
| `DB_CONNECT_TIMEOUT` | `5` | segundos máximos para conectar a TiDB |

---

## 4. Capa C — Base de datos y costo

1. **TiDB Cloud**: configurar *spend limit* y alerta de presupuesto. Hobby no
   protege la factura de la base de datos.
2. Vigilar conexiones concurrentes vs. concurrencia de Vercel.
3. La poda corre en `/cron/prune` (03:30) y también por scheduler local.
4. Opcional a futuro: mover `cache` y `sessions` a Redis/KV para que el tráfico
   anónimo no escriba en TiDB.

> Verificar en Vercel que exista `CRON_SECRET`: Vercel Cron firma las peticiones
> con `Authorization: Bearer <CRON_SECRET>`. Si falta, `/cron/*` responde 401 y
> no se marcan ausencias ni se poda nada.

---

## 5. Verificación

En un **preview**, nunca en producción:

```bash
# 200 rps durante 60 s
hey -z 60s -q 200 https://<preview>/login
```

Criterios de aceptación:

1. El borde responde 429/Challenge y el Active CPU no sube.
2. `/` y `/estacion` siguen por debajo de 1 s durante el flood.
3. Conexiones TiDB por debajo del 50 % del límite.
4. `/.env` y `/wp-login.php` responden 403 sin ejecutar la función.
5. Suite de tests verde (los fallos de QR estático son preexistentes).

---

## 6. Runbook de incidente

1. Activar **Attack Mode** (Firewall → Bot Management).
2. Endurecer A3 (bajar el umbral) y añadir la IP atacante al bloqueo (máx. 10).
3. Revisar en Vercel: invocaciones, Active CPU y errores 5xx.
4. Revisar en TiDB: conexiones y consultas lentas.
5. Si se filtró `CRON_SECRET`, rotarlo y redesplegar.
6. Si hubo corrupción de datos, restaurar desde `storage/app/backups/` o el tag
   `backup/pre-ddos-*`.
7. Al ceder el ataque, desactivar Attack Mode y restaurar los umbrales.
