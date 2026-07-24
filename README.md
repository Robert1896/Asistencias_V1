# Sistema de Control de Asistencia por QR

Sistema web para el control de asistencia de personal universitario mediante escaneo de códigos QR, con gestión de roles, horarios, salones y reportes de productividad.

## Requisitos

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8.2+)
- Navegador web actualizado

## Instalación

### 1. Copiar la carpeta del proyecto

Copiar la carpeta `asistencias_v1` dentro de:

```
C:\xampp\htdocs\
```

La estructura debe quedar así:

```
C:\xampp\htdocs\asistencias_v1\
```

### 2. Crear la base de datos

Abrir **phpMyAdmin** (`http://localhost/phpmyadmin`) y crear una base de datos:

- Nombre: `qr_asistencia`
- Cotejamiento: `utf8mb4_unicode_ci`

### 3. Importar los datos

En phpMyAdmin, seleccionar la base de datos `qr_asistencia`, ir a la pestaña **Importar** y seleccionar el archivo:

```
qr_asistencia_backup.sql
```

Hacer clic en **Continuar** para importar.

> **Alternativa**: Si no se usa el archivo SQL, se pueden crear las tablas con las migraciones y los datos con el seeder:
> ```
> cd C:\xampp\htdocs\qr-laravel
> php artisan migrate
> php artisan db:seed
> ```

### 4. Configurar la conexión

Verificar que el archivo `.env` tenga esta configuración:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qr_asistencia
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Generar clave de aplicación

```
cd C:\xampp\htdocs\asistencias_v1
php artisan key:generate
```

### 6. Iniciar el servidor

```bash
php artisan serve
```

O acceder directamente desde XAMPP:

```
http://localhost/asistencias_v1/public
```

## Credenciales de acceso

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| `admin@universidad.edu` | `Admin123!` | Superadmin |

## Módulos del sistema

### Autenticación
- Inicio de sesión con correo y contraseña
- Registro de nuevos usuarios (requiere activación del admin)
- Recuperación de contraseña
- Edición de perfil personal

### Dashboard
- Panel principal con acceso rápido a módulos según permisos

### Gestión de Usuarios
- CRUD completo de usuarios
- Asignación de roles
- Generación automática de código QR único por usuario
- Activar/desactivar usuarios

### Gestión de Roles y Permisos
- 4 roles predefinidos: Superadmin, Administrativo, Docente, Asistente
- 11 permisos modulares (usuarios, roles, horarios, estación, reportes, asistencias)
- Asignación granular de permisos por rol

### Gestión de Horarios
- **Turnos administrativos**: Matutino/Vespertino con días de la semana
- **Carga académica docente**: Materia, tipo (Clase/Coordinación/Laboratorio/Apoyo), horas semanales

### Horarios de Docentes
- Asignación de bloques de horario a docentes
- Detección de conflictos de horario
- Asignación de salón y coordinación por bloque
- Diferenciación por tipo de actividad (carga académica, atención al estudiante, administrativa)

### Salones
- CRUD de aulas con capacidad, código y ubicación
- **Grid de ocupación**: Vista semanal de uso de salones
- **Disponibilidad AJAX**: Consulta en tiempo real de disponibilidad

### Estación de Chequeo (QR)
- Escaneo de código QR con cámara del dispositivo
- Entrada manual de código QR como respaldo
- Detección automática de entrada y salida
- Indicador de tardanza (tolerancia de 15 minutos)
- Registro de asistencia en sábado como "Apoyo Sábado"
- Bloqueo de registros en domingo
- Efectos de sonido y overlays visuales de retroalimentación

### Asistencias
- Listado paginado de registros de asistencia
- Filtros por fecha, usuario y estado

### Reportes de Productividad
- Cálculo de porcentaje de asistencia por usuario
- Resumen diario: Presentes, Tardes, Ausentes, Apoyos Sábado
- Filtros por rango de fechas y rol
- Sistema de justificación de inasistencias

## Roles y permisos

| Permiso | Superadmin | Administrativo | Docente | Asistente |
|---------|:----------:|:--------------:|:-------:|:---------:|
| Ver usuarios | ✅ | ❌ | ❌ | ❌ |
| Crear usuarios | ✅ | ❌ | ❌ | ❌ |
| Editar usuarios | ✅ | ❌ | ❌ | ❌ |
| Eliminar usuarios | ✅ | ❌ | ❌ | ❌ |
| Ver roles | ✅ | ❌ | ❌ | ❌ |
| Editar roles | ✅ | ❌ | ❌ | ❌ |
| Ver horarios | ✅ | ✅ | ❌ | ❌ |
| Editar horarios | ✅ | ❌ | ❌ | ❌ |
| Operar estación | ✅ | ✅ | ✅ | ✅ |
| Ver reportes | ✅ | ❌ | ❌ | ❌ |
| Justificar asistencias | ✅ | ❌ | ❌ | ❌ |

## Estructura del proyecto

```
asistencias_v1/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/          # Controladores de administración
│   │   ├── Auth/           # Autenticación
│   │   ├── DashboardController.php
│   │   ├── EstacionController.php
│   │   └── QrController.php
│   └── Models/             # Modelos Eloquent
├── database/
│   ├── migrations/         # Migraciones de BD
│   ├── seeders/            # Datos iniciales
│   └── qr_asistencia_backup.sql  # Respaldo de BD
├── resources/views/        # Plantillas Blade
│   ├── admin/              # Vistas de administración
│   ├── auth/               # Vistas de autenticación
│   ├── dashboard/
│   ├── estacion/
│   └── layouts/
├── routes/web.php          # Rutas de la aplicación
└── .env                    # Configuración
```

## Tecnologías

- **Backend**: Laravel 11, PHP 8.2
- **Base de datos**: MySQL/MariaDB
- **Frontend**: Blade templates, Bootstrap 5
- **QR**: html5-qrcode (escáner), QRious (generación)
- **UI**: SweetAlert2, Web Audio API (efectos de sonido)
