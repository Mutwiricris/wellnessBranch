<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'booking_reference' => $this->payment->booking->booking_reference,
            'client_name' => $this->payment->booking->client->first_name . ' ' . $this->payment->booking->client->last_name,
            'amount' => $this->payment->amount,
            'payment_method' => $this->payment->payment_method,
            'transaction_reference' => $this->payment->transaction_reference,
            'sound' => 'payment',
            'color' => 'success',
            'icon' => 'heroicon-o-banknotes',
            'priority' => 'medium',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Payment Received',
            'message' => "Payment of KES " . number_format($this->payment->amount, 2) . " received for booking {$this->payment->booking->booking_reference}",
            'payment_id' => $this->payment->id,
            'sound' => 'payment',
            'color' => 'success',
            'icon' => 'heroicon-o-banknotes',
        ]);
    }
}