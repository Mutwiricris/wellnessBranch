<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class PaymentQueryBuilder extends Builder
{
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // Intercept and ignore any attempts to filter by branch_id on payments table
        if ($column === 'payments.branch_id' || $column === 'branch_id') {
            return $this;
        }
        
        return parent::where($column, $operator, $value, $boolean);
    }
    
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        // Intercept and ignore any attempts to filter by branch_id on payments table
        if ($column === 'payments.branch_id' || $column === 'branch_id') {
            return $this;
        }
        
        return parent::whereIn($column, $values, $boolean, $not);
    }
}
