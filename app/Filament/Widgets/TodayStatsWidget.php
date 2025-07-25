<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class TodayStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        if (!$tenant) {
            return $this->getEmptyStats();
        }

        try {
            $today = today();
            $yesterday = today()->subDay();

            // Today's bookings
            $todayBookings = Booking::where('branch_id', $tenant->id)
                ->whereDate('appointment_date', $today);
            
            $totalBookings = $todayBookings->count();
            $pendingBookings = $todayBookings->clone()->where('status', 'pending')->count();
            $completedServices = $todayBookings->clone()->where('status', 'completed')->count();
            $noShowBookings = $todayBookings->clone()->where('status', 'no_show')->count();

            // Yesterday's comparison
            $yesterdayBookings = Booking::where('branch_id', $tenant->id)
                ->whereDate('appointment_date', $yesterday)
                ->count();

            // Daily Revenue
            $todayRevenue = Payment::where('branch_id', $tenant->id)
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('amount');

            $yesterdayRevenue = Payment::where('branch_id', $tenant->id)
                ->whereDate('created_at', $yesterday)
                ->where('status', 'completed')
                ->sum('amount');

            // Staff Utilization
            $activeStaff = Staff::where('branch_id', $tenant->id)
                ->where('status', 'active')
                ->count();

            $busyStaff = Booking::where('branch_id', $tenant->id)
                ->whereDate('appointment_date', $today)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->distinct('staff_id')
                ->count('staff_id');

            $staffUtilization = $activeStaff > 0 ? ($busyStaff / $activeStaff) * 100 : 0;

            // No-Show Rate
            $noShowRate = $totalBookings > 0 ? ($noShowBookings / $totalBookings) * 100 : 0;

            // Average Service Time (estimated based on service duration)
            $avgServiceTime = $todayBookings->clone()
                ->where('status', 'completed')
                ->join('services', 'bookings.service_id', '=', 'services.id')
                ->avg('services.duration_minutes') ?? 0;

            // Upcoming services today
            $upcomingCount = $todayBookings->clone()
                ->where('appointment_date', '>=', now())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            // Growth calculations
            $bookingGrowth = $yesterdayBookings > 0 ? 
                (($totalBookings - $yesterdayBookings) / $yesterdayBookings) * 100 : 
                ($totalBookings > 0 ? 100 : 0);

            $revenueGrowth = $yesterdayRevenue > 0 ? 
                (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100 : 
                ($todayRevenue > 0 ? 100 : 0);

            return [
                Stat::make('Total Bookings', $totalBookings)
                    ->description($pendingBookings . ' pending')
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('primary')
                    ->chart($this->getHourlyBookings($tenant->id))
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                    ]),

                Stat::make('Completed Services', $completedServices)
                    ->description($completedServices . ' confirmed')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Daily Revenue', 'KES ' . number_format($todayRevenue, 2))
                    ->description(
                        ($revenueGrowth >= 0 ? '↗ ' : '↘ ') . 
                        number_format(abs($revenueGrowth), 1) . '% vs yesterday'
                    )
                    ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($revenueGrowth >= 0 ? 'success' : 'danger')
                    ->chart($this->getDailyRevenueChart($tenant->id)),

                Stat::make('Staff Utilization', number_format($staffUtilization, 1) . '%')
                    ->description($busyStaff . ' of ' . $activeStaff . ' staff active')
                    ->descriptionIcon('heroicon-m-users')
                    ->color($staffUtilization > 75 ? 'success' : ($staffUtilization > 50 ? 'warning' : 'danger')),

                Stat::make('No-Show Rate', number_format($noShowRate, 1) . '%')
                    ->description($noShowBookings . ' no-shows today')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color($noShowRate < 5 ? 'success' : ($noShowRate < 10 ? 'warning' : 'danger')),

                Stat::make('Avg Service Time', number_format($avgServiceTime, 0) . ' min')
                    ->description($upcomingCount . ' upcoming')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('info'),
            ];

        } catch (\Exception $e) {
            \Log::error('Today stats widget error: ' . $e->getMessage());
            return $this->getEmptyStats();
        }
    }

    private function getHourlyBookings(int $branchId): array
    {
        $data = [];
        $currentHour = now()->hour;
        
        for ($hour = 8; $hour <= min($currentHour + 1, 20); $hour++) {
            $count = Booking::where('branch_id', $branchId)
                ->whereDate('appointment_date', today())
                ->whereTime('start_time', '>=', sprintf('%02d:00:00', $hour))
                ->whereTime('start_time', '<', sprintf('%02d:00:00', $hour + 1))
                ->count();
            $data[] = $count;
        }
        
        return $data;
    }

    private function getDailyRevenueChart(int $branchId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
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
            Stat::make('Total Bookings', 0),
            Stat::make('Completed Services', 0),
            Stat::make('Daily Revenue', 'KES 0.00'),
            Stat::make('Staff Utilization', '0%'),
            Stat::make('No-Show Rate', '0%'),
            Stat::make('Avg Service Time', '0 min'),
        ];
    }
}