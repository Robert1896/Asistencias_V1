<?php

namespace App\Support;

class ActivityActionTranslator
{
    protected static array $map = [
        'login' => 'Inició sesión',
        'login_fallido' => 'Intento de inicio de sesión fallido',
        'login_bloqueado' => 'Inicio de sesión bloqueado por intentos excesivos',
        'login_bloqueado_ip' => 'Inicio de sesión bloqueado por IP',
        'login_2fa_enviado' => 'Código 2FA enviado',
        'login_2fa_fallido' => 'Código 2FA inválido',
        'login_2fa_reenviado' => 'Código 2FA reenviado',
        'logout' => 'Cerró sesión',
        'sesion_cerrar' => 'Cerró sesión',

        'usuario_crear' => 'Creó usuario',
        'usuario_editar' => 'Editó usuario',
        'usuario_eliminar' => 'Eliminó usuario',
        'usuario_activar' => 'Activó usuario',
        'usuario_desactivar' => 'Desactivó usuario',
        'usuarios_importar' => 'Importó usuarios',

        'asistencia_entrada' => 'Registró entrada',
        'asistencia_salida' => 'Registró salida',
        'asistencia_justificar' => 'Justificó asistencia',
        'asistencia_desjustificar' => 'Removió justificación',
        'asistencia_rechazada' => 'Asistencia rechazada',
        'asistencia_registrar' => 'Registró asistencia manual',

        'horario_admin' => 'Actualizó horario administrativo',
        'horario_docente' => 'Actualizó horario docente',

        'salon_crear' => 'Creó salón',
        'salon_editar' => 'Editó salón',
        'salon_eliminar' => 'Eliminó salón',
        'salon_importar' => 'Importó asignaciones de salones',
        'salon_limpiar' => 'Eliminó todas las asignaciones',
        'salon_qr_regenerar' => 'Regeneró QR del salón',
        'salon_reasignar' => 'Reasignó salón',

        'carga_agregar' => 'Agregó carga académica',
        'carga_eliminar' => 'Eliminó carga académica',

        'scan_fallido' => 'Escaneo QR fallido',
        'scan_bloqueado' => 'Escaneo bloqueado por límite',
        'scan_sospechoso' => 'Escaneo sospechoso',

        'justificaciones_solicitar' => 'Solicitó justificación',

        'estacion_configurar' => 'Configuró estación central',
    ];

    public static function translate(string $accion): string
    {
        if (isset(self::$map[$accion])) {
            return self::$map[$accion];
        }

        $texto = str_replace('_', ' ', $accion);
        $texto = ucwords($texto);

        return $texto;
    }
}
