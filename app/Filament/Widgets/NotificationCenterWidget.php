<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Notifications\DatabaseNotification;

class NotificationCenterWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-center';
    
    protected static ?int $sort = 10;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '10s';

    public function getViewData(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $user = auth()->user();
        
        if (!$tenant || !$user) {
            return ['notifications' => collect()];
        }

        $notifications = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                
                return [
                    'id' => $notification->id,
                    'title' => $this->getNotificationTitle($notification->type, $data),
                    'message' => $this->getNotificationMessage($notification->type, $data),
                    'time' => $notification->created_at->diffForHumans(),
                    'read' => !is_null($notification->read_at),
                    'color' => $data['color'] ?? 'gray',
                    'icon' => $data['icon'] ?? 'heroicon-o-bell',
                    'priority' => $data['priority'] ?? 'low',
                    'sound' => $data['sound'] ?? 'default',
                    'data' => $data,
                ];
            });

        return [
            'notifications' => $notifications,
            'unread_count' => $notifications->where('read', false)->count(),
        ];
    }

    private function getNotificationTitle(string $type, array $data): string
    {
        return match ($type) {
            'App\\Notifications\\BookingStatusChanged' => 'Booking Status Changed',
            'App\\Notifications\\PaymentReceived' => 'Payment Received',
            'App\\Notifications\\NewBooking' => 'New Booking',
            'App\\Notifications\\UpcomingAppointment' => 'Upcoming Appointment',
            default => 'Notification'
        };
    }

    private function getNotificationMessage(string $type, array $data): string
    {
        return match ($type) {
            'App\\Notifications\\BookingStatusChanged' => 
                "Booking {$data['booking_reference']} status changed to {$data['new_status']}",
            'App\\Notifications\\PaymentReceived' => 
                "Payment of KES " . number_format($data['amount'], 2) . " received from {$data['client_name']}",
            'App\\Notifications\\NewBooking' => 
                "New booking {$data['booking_reference']} from {$data['client_name']}",
            'App\\Notifications\\UpcomingAppointment' => 
                "Upcoming appointment with {$data['client_name']} in {$data['time_until']}",
            default => 'You have a new notification'
        };
    }

    public function markAsRead(string $notificationId): void
    {
        $user = auth()->user();
        
        DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();
        
        DatabaseNotification::where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function deleteNotification(string $notificationId): void
    {
        $user = auth()->user();
        
        DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->delete();
    }
}