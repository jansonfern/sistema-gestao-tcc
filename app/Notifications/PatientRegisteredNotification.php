<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientRegisteredNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cadastro realizado - '.config('app.name'))
            ->greeting('Olá'.($notifiable->name ? ', '.$notifiable->name : '').'!')
            ->line('Seu cadastro foi realizado com sucesso no Sistema de Gestão Médica.')
            ->line('Você já pode acessar o sistema para agendar consultas e acompanhar seu histórico.')
            ->action('Acessar o sistema', url('/login'))
            ->line('Se você não reconhece este cadastro, entre em contato com o suporte.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'patient_registered',
        ];
    }
}
