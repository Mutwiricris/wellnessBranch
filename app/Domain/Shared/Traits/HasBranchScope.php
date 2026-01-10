<?php

namespace App\Domain\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasBranchScope
{
    /**
     * Boot the trait and add global scope
     */
    protected static function bootHasBranchScope(): void
    {
        // Add global scope to automatically filter by branch
        static::addGlobalScope('branch', function (Builder $builder) {
            $branchId = static::getCurrentBranchIdForScope();

            if ($branchId && static::hasBranchIdColumn($builder->getModel())) {
                $builder->where($builder->getModel()->getTable() . '.branch_id', $branchId);
            }
        });

        // Automatically fill branch_id when creating new records
        static::creating(function (Model $model) {
            if (!$model->branch_id && static::hasBranchIdColumn($model)) {
                $branchId = static::getCurrentBranchIdForScope();
                if ($branchId) {
                    $model->branch_id = $branchId;
                }
            }
        });
    }

    /**
     * Get the current branch ID from Filament tenant or app instance
     */
    protected static function getCurrentBranchIdForScope(): ?int
    {
        // Try to get from Filament tenant first
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            if ($tenant) {
                return $tenant->id;
            }
        } catch (\Exception $e) {
            // Filament not available or no tenant set
        }

        // Try to get from app instance (set by middleware)
        if (app()->bound('current_branch_id')) {
            return app('current_branch_id');
        }

        return null;
    }

    /**
     * Check if model has branch_id column
     */
    protected static function hasBranchIdColumn(Model $model): bool
    {
        return in_array('branch_id', $model->getFillable()) ||
               in_array('branch_id', $model->getGuarded());
    }

    /**
     * Query without branch scope
     */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('branch');
    }

    /**
     * Query for a specific branch
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->withoutGlobalScope('branch')
            ->where('branch_id', $branchId);
    }

    /**
     * Get the branch relationship
     */
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
