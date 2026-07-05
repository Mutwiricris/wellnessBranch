<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionStructure extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'type',
        'applies_to',
        'rules',
        'is_active',
        'description'
    ];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffCommissionAssignment::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(StaffCommission::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
