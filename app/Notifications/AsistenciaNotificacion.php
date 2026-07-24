<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AsistenciaNotificacion extends Notification
{
    use Queueable;

    public function __construct(
        public string $tipo,
        public string $fecha,
        public ?string $hora = null,
        public ?string $estado = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $notifiable->nombre_completo;

        return match ($this->tipo) {
            'tarde' => (new MailMessage)
                ->subject("Notificación de Tardanza - {$nombre}")
                ->greeting("Hola {$nombre}")
                ->line("Se ha registrado una **tardanza** en su asistencia del día **{$this->fecha}**.")
                ->line("Hora de registro: **{$this->hora}**")
                ->line("Estado: **Tarde**")
                ->line("Si considera que esto es un error, por favor contacte al administrador.")
                ->line("Atentamente, Sistema de Control de Asistencia"),

            'inasistente' => (new MailMessage)
                ->subject("Notificación de Inasistencia - {$nombre}")
                ->greeting("Hola {$nombre}")
                ->line("Se ha registrado una **inasistencia** en el día **{$this->fecha}**.")
                ->line("Estado: **Inasistente**")
                ->line("Si cuenta con una justificación, por favor solicítala a través del sistema.")
                ->line("Atentamente, Sistema de Control de Asistencia"),

            'activo' => (new MailMessage)
                ->subject("Cuenta Activada - {$nombre}")
                ->greeting("Hola {$nombre}")
                ->line("Su cuenta ha sido **activada** correctamente.")
                ->line("Ya puede acceder al sistema con sus credenciales.")
                ->line("Atentamente, Sistema de Control de Asistencia"),

            'inactivo' => (new MailMessage)
                ->subject("Cuenta Desactivada - {$nombre}")
                ->greeting("Hola {$nombre}")
                ->line("Su cuenta ha sido **desactivada**.")
                ->line("Si considera que esto es un error, por favor contacte al administrador.")
                ->line("Atentamente, Sistema de Control de Asistencia"),

            default => (new MailMessage)
                ->subject("Notificación del Sistema de Asistencia")
                ->greeting("Hola {$nombre}")
                ->line("Tiene una nueva notificación del sistema de asistencia.")
                ->line("Atentamente, Sistema de Control de Asistencia"),
        };
    }
}
