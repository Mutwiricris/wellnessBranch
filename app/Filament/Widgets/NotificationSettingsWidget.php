<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Notifications\Notification;

class NotificationSettingsWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-settings';
    
    protected static ?int $sort = 10;
    
    protected int | string | array $columnSpan = 'full';

    public function testNotification()
    {
        Notification::make()
            ->title('Test Notification')
            ->body('This is a test notification to verify your settings are working correctly.')
            ->success()
            ->icon('heroicon-o-bell')
            ->send();
    }

    public function testSound()
    {
        Notification::make()
            ->title('Sound Test')
            ->body('Testing notification sound - check your browser audio settings.')
            ->info()
            ->icon('heroicon-o-speaker-wave')
            ->send();
            
        // Dispatch browser event for sound
        $this->dispatch('play-notification-sound', sound: 'reminder');
    }

    public function showBookingNotification()
    {
        Notification::make()
            ->title('New Booking Received')
            ->body('John Doe booked Spa Treatment for tomorrow at 2:00 PM')
            ->success()
            ->icon('heroicon-o-calendar-days')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Booking')
                    ->button()
                    ->url('#'),
                \Filament\Notifications\Actions\Action::make('dismiss')
                    ->label('Dismiss')
                    ->close()
            ])
            ->send();
            
        $this->dispatch('play-notification-sound', sound: 'new-booking');
    }

    public function showPaymentNotification()
    {
        Notification::make()
            ->title('Payment Received')
            ->body('Payment of KES 5,000 received from Jane Smith via M-Pesa')
            ->success()
            ->icon('heroicon-o-banknotes')
            ->duration(5000)
            ->send();
            
        $this->dispatch('play-notification-sound', sound: 'payment-received');
    }

    public function showReminderNotification()
    {
        Notification::make()
            ->title('Upcoming Appointment')
            ->body('Appointment with John Doe starts in 15 minutes')
            ->warning()
            ->icon('heroicon-o-clock')
            ->persistent()
            ->send();
            
        $this->dispatch('play-notification-sound', sound: 'reminder');
    }

    public function showErrorNotification()
    {
        Notification::make()
            ->title('Payment Processing Failed')
            ->body('Payment gateway error - please retry or use alternative payment method')
            ->danger()
            ->icon('heroicon-o-exclamation-triangle')
            ->persistent()
            ->send();
            
        $this->dispatch('play-notification-sound', sound: 'error');
    }

    public function enableBrowserNotifications()
    {
        $this->dispatch('request-notification-permission');
        
        Notification::make()
            ->title('Browser Notifications')
            ->body('Click "Allow" in your browser to enable desktop notifications')
            ->info()
            ->icon('heroicon-o-computer-desktop')
            ->send();
    }
}