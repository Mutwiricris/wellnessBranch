<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Staff;
use App\Models\StaffCommission;
use App\Models\CommissionStructure;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Create commission when booking is completed
     */
    public function createCommissionOnBookingComplete(Booking $booking): ?StaffCommission
    {
        if (!$booking->staff_id || ($booking->status !== 'completed' && $booking->payment_status !== 'completed')) {
            return null;
        }

        // Check if commission already exists
        $existing = StaffCommission::where('booking_id', $booking->id)->first();
        if ($existing) {
            return $existing;
        }

        $staff = $booking->staff;
        $structure = $this->getActiveCommissionStructure($staff);
        
        $commissionData = $this->calculateCommission($booking, $staff, $structure);
        
        return DB::transaction(function () use ($booking, $staff, $structure, $commissionData) {
            return StaffCommission::create([
                'staff_id' => $staff->id,
                'branch_id' => $booking->branch_id,
                'booking_id' => $booking->id,
                'commission_structure_id' => $structure?->id,
                'service_amount' => $booking->total_amount,
                'commission_rate' => $commissionData['rate'],
                'commission_amount' => $commissionData['amount'],
                'total_earning' => $commissionData['amount'],
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'payment_status' => 'pending',
                'hold_until' => now()->addDays(30), // 30-day hold period
            ]);
        });
    }

    /**
     * Get active commission structure for staff
     */
    public function getActiveCommissionStructure(Staff $staff): ?CommissionStructure
    {
        $assignment = $staff->commissionAssignments()
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now());
            })
            ->with('commissionStructure')
            ->first();

        return $assignment?->commissionStructure;
    }

    /**
     * Calculate commission amount
     */
    protected function calculateCommission(Booking $booking, Staff $staff, ?CommissionStructure $structure): array
    {
        if (!$structure) {
            // Default 25% commission
            return [
                'rate' => 25.00,
                'amount' => $booking->total_amount * 0.25
            ];
        }

        $rules = $structure->rules;
        $amount = $booking->total_amount;

        return match($structure->type) {
            'percentage' => [
                'rate' => $rules['rate'],
                'amount' => $amount * ($rules['rate'] / 100)
            ],
            'fixed' => [
                'rate' => 0,
                'amount' => $rules['amount']
            ],
            'tiered' => $this->calculateTieredCommission($staff, $amount, $rules['tiers']),
            'hybrid' => $this->calculateHybridCommission($amount, $rules),
            default => ['rate' => 25.00, 'amount' => $amount * 0.25]
        };
    }

    /**
     * Calculate tiered commission based on monthly performance
     */
    protected function calculateTieredCommission(Staff $staff, float $amount, array $tiers): array
    {
        $monthlyCount = StaffCommission::where('staff_id', $staff->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        foreach ($tiers as $tier) {
            if ($monthlyCount >= $tier['min'] && ($tier['max'] === null || $monthlyCount <= $tier['max'])) {
                return [
                    'rate' => $tier['rate'],
                    'amount' => $amount * ($tier['rate'] / 100)
                ];
            }
        }

        return ['rate' => 25.00, 'amount' => $amount * 0.25];
    }

    /**
     * Calculate hybrid commission (fixed + percentage)
     */
    protected function calculateHybridCommission(float $amount, array $rules): array
    {
        $fixedAmount = $rules['fixed_amount'] ?? 0;
        $percentageRate = $rules['percentage_rate'] ?? 0;
        $percentageAmount = $amount * ($percentageRate / 100);
        
        return [
            'rate' => $percentageRate,
            'amount' => $fixedAmount + $percentageAmount
        ];
    }

    /**
     * Approve commission
     */
    public function approveCommission(StaffCommission $commission, int $approverId): void
    {
        $status = 'approved';
        
        // If there's a hold period that hasn't passed, mark as on_hold
        if ($commission->hold_until && $commission->hold_until->isFuture()) {
            $status = 'on_hold';
        }

        $commission->update([
            'payment_status' => $status,
            'approved_by' => $approverId,
            'approved_at' => now()
        ]);
    }

    /**
     * Release commissions that have passed hold period
     */
    public function releaseHeldCommissions(): Collection
    {
        $commissions = StaffCommission::where('payment_status', 'on_hold')
            ->whereNotNull('hold_until')
            ->where('hold_until', '<=', now())
            ->get();

        foreach ($commissions as $commission) {
            $commission->update([
                'payment_status' => 'ready_for_payment',
                'hold_until' => null
            ]);
        }

        return $commissions;
    }

    /**
     * Get commissions ready for payroll
     */
    public function getCommissionsReadyForPayroll(int $branchId, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return StaffCommission::where('branch_id', $branchId)
            ->whereIn('payment_status', ['approved', 'ready_for_payment'])
            ->whereNull('payroll_id')
            ->where(function ($q) use ($periodEnd) {
                $q->whereNull('hold_until')
                  ->orWhere('hold_until', '<=', $periodEnd);
            })
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->with('staff')
            ->get();
    }
}
