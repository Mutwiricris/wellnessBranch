<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipTier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'product_discount_percentage',
        'service_discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'product_discount_percentage' => 'decimal:2',
        'service_discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function tierServices(): HasMany
    {
        return $this->hasMany(MembershipTierService::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'membership_tier_services')
            ->withPivot(['is_unlimited', 'allocated_quantity'])
            ->withTimestamps();
    }

    public function clientMemberships(): HasMany
    {
        return $this->hasMany(ClientMembership::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
