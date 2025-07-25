<?php

namespace App\Filament\Widgets;

use App\Models\Staff;
use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StaffPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            if (!$tenant) {
                return $this->getEmptyStats();
            }
            
            // Get active staff for this branch
            $activeStaff = Staff::whereHas('branches', function (Builder $query) use ($tenant) {
                $query->where('branch_id', $tenant->id);
            })->where('status', 'active')->get();
            
            $totalStaff = $activeStaff->count();
            
            if ($totalStaff === 0) {
                return $this->getEmptyStats();
            }
            
            // Calculate averages
            $totalBookingsToday = 0;
            $totalRevenueToday = 0;
            $totalUtilization = 0;
            $totalCompletionRate = 0;
            
            foreach ($activeStaff as $staff) {
                $totalBookingsToday += $staff->getTotalBookings(today(), today());
                $totalRevenueToday += $staff->getTotalRevenue(today(), today());
                $totalUtilization += $staff->getUtilizationRate(today());
                $totalCompletionRate += $staff->getCompletionRate(
                    now()->startOfWeek(), 
                    now()->endOfWeek()
                );
            }
            
            $avgUtilization = $totalStaff > 0 ? round($totalUtilization / $totalStaff, 1) : 0;
            $avgCompletionRate = $totalStaff > 0 ? round($totalCompletionRate / $totalStaff, 1) : 0;
            
            // Get top performer
            $topPerformer = $activeStaff->sortByDesc(function ($staff) {
                return $staff->getTotalRevenue(now()->startOfMonth(), now()->endOfMonth());
            })->first();
            
            // Get weekly comparison
            $thisWeekBookings = 0;
            $lastWeekBookings = 0;
            
            foreach ($activeStaff as $staff) {
                $thisWeekBookings += $staff->getTotalBookings(
                    now()->startOfWeek(), 
                    now()->endOfWeek()
                );
                $lastWeekBookings += $staff->getTotalBookings(
                    now()->subWeek()->startOfWeek(), 
                    now()->subWeek()->endOfWeek()
                );
            }
            
            $weeklyChange = $lastWeekBookings > 0 
                ? round((($thisWeekBookings - $lastWeekBookings) / $lastWeekBookings) * 100, 1)
                : 0;
            
            return [
                Stat::make('Active Staff', $totalStaff)
                    ->description('Currently working at this branch')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('primary'),
                    
                Stat::make('Today\'s Bookings', $totalBookingsToday)
                    ->description('Total appointments today')
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('success'),
                    
                Stat::make('Today\'s Revenue', 'KES ' . number_format($totalRevenueToday, 2))
                    ->description('From completed services')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('success'),
                    
                Stat::make('Average Utilization', $avgUtilization . '%')
                    ->description('Staff time utilization today')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color($avgUtilization >= 70 ? 'success' : ($avgUtilization >= 50 ? 'warning' : 'danger')),
                    
                Stat::make('Completion Rate', $avgCompletionRate . '%')
                    ->description('This week average')
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->color($avgCompletionRate >= 90 ? 'success' : ($avgCompletionRate >= 80 ? 'warning' : 'danger')),
                    
                Stat::make('Top Performer', $topPerformer?->name ?? 'N/A')
                    ->description($topPerformer 
                        ? 'KES ' . number_format($topPerformer->getTotalRevenue(now()->startOfMonth(), now()->endOfMonth()), 2) . ' this month'
                        : 'No data available'
                    )
                    ->descriptionIcon('heroicon-m-trophy')
                    ->color('warning'),
            ];
            
        } catch (\Exception $e) {
            \Log::error('Staff performance widget error: ' . $e->getMessage());
            return $this->getEmptyStats();
        }
    }
    
    private function getEmptyStats(): array
    {
        return [
            Stat::make('Active Staff', 0),
            Stat::make('Today\'s Bookings', 0),
            Stat::make('Today\'s Revenue', 'KES 0.00'),
            Stat::make('Average Utilization', '0%'),
            Stat::make('Completion Rate', '0%'),
            Stat::make('Top Performer', 'N/A'),
        ];
    }
    
    public function getColumns(): int
    {
        return 6;
    }
    
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;
}