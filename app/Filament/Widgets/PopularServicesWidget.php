<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Service;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class PopularServicesWidget extends ChartWidget
{
    protected static ?string $heading = 'Popular Services';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'month';

    protected function getData(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        if (!$tenant) {
            return $this->getEmptyData();
        }

        $query = Booking::where('branch_id', $tenant->id)
            ->select('service_id')
            ->selectRaw('COUNT(*) as booking_count')
            ->selectRaw('SUM(total_amount) as total_revenue')
            ->with('service')
            ->groupBy('service_id')
            ->orderByDesc('booking_count');

        // Apply time filter
        switch ($this->filter) {
            case 'week':
                $query->where('appointment_date', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->where('appointment_date', '>=', now()->startOfMonth());
                break;
            case 'year':
                $query->where('appointment_date', '>=', now()->startOfYear());
                break;
        }

        $services = $query->limit(8)->get();

        if ($services->isEmpty()) {
            return $this->getEmptyData();
        }

        $labels = [];
        $bookingCounts = [];
        $revenues = [];
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
            '#DDA0DD', '#98D8C8', '#FFB347'
        ];

        foreach ($services as $index => $serviceData) {
            $service = $serviceData->service;
            $labels[] = $service ? substr($service->name, 0, 20) . (strlen($service->name) > 20 ? '...' : '') : 'Unknown';
            $bookingCounts[] = $serviceData->booking_count;
            $revenues[] = $serviceData->total_revenue ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $bookingCounts,
                    'backgroundColor' => array_slice($colors, 0, count($labels)),
                    'borderColor' => array_slice($colors, 0, count($labels)),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => 'This Week',
            'month' => 'This Month',
            'year' => 'This Year',
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
                    'display' => true,
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.label || "";
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ": " + value + " (" + percentage + "%)";
                        }'
                    ]
                ]
            ],
            'cutout' => '60%',
        ];
    }
}