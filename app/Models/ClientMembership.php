<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientMembership extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'user_id',
        'membership_tier_id',
        'status',
        'start_date',
        'next_billing_date',
        'end_date',
        'auto_renew',
        'total_paid',
        'canceled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'end_date' => 'date',
        'auto_renew' => 'boolean',
        'total_paid' => 'decimal:2',
        'canceled_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function membershipTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(ClientMembershipUsage::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->end_date === null || $this->end_date->isFuture());
    }
}
