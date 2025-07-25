<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class UpcomingAppointment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public int $minutesUntil
    ) {}

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
            'minutes_until' => $this->minutesUntil,
            'time_until' => $this->getTimeUntilDescription(),
            'sound' => 'alert',
            'color' => 'warning',
            'icon' => 'heroicon-o-clock',
            'priority' => 'high',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Upcoming Appointment',
            'message' => "Appointment with {$this->booking->client->first_name} {$this->booking->client->last_name} in {$this->getTimeUntilDescription()}",
            'booking_id' => $this->booking->id,
            'sound' => 'alert',
            'color' => 'warning',
            'icon' => 'heroicon-o-clock',
        ]);
    }

    private function getTimeUntilDescription(): string
    {
        if ($this->minutesUntil <= 5) {
            return '5 minutes';
        } elseif ($this->minutesUntil <= 15) {
            return '15 minutes';
        } elseif ($this->minutesUntil <= 30) {
            return '30 minutes';
        } else {
            return '1 hour';
        }
    }
}