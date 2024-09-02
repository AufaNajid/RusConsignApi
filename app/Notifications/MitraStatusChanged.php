<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MitraStatusChanged extends Notification
{
    use Queueable;

    protected $mitra;
    protected $status;

    public function __construct($mitra, $status)
    {
        $this->mitra = $mitra;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('Your Mitra status has been ' . $this->status . '.')
            ->action('View Mitra', url('/mitra/' . $this->mitra->id))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable)
    {
        return [
            'mitra_id' => $this->mitra->id,
            'status' => $this->status,
            'message' => 'Your Mitra status has been ' . $this->status,
        ];
    }
}
