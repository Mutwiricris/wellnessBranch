<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Service;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PerformanceSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.performance-summary';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        if (!$tenant) {
            return $this->getEmptyData();
        }

        try {
            return [
                'weekly_summary' => $this->getWeeklySummary($tenant),
                'monthly_summary' => $this->getMonthlySummary($tenant),
                'top_performers' => $this->getTopPerformers($tenant),
                'service_performance' => $this->getServicePerformance($tenant),
                'payment_insights' => $this->getPaymentInsights($tenant),
                'trends' => $this->getTrends($tenant),
                'alerts' => $this->getAlerts($tenant),
            ];
        } catch (\Exception $e) {
            \Log::error('Performance summary widget error: ' . $e->getMessage());
            return $this->getEmptyData();
        }
    }

    private function getWeeklySummary($tenant): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        $thisWeek = Booking::where('branch_id', $tenant->id)
            ->whereBetween('appointment_date', [$startOfWeek, $endOfWeek]);
            
        $lastWeek = Booking::where('branch_id', $tenant->id)
            ->whereBetween('appointment_date', [$lastWeekStart, $lastWeekEnd]);

        $thisWeekData = [
            'total_bookings' => $thisWeek->count(),
            'completed_bookings' => $thisWeek->clone()->where('status', 'completed')->count(),
            'revenue' => $thisWeek->clone()->where('status', 'completed')->sum('total_amount') ?? 0,
            'pending_bookings' => $thisWeek->clone()->where('status', 'pending')->count(),
        ];

        $lastWeekData = [
            'total_bookings' => $lastWeek->count(),
            'completed_bookings' => $lastWeek->clone()->where('status', 'completed')->count(),
            'revenue' => $lastWeek->clone()->where('status', 'completed')->sum('total_amount') ?? 0,
        ];

        // Calculate changes
        $bookingChange = $lastWeekData['total_bookings'] > 0 
            ? round((($thisWeekData['total_bookings'] - $lastWeekData['total_bookings']) / $lastWeekData['total_bookings']) * 100, 1)
            : 0;

        $revenueChange = $lastWeekData['revenue'] > 0 
            ? round((($thisWeekData['revenue'] - $lastWeekData['revenue']) / $lastWeekData['revenue']) * 100, 1)
            : 0;

        $completionRate = $thisWeekData['total_bookings'] > 0 
            ? round(($thisWeekData['completed_bookings'] / $thisWeekData['total_bookings']) * 100, 1)
            : 0;

        return [
            'current' => $thisWeekData,
            'previous' => $lastWeekData,
            'booking_change' => $bookingChange,
            'revenue_change' => $revenueChange,
            'completion_rate' => $completionRate,
        ];
    }

    private function getMonthlySummary($tenant): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $thisMonth = Booking::where('branch_id', $tenant->id)
            ->whereBetween('appointment_date', [$startOfMonth, $endOfMonth]);
            
        $lastMonth = Booking::where('branch_id', $tenant->id)
            ->whereBetween('appointment_date', [$lastMonthStart, $lastMonthEnd]);

        $thisMonthRevenue = $thisMonth->clone()->where('status', 'completed')->sum('total_amount') ?? 0;
        $lastMonthRevenue = $lastMonth->clone()->where('status', 'completed')->sum('total_amount') ?? 0;

        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        return [
            'revenue' => $thisMonthRevenue,
            'revenue_growth' => $revenueGrowth,
            'total_bookings' => $thisMonth->count(),
            'avg_daily_revenue' => $thisMonthRevenue / now()->day,
        ];
    }

    private function getTopPerformers($tenant): array
    {
        $staff = Staff::whereHas('branches', function (Builder $query) use ($tenant) {
            $query->where('branch_id', $tenant->id);
        })
        ->where('status', 'active')
        ->get();

        $performers = [];
        foreach ($staff as $member) {
            $weeklyBookings = $member->getTotalBookings(now()->startOfWeek(), now()->endOfWeek());
            $weeklyRevenue = $member->getTotalRevenue(now()->startOfWeek(), now()->endOfWeek());
            $completionRate = $member->getCompletionRate(now()->startOfWeek(), now()->endOfWeek());

            $performers[] = [
                'name' => $member->name,
                'bookings' => $weeklyBookings,
                'revenue' => $weeklyRevenue,
                'completion_rate' => $completionRate,
                'color' => $member->color,
            ];
        }

        // Sort by revenue and take top 5
        usort($performers, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        return array_slice($performers, 0, 5);
    }

    private function getServicePerformance($tenant): array
    {
        $services = Service::whereHas('branches', function (Builder $query) use ($tenant) {
            $query->where('branch_id', $tenant->id);
        })
        ->withCount(['bookings as this_week_bookings' => function (Builder $query) use ($tenant) {
            $query->where('branch_id', $tenant->id)
                  ->whereBetween('appointment_date', [now()->startOfWeek(), now()->endOfWeek()]);
        }])
        ->withSum(['bookings as this_week_revenue' => function (Builder $query) use ($tenant) {
            $query->where('branch_id', $tenant->id)
                  ->whereBetween('appointment_date', [now()->startOfWeek(), now()->endOfWeek()])
                  ->where('status', 'completed');
        }], 'total_amount')
        ->orderByDesc('this_week_bookings')
        ->limit(5)
        ->get();

        return $services->map(function ($service) {
            return [
                'name' => $service->name,
                'bookings' => $service->this_week_bookings ?? 0,
                'revenue' => $service->this_week_revenue ?? 0,
                'price' => $service->price,
                'duration' => $service->duration,
            ];
        })->toArray();
    }

    private function getPaymentInsights($tenant): array
    {
        $payments = Payment::whereHas('booking', function (Builder $query) use ($tenant) {
            $query->where('branch_id', $tenant->id);
        })
        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);

        $totalPayments = $payments->count();
        $completedPayments = $payments->clone()->where('status', 'completed')->count();
        $pendingPayments = $payments->clone()->where('status', 'pending')->count();
        $failedPayments = $payments->clone()->where('status', 'failed')->count();

        $paymentMethods = $payments->clone()
            ->select('payment_method')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('payment_method')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'payment_method')
            ->toArray();

        return [
            'total' => $totalPayments,
            'completed' => $completedPayments,
            'pending' => $pendingPayments,
            'failed' => $failedPayments,
            'success_rate' => $totalPayments > 0 ? round(($completedPayments / $totalPayments) * 100, 1) : 0,
            'methods' => $paymentMethods,
        ];
    }

    private function getTrends($tenant): array
    {
        $dailyData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $dayBookings = Booking::where('branch_id', $tenant->id)
                ->whereDate('appointment_date', $date)
                ->where('status', 'completed');
                
            $dailyData[] = [
                'date' => $date->format('M j'),
                'bookings' => $dayBookings->count(),
                'revenue' => $dayBookings->sum('total_amount') ?? 0,
            ];
        }

        return $dailyData;
    }

    private function getAlerts($tenant): array
    {
        $alerts = [];

        // Low completion rate alert
        $weeklyBookings = Booking::where('branch_id', $tenant->id)
            ->whereBetween('appointment_date', [now()->startOfWeek(), now()->endOfWeek()]);
            
        $totalBookings = $weeklyBookings->count();
        $completedBookings = $weeklyBookings->clone()->where('status', 'completed')->count();
        
        if ($totalBookings > 0) {
            $completionRate = ($completedBookings / $totalBookings) * 100;
            if ($completionRate < 70) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Low Completion Rate',
                    'message' => "Weekly completion rate is {$completionRate}%. Consider reviewing booking processes.",
                    'icon' => '⚠️'
                ];
            }
        }

        // High pending payments alert
        $pendingPayments = Payment::whereHas('booking', function (Builder $query) use ($tenant) {
            $query->where('branch_id', $tenant->id);
        })
        ->where('status', 'pending')
        ->where('created_at', '>=', now()->subDays(7))
        ->count();

        if ($pendingPayments > 5) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Pending Payments',
                'message' => "{$pendingPayments} payments are pending verification. Review payment processing.",
                'icon' => '💳'
            ];
        }

        // No bookings today alert
        $todayBookings = Booking::where('branch_id', $tenant->id)
            ->whereDate('appointment_date', today())
            ->count();

        if ($todayBookings === 0 && now()->hour > 10) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'No Bookings Today',
                'message' => 'No appointments scheduled for today. Consider promotional activities.',
                'icon' => '📅'
            ];
        }

        return $alerts;
    }

    private function getEmptyData(): array
    {
        return [
            'weekly_summary' => [
                'current' => ['total_bookings' => 0, 'completed_bookings' => 0, 'revenue' => 0],
                'booking_change' => 0,
                'revenue_change' => 0,
                'completion_rate' => 0,
            ],
            'monthly_summary' => [
                'revenue' => 0,
                'revenue_growth' => 0,
                'total_bookings' => 0,
                'avg_daily_revenue' => 0,
            ],
            'top_performers' => [],
            'service_performance' => [],
            'payment_insights' => [
                'total' => 0,
                'completed' => 0,
                'success_rate' => 0,
                'methods' => [],
            ],
            'trends' => [],
            'alerts' => [],
        ];
    }
}