<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class FinancialMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        if (!$tenant) {
            return $this->getEmptyStats();
        }

        try {
            // Weekly Revenue
            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();
            $lastWeekStart = now()->subWeek()->startOfWeek();
            $lastWeekEnd = now()->subWeek()->endOfWeek();

            $weeklyRevenue = Payment::where('branch_id', $tenant->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->where('status', 'completed')
                ->sum('amount');

            $lastWeekRevenue = Payment::where('branch_id', $tenant->id)
                ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
                ->where('status', 'completed')
                ->sum('amount');

            // Monthly Revenue
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();
            $lastMonthStart = now()->subMonth()->startOfMonth();
            $lastMonthEnd = now()->subMonth()->endOfMonth();

            $monthlyRevenue = Payment::where('branch_id', $tenant->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->sum('amount');

            $lastMonthRevenue = Payment::where('branch_id', $tenant->id)
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->where('status', 'completed')
                ->sum('amount');

            // Pending Payments
            $pendingPayments = Payment::where('branch_id', $tenant->id)
                ->where('status', 'pending')
                ->sum('amount');

            $pendingPaymentCount = Payment::where('branch_id', $tenant->id)
                ->where('status', 'pending')
                ->count();

            // Client Retention (clients who booked again in last 30 days)
            $thirtyDaysAgo = now()->subDays(30);
            
            $returningClients = User::whereHas('bookings', function($query) use ($tenant, $thirtyDaysAgo) {
                $query->where('branch_id', $tenant->id)
                      ->where('appointment_date', '>=', $thirtyDaysAgo);
            })
            ->whereHas('bookings', function($query) use ($tenant, $thirtyDaysAgo) {
                $query->where('branch_id', $tenant->id)
                      ->where('appointment_date', '<', $thirtyDaysAgo);
            })
            ->count();

            $totalClientsLast30Days = User::whereHas('bookings', function($query) use ($tenant, $thirtyDaysAgo) {
                $query->where('branch_id', $tenant->id)
                      ->where('appointment_date', '>=', $thirtyDaysAgo);
            })->count();

            $clientRetentionRate = $totalClientsLast30Days > 0 ? 
                ($returningClients / $totalClientsLast30Days) * 100 : 0;

            // Growth calculations
            $weeklyGrowth = $lastWeekRevenue > 0 ? 
                (($weeklyRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100 : 
                ($weeklyRevenue > 0 ? 100 : 0);

            $monthlyGrowth = $lastMonthRevenue > 0 ? 
                (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 
                ($monthlyRevenue > 0 ? 100 : 0);

            // Repeat client ratio
            $repeatClientRatio = $totalClientsLast30Days > 0 ? 
                ($returningClients / $totalClientsLast30Days) * 100 : 0;

            return [
                Stat::make('Weekly Revenue', 'KES ' . number_format($weeklyRevenue, 2))
                    ->description(
                        ($weeklyGrowth >= 0 ? '↗ ' : '↘ ') . 
                        number_format(abs($weeklyGrowth), 1) . '% from last week'
                    )
                    ->descriptionIcon($weeklyGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($weeklyGrowth >= 0 ? 'success' : 'danger')
                    ->chart($this->getWeeklyRevenueChart($tenant->id)),

                Stat::make('Monthly Revenue', 'KES ' . number_format($monthlyRevenue, 2))
                    ->description(
                        ($monthlyGrowth >= 0 ? '↗ ' : '↘ ') . 
                        number_format(abs($monthlyGrowth), 1) . '% from last month'
                    )
                    ->descriptionIcon($monthlyGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($monthlyGrowth >= 0 ? 'success' : 'danger')
                    ->chart($this->getMonthlyRevenueChart($tenant->id)),

                Stat::make('Pending Payments', 'KES ' . number_format($pendingPayments, 2))
                    ->description($pendingPaymentCount . ' pending transactions')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color($pendingPayments > 10000 ? 'warning' : 'info'),

                Stat::make('Client Retention', number_format($clientRetentionRate, 1) . '%')
                    ->description(number_format($repeatClientRatio, 1) . '% repeat client ratio')
                    ->descriptionIcon('heroicon-m-heart')
                    ->color($clientRetentionRate > 70 ? 'success' : ($clientRetentionRate > 50 ? 'warning' : 'danger')),
            ];

        } catch (\Exception $e) {
            \Log::error('Financial metrics widget error: ' . $e->getMessage());
            return $this->getEmptyStats();
        }
    }

    private function getWeeklyRevenueChart(int $branchId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = Payment::where('branch_id', $branchId)
                ->whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('amount');
            $data[] = $revenue;
        }
        return $data;
    }

    private function getMonthlyRevenueChart(int $branchId): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = Payment::where('branch_id', $branchId)
                ->whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('amount');
            $data[] = $revenue;
        }
        return $data;
    }

    private function getEmptyStats(): array
    {
        return [
            Stat::make('Weekly Revenue', 'KES 0.00'),
            Stat::make('Monthly Revenue', 'KES 0.00'),
            Stat::make('Pending Payments', 'KES 0.00'),
            Stat::make('Client Retention', '0%'),
        ];
    }
}