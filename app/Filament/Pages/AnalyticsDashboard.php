<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\User;
use App\Models\Service;
use App\Models\Expense;
use App\Models\InventoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    
    protected static ?string $navigationGroup = '📊 Business Intelligence';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.analytics-dashboard';
    
    protected static ?string $title = 'Analytics Dashboard';
    
    protected static ?string $slug = 'analytics-dashboard';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->filters = [
            'date_range' => 'today',
            'status' => 'all',
            'service_id' => null,
            'staff_id' => null,
            'payment_method' => 'all',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filters')
                    ->schema([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\Select::make('date_range')
                                    ->label('Date Range')
                                    ->options([
                                        'today' => 'Today',
                                        'yesterday' => 'Yesterday',
                                        'this_week' => 'This Week',
                                        'last_week' => 'Last Week',
                                        'this_month' => 'This Month',
                                        'last_month' => 'Last Month',
                                        'this_year' => 'This Year',
                                        'custom' => 'Custom Range',
                                    ])
                                    ->default('today')
                                    ->native(false)
                                    ->reactive(),

                                Forms\Components\Select::make('status')
                                    ->label('Booking Status')
                                    ->options([
                                        'all' => 'All Statuses',
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                        'no_show' => 'No Show',
                                    ])
                                    ->default('all')
                                    ->native(false),

                                Forms\Components\Select::make('service_id')
                                    ->label('Service')
                                    ->options(function () {
                                        $tenant = \Filament\Facades\Filament::getTenant();
                                        if (!$tenant) return [];
                                        
                                        return Service::whereHas('branches', function($query) use ($tenant) {
                                            $query->where('branch_id', $tenant->id);
                                        })->pluck('name', 'id')->prepend('All Services', null);
                                    })
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\Select::make('staff_id')
                                    ->label('Staff Member')
                                    ->options(function () {
                                        $tenant = \Filament\Facades\Filament::getTenant();
                                        if (!$tenant) return [];
                                        
                                        return Staff::whereHas('branches', function($query) use ($tenant) {
                                            $query->where('branch_id', $tenant->id);
                                        })
                                            ->get()
                                            ->mapWithKeys(function ($staff) {
                                                return [$staff->id => $staff->name];
                                            })
                                            ->prepend('All Staff', null);
                                    })
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'all' => 'All Methods',
                                        'cash' => 'Cash',
                                        'mpesa' => 'M-Pesa',
                                        'card' => 'Card',
                                        'bank_transfer' => 'Bank Transfer',
                                    ])
                                    ->default('all')
                                    ->native(false),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('custom_start_date')
                                    ->label('Start Date')
                                    ->visible(fn (Forms\Get $get) => $get('date_range') === 'custom'),

                                Forms\Components\DatePicker::make('custom_end_date')
                                    ->label('End Date')
                                    ->visible(fn (Forms\Get $get) => $get('date_range') === 'custom')
                                    ->after('custom_start_date'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ])
            ->statePath('filters');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->dispatch('$refresh')),

            Action::make('export_data')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action('exportData'),
        ];
    }

    public function exportData()
    {
        $data = $this->getAnalyticsData();
        
        $content = $this->generateExportContent($data);
        
        Notification::make()
            ->title('Export Successful')
            ->body('Analytics data exported successfully!')
            ->success()
            ->send();

        return Response::streamDownload(
            fn () => print($content),
            'analytics-export-' . now()->format('Y-m-d-H-i-s') . '.html',
            ['Content-Type' => 'text/html']
        );
    }

    public function getAnalyticsData(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant) return [];

        $dateRange = $this->getDateRange();
        $query = Booking::where('branch_id', $tenant->id);

        // Apply date filter
        if ($dateRange['start'] && $dateRange['end']) {
            $query->whereBetween('appointment_date', [$dateRange['start'], $dateRange['end']]);
        }

        // Apply status filter
        if ($this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        // Apply service filter
        if ($this->filters['service_id']) {
            $query->where('service_id', $this->filters['service_id']);
        }

        // Apply staff filter
        if ($this->filters['staff_id']) {
            $query->where('staff_id', $this->filters['staff_id']);
        }

        $bookings = $query->with(['client', 'service', 'staff', 'payments'])->get();

        // Payment query with filters
        $paymentQuery = Payment::where('branch_id', $tenant->id);
        if ($dateRange['start'] && $dateRange['end']) {
            $paymentQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        if ($this->filters['payment_method'] !== 'all') {
            $paymentQuery->where('payment_method', $this->filters['payment_method']);
        }

        $payments = $paymentQuery->get();

        // Get expense data
        $expenseQuery = Expense::where('branch_id', $tenant->id)->approved();
        if ($dateRange['start'] && $dateRange['end']) {
            $expenseQuery->byDateRange($dateRange['start'], $dateRange['end']);
        }
        $expenses = $expenseQuery->get();

        // Get inventory data
        $inventoryItems = InventoryItem::where('branch_id', $tenant->id)->active()->get();

        return [
            'period' => $this->getPeriodLabel(),
            'bookings' => $bookings,
            'payments' => $payments,
            'expenses' => $expenses,
            'inventory_items' => $inventoryItems,
            'metrics' => $this->calculateMetrics($bookings, $payments, $expenses, $inventoryItems),
            'charts' => $this->generateChartData($bookings, $payments, $expenses),
            'popular_services' => $this->getPopularServices($bookings),
            'staff_performance' => $this->getStaffPerformance($bookings),
            'client_analytics' => $this->getClientAnalytics($bookings),
            'financial_breakdown' => $this->getFinancialBreakdown($payments),
            'expense_analytics' => $this->getExpenseAnalytics($expenses),
            'inventory_analytics' => $this->getInventoryAnalytics($inventoryItems),
        ];
    }

    private function getDateRange(): array
    {
        $range = $this->filters['date_range'];
        
        return match ($range) {
            'today' => ['start' => today(), 'end' => today()],
            'yesterday' => ['start' => today()->subDay(), 'end' => today()->subDay()],
            'this_week' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'last_week' => ['start' => now()->subWeek()->startOfWeek(), 'end' => now()->subWeek()->endOfWeek()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'last_month' => ['start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()],
            'this_year' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            'custom' => [
                'start' => $this->filters['custom_start_date'] ? Carbon::parse($this->filters['custom_start_date']) : null,
                'end' => $this->filters['custom_end_date'] ? Carbon::parse($this->filters['custom_end_date']) : null,
            ],
            default => ['start' => today(), 'end' => today()],
        };
    }

    private function getPeriodLabel(): string
    {
        $range = $this->filters['date_range'];
        $dateRange = $this->getDateRange();
        
        if ($range === 'custom' && $dateRange['start'] && $dateRange['end']) {
            return $dateRange['start']->format('M j, Y') . ' - ' . $dateRange['end']->format('M j, Y');
        }
        
        return match ($range) {
            'today' => 'Today (' . today()->format('M j, Y') . ')',
            'yesterday' => 'Yesterday (' . today()->subDay()->format('M j, Y') . ')',
            'this_week' => 'This Week (' . now()->startOfWeek()->format('M j') . ' - ' . now()->endOfWeek()->format('M j, Y') . ')',
            'last_week' => 'Last Week',
            'this_month' => 'This Month (' . now()->format('M Y') . ')',
            'last_month' => 'Last Month',
            'this_year' => 'This Year (' . now()->format('Y') . ')',
            default => 'Today',
        };
    }

    private function calculateMetrics($bookings, $payments, $expenses, $inventoryItems): array
    {
        $completedBookings = $bookings->where('status', 'completed');
        $completedPayments = $payments->where('status', 'completed');
        $cancelledBookings = $bookings->where('status', 'cancelled');
        
        // Previous period comparison for growth metrics
        $previousPeriod = $this->getPreviousPeriodData();
        
        return [
            // Basic Metrics
            'total_bookings' => $bookings->count(),
            'completed_bookings' => $completedBookings->count(),
            'cancelled_bookings' => $cancelledBookings->count(),
            'no_show_bookings' => $bookings->where('status', 'no_show')->count(),
            'pending_bookings' => $bookings->where('status', 'pending')->count(),
            
            // Financial Metrics
            'total_revenue' => $completedPayments->sum('amount'),
            'average_booking_value' => $completedBookings->avg('total_amount') ?? 0,
            'pending_payments' => $payments->where('status', 'pending')->sum('amount'),
            'refunded_amount' => $payments->where('status', 'refunded')->sum('refund_amount'),
            
            // Performance Metrics
            'completion_rate' => $bookings->count() > 0 ? ($completedBookings->count() / $bookings->count()) * 100 : 0,
            'no_show_rate' => $bookings->count() > 0 ? ($bookings->where('status', 'no_show')->count() / $bookings->count()) * 100 : 0,
            'cancellation_rate' => $bookings->count() > 0 ? ($cancelledBookings->count() / $bookings->count()) * 100 : 0,
            'booking_conversion_rate' => $bookings->count() > 0 ? (($completedBookings->count() + $bookings->where('status', 'confirmed')->count()) / $bookings->count()) * 100 : 0,
            
            // Client Metrics
            'unique_clients' => $bookings->unique('client_id')->count(),
            'new_clients' => $this->getNewClientsCount($bookings),
            'returning_clients' => $this->getReturningClientsCount($bookings),
            'client_retention_rate' => $this->calculateClientRetentionRate($bookings),
            
            // Operational Metrics
            'average_service_duration' => $this->calculateAverageServiceDuration($completedBookings),
            'peak_hour' => $this->findPeakHour($bookings),
            'staff_utilization' => $this->calculateStaffUtilization($bookings),
            
            // Growth Metrics (vs previous period)
            'revenue_growth' => $this->calculateGrowthRate($completedPayments->sum('amount'), $previousPeriod['revenue']),
            'booking_growth' => $this->calculateGrowthRate($bookings->count(), $previousPeriod['bookings']),
            'client_growth' => $this->calculateGrowthRate($bookings->unique('client_id')->count(), $previousPeriod['clients']),
            
            // Expense Metrics
            'total_expenses' => $expenses->sum('amount'),
            'expense_growth' => $this->calculateGrowthRate($expenses->sum('amount'), $previousPeriod['expenses'] ?? 0),
            'profit_margin' => $this->calculateProfitMargin($completedPayments->sum('amount'), $expenses->sum('amount')),
            
            // Inventory Metrics
            'low_stock_items' => $inventoryItems->filter(fn($item) => $item->isLowStock())->count(),
            'inventory_value' => $inventoryItems->sum(fn($item) => $item->getStockValue()),
            'expiring_items' => $inventoryItems->filter(fn($item) => $item->isExpiringSoon())->count(),
            
            // Alert Metrics
            'high_cancellation_alert' => $cancelledBookings->count() > ($bookings->count() * 0.15), // >15% cancellation
            'low_completion_alert' => $completedBookings->count() < ($bookings->count() * 0.75), // <75% completion
            'payment_pending_alert' => $payments->where('status', 'pending')->sum('amount') > 50000, // >50k pending
            'low_stock_alert' => $inventoryItems->filter(fn($item) => $item->isLowStock())->count() > 5,
            'high_expense_alert' => $expenses->sum('amount') > ($completedPayments->sum('amount') * 0.7), // >70% of revenue
        ];
    }

    private function generateChartData($bookings, $payments, $expenses): array
    {
        return [
            'booking_status_chart' => [
                'labels' => ['Completed', 'Cancelled', 'No Show', 'Pending'],
                'data' => [
                    $bookings->where('status', 'completed')->count(),
                    $bookings->where('status', 'cancelled')->count(),
                    $bookings->where('status', 'no_show')->count(),
                    $bookings->where('status', 'pending')->count(),
                ],
                'colors' => ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
            ],
            'payment_methods' => [
                'labels' => ['Cash', 'M-Pesa', 'Card', 'Bank Transfer'],
                'data' => [
                    $payments->where('payment_method', 'cash')->sum('amount'),
                    $payments->where('payment_method', 'mpesa')->sum('amount'),
                    $payments->where('payment_method', 'card')->sum('amount'),
                    $payments->where('payment_method', 'bank_transfer')->sum('amount'),
                ],
                'colors' => ['#059669', '#d97706', '#7c3aed', '#0891b2'],
            ],
            'expense_categories' => [
                'labels' => $expenses->groupBy('category')->keys()->map(fn($cat) => ucfirst($cat))->toArray(),
                'data' => $expenses->groupBy('category')->map(fn($group) => $group->sum('amount'))->values()->toArray(),
                'colors' => ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'],
            ],
            'profit_loss_chart' => [
                'labels' => ['Revenue', 'Expenses', 'Profit'],
                'data' => [
                    $payments->where('status', 'completed')->sum('amount'),
                    $expenses->sum('amount'),
                    $payments->where('status', 'completed')->sum('amount') - $expenses->sum('amount')
                ],
                'colors' => ['#10b981', '#ef4444', '#3b82f6'],
            ],
        ];
    }

    private function getPopularServices($bookings): Collection
    {
        return $bookings->groupBy('service_id')
            ->map(function ($serviceBookings) {
                $service = $serviceBookings->first()->service;
                return [
                    'name' => $service->name ?? 'Unknown',
                    'count' => $serviceBookings->count(),
                    'revenue' => $serviceBookings->where('status', 'completed')->sum('total_amount'),
                ];
            })
            ->sortByDesc('count')
            ->take(5);
    }

    private function getStaffPerformance($bookings): Collection
    {
        return $bookings->groupBy('staff_id')
            ->map(function ($staffBookings) {
                $staff = $staffBookings->first()->staff;
                return [
                    'name' => $staff ? $staff->name : 'Unknown',
                    'total_bookings' => $staffBookings->count(),
                    'completed_bookings' => $staffBookings->where('status', 'completed')->count(),
                    'completion_rate' => $staffBookings->count() > 0 ? 
                        ($staffBookings->where('status', 'completed')->count() / $staffBookings->count()) * 100 : 0,
                    'revenue' => $staffBookings->where('status', 'completed')->sum('total_amount'),
                ];
            })
            ->sortByDesc('completed_bookings')
            ->take(5);
    }

    private function getClientAnalytics($bookings): array
    {
        $clients = $bookings->groupBy('client_id');
        
        return [
            'total_clients' => $clients->count(),
            'new_clients' => $clients->filter(function ($clientBookings) {
                return $clientBookings->first()->client->created_at >= $this->getDateRange()['start'];
            })->count(),
            'returning_clients' => $clients->filter(function ($clientBookings) {
                return $clientBookings->count() > 1;
            })->count(),
            'top_clients' => $clients->map(function ($clientBookings) {
                $client = $clientBookings->first()->client;
                return [
                    'name' => $client->first_name . ' ' . $client->last_name,
                    'booking_count' => $clientBookings->count(),
                    'total_spent' => $clientBookings->where('status', 'completed')->sum('total_amount'),
                ];
            })->sortByDesc('booking_count')->take(5),
        ];
    }

    private function getFinancialBreakdown($payments): array
    {
        $completedPayments = $payments->where('status', 'completed');
        
        return [
            'total_revenue' => $completedPayments->sum('amount'),
            'pending_amount' => $payments->where('status', 'pending')->sum('amount'),
            'failed_amount' => $payments->where('status', 'failed')->sum('amount'),
            'by_method' => [
                'cash' => $completedPayments->where('payment_method', 'cash')->sum('amount'),
                'mpesa' => $completedPayments->where('payment_method', 'mpesa')->sum('amount'),
                'card' => $completedPayments->where('payment_method', 'card')->sum('amount'),
                'bank_transfer' => $completedPayments->where('payment_method', 'bank_transfer')->sum('amount'),
            ],
            'transaction_count' => $completedPayments->count(),
            'average_transaction' => $completedPayments->avg('amount') ?? 0,
        ];
    }

    private function generateExportContent($data): string
    {
        return view('reports.analytics-export', $data)->render();
    }

    // Enhanced Analytics Helper Methods
    private function getPreviousPeriodData(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant) return ['revenue' => 0, 'bookings' => 0, 'clients' => 0];

        $currentRange = $this->getDateRange();
        $daysDiff = $currentRange['start']->diffInDays($currentRange['end']) + 1;
        
        $previousStart = $currentRange['start']->copy()->subDays($daysDiff);
        $previousEnd = $currentRange['end']->copy()->subDays($daysDiff);

        $previousBookings = Booking::where('branch_id', $tenant->id)
            ->whereBetween('appointment_date', [$previousStart, $previousEnd]);

        $previousPayments = Payment::where('branch_id', $tenant->id)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->where('status', 'completed');

        $previousExpenses = Expense::where('branch_id', $tenant->id)
            ->approved()
            ->byDateRange($previousStart, $previousEnd);

        return [
            'revenue' => $previousPayments->sum('amount'),
            'bookings' => $previousBookings->count(),
            'clients' => $previousBookings->distinct('client_id')->count(),
            'expenses' => $previousExpenses->sum('amount'),
        ];
    }

    private function calculateGrowthRate($current, $previous): float
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    private function getNewClientsCount($bookings): int
    {
        $dateRange = $this->getDateRange();
        return $bookings->filter(function($booking) use ($dateRange) {
            return $booking->client && 
                   $booking->client->created_at >= $dateRange['start'] &&
                   $booking->client->created_at <= $dateRange['end'];
        })->unique('client_id')->count();
    }

    private function getReturningClientsCount($bookings): int
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $dateRange = $this->getDateRange();
        
        return $bookings->filter(function($booking) use ($tenant, $dateRange) {
            if (!$booking->client) return false;
            
            // Check if client had bookings before this period
            $previousBookings = Booking::where('branch_id', $tenant->id)
                ->where('client_id', $booking->client_id)
                ->where('appointment_date', '<', $dateRange['start'])
                ->exists();
                
            return $previousBookings;
        })->unique('client_id')->count();
    }

    private function calculateClientRetentionRate($bookings): float
    {
        $newClients = $this->getNewClientsCount($bookings);
        $totalClients = $bookings->unique('client_id')->count();
        
        if ($totalClients == 0) return 0;
        return (($totalClients - $newClients) / $totalClients) * 100;
    }

    private function calculateAverageServiceDuration($bookings): float
    {
        $totalMinutes = $bookings->sum(function($booking) {
            if ($booking->service && $booking->service->duration_minutes) {
                return $booking->service->duration_minutes;
            }
            return 60; // Default 60 minutes if not specified
        });
        
        return $bookings->count() > 0 ? $totalMinutes / $bookings->count() : 0;
    }

    private function findPeakHour($bookings): string
    {
        $hourCounts = [];
        
        foreach ($bookings as $booking) {
            $hour = $booking->start_time ? date('H', strtotime($booking->start_time)) : 12;
            $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
        }
        
        if (empty($hourCounts)) return '12:00';
        
        $peakHour = array_key_first(array_slice(arsort($hourCounts) ? $hourCounts : [], 0, 1, true));
        return sprintf('%02d:00', $peakHour);
    }

    private function calculateStaffUtilization($bookings): float
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $totalStaff = Staff::whereHas('branches', function($query) use ($tenant) {
            $query->where('branch_id', $tenant->id);
        })->count();
        
        $activeStaff = $bookings->where('status', 'completed')->unique('staff_id')->count();
        
        return $totalStaff > 0 ? ($activeStaff / $totalStaff) * 100 : 0;
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'filters.')) {
            $this->dispatch('filtersUpdated');
        }
    }

    private function calculateProfitMargin(float $revenue, float $expenses): float
    {
        if ($revenue <= 0) return 0;
        return (($revenue - $expenses) / $revenue) * 100;
    }

    private function getExpenseAnalytics($expenses): array
    {
        $categorized = $expenses->groupBy('category');
        
        return [
            'total_expenses' => $expenses->sum('amount'),
            'by_category' => $categorized->map(function($categoryExpenses, $category) {
                return [
                    'name' => Expense::getCategories()[$category] ?? ucfirst($category),
                    'amount' => $categoryExpenses->sum('amount'),
                    'count' => $categoryExpenses->count(),
                    'percentage' => $expenses->sum('amount') > 0 ? 
                        ($categoryExpenses->sum('amount') / $expenses->sum('amount')) * 100 : 0
                ];
            })->sortByDesc('amount')->take(5),
            'top_vendors' => $expenses->whereNotNull('vendor_name')
                ->groupBy('vendor_name')
                ->map(function($vendorExpenses, $vendor) {
                    return [
                        'name' => $vendor,
                        'amount' => $vendorExpenses->sum('amount'),
                        'transactions' => $vendorExpenses->count()
                    ];
                })->sortByDesc('amount')->take(5),
            'monthly_trend' => $this->getExpenseMonthlyTrend($expenses)
        ];
    }

    private function getInventoryAnalytics($inventoryItems): array
    {
        $lowStockItems = $inventoryItems->filter(fn($item) => $item->isLowStock());
        $expiringItems = $inventoryItems->filter(fn($item) => $item->isExpiringSoon());
        
        return [
            'total_items' => $inventoryItems->count(),
            'total_value' => $inventoryItems->sum(fn($item) => $item->getStockValue()),
            'low_stock_count' => $lowStockItems->count(),
            'expiring_count' => $expiringItems->count(),
            'by_category' => $inventoryItems->groupBy('category')
                ->map(function($categoryItems, $category) {
                    return [
                        'name' => InventoryItem::getCategories()[$category] ?? ucfirst($category),
                        'count' => $categoryItems->count(),
                        'value' => $categoryItems->sum(fn($item) => $item->getStockValue()),
                        'low_stock' => $categoryItems->filter(fn($item) => $item->isLowStock())->count()
                    ];
                })->sortByDesc('value'),
            'low_stock_items' => $lowStockItems->map(function($item) {
                return [
                    'name' => $item->name,
                    'current_stock' => $item->current_stock,
                    'minimum_stock' => $item->minimum_stock,
                    'status' => $item->getStockStatus()
                ];
            })->take(10),
            'expiring_items' => $expiringItems->map(function($item) {
                return [
                    'name' => $item->name,
                    'expiry_date' => $item->expiry_date->format('M j, Y'),
                    'days_to_expiry' => $item->expiry_date->diffInDays(now()),
                    'current_stock' => $item->current_stock
                ];
            })->take(10)
        ];
    }

    private function getExpenseMonthlyTrend($expenses): array
    {
        return $expenses->groupBy(function($expense) {
            return $expense->expense_date->format('M Y');
        })->map(function($monthExpenses, $month) {
            return [
                'month' => $month,
                'amount' => $monthExpenses->sum('amount'),
                'count' => $monthExpenses->count()
            ];
        })->values()->toArray();
    }
}