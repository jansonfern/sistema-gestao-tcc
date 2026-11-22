<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileUpdatedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dados atualizados - '.config('app.name'))
            ->greeting('Olá'.($notifiable->name ? ', '.$notifiable->name : '').'!')
            ->line('Seus dados de perfil foram atualizados no '.config('app.name').'.')
            ->line('Se você não reconhece esta alteração, entre em contato com o suporte imediatamente.')
            ->action('Acessar o sistema', url('/login'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'profile_updated',
        ];
    }
}
