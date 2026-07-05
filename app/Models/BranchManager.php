<?php

namespace App\Models;

class BranchManager extends User
{
    protected $table = 'users';

    /**
     * Boot the model and apply global scope for branch managers
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('branch_manager', function ($builder) {
            $builder->where('user_type', 'branch_manager');
        });

        static::creating(function ($model) {
            $model->user_type = 'branch_manager';
        });
    }
}
