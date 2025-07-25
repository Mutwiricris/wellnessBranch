<?php

namespace App\Filament\Resources\BookingResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class BookingStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            if (!$tenant) {
                return $this->getEmptyStats();
            }
            
            // Base query for this branch
            $baseQuery = Booking::where('branch_id', $tenant->id);
            
            // Today's stats
            $todayBookings = $baseQuery->clone()->whereDate('appointment_date', today());
            $todayTotal = $todayBookings->count();
            $todayRevenue = $todayBookings->clone()->where('payment_status', 'completed')->sum('total_amount') ?? 0;
            $todayPending = $todayBookings->clone()->where('status', 'pending')->count();
            $todayConfirmed = $todayBookings->clone()->where('status', 'confirmed')->count();
            $todayCompleted = $todayBookings->clone()->where('status', 'completed')->count();
            
            // Yesterday's stats for comparison
            $yesterdayBookings = $baseQuery->clone()->whereDate('appointment_date', today()->subDay());
            $yesterdayTotal = $yesterdayBookings->count();
            $yesterdayRevenue = $yesterdayBookings->clone()->where('payment_status', 'completed')->sum('total_amount') ?? 0;
            
            // This week's stats
            $weeklyBookings = $baseQuery->clone()->whereBetween('appointment_date', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
            $weeklyTotal = $weeklyBookings->count();
            $weeklyRevenue = $weeklyBookings->clone()->where('payment_status', 'completed')->sum('total_amount') ?? 0;
            
            // This month's stats
            $monthlyBookings = $baseQuery->clone()->whereBetween('appointment_date', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ]);
            $monthlyTotal = $monthlyBookings->count();
            $monthlyRevenue = $monthlyBookings->clone()->where('payment_status', 'completed')->sum('total_amount') ?? 0;
            
            // Status overview (upcoming bookings)
            $upcomingBookings = $baseQuery->clone()->where('appointment_date', '>=', today());
            $totalPending = $upcomingBookings->clone()->where('status', 'pending')->count();
            $totalConfirmed = $upcomingBookings->clone()->where('status', 'confirmed')->count();
            $totalInProgress = $upcomingBookings->clone()->where('status', 'in_progress')->count();
            
            // Calculate percentage changes
            $dailyChange = $yesterdayTotal > 0 ? (($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100 : 0;
            $revenueChange = $yesterdayRevenue > 0 ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100 : 0;
            
            return [
                Stat::make('Today\'s Bookings', $todayTotal)
                    ->description($todayCompleted . ' completed, ' . $todayPending . ' pending')
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('primary')
                    ->chart($this->getLast7DaysBookings($baseQuery)),
                    
                Stat::make('Today\'s Revenue', 'KES ' . number_format($todayRevenue, 2))
                    ->description($revenueChange >= 0 ? 
                        '↑ ' . number_format(abs($revenueChange), 1) . '% from yesterday' : 
                        '↓ ' . number_format(abs($revenueChange), 1) . '% from yesterday'
                    )
                    ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($revenueChange >= 0 ? 'success' : 'danger')
                    ->chart($this->getLast7DaysRevenue($baseQuery)),
                    
                Stat::make('This Week', $weeklyTotal)
                    ->description('KES ' . number_format($weeklyRevenue, 2) . ' revenue')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color('info'),
                    
                Stat::make('This Month', $monthlyTotal)
                    ->description('KES ' . number_format($monthlyRevenue, 2) . ' revenue')
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color('success'),
                    
                Stat::make('Pending Bookings', $totalPending)
                    ->description($totalConfirmed . ' confirmed, ' . $totalInProgress . ' in progress')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),
                    
                Stat::make('Total Bookings', $baseQuery->clone()->count())
                    ->description('All time bookings')
                    ->descriptionIcon('heroicon-m-rectangle-stack')
                    ->color('gray'),
            ];
            
        } catch (\Exception $e) {
            \Log::error('Booking stats widget error: ' . $e->getMessage());
            return $this->getEmptyStats();
        }
    }
    
    private function getLast7DaysBookings($baseQuery): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $count = $baseQuery->clone()->whereDate('appointment_date', $date)->count();
            $data[] = $count;
        }
        return $data;
    }
    
    private function getLast7DaysRevenue($baseQuery): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $revenue = $baseQuery->clone()
                ->whereDate('appointment_date', $date)
                ->where('payment_status', 'completed')
                ->sum('total_amount') ?? 0;
            $data[] = $revenue;
        }
        return $data;
    }
    
    private function getEmptyStats(): array
    {
        return [
            Stat::make('Today\'s Bookings', 0),
            Stat::make('Today\'s Revenue', 'KES 0.00'),
            Stat::make('This Week', 0),
            Stat::make('This Month', 0),
            Stat::make('Pending Bookings', 0),
            Stat::make('Total Bookings', 0),
        ];
    }
    
    public function getColumns(): int
    {
        return 6;
    }
    
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;
}