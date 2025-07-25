<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        $tenant = Filament::getTenant();
        return $tenant ? $tenant->name . ' - Dashboard' : 'Dashboard';
    }
    
    public function getSubheading(): ?string
    {
        $tenant = Filament::getTenant();
        if ($tenant) {
            return 'Welcome to ' . $tenant->name . ' management portal - Real-time analytics and insights';
        }
        return null;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\TodayStatsWidget::class,
            \App\Filament\Widgets\FinancialMetricsWidget::class,
            \App\Filament\Resources\BookingResource\Widgets\BookingStatsWidget::class,
            \App\Filament\Widgets\BusinessInsightsWidget::class,
            \App\Filament\Widgets\PopularServicesWidget::class,
            \App\Filament\Widgets\LatestBookingsWidget::class,
            \App\Filament\Widgets\RecentTransactionsWidget::class,
            \App\Filament\Widgets\PeakHoursWidget::class,
            \App\Filament\Widgets\StaffPerformanceWidget::class,
            \App\Filament\Widgets\ClientRetentionWidget::class,
            \App\Filament\Widgets\NotificationCenterWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            'lg' => 4,
            'xl' => 6,
            '2xl' => 6,
        ];
    }
}
