# PENDIENTES

Traspaso de trabajo en curso. Contexto completo en `CONTINUACION.md`.

## 🔴 Bloqueante — tests en rojo (5 de 30)

Los tests usan contraseñas de 8-13 caracteres y el flujo de borrado antiguo.
La política nueva exige **mínimo 16 caracteres** (#23) y **contraseña de
confirmación al eliminar usuario** (#19).

- [ ] `database/factories/UserFactory.php:22` — `Hash::make('Password1')` → usar
      una contraseña de 16+ (p.ej. `ClaveFactorySegura2026!`). Afecta a varios tests.
- [ ] `tests/Feature/UsuarioTest.php:64` — `'password' => 'Password1'` → 16+.
- [ ] `tests/Feature/UsuarioTest.php:104` — `delete(...)` debe enviar
      `password_confirmacion` con la contraseña del admin.
- [ ] `tests/Feature/AuthHardeningTest.php` — ya se reemplazaron `Original123` →
      `ClaveOriginalSegura2026!` y `NuevaClave123` → `ClaveNuevaSegura2026!x`;
      re-ejecutar por si quedan literales viejos.
- [ ] Revisar cualquier test que haga login con la contraseña del factory.
- [ ] Verificar: `php artisan test` en verde.

## 🟡 Cambios locales listos, SIN COMMITEAR

- [ ] `resources/views/auth/profile.blade.php` — botón mostrar contraseña (3 campos),
      umbrales de fortaleza a 16.
- [ ] `resources/views/admin/usuarios.blade.php` — botón mostrar contraseña en editar
      usuario, placeholders/minlength 16, JS toggle y fortaleza.
- [ ] `resources/views/auth/reset.blade.php` — minlength/pattern 16.
- [ ] `resources/views/layouts/app.blade.php` — mapa de mensaje del patrón a 16.

## 🟡 Rúbrica — pendiente

- [ ] W3C/accesibilidad: `<h1>` por página, `scope` en `<th>`, labels `for`,
      contraste (`--text-muted`, `stat-yellow/info`), `prefers-reduced-motion`.
- [ ] `SESSION_SECURE_COOKIE` forzado en producción (`vercel.json`).
- [ ] #16 Integridad: envolver importaciones en `DB::transaction`.

## 🔧 Limpieza

- [ ] **Eliminar endpoint temporal `/cron/reset-admin`** en `bootstrap/app.php`
      (ya cumplió su función de resetear la contraseña del admin).
- [ ] Commit + push a `sadi` y `origin` (autor `SADI-1971`), verificar deploy.

## Notas operativas

- Credenciales admin actuales (cambiar tras pruebas): `admin@universidad.edu` /
  `QxZn@EZUeLLz7syN@w34`.
- Probar tests: `php artisan test`.
- Comprobar sintaxis PHP: `php -l <archivo>`.
- Push (PowerShell escribe en stderr pero funciona):
  `git push sadi main; git push origin main`.
