<?php

namespace App\Filament\Pages;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\MpesaTransaction;
use App\Domain\Payment\Services\MpesaService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class SubscriptionHold extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string $view = 'filament.pages.subscription-hold';

    protected static ?string $title = 'Subscription on Hold';

    protected static ?string $navigationLabel = 'Reactivate Subscription';

    public string $mpesaPhone = '';
    public ?string $selectedPlanSlug = 'business-plus';
    public bool $isProcessing = false;
    public ?string $paymentStatus = null;
    public ?string $paymentMessage = null;
    public ?string $activeCheckoutRequestId = null;

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        if ($tenant) {
            $this->mpesaPhone = $tenant->phone ?? '';
        }
        
        $this->ensureDefaultPlansExist();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return false;
        }
        $owner = $tenant->owner;
        return !$owner || !$owner->hasActiveSubscription();
    }

    public function getPlans()
    {
        return Plan::where('is_active', true)->orderBy('price')->get();
    }

    public function getSubscription(): ?Subscription
    {
        $tenant = Filament::getTenant();
        if (!$tenant || !$tenant->owner) {
            return null;
        }
        return $tenant->owner->subscription;
    }

    public function initiatePayment(): void
    {
        $this->validate([
            'mpesaPhone' => 'required|string|min:9',
            'selectedPlanSlug' => 'required|string|exists:plans,slug',
        ]);

        $this->isProcessing = true;
        $this->paymentStatus = 'processing';
        $this->paymentMessage = 'Initiating payment request...';

        try {
            $tenant = Filament::getTenant();
            if (!$tenant || !$tenant->owner) {
                throw new \Exception('No branch or branch owner found');
            }

            $plan = Plan::where('slug', $this->selectedPlanSlug)->first();
            if (!$plan) {
                throw new \Exception('Selected plan not found');
            }

            $owner = $tenant->owner;
            $subscription = $owner->subscription;

            if (!$subscription) {
                $subscription = Subscription::create([
                    'user_id' => $owner->id,
                    'plan_id' => $plan->id,
                    'status' => 'pending',
                ]);
            } else {
                $subscription->update([
                    'plan_id' => $plan->id,
                    'status' => 'pending',
                ]);
            }

            $mpesaService = app(MpesaService::class);
            $response = $mpesaService->stkPush(
                $this->mpesaPhone,
                $plan->price,
                "SUB-{$subscription->id}",
                "Subscription for {$plan->name}"
            );

            if ($response['success']) {
                $this->activeCheckoutRequestId = $response['checkout_request_id'];
                
                // Create MpesaTransaction record
                MpesaTransaction::create([
                    'subscription_id' => $subscription->id,
                    'merchant_request_id' => $response['merchant_request_id'],
                    'checkout_request_id' => $response['checkout_request_id'],
                    'amount' => $plan->price,
                    'phone_number' => $this->mpesaPhone,
                    'status' => 'pending',
                    'account_reference' => "SUB-{$subscription->id}",
                    'transaction_desc' => "Subscription for {$plan->name}",
                ]);

                $this->paymentStatus = 'pending_pin';
                $this->paymentMessage = "STK Push sent to {$this->mpesaPhone}. Please check your phone, enter your M-Pesa PIN, and wait for confirmation.";

                Notification::make()
                    ->success()
                    ->title('STK Push Sent')
                    ->body('Please check your phone to complete payment.')
                    ->send();
            } else {
                throw new \Exception($response['error'] ?? 'Failed to send payment request');
            }
        } catch (\Exception $e) {
            $this->isProcessing = false;
            $this->paymentStatus = 'failed';
            $this->paymentMessage = $e->getMessage();

            Notification::make()
                ->danger()
                ->title('Payment Request Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Poll database to check if payment was approved via M-Pesa webhook callback
     */
    public function checkPaymentStatus(): void
    {
        if (!$this->activeCheckoutRequestId) {
            return;
        }

        $transaction = MpesaTransaction::where('checkout_request_id', $this->activeCheckoutRequestId)->first();

        if ($transaction) {
            if ($transaction->status === 'success') {
                $this->paymentStatus = 'success';
                $this->paymentMessage = 'Payment received! Redirecting...';
                
                Notification::make()
                    ->success()
                    ->title('Payment Verified')
                    ->body('Subscription activated successfully.')
                    ->send();

                $this->isProcessing = false;
                
                // Redirect back to dashboard
                $this->redirect(route('filament.admin.pages.dashboard', ['tenant' => Filament::getTenant()]));
            } elseif (in_array($transaction->status, ['failed', 'cancelled', 'timeout'])) {
                $this->paymentStatus = 'failed';
                $this->paymentMessage = 'Payment failed: ' . ($transaction->result_desc ?? 'Transaction cancelled by user.');
                $this->isProcessing = false;
                $this->activeCheckoutRequestId = null;

                Notification::make()
                    ->danger()
                    ->title('Payment Failed')
                    ->body($this->paymentMessage)
                    ->send();
            }
        }
    }

    private function ensureDefaultPlansExist(): void
    {
        $defaultPlans = [
            [
                'name' => 'Business Starter',
                'slug' => 'business-starter',
                'description' => 'Great for single-location wellness centers and spas',
                'price' => 4500.00,
                'trial_days' => 7,
                'interval' => 'monthly',
                'features' => ['1 Location', 'Up to 5 Staff', 'Core POS Features', 'Basic Web Bookings'],
                'is_active' => true,
            ],
            [
                'name' => 'Business Plus',
                'slug' => 'business-plus',
                'description' => 'Perfect for growing businesses needing advanced marketing and analytics',
                'price' => 6500.00,
                'trial_days' => 7,
                'interval' => 'monthly',
                'features' => ['Up to 3 Locations', 'Up to 15 Staff', 'Full POS + M-Pesa STK Push', 'Advanced Analytics Dashboard', 'Custom Coupons & Vouchers'],
                'is_active' => true,
            ],
            [
                'name' => 'Business Pro',
                'slug' => 'business-pro',
                'description' => 'Complete enterprise package for large multi-branch empires',
                'price' => 8500.00,
                'trial_days' => 7,
                'interval' => 'monthly',
                'features' => ['Unlimited Locations', 'Unlimited Staff', 'Full POS & Split Payments', 'Dedicated Account Manager', 'Custom API Access', 'KRA eTIMS Compliance'],
                'is_active' => true,
            ],
        ];

        foreach ($defaultPlans as $pData) {
            Plan::updateOrCreate(['slug' => $pData['slug']], $pData);
        }
    }
}
