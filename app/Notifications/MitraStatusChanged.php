<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MitraStatusChanged extends Notification
{
    use Queueable;

    protected $mitra;
    protected $status;

    /**
     * Create a new notification instance.
     *
     * @param $mitra
     * @param string $status
     */
    public function __construct($mitra, string $status)
    {
        $this->mitra = $mitra;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'mitra_id' => $this->mitra->id,
            'status' => $this->status,
            'message' => 'Status mitra Anda telah berubah menjadi: ' . $this->status,
        ];
    }
}
