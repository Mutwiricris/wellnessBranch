<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id',
        'staff_id',
        'base_salary',
        'service_commission',
        'product_commission',
        'bonuses',
        'overtime',
        'gross_pay',
        'paye',
        'nssf',
        'nhif',
        'housing_levy',
        'loan_deductions',
        'advance_deductions',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'notes'
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'service_commission' => 'decimal:2',
        'product_commission' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'overtime' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'paye' => 'decimal:2',
        'nssf' => 'decimal:2',
        'nhif' => 'decimal:2',
        'housing_levy' => 'decimal:2',
        'loan_deductions' => 'decimal:2',
        'advance_deductions' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2'
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
