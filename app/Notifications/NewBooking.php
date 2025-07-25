<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewBooking extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'client_name' => $this->booking->client->first_name . ' ' . $this->booking->client->last_name,
            'service_name' => $this->booking->service->name,
            'appointment_date' => $this->booking->appointment_date,
            'start_time' => $this->booking->start_time,
            'total_amount' => $this->booking->total_amount,
            'sound' => 'booking',
            'color' => 'info',
            'icon' => 'heroicon-o-calendar-plus',
            'priority' => 'medium',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'New Booking Received',
            'message' => "New booking {$this->booking->booking_reference} from {$this->booking->client->first_name} {$this->booking->client->last_name}",
            'booking_id' => $this->booking->id,
            'sound' => 'booking',
            'color' => 'info',
            'icon' => 'heroicon-o-calendar-plus',
        ]);
    }
}