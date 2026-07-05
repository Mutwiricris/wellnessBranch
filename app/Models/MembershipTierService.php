<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipTierService extends Model
{
    protected $table = 'membership_tier_services';

    protected $fillable = [
        'membership_tier_id',
        'service_id',
        'is_unlimited',
        'allocated_quantity',
    ];

    protected $casts = [
        'is_unlimited' => 'boolean',
        'allocated_quantity' => 'integer',
    ];

    public function membershipTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
