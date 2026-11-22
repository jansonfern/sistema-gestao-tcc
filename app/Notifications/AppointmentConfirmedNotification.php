<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment->loadMissing(['doctor.user']);

        return (new MailMessage)
            ->subject('Consulta confirmada - '.config('app.name'))
            ->greeting('Olá'.($notifiable->name ? ', '.$notifiable->name : '').'!')
            ->line('Sua consulta foi confirmada pela clínica.')
            ->line('**Médico:** '.$appointment->doctor->user->name)
            ->line('**Data:** '.\Carbon\Carbon::parse($appointment->date)->format('d/m/Y'))
            ->line('**Horário:** '.\Carbon\Carbon::parse($appointment->time)->format('H:i'))
            ->action('Ver consultas', route('appointments.index'))
            ->line('Compareça com alguns minutos de antecedência.');
    }
}
