<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'next_billing_at',
        'ends_at',
        'last_payment_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_payment_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function onTrial(): bool
    {
        return $this->status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        if ($this->status === 'active') {
            return $this->ends_at === null || $this->ends_at->isFuture();
        }

        if ($this->status === 'trialing') {
            return $this->trial_ends_at && $this->trial_ends_at->isFuture();
        }

        // 'pending', 'expired', 'canceled', etc. are all inactive
        return false;
    }

    public function trialDaysRemaining(): int
    {
        if (!$this->onTrial()) {
            return 0;
        }

        return now()->diffInDays($this->trial_ends_at, false);
    }

    public function getTrialCountdownAttribute(): string
    {
        if (!$this->onTrial()) {
            return '';
        }

        $remaining = $this->trialDaysRemaining();
        
        if ($remaining <= 0) {
            $hours = now()->diffInHours($this->trial_ends_at, false);
            if ($hours > 0) {
                return $hours . ' hours left';
            }
            return 'Trial ending soon';
        }

        return $remaining . ' ' . ($remaining === 1 ? 'day' : 'days') . ' left';
    }
}
