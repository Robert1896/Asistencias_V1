# Auditoría Frontend + Backend — 23 de julio 2026

> Generado: 2026-07-23
> Proyecto: asistencias_v1
> Alcance: Todos los controllers, models, views, routes, middleware, JS, CSS

---

## RESUMEN EJECUTIVO

| Severidad | Frontend | Backend | Total | Estado |
|-----------|----------|---------|-------|--------|
| **CRÍTICA** | 2 | 5 | 7 | ✅ 7/7 |
| **ALTA** | 6 | 8 | 14 | ✅ 13/14 (F5 revertido) |
| **MEDIA** | 10 | 9 | 19 | ✅ 19/19 |
| **BAJA** | 4 | 4 | 8 | ✅ 8/8 |
| **Total** | 22 | 26 | **48** | **47/48** |

---

## BACKEND — 26 hallazgos

### CRÍTICO (5)

#### B1. Doble hashing de contraseñas
**Prioridad:** CRÍTICA
**Archivo:** `ProfileController.php:37`, `LoginController.php:122`, `ImportService.php:192`
**Problema:** Se usa `Hash::make()` manualmente Y el modelo `User` tiene el cast `hashed` (linea 35), que hashea automáticamente al guardar. Resultado: contraseñas hasheadas dos veces → **nunca se pueden verificar**.
```php
// ProfileController.php:37 — MAL
$payload['password'] = Hash::make($data['password'], ['rounds' => 12]);

// User.php:35 — el modelo YA hashea
protected function casts(): array { return ['password' => 'hashed']; }
```
**Fix:** Eliminar `Hash::make()` de ProfileController, LoginController, e ImportService. El cast `hashed` se encarga.

---

#### B2. Sin rate limiting global en API
**Prioridad:** CRÍTICA
**Archivo:** `routes/api.php:7`
**Problema:** Las rutas API no tienen middleware de rate limiting. Un cliente podría hacer requests ilimitados.
**Fix:** Agregar `->middleware('throttle:60,1')` o `throttle:api` al grupo API.

---

#### B3. Sin autorización en API
**Prioridad:** CRÍTICA
**Archivo:** `routes/api.php:7-13`
**Problema:** Cualquier usuario autenticado via Sanctum puede ver TODOS los usuarios y TODAS las asistencias. No hay middleware `can:` o de roles.
**Fix:** Agregar middleware `can:usuarios_ver` o crear permisos específicos para API.

---

#### B4. API expone campos sensibles
**Prioridad:** CRÍTICA
**Archivo:** `Api/V1/UsuarioApiController.php:20`
**Problema:** Retorna el modelo User completo, incluyendo `codigo_qr_estatico`, `cedula`, `email`. Un docente podría ver datos de otro.
**Fix:** Usar API Resources para filtrar campos expuestos.

---

#### B5. Sin tests del core del negocio
**Prioridad:** CRÍTICA
**Archivo:** `tests/`
**Problema:** Solo ~18 tests feature y 0 unitarios. No hay tests para:
- `AttendanceService` (el servicio más crítico)
- `ReportService`
- `ImportService`
- Form Requests
- Control de asistencia completo
**Fix:** Crear tests unitarios para services y tests de integración para el flujo de asistencia.

---

### ALTO (8)

#### B6. LIKE con input sin escape
**Prioridad:** ALTA
**Archivo:** `EstacionController.php:277-280`, `CargaAcademicaController.php:18-20`
**Problema:** Los caracteres `%` y `_` del LIKE no están escapados. Un usuario podría usar wildcards para enumeración de datos.
**Fix:** Usar `addslashes()` o escape manual de `%` y `_`.

---

#### B7. N+1 queries en ocupacionData
**Prioridad:** ALTA
**Archivo:** `SalonController.php:137-147`
**Problema:** Dentro de un foreach sobre asignaciones, se ejecuta una query individual de Asistencia para cada pivot.
**Fix:** Precargar asistencias con una query agregada antes del loop.

---

#### B8. Passwords en texto plano en session flash
**Prioridad:** ALTA
**Archivo:** `SalonController.php:410`, `ImportService.php:296-301`
**Problema:** Contraseñas generadas se pasan a la vista via `->with('usuarios_creados')`. Si alguien comparte pantalla, ve las contraseñas.
**Fix:** Hashear antes de guardar en session, o mostrar una sola vez y no en session.

---

#### B9. Contraseña generada predecible
**Prioridad:** ALTA
**Archivo:** `ImportService.php:283`
**Problema:** `Str::random(12) . 'Aa1'` — el sufijo fijo reduce la entropía.
**Fix:** Usar `Str::random(16)` sin sufijo fijo, o usar un generador de contraseñas seguro.

---

#### B10. DELETE masivo sin confirmación robusta
**Prioridad:** ALTA
**Archivo:** `routes/web.php:119`
**Problema:** `Route::delete('salones/asignaciones/limpiar')` elimina TODAS las asignaciones y cargas en un solo request sin confirmación adicional.
**Fix:** Requerir confirmación con texto escrito (tipo "ESCRIBIR PARA CONFIRMAR") o mover a una ruta protegida con permiso especial.

---

#### B11. Unique constraint obsoleto en asistencias
**Prioridad:** ALTA
**Archivo:** Migración original, linea 101
**Problema:** `unique(['usuario_id', 'fecha'])` impide múltiples registros por día. Pero con la migración de bloques múltiples, ahora se permiten múltiples registros por día.
**Fix:** Eliminar el unique constraint o cambiarlo a `unique(['usuario_id', 'fecha', 'horario_id'])`.

---

#### B12. FK faltante en asistencias.horario_id
**Prioridad:** ALTA
**Archivo:** `migrations/2026_07_10_000001_add_bloques_multiples_to_asistencias.php:13`
**Problema:** `horario_id` se agrega sin `->constrained('horarios')`. Permite datos huérfanos.
**Fix:** Agregar foreign key con nullable (puede ser null para asistencias ad-hoc).

---

#### B13. AttendanceService excesivamente largo
**Prioridad:** ALTA
**Archivo:** `AttendanceService.php` (686 líneas)
**Problema:** Contiene mucha lógica repetitiva en 6 métodos públicos. El cálculo de estado se repite 4 veces.
**Fix:** Dividir en sub-servicios o extraer cálculo de estado a método privado reutilizable.

---

### MEDIO (9)

#### B14. Sin FormRequest en varios controllers
**Prioridad:** MEDIA
**Archivos:** `ReporteController.php`, `BitacoraController.php`, `EstacionController.php:266-287`
**Problema:** Parámetros de request se leen sin validación formal.

---

#### B15. Lógica pesada en ReporteController y SalonController
**Prioridad:** MEDIA
**Archivos:** `ReporteController.php:143-307`, `SalonController.php:100-163, 193-332`
**Problema:** Generación de PDF, Excel, CSV directamente en el controller. Debería ir a un `ExportService`.

---

#### B16. CSP con unsafe-inline
**Prioridad:** MEDIA
**Archivo:** `SecurityHeaders.php:21`
**Problema:** `script-src` y `style-src` usan `'unsafe-inline'`. Idealmente nonces o hashes.

---

#### B17. decryptPassword fallback inseguro
**Prioridad:** MEDIA
**Archivo:** `CorreoConfigController.php:87-94`
**Problema:** Si falla la desencriptación, retorna el texto cifrado como si fuera la contraseña real.

---

#### B18. ReportService carga todas las asistencias a memoria
**Prioridad:** MEDIA
**Archivo:** `ReportService.php:27, 80-82`
**Problema:** `User::with('asistencias')` carga TODAS las asistencias de TODOS los usuarios, luego filtra con Collection. Para períodos largos causa problemas de memoria.

---

#### B19. DB_PASSWORD vacía
**Prioridad:** MEDIA
**Archivo:** `.env:30`
**Problema:** MySQL sin contraseña. Inseguro incluso en local.

---

#### B20. LOG_LEVEL=debug
**Prioridad:** MEDIA
**Archivo:** `.env:23`
**Problema:** Puede filtrar información sensible a los logs.

---

#### B21. per_page sin límite en API
**Prioridad:** MEDIA
**Archivo:** `Api/V1/` controllers
**Problema:** `$request->get('per_page', 15)` permite valores como 10000.

---

#### B22. Models pivot duplicados
**Prioridad:** MEDIA
**Archivos:** `DocenteHorario.php`, `DocenteHorarioPivot.php`
**Problema:** Ambos mapean la tabla `docente_horario` con campos diferentes.

---

### BAJO (4)

#### B23. Código de opciones QR duplicado
**Archivo:** `QrController.php:17-25`, `SalonController.php:83-88`

#### B24. Lógica domingo/feriado duplicada
**Archivo:** `EstacionController.php:114-128, 226-239`

#### B25. Cálculo de estado duplicado en AttendanceService
**Archivo:** `AttendanceService.php` — 4 métodos repiten la lógica

#### B26. Ausencia de scopes reutilizables
**Archivo:** Todos los modelos — `->where('estatus', 'activo')` se repite en 6+ places

---

## FRONTEND — 22 hallazgos

### CRÍTICO (2)

#### F1. Sin skip links para accesibilidad
**Prioridad:** CRÍTICA
**Archivo:** `layouts/app.blade.php`, `layouts/auth.blade.php`
**Problema:** No hay "Skip to content" para usuarios de lectores de pantalla. La navegación del navbar es la primera cosa que escuchan.
**Fix:** Agregar `<a href="#main-content" class="visually-hidden">Saltar al contenido</a>` al inicio del body.

---

#### F2. Inputs sin label explícito en forms de filtros
**Prioridad:** CRÍTICA
**Archivos:** `admin/asistencias.blade.php:17-31`, `admin/reportes.blade.php:17-33`, `admin/usuarios.blade.php`
**Problema:** Algunos inputs de búsqueda usan placeholder sin `<label>`. Los lectores de pantalla no pueden identificar el campo.
**Fix:** Agregar `<label class="visually-hidden">` o usar `aria-label` en todos los inputs.

---

### ALTO (6)

#### F3. Iconos Font Awesome en vez de Bootstrap Icons
**Prioridad:** ALTA
**Archivos:** `admin/salones/ocupacion.blade.php` (múltiples `fas fa-*`), `admin/salones/index.blade.php`
**Problema:** El proyecto carga Bootstrap Icons pero algunas vistas usan Font Awesome (`fas fa-calendar-alt`, `fas fa-door-open`, etc.). Si Font Awesome no se carga, los iconos no aparecen.
**Fix:** Reemplazar todos los `fas fa-*` por `bi bi-*`.

---

#### F4. Alertas duplicadas
**Prioridad:** ALTA
**Archivos:** Vistas que tienen tanto `@if(session('success'))` como `@if(session('error'))` pero sin `@if` para la sesión de la vista anterior.
**Problema:** Si hay múltiples mensajes flash, se apilan sin estilo consistente.
**Fix:** Crear un componente parcial `components/alert.blade.php` que maneje todos los tipos de sesión.

---

#### F5. Scripts cargados sin defer/async
**Prioridad:** ALTA
**Archivo:** `layouts/app.blade.php` — Chart.js, y scripts inline
**Problema:** Chart.js se carga en todas las vistas que usan `@push('scripts')`, pero el CDN se carga sincrónicamente en el `<head>` de esas vistas.
**Fix:** Mover Chart.js a un `<script defer>` o cargarlo dinámicamente solo cuando se necesita.

---

#### F6. Tablas sin aria-label
**Prioridad:** ALTA
**Archivos:** Todas las tablas de admin
**Problema:** Las tablas no tienen `aria-label` o `aria-describedby` para indicar su propósito a lectores de pantalla.
**Fix:** Agregar `<table aria-label="...">` descriptivo.

---

#### F7. Modales sin focus trap
**Prioridad:** ALTA
**Archivos:** Todos los modales de Bootstrap
**Problema:** Bootstrap 5 maneja focus trap nativamente, pero los modales creados dinámicamente (como `justificarModal`) no siempre lo respetan.
**Fix:** Verificar que todos los modales tengan `tabindex="-1"` y usen `data-bs-focus="true"`.

---

#### F8. Formularios sin autocomplete attributes
**Prioridad:** ALTA
**Archivos:** `auth/login.blade.php`, `admin/usuarios.blade.php`, `admin/carga_academica/index.blade.php`
**Problema:** Los campos de email, password, nombre no tienen `autocomplete`.
**Fix:** Agregar `autocomplete="email"`, `autocomplete="current-password"`, etc.

---

### MEDIO (10)

#### F9. Dark mode sin transición en algunos elementos
**Prioridad:** MEDIA
**Archivo:** `layouts/app.blade.php`
**Problema:** Algunos elementos como `.progress`, `.modal-content`, `.badge` no tenían transición (ya corregido parcialmente con la regla global).

---

#### F10. Skeleton loading no cubre todas las tablas
**Prioridad:** MEDIA
**Archivo:** `layouts/app.blade.php:214-218`
**Problema:** Solo hay skeleton para la tabla de ocupación. Otras tablas (asistencias, usuarios, reportes) muestran vacío hasta que cargan.

---

#### F11. Chart.js sin fallback para NoScript
**Prioridad:** MEDIA
**Archivos:** `dashboard/index.blade.php:41-50`
**Problema:** Solo el dashboard tiene `<noscript>` con tabla de respaldo. La vista de asistencias (nueva) no lo tiene.

---

#### F12. Spinner de carga global sin texto descriptivo
**Prioridad:** MEDIA
**Archivo:** `layouts/app.blade.php:266-271`
**Problema:** El overlay dice "Procesando..." pero no indica qué se está procesando.
**Fix:** Usar `data-loading-text` personalizable.

---

#### F13. Dropdown del admin demasiado largo
**Prioridad:** MEDIA
**Archivo:** `layouts/app.blade.php:318-332`
**Problema:** El menú Admin tiene 12 items. En móvil es difícil de navegar.
**Fix:** Considerar submenús o agrupar items (ej: "Reportes" submenú con Reportes + Bitácora + Correo).

---

#### F14. Tab index incorrecto en modales anidados
**Prioridad:** MEDIA
**Archivos:** `admin/salones/ocupacion.blade.php` (modales de justificación)
**Problema:** Los modales de justificación dentro de la tabla tienen `tabindex="-1"` pero el `id` puede colisionar si hay duplicados.

---

#### F15. CSS custom sin organization
**Prioridad:** MEDIA
**Archivo:** `layouts/app.blade.php:11-262`
**Problema:** Todo el CSS está en un solo bloque `<style>` de ~250 líneas. Difícil de mantener.
**Fix:** Mover a `public/css/app.css` o usar Vite.

---

#### F16. Imágenes sin lazy loading
**Prioridad:** MEDIA
**Archivos:** Logo login, logo navbar
**Problema:** Las imágenes no usan `loading="lazy"`. Son críticas (above the fold), así que no es un problema mayor.

---

#### F17. Formulario de justificación sin preview de archivo
**Prioridad:** MEDIA
**Archivo:** `admin/asistencias.blade.php:171-173`
**Problema:** El input de archivo no muestra preview del archivo seleccionado ni nombre del archivo actual.
**Fix:** Agregar JS para mostrar nombre del archivo y preview si es imagen.

---

#### F18. Botón de importar sin feedback de progreso
**Prioridad:** MEDIA
**Archivos:** `admin/salones/index.blade.php`, `admin/usuarios.blade.php`
**Problema:** Al importar archivos grandes, no hay indicación de progreso. El usuario no sabe si está funcionando.

---

### BAJO (4)

#### F19. Favicon inconsistente
**Prioridad:** BAJA
**Archivos:** `layouts/app.blade.php`, `layouts/auth.blade.php`
**Problema:** Ambas usan `favicon.ico` pero podría no existir o estar desactualizado.

---

#### F20. Meta description faltante
**Prioridad:** BAJA
**Archivos:** Ambas layouts
**Problema:** No hay `<meta name="description">` para SEO.

---

#### F21. CSS print no oculta todos los elementos innecesarios
**Prioridad:** BAJA
**Archivo:** `layouts/app.blade.php:248-261`
**Problema:** El print oculta navbar y botones pero podría mejorar la presentación de tablas.

---

#### F22. Variables CSS dark mode no cubren todos los colores hardcodeados
**Prioridad:** BAJA
**Archivos:** Varios `.blade.php` con `style="color: #333"` o `style="background: #fff"`
**Problema:** Algunos colores están inline y no responden al dark mode.
**Fix:** Mover todos los colores a CSS variables.

---

## PLAN DE ACCIÓN PROPUESTO

### Semana 1 — Críticos (B1-B5, F1-F2)
| Día | Tarea | Tiempo est. |
|-----|-------|-------------|
| Lun | Fix doble hashing (B1) + test login | 2h |
| Mar | Rate limiting API (B2) + autorización API (B3-B4) | 3h |
| Mié | Tests unitarios AttendanceService (B5) | 4h |
| Jue | Tests de integración flujo asistencia (B5) | 4h |
| Vie | Skip links (F1) + labels en forms (F2) | 2h |

### Semana 2 — Altos (B6-B13, F3-F8)
| Día | Tarea | Tiempo est. |
|-----|-------|-------------|
| Lun | Fix LIKE escape (B6) + N+1 ocupacion (B7) | 3h |
| Mar | Fix passwords session (B8-B9) + DELETE masivo (B10) | 3h |
| Mié | Unique constraint (B11) + FK faltante (B12) | 2h |
| Jue | Refactorizar AttendanceService (B13) | 4h |
| Vie | Fix iconos FA→BI (F3) + autocomplete (F8) | 3h |

### Semana 3 — Medios (B14-B22, F9-F18)
| Día | Tarea | Tiempo est. |
|-----|-------|-------------|
| Lun | FormRequests faltantes (B14) + ExportService (B15) | 4h |
| Mar | ReportService fix memoria (B18) + models duplicados (B22) | 3h |
| Mié | Skeleton loading universal (F10) + dropdown admin (F13) | 3h |
| Jue | CSS organization (F15) + dark mode fixes (F22) | 4h |
| Vie | Alertas componente (F4) + form improvements (F17-F18) | 3h |

### Semana 4 — Bajos + Testing
| Día | Tarea | Tiempo est. |
|-----|-------|-------------|
| Lun | DRY code: QR, feriado, estado (B23-B25) | 3h |
| Mar | Scopes reutilizables (B26) + perf tuning | 3h |
| Mié | Tests de regresión completos | 4h |
| Jue | QA manual completo + fixing | 4h |
| Vie | Deploy verification + documentation | 2h |

---

## ESTADO ANTERIOR (30/30 completados)

Los 30 items de la auditoría anterior (2026-07-22/23) están marcados como completados en `AUDIT_TODO.md`. Esta auditoría es una **segunda ronda** más profunda que identifica problemas que la primera pasada no detectó.

---

## NOTAS

- El **doble hashing** (B1) es el bug más crítico — puede causar que usuarios no puedan iniciar sesión
- La **API sin autorización** (B3) es un riesgo de seguridad grave si se expone
- Los **tests** (B5) son la deuda técnica más grande del proyecto
- Los **iconos Font Awesome** (F3) pueden causar errores 404 si el CDN no carga
