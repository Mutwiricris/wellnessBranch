<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class BookingStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $oldStatus,
        public string $newStatus
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'appointment_date' => $this->booking->appointment_date,
            'sound' => $this->getNotificationSound(),
            'color' => $this->getNotificationColor(),
            'icon' => $this->getNotificationIcon(),
            'priority' => $this->getNotificationPriority(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Booking Status Changed',
            'message' => "Booking {$this->booking->booking_reference} status changed from {$this->oldStatus} to {$this->newStatus}",
            'booking_id' => $this->booking->id,
            'sound' => $this->getNotificationSound(),
            'color' => $this->getNotificationColor(),
            'icon' => $this->getNotificationIcon(),
        ]);
    }

    private function getNotificationSound(): string
    {
        return match ($this->newStatus) {
            'confirmed' => 'confirmation',
            'completed' => 'success',
            'cancelled' => 'warning',
            'no_show' => 'alert',
            default => 'default'
        };
    }

    private function getNotificationColor(): string
    {
        return match ($this->newStatus) {
            'confirmed' => 'info',
            'completed' => 'success',
            'cancelled' => 'warning',
            'no_show' => 'danger',
            default => 'gray'
        };
    }

    private function getNotificationIcon(): string
    {
        return match ($this->newStatus) {
            'confirmed' => 'heroicon-o-check-circle',
            'completed' => 'heroicon-o-sparkles',
            'cancelled' => 'heroicon-o-x-circle',
            'no_show' => 'heroicon-o-exclamation-triangle',
            default => 'heroicon-o-bell'
        };
    }

    private function getNotificationPriority(): string
    {
        return match ($this->newStatus) {
            'no_show' => 'high',
            'cancelled' => 'medium',
            'completed' => 'medium',
            default => 'low'
        };
    }
}