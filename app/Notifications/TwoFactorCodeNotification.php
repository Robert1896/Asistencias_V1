<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Requisito #15: código de verificación de dos factores enviado por correo.
 */
class TwoFactorCodeNotification extends Notification
{
    public function __construct(
        public string $codigo,
        public int $minutosValidez = 10,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nombre = $notifiable->nombre_completo;

        return (new MailMessage)
            ->subject('Código de verificación - Control de Asistencia')
            ->greeting("Hola {$nombre}")
            ->line('Se ha solicitado un inicio de sesión. Usa el siguiente código para completar la verificación en dos pasos:')
            ->line("**{$this->codigo}**")
            ->line("El código vence en {$this->minutosValidez} minutos. Si no fuiste tú, ignora este mensaje y cambia tu contraseña.")
            ->line('Atentamente, Sistema de Control de Asistencia');
    }
}
