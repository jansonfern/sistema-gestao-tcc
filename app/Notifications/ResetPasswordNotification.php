<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinição de senha - '.config('app.name'))
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta no Sistema de Gestão Médica.')
            ->line('Clique no botão abaixo para criar uma nova senha. Este link é válido por '.config('auth.passwords.users.expire').' minutos.')
            ->action('Redefinir senha', $this->resetUrl($notifiable))
            ->line('Se você não solicitou a redefinição, nenhuma ação é necessária — sua senha permanece a mesma.');
    }
}
