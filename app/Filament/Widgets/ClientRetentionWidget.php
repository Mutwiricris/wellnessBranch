<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ClientRetentionWidget extends BaseWidget
{
    protected static ?int $sort = 5;
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

            // Get client statistics
            $totalClients = User::where('user_type', 'client')
                ->whereHas('bookings', function (Builder $query) use ($tenant) {
                    $query->where('branch_id', $tenant->id);
                })
                ->count();

            // New clients this month
            $newClientsThisMonth = User::where('user_type', 'client')
                ->whereHas('bookings', function (Builder $query) use ($tenant) {
                    $query->where('branch_id', $tenant->id)
                          ->where('created_at', '>=', now()->startOfMonth());
                })
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            // Repeat clients (clients with more than 1 booking)
            $repeatClients = User::where('user_type', 'client')
                ->whereHas('bookings', function (Builder $query) use ($tenant) {
                    $query->where('branch_id', $tenant->id);
                }, '>', 1)
                ->count();

            $retentionRate = $totalClients > 0 ? round(($repeatClients / $totalClients) * 100, 1) : 0;

            // Active clients (had booking in last 30 days)
            $activeClients = User::where('user_type', 'client')
                ->whereHas('bookings', function (Builder $query) use ($tenant) {
                    $query->where('branch_id', $tenant->id)
                          ->where('appointment_date', '>=', now()->subDays(30));
                })
                ->count();

            // Client lifetime value (average revenue per client)
            $totalRevenue = Booking::where('branch_id', $tenant->id)
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
                
            $avgLifetimeValue = $totalClients > 0 ? $totalRevenue / $totalClients : 0;

            // Average booking frequency
            $totalBookings = Booking::where('branch_id', $tenant->id)->count();
            $avgBookingsPerClient = $totalClients > 0 ? round($totalBookings / $totalClients, 1) : 0;

            // Peak hours analysis
            $peakHours = $this->getPeakHours($tenant);

            return [
                Stat::make('Total Clients', $totalClients)
                    ->description($newClientsThisMonth . ' new this month')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('primary')
                    ->chart($this->getClientGrowthChart($tenant)),

                Stat::make('Retention Rate', $retentionRate . '%')
                    ->description($repeatClients . ' repeat clients')
                    ->descriptionIcon('heroicon-m-heart')
                    ->color($retentionRate >= 70 ? 'success' : ($retentionRate >= 50 ? 'warning' : 'danger')),

                Stat::make('Active Clients', $activeClients)
                    ->description('Visited in last 30 days')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('info'),

                Stat::make('Avg Lifetime Value', 'KES ' . number_format($avgLifetimeValue, 0))
                    ->description('Per client revenue')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('success'),

                Stat::make('Avg Bookings/Client', $avgBookingsPerClient)
                    ->description('Booking frequency')
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('info'),

                Stat::make('Peak Hours', $peakHours['time'])
                    ->description($peakHours['count'] . ' bookings on average')
                    ->descriptionIcon('heroicon-m-fire')
                    ->color('warning'),
            ];

        } catch (\Exception $e) {
            \Log::error('Client retention widget error: ' . $e->getMessage());
            return $this->getEmptyStats();
        }
    }

    private function getEmptyStats(): array
    {
        return [
            Stat::make('Total Clients', 0),
            Stat::make('Retention Rate', '0%'),
            Stat::make('Active Clients', 0),
            Stat::make('Avg Lifetime Value', 'KES 0'),
            Stat::make('Avg Bookings/Client', 0),
            Stat::make('Peak Hours', 'N/A'),
        ];
    }

    private function getClientGrowthChart($tenant): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            
            $newClients = User::where('user_type', 'client')
                ->whereHas('bookings', function (Builder $query) use ($tenant, $date) {
                    $query->where('branch_id', $tenant->id)
                          ->whereDate('created_at', $date);
                })
                ->whereDate('created_at', $date)
                ->count();
                
            $data[] = $newClients;
        }
        
        return $data;
    }

    private function getPeakHours($tenant): array
    {
        $hourCounts = [];
        
        // Count bookings by hour for the last 30 days
        $bookings = Booking::where('branch_id', $tenant->id)
            ->where('appointment_date', '>=', now()->subDays(30))
            ->whereNotNull('start_time')
            ->get();
            
        foreach ($bookings as $booking) {
            try {
                $hour = Carbon::parse($booking->start_time)->format('H');
                $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
            } catch (\Exception $e) {
                // Skip invalid time formats
                continue;
            }
        }
        
        if (empty($hourCounts)) {
            return ['time' => 'N/A', 'count' => 0];
        }
        
        // Find the hour with most bookings
        $peakHour = array_keys($hourCounts, max($hourCounts))[0];
        $peakCount = max($hourCounts);
        
        // Convert to 12-hour format
        $peakTime = Carbon::createFromFormat('H', $peakHour)->format('g A');
        
        return [
            'time' => $peakTime,
            'count' => round($peakCount / 30, 1) // Average per day
        ];
    }

    public function getColumns(): int
    {
        return 6;
    }
    
    protected static ?string $pollingInterval = '60s';
}