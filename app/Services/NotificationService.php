<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    const SOUND_NEW_BOOKING = 'new-booking';
    const SOUND_BOOKING_CONFIRMED = 'booking-confirmed';
    const SOUND_BOOKING_COMPLETED = 'booking-completed';
    const SOUND_PAYMENT_RECEIVED = 'payment-received';
    const SOUND_BOOKING_CANCELLED = 'booking-cancelled';
    const SOUND_REMINDER = 'reminder';
    const SOUND_ERROR = 'error';

    private array $sounds = [
        self::SOUND_NEW_BOOKING => '/sounds/new-booking.mp3',
        self::SOUND_BOOKING_CONFIRMED => '/sounds/booking-confirmed.mp3',
        self::SOUND_BOOKING_COMPLETED => '/sounds/booking-completed.mp3',
        self::SOUND_PAYMENT_RECEIVED => '/sounds/payment-received.mp3',
        self::SOUND_BOOKING_CANCELLED => '/sounds/booking-cancelled.mp3',
        self::SOUND_REMINDER => '/sounds/reminder.mp3',
        self::SOUND_ERROR => '/sounds/error.mp3',
    ];

    public function sendBookingNotification(Booking $booking, string $event, array $extra = [])
    {
        try {
            $notification = $this->buildBookingNotification($booking, $event, $extra);
            
            // Send browser notification
            $this->sendBrowserNotification($notification);
            
            // Send audio notification
            $this->playSound($notification['sound']);
            
            // Log notification
            Log::info('Notification sent', [
                'type' => 'booking',
                'event' => $event,
                'booking_id' => $booking->id,
                'title' => $notification['title']
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send booking notification', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
                'event' => $event
            ]);
            return false;
        }
    }

    public function sendPaymentNotification(Payment $payment, string $event, array $extra = [])
    {
        try {
            $notification = $this->buildPaymentNotification($payment, $event, $extra);
            
            // Send browser notification
            $this->sendBrowserNotification($notification);
            
            // Send audio notification
            $this->playSound($notification['sound']);
            
            // Log notification
            Log::info('Notification sent', [
                'type' => 'payment',
                'event' => $event,
                'payment_id' => $payment->id,
                'title' => $notification['title']
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send payment notification', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
                'event' => $event
            ]);
            return false;
        }
    }

    public function sendSystemNotification(string $title, string $message, string $type = 'info', string $sound = self::SOUND_REMINDER)
    {
        try {
            $notification = [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'sound' => $sound,
                'timestamp' => now()->toISOString(),
                'icon' => $this->getTypeIcon($type)
            ];
            
            // Send browser notification
            $this->sendBrowserNotification($notification);
            
            // Send audio notification
            $this->playSound($sound);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send system notification', [
                'error' => $e->getMessage(),
                'title' => $title
            ]);
            return false;
        }
    }

    private function buildBookingNotification(Booking $booking, string $event, array $extra = []): array
    {
        $clientName = ($booking->client->first_name ?? '') . ' ' . ($booking->client->last_name ?? '');
        $serviceName = $booking->service->name ?? 'Unknown Service';
        
        switch ($event) {
            case 'created':
                return [
                    'title' => 'New Booking Received',
                    'message' => "{$clientName} booked {$serviceName} for {$booking->appointment_date}",
                    'type' => 'success',
                    'sound' => self::SOUND_NEW_BOOKING,
                    'icon' => '📅',
                    'timestamp' => now()->toISOString(),
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference
                ];
                
            case 'confirmed':
                return [
                    'title' => 'Booking Confirmed',
                    'message' => "{$clientName}'s appointment for {$serviceName} has been confirmed",
                    'type' => 'info',
                    'sound' => self::SOUND_BOOKING_CONFIRMED,
                    'icon' => '✅',
                    'timestamp' => now()->toISOString(),
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference
                ];
                
            case 'completed':
                return [
                    'title' => 'Service Completed',
                    'message' => "{$serviceName} for {$clientName} has been completed",
                    'type' => 'success',
                    'sound' => self::SOUND_BOOKING_COMPLETED,
                    'icon' => '🎉',
                    'timestamp' => now()->toISOString(),
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference
                ];
                
            case 'cancelled':
                return [
                    'title' => 'Booking Cancelled',
                    'message' => "{$clientName}'s appointment for {$serviceName} has been cancelled",
                    'type' => 'warning',
                    'sound' => self::SOUND_BOOKING_CANCELLED,
                    'icon' => '❌',
                    'timestamp' => now()->toISOString(),
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference
                ];
                
            case 'reminder':
                $timeUntil = $extra['time_until'] ?? '15 minutes';
                return [
                    'title' => 'Upcoming Appointment',
                    'message' => "{$clientName}'s appointment for {$serviceName} starts in {$timeUntil}",
                    'type' => 'info',
                    'sound' => self::SOUND_REMINDER,
                    'icon' => '⏰',
                    'timestamp' => now()->toISOString(),
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference
                ];
                
            default:
                return [
                    'title' => 'Booking Update',
                    'message' => "Booking {$booking->booking_reference} has been updated",
                    'type' => 'info',
                    'sound' => self::SOUND_REMINDER,
                    'icon' => 'ℹ️',
                    'timestamp' => now()->toISOString(),
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference
                ];
        }
    }

    private function buildPaymentNotification(Payment $payment, string $event, array $extra = []): array
    {
        $amount = 'KES ' . number_format($payment->amount, 2);
        $clientName = ($payment->booking->client->first_name ?? '') . ' ' . ($payment->booking->client->last_name ?? '');
        
        switch ($event) {
            case 'completed':
                return [
                    'title' => 'Payment Received',
                    'message' => "Payment of {$amount} received from {$clientName}",
                    'type' => 'success',
                    'sound' => self::SOUND_PAYMENT_RECEIVED,
                    'icon' => '💰',
                    'timestamp' => now()->toISOString(),
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount
                ];
                
            case 'failed':
                return [
                    'title' => 'Payment Failed',
                    'message' => "Payment of {$amount} from {$clientName} failed",
                    'type' => 'error',
                    'sound' => self::SOUND_ERROR,
                    'icon' => '❌',
                    'timestamp' => now()->toISOString(),
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount
                ];
                
            case 'refunded':
                return [
                    'title' => 'Payment Refunded',
                    'message' => "Refund of {$amount} processed for {$clientName}",
                    'type' => 'warning',
                    'sound' => self::SOUND_REMINDER,
                    'icon' => '↩️',
                    'timestamp' => now()->toISOString(),
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount
                ];
                
            default:
                return [
                    'title' => 'Payment Update',
                    'message' => "Payment of {$amount} has been updated",
                    'type' => 'info',
                    'sound' => self::SOUND_REMINDER,
                    'icon' => 'ℹ️',
                    'timestamp' => now()->toISOString(),
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount
                ];
        }
    }

    private function sendBrowserNotification(array $notification)
    {
        // This would typically use WebSockets or Server-Sent Events
        // For now, we'll use session flash data that can be picked up by JavaScript
        session()->flash('notification', $notification);
        
        // In a real implementation, you might use:
        // - Pusher/Laravel Echo for real-time notifications
        // - Server-Sent Events
        // - WebSockets
        // - Firebase Cloud Messaging for browser push notifications
    }

    private function playSound(string $sound)
    {
        // This would typically trigger a client-side audio play
        // We'll store the sound in session for JavaScript to pick up
        session()->flash('play_sound', $sound);
    }

    private function getTypeIcon(string $type): string
    {
        return match ($type) {
            'success' => '✅',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '📢'
        };
    }

    public function getDailyReport(string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $tenant = \Filament\Facades\Filament::getTenant();
        
        if (!$tenant) {
            return [];
        }
        
        // Get daily statistics
        $bookings = Booking::where('branch_id', $tenant->id)
            ->whereDate('appointment_date', $date)
            ->with(['client', 'service', 'staff'])
            ->get();
            
        $totalBookings = $bookings->count();
        $completedBookings = $bookings->where('status', 'completed')->count();
        $cancelledBookings = $bookings->where('status', 'cancelled')->count();
        $pendingBookings = $bookings->where('status', 'pending')->count();
        
        $totalRevenue = $bookings->where('status', 'completed')->sum('total_amount');
        
        $completionRate = $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 0;
        
        return [
            'date' => $date,
            'total_bookings' => $totalBookings,
            'completed_bookings' => $completedBookings,
            'cancelled_bookings' => $cancelledBookings,
            'pending_bookings' => $pendingBookings,
            'total_revenue' => $totalRevenue,
            'completion_rate' => $completionRate,
            'top_services' => $this->getTopServices($date),
            'staff_performance' => $this->getStaffPerformance($date)
        ];
    }

    private function getTopServices(string $date): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        return Booking::where('branch_id', $tenant->id)
            ->whereDate('appointment_date', $date)
            ->select('service_id')
            ->selectRaw('COUNT(*) as booking_count')
            ->selectRaw('SUM(total_amount) as total_revenue')
            ->with('service')
            ->groupBy('service_id')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getStaffPerformance(string $date): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        return Booking::where('branch_id', $tenant->id)
            ->whereDate('appointment_date', $date)
            ->select('staff_id')
            ->selectRaw('COUNT(*) as booking_count')
            ->selectRaw('SUM(total_amount) as total_revenue')
            ->with('staff')
            ->groupBy('staff_id')
            ->orderByDesc('total_revenue')
            ->get()
            ->toArray();
    }
}