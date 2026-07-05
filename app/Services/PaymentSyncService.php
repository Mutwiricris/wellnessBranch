<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use Carbon\Carbon;

class PaymentSyncService
{
    /**
     * Get accurate revenue data by combining payments and bookings
     */
    public static function getAccurateRevenue(Carbon $start, Carbon $end, ?int $branchId = null): array
    {
        try {
            // Base payment query - use created_at if processed_at is null
            $paymentQuery = Payment::withoutGlobalScopes()
                ->where('status', 'completed')
                ->where(function($query) use ($start, $end) {
                    $query->whereBetween('processed_at', [$start, $end])
                          ->orWhere(function($q) use ($start, $end) {
                              $q->whereNull('processed_at')
                                ->whereBetween('created_at', [$start, $end]);
                          });
                });

            // Base booking query
            $bookingQuery = Booking::where('payment_status', 'completed')
                ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()]);

            // Apply branch filtering if specified
            if ($branchId) {
                $paymentQuery->whereHas('booking', function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
                $bookingQuery->where('branch_id', $branchId);
            }

            // Get payment revenue (most accurate source)
            $paymentRevenue = $paymentQuery->sum('amount') ?? 0;
            $paymentCount = $paymentQuery->count() ?? 0;

            // Get booking revenue for bookings without payment records (fallback)
            $bookingRevenue = $bookingQuery
                ->whereDoesntHave('payments', function($query) {
                    $query->where('status', 'completed');
                })
                ->sum('total_amount');

            $bookingCount = $bookingQuery
                ->whereDoesntHave('payments', function($query) {
                    $query->where('status', 'completed');
                })
                ->count();

            $totalRevenue = $paymentRevenue + $bookingRevenue;
            $totalCount = $paymentCount + $bookingCount;

            return [
                'total_revenue' => $totalRevenue,
                'payment_revenue' => $paymentRevenue,
                'booking_revenue' => $bookingRevenue,
                'total_count' => $totalCount,
                'payment_count' => $paymentCount,
                'booking_count' => $bookingCount,
                'average_value' => $totalCount > 0 ? $totalRevenue / $totalCount : 0,
            ];
        } catch (\Exception $e) {
            return [
                'total_revenue' => 0,
                'payment_revenue' => 0,
                'booking_revenue' => 0,
                'total_count' => 0,
                'payment_count' => 0,
                'booking_count' => 0,
                'average_value' => 0,
            ];
        }
    }

    /**
     * Get payment method breakdown
     */
    public static function getPaymentMethodBreakdown(Carbon $start, Carbon $end, ?int $branchId = null): array
    {
        try {
            $query = Payment::withoutGlobalScopes()
                ->where('status', 'completed')
                ->whereBetween('processed_at', [$start, $end]);

            if ($branchId) {
                $query->whereHas('booking', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $breakdown = $query
                ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('payment_method')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->payment_method => [
                        'total' => $item->total,
                        'count' => $item->count,
                    ]];
                })
                ->toArray();

            return $breakdown;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get payment status summary
     */
    public static function getPaymentStatusSummary(Carbon $start, Carbon $end, ?int $branchId = null): array
    {
        try {
            $query = Payment::withoutGlobalScopes()
                ->whereBetween('created_at', [$start, $end]);

            if ($branchId) {
                $query->whereHas('booking', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $summary = $query
                ->selectRaw('status, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => [
                        'total' => $item->total,
                        'count' => $item->count,
                    ]];
                })
                ->toArray();

            $totalPayments = array_sum(array_column($summary, 'count'));
            $completedPayments = $summary['completed']['count'] ?? 0;
            $successRate = $totalPayments > 0 ? ($completedPayments / $totalPayments) * 100 : 0;

            return array_merge($summary, [
                'success_rate' => $successRate,
                'total_payments' => $totalPayments,
            ]);
        } catch (\Exception $e) {
            return [
                'success_rate' => 0,
                'total_payments' => 0,
            ];
        }
    }

    /**
     * Sync booking payment status with actual payments
     */
    public static function syncBookingPaymentStatus(): array
    {
        $synced = 0;
        $errors = [];

        try {
            // Find bookings with completed payments but not marked as paid
            $bookingsToUpdate = Booking::whereHas('payments', function($query) {
                $query->where('status', 'completed');
            })
            ->where('payment_status', '!=', 'completed')
            ->get();

            foreach ($bookingsToUpdate as $booking) {
                try {
                    $booking->update(['payment_status' => 'completed']);
                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = "Booking {$booking->id}: " . $e->getMessage();
                }
            }

            return [
                'success' => true,
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'synced' => $synced,
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
