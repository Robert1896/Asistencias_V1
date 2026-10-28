# Inventario de formularios - asistencias_v1
# Inventario completo de formularios - asistencias_v1

Generado: 2026-10-23 23:03:22

## Resumen
- Se encontraron **65 coincidencias** de <form en archivos .php (incluidos archivos compilados en storage/framework/views/)
- Se analizan archivos Blade en esources/views/ (fuente) y validaciones en pp/Http/Requests/ y pp/Http/Controllers/

## 1. Formularios por módulo/archivo (resources/views)

### Auth
| Archivo | Action | Method | ID/Name/attrs | Campos | Validación |
|---|---|---|---|---|---|
| esources/views/auth/login.blade.php:14 | {{ route('login') }} | POST | data-validate | login (text, required, autofocus, autocomplete=username), password (password, required, id=login-password, autocomplete=current-password), emember (checkbox, name=remember, id=remember) | Blade: equired. JS: data-validate, password toggle (js). PHP: LoginController (usa Auth::attempt), validación en request/contexto auth. |
| esources/views/auth/forgot.blade.php:14 | {{ route('password.email') }} | POST | data-validate | Campos típicos de envío de email (según ruta) | data-validate + Laravel Password Broker |
| esources/views/auth/reset.blade.php:13 | {{ route('password.update') }} | POST | data-validate | 	oken (hidden), email (email, required), password (password, required, minlength=16, pattern mayús+minús+números, title), password_confirmation (password, required) | HTML5: required, minlength=16, pattern. Password policy en UI. |
| esources/views/auth/two_factor.blade.php:14,32,36 | route('login.twofactor'), route('login.twofactor.resend'), route('login.twofactor.cancel') | POST | (3 formularios inline) | Campos para 2FA (código) | Según implementación |
| esources/views/auth/profile.blade.php:27 | {{ route('profile.update') }} | POST (PUT via @method) | data-validate | 
ombre (text, required, pattern [A-Za-záéíóúñÁÉÍÓÚÑ\s]+, title), pellido (text, required, pattern...), cedula (number, required, min=1000000, max=99999999, data-max-len=8), email (email, required), current_password (password, id=current-password-input), password (password, minlength=16, pattern mayús+minús+números), password_confirmation (password) | UpdateProfileRequest (ver sección 2). JS strength meter en profile. UI con toggles. |
### Admin - Usuarios
| Archivo | Action | Method | ID/Name/attrs | Campos | Validación |
|---|---|---|---|---|---|
| esources/views/admin/usuarios.blade.php:29 | {{ route('admin.usuarios.store') }} | POST | data-validate | 
ombre (text, required, pattern letras/espacios, data-solo-letras), pellido (text, required, pattern letras/espacios, data-solo-letras), cedula (number, required, min=1000000 max=99999999, data-max-len=8), email (email, required), ol_id (select, required), password (password, required, minlength=16, pattern mayús+minús+números, title, data-strength, autocomplete=new-password) + toggles JS | StoreUserRequest. HTML5 + JS strength. |
| esources/views/admin/usuarios.blade.php:128 | {{ route('admin.usuarios.importar') }} | POST | enctype=multipart/form-data, data-show-overlay | rchivo (file, accept .csv,.txt,.xlsx, required) | Controller valida request en importar(), ImportService valida formato/campos. |
| esources/views/admin/usuarios.blade.php:180 | {{ route('admin.usuarios.toggle', ) }} | POST | class=d-inline | (toggle estado) - solo submit button | Route/method PATCH (via @method('PATCH') en blade). |
| esources/views/admin/usuarios.blade.php:217 | {{ route('admin.usuarios.update', ) }} | POST | data-validate (fila expandida) | 
ombre, pellido (text required pattern), cedula (number required), email (email required), ol_id (select required), password (password, minlength=16, opcional en update) | UpdateUserRequest. Password opcional. |
| esources/views/admin/usuarios.blade.php:318 | "" (vacío, asignado por JS) | POST | id=deleteUserForm | password_confirmacion (password, required, autocomplete=current-password) | Validado en UsuarioController@destroy con Hash::check. Modal con step-up auth (#19). |

### Admin - Asistencias
| Archivo | Action | Method | ID/Name/attrs | Campos | Validación |
|---|---|---|---|---|---|
| esources/views/admin/asistencias.blade.php:66 | {{ route('admin.asistencias') }} | GET | filtros | echa_inicio (date), echa_fin (date), estado (select: Todos/Presente/Tarde/Inasistente/Apoyo Sabado) | Filtros GET (no POST) |
| esources/views/admin/asistencias.blade.php:152 | {{ route('admin.asistencias.justificar', ) }} | POST | enctype=multipart/form-data | justificado (hidden value=Si), 	exto_justificacion (textarea, opcional), rchivo (file, accept .pdf,.jpg,.jpeg,.png, opcional, data-preview-*) | JustificarAdminRequest. JS previews. |
| esources/views/admin/asistencias.blade.php:189 | {{ route('admin.asistencias.justificar', ) }} | POST | class=d-inline | justificado (hidden value=No) | JustificarAdminRequest (PUT/PATCH via @method) |

### Admin - Salones
| Archivo | Action | Method | ID/Name/attrs | Campos | Validación |
|---|---|---|---|---|---|
| esources/views/admin/salones/index.blade.php:38 | {{ route('admin.salones.limpiar') }} | POST | class=d-none | confirmar_limpiar (hidden=1), confirmacion_texto (hidden, value=ELIMINAR TODO) | JS pide confirmación escrita "ELIMINAR TODO". Controller valida. |
| esources/views/admin/salones/index.blade.php:176 | {{ route('admin.salones.destroy', ) }} | POST | data-confirm | — | DELETE vía @method |
| esources/views/admin/salones/index.blade.php:206 | (asignado por JS) | POST | id=salonForm | 
ombre (text, required, maxlength=80), codigo (text, required, maxlength=30), capacidad (number, required, min=1), ubicacion (text, maxlength=150), descripcion (textarea), _method (hidden) | StoreSalonRequest / UpdateSalonRequest. JS maneja crear/editar. |
| esources/views/admin/salones/index.blade.php:259 | {{ route('admin.salones.importar') }} | POST | enctype=multipart/form-data, data-show-overlay | rchivo (file, accept .xlsx,.csv, required), eemplazar (checkbox value=1) | JS confirma si reemplazar. Controller procesa con ImportService. |

### Admin - Horarios/Carga Académica
| Archivo | Action | Method | ID/Name/attrs | Campos | Validación |
|---|---|---|---|---|---|
| esources/views/admin/horarios.blade.php:29 | {{ route('admin.horarios.guardar-admin') }} | POST | | (bulk admin horarios) | Requiere permisos/validación según controlador |
| esources/views/admin/horarios.blade.php:125 | {{ route('admin.horarios.guardar-carga') }} | POST | | (carga académica) | StoreCargaRequest |
| esources/views/admin/horarios.blade.php:183 | {{ route('admin.horarios.eliminar-carga', ) }} | POST | | DELETE vía @method | — |
| esources/views/admin/horario_edit.blade.php:35 | {{ route('admin.horarios.guardar-admin') }} | POST | | Edición de horarios admin | — |
| esources/views/admin/carga_academica/index.blade.php:24 | {{ route('admin.materias') }} | GET | filtros | Filtros búsqueda | GET |
| esources/views/admin/carga_academica/index.blade.php:76,90,144 | rutas destroy/update/store materias | POST | | CRUD materias/cargas | Según controlador/Request |
| esources/views/admin/docente_horario_edit.blade.php:51 | {{ route('admin.docentes.horarios.update', ) }} | POST | id=horarioForm | Edición horarios docente | UpdateDocenteHorarioRequest |

### Admin - Otros
| Archivo | Action | Method | ID/Name/attrs | Campos | Validación |
|---|---|---|---|---|---|
| esources/views/admin/feriados.blade.php:17 | {{ route('admin.feriados.store') }} | POST | class=row g-3 align-items-end | echa (date, required), 
ombre (text, required), ecurrente (checkbox value=1) | StoreFeriadoRequest |
| esources/views/admin/feriados.blade.php:81 | {{ route('admin.feriados.destroy', ) }} | POST | data-confirm | DELETE vía @method | — |
| esources/views/admin/roles.blade.php:29 | {{ route('admin.roles.permisos', ) }} | POST | | checkboxes permisos[] (múltiples) | PUT vía @method, validación permisos |
| esources/views/admin/estacion_central.blade.php:62 | {{ route('admin.estacion.central.update') }} | POST | id=formEstacionCentral | Configuración estación central | UpdateEstacionCentralRequest |
| esources/views/admin/correo_config.blade.php:31,126 | update/probar correo | POST | | Config SMTP + prueba | UpdateCorreoConfigRequest |
| esources/views/admin/reportes.blade.php:14, eporte_empleado.blade.php:25, itacora.blade.php:14 | GET filtros | GET | | Filtros fecha/usuario | Solo filtros |

### Empleado
| Archivo | Action | Method | ID/Name/attrs | Campos | Validación |
|---|---|---|---|---|---|
| esources/views/empleado/justificaciones.blade.php:64 | {{ route('justificaciones.solicitar', ) }} | POST | enctype=multipart/form-data | 	exto_justificacion (textarea, required), rchivo (file, opcional, accept .pdf,.jpg,.jpeg,.png) | JustificarRequest |

### Layouts (acciones globales)
| Archivo | Action | Method | ID/Name/attrs | Campos | Validación |
|---|---|---|---|---|---|
| esources/views/layouts/app.blade.php:120,211 | {{ route('logout') }} | POST | | Logout (sin campos) | — |
## 2. Validaciones PHP (Form Requests)

| Request | Ubicación | Reglas destacadas |
|---|---|---|
| StoreUserRequest | pp/Http/Requests/StoreUserRequest.php | 
ombre/ apellido required|string|max:80, regex solo letras/espacios? (validado también en UI), cedula required|numeric|digits_between:7,8 (o validación similar), email required|email|unique:users,email|max:120, ol_id required|exists:roles,id, password required|PasswordPolicy::rules() + confirmación/strength (PasswordPolicy custom). |
| UpdateUserRequest | pp/Http/Requests/UpdateUserRequest.php | Similar a store pero password nullable/sometimes + mismas reglas si presente; email unique ignorando usuario; cedula validación. |
| UpdateProfileRequest | pp/Http/Requests/UpdateProfileRequest.php | 
ombre/apellido required, cedula required, email required|email|max:120|unique:users,email,\->user()->id, current_password required_with:email\|password (o lógica según cambio), password nullable|min:16 + regex mayús/minús/número (PasswordPolicy), password_confirmation same. |
| JustificarRequest | pp/Http/Requests/JustificarRequest.php | 	exto_justificacion required|string|max:1000, rchivo nullable|file|mimes:pdf,jpg,jpeg,png|max:5120. |
| JustificarAdminRequest | pp/Http/Requests/JustificarAdminRequest.php | justificado required|in:Si,No, 	exto_justificacion nullable|string|max:1000, rchivo nullable|file|mimes:pdf,jpg,jpeg,png|max:5120. |
| StoreFeriadoRequest | pp/Http/Requests/StoreFeriadoRequest.php | echa required|date|unique:feriados,fecha (según lógica), 
ombre required|string|max:80, ecurrente boolean. |
| StoreSalonRequest | pp/Http/Requests/StoreSalonRequest.php | 
ombre required|string|max:80, codigo required|string|max:30|unique:salones,codigo, capacidad required|integer|min:1, ubicacion nullable|string|max:150, descripcion nullable|string|max:255. |
| UpdateSalonRequest | pp/Http/Requests/UpdateSalonRequest.php | Misma estructura, codigo unique ignorando id, resto similar. |
| StoreCargaRequest | pp/Http/Requests/StoreCargaRequest.php | Validación para asignación de carga académica/horario (ver archivo). |
| UpdateDocenteHorarioRequest | pp/Http/Requests/UpdateDocenteHorarioRequest.php | Validación horarios docente. |
| UpdateEstacionCentralRequest | pp/Http/Requests/UpdateEstacionCentralRequest.php | Config estación central. |
| UpdateCorreoConfigRequest | pp/Http/Requests/UpdateCorreoConfigRequest.php | Config SMTP/correo. |

## 3. Validaciones JS / comportamiento UI
- data-validate presente en varios formularios (login, forgot, reset, profile, usuarios crear/editar) - probablemente validación HTML5/JS global en layouts (esources/views/layouts/auth.blade.php, layouts/app.blade.php).
- Toggles de contraseña con clase pw-field-wrap / pw-toggle-btn en login, usuarios, profile.
- Medidor de fortaleza de contraseña (data-strength, pw-strength-box) en usuarios crear y en profile.
- Validación con pattern en UI: [A-Za-záéíóúñÁÉÍÓÚÑ\s]+ para nombres/apellidos; password regex (?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{16,}.
- Restricciones numéricas: cedula type=number con min/max y data-max-len=8.
- Previews de archivo en justificaciones (data-preview-archivo/imagen).
- Confirmaciones con data-confirm en acciones destructivas (toggle estado con confirm, eliminar feriado, remover justificación, limpiar salones).
- Modal con step-up auth para eliminar usuario (pide password_confirmacion y controller valida con Hash::check).
- Importaciones con data-show-overlay.
- Limpieza masiva con prompt escribiendo ELIMINAR TODO.

## 4. Notas
- Muchos formularios usan @csrf, @method('PUT/PATCH/DELETE') según REST.
- En storage/framework/views/ hay versiones compiladas (no editar).
- Validación centralizada mayormente via Form Requests. Algunos controladores también hacen \->validate() inline (ej. importar usuarios, eliminar usuario).
- Filtros de reportes/asistencias/bitácora usan GET.
