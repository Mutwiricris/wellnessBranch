<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class PeakHoursWidget extends ChartWidget
{
    protected static ?string $heading = 'Peak Hours Analysis';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '350px';

    public ?string $filter = 'week';

    protected function getData(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        if (!$tenant) {
            return $this->getEmptyData();
        }

        $query = Booking::where('branch_id', $tenant->id);

        // Apply time filter
        switch ($this->filter) {
            case 'today':
                $query->whereDate('appointment_date', today());
                break;
            case 'week':
                $query->whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereBetween('appointment_date', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
        }

        $hourlyBookings = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            $hourlyBookings[$hour] = $query->clone()
                ->whereTime('start_time', '>=', sprintf('%02d:00:00', $hour))
                ->whereTime('start_time', '<', sprintf('%02d:00:00', $hour + 1))
                ->count();
        }

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($hourlyBookings as $hour => $count) {
            $labels[] = sprintf('%02d:00', $hour);
            $data[] = $count;
            
            // Color based on booking intensity
            if ($count >= 10) {
                $colors[] = '#ef4444'; // High (red)
            } elseif ($count >= 5) {
                $colors[] = '#f59e0b'; // Medium (yellow)
            } else {
                $colors[] = '#10b981'; // Low (green)
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 2,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
        ];
    }

    private function getEmptyData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => [],
                    'backgroundColor' => [],
                ],
            ],
            'labels' => [],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const hour = context.label;
                            const count = context.parsed.y;
                            let intensity = "Low";
                            if (count >= 10) intensity = "High";
                            else if (count >= 5) intensity = "Medium";
                            return hour + ": " + count + " bookings (" + intensity + " traffic)";
                        }'
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Bookings'
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Time of Day'
                    ],
                ],
            ],
        ];
    }
}