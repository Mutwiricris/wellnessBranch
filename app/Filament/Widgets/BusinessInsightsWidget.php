<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class BusinessInsightsWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trends';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '400px';

    public ?string $filter = 'week';

    protected function getData(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        if (!$tenant) {
            return $this->getEmptyData();
        }

        switch ($this->filter) {
            case 'today':
                return $this->getTodayData($tenant);
            case 'week':
                return $this->getWeekData($tenant);
            case 'month':
                return $this->getMonthData($tenant);
            case 'year':
                return $this->getYearData($tenant);
            default:
                return $this->getWeekData($tenant);
        }
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today (Hourly)',
            'week' => 'This Week',
            'month' => 'This Month',
            'year' => 'This Year',
        ];
    }

    private function getTodayData($tenant): array
    {
        $hours = [];
        $bookingCounts = [];
        $revenues = [];

        for ($hour = 8; $hour <= 20; $hour++) {
            $hourStart = today()->setHour($hour);
            $hourEnd = today()->setHour($hour + 1);
            
            $hourBookings = Booking::where('branch_id', $tenant->id)
                ->whereDate('appointment_date', today())
                ->whereTime('start_time', '>=', $hourStart->format('H:i:s'))
                ->whereTime('start_time', '<', $hourEnd->format('H:i:s'))
                ->where('status', 'completed');
                
            $bookingCount = $hourBookings->count();
            $revenue = $hourBookings->sum('total_amount') ?? 0;
            
            $hours[] = $hourStart->format('H:00');
            $bookingCounts[] = $bookingCount;
            $revenues[] = $revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $revenues,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Bookings',
                    'data' => $bookingCounts,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $hours,
        ];
    }

    private function getWeekData($tenant): array
    {
        $days = [];
        $bookingCounts = [];
        $revenues = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $dayBookings = Booking::where('branch_id', $tenant->id)
                ->whereDate('appointment_date', $date)
                ->where('status', 'completed');
                
            $bookingCount = $dayBookings->count();
            $revenue = $dayBookings->sum('total_amount') ?? 0;
            
            $days[] = $date->format('M j');
            $bookingCounts[] = $bookingCount;
            $revenues[] = $revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $revenues,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Bookings',
                    'data' => $bookingCounts,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $days,
        ];
    }

    private function getMonthData($tenant): array
    {
        $weeks = [];
        $bookingCounts = [];
        $revenues = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            
            $weekBookings = Booking::where('branch_id', $tenant->id)
                ->whereBetween('appointment_date', [$weekStart, $weekEnd])
                ->where('status', 'completed');
                
            $bookingCount = $weekBookings->count();
            $revenue = $weekBookings->sum('total_amount') ?? 0;
            
            $weeks[] = 'Week ' . $weekStart->format('M j');
            $bookingCounts[] = $bookingCount;
            $revenues[] = $revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $revenues,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Bookings',
                    'data' => $bookingCounts,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $weeks,
        ];
    }

    private function getYearData($tenant): array
    {
        $months = [];
        $bookingCounts = [];
        $revenues = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            
            $monthBookings = Booking::where('branch_id', $tenant->id)
                ->whereBetween('appointment_date', [$monthStart, $monthEnd])
                ->where('status', 'completed');
                
            $bookingCount = $monthBookings->count();
            $revenue = $monthBookings->sum('total_amount') ?? 0;
            
            $months[] = $monthStart->format('M Y');
            $bookingCounts[] = $bookingCount;
            $revenues[] = $revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $revenues,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Bookings',
                    'data' => $bookingCounts,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    private function getEmptyData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => [],
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'Bookings',
                    'data' => [],
                    'borderColor' => '#3b82f6',
                ],
            ],
            'labels' => [],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue (KES)'
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Bookings'
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.dataset.label === "Revenue (KES)") {
                                label += "KES " + context.parsed.y.toLocaleString();
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }'
                    ]
                ]
            ],
        ];
    }
}