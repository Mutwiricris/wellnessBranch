<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-700 rounded-2xl p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Welcome back! 👋</h1>
                    <p class="text-blue-100 text-lg">Here's what's happening at {{ \Filament\Facades\Filament::getTenant()?->name ?? 'your spa' }} today</p>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold">{{ date('d') }}</div>
                        <div class="text-blue-200 text-sm">{{ date('M Y') }}</div>
                    </div>
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <x-heroicon-o-calendar-days class="w-8 h-8" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Today's Revenue Card -->
            <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium">Today's Revenue</p>
                        <div class="text-2xl font-bold mt-2" id="today-revenue">KES 0</div>
                        <p class="text-emerald-200 text-xs mt-1">+12% from yesterday</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-currency-dollar class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Total Bookings Card -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Today's Bookings</p>
                        <div class="text-2xl font-bold mt-2" id="today-bookings">0</div>
                        <p class="text-blue-200 text-xs mt-1">3 pending approval</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-calendar-days class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Staff Utilization Card -->
            <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Staff Utilization</p>
                        <div class="text-2xl font-bold mt-2" id="staff-utilization">0%</div>
                        <p class="text-purple-200 text-xs mt-1">5 of 8 staff active</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-users class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Satisfaction Score Card -->
            <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Satisfaction</p>
                        <div class="text-2xl font-bold mt-2">4.8⭐</div>
                        <p class="text-orange-200 text-xs mt-1">Based on 45 reviews</p>
                    </div>
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-heart class="w-6 h-6" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Stats Widgets -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <div class="lg:col-span-1">
                <x-filament-widgets::widgets
                    :widgets="[\App\Filament\Widgets\TodayStatsWidget::class]"
                />
            </div>
            <div class="lg:col-span-1">
                <x-filament-widgets::widgets
                    :widgets="[\App\Filament\Widgets\StaffPerformanceWidget::class]"
                />
            </div>
            <div class="lg:col-span-1">
                <x-filament-widgets::widgets
                    :widgets="[\App\Filament\Widgets\ClientRetentionWidget::class]"
                />
            </div>
            <div class="lg:col-span-1">
                <x-filament-widgets::widgets
                    :widgets="[\App\Filament\Widgets\BusinessInsightsWidget::class]"
                />
            </div>
        </div>

        <!-- Latest Activities Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Latest Bookings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <x-filament-widgets::widgets
                    :widgets="[\App\Filament\Widgets\LatestBookingsWidget::class]"
                />
            </div>
            
            <!-- Recent Transactions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <x-filament-widgets::widgets
                    :widgets="[\App\Filament\Widgets\RecentTransactionsWidget::class]"
                />
            </div>
        </div>

        <!-- Secondary Widgets Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Popular Services - 2 columns -->
            <div class="lg:col-span-2">
                <x-filament-widgets::widgets
                    :widgets="[\App\Filament\Widgets\PopularServicesWidget::class]"
                />
            </div>
            
            <!-- Quick Actions - 2 columns -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <x-heroicon-o-bolt class="w-5 h-5 text-blue-500" />
                        Quick Actions
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="{{ \App\Filament\Resources\BookingResource::getUrl('create') }}" 
                           class="flex items-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 dark:hover:from-blue-900/30 dark:hover:to-indigo-900/30 rounded-lg transition-all duration-200 group border border-blue-100 dark:border-blue-800">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-105 transition-transform shadow-md">
                                <x-heroicon-o-plus class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">New Booking</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Schedule appointment</div>
                            </div>
                        </a>
                        
                        <a href="{{ \App\Filament\Pages\PosTerminal::getUrl() }}" 
                           class="flex items-center p-4 bg-gradient-to-r from-green-50 to-emerald-50 hover:from-green-100 hover:to-emerald-100 dark:from-green-900/20 dark:to-emerald-900/20 dark:hover:from-green-900/30 dark:hover:to-emerald-900/30 rounded-lg transition-all duration-200 group border border-green-100 dark:border-green-800">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-105 transition-transform shadow-md">
                                <x-heroicon-o-device-tablet class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">POS Terminal</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Process payments</div>
                            </div>
                        </a>
                        
                        <a href="{{ \App\Filament\Resources\ServiceResource::getUrl() }}" 
                           class="flex items-center p-4 bg-gradient-to-r from-purple-50 to-pink-50 hover:from-purple-100 hover:to-pink-100 dark:from-purple-900/20 dark:to-pink-900/20 dark:hover:from-purple-900/30 dark:hover:to-pink-900/30 rounded-lg transition-all duration-200 group border border-purple-100 dark:border-purple-800">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-105 transition-transform shadow-md">
                                <x-heroicon-o-sparkles class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Services</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Manage menu</div>
                            </div>
                        </a>
                        
                        <a href="{{ \App\Filament\Resources\GiftVoucherResource::getUrl() }}" 
                           class="flex items-center p-4 bg-gradient-to-r from-yellow-50 to-orange-50 hover:from-yellow-100 hover:to-orange-100 dark:from-yellow-900/20 dark:to-orange-900/20 dark:hover:from-yellow-900/30 dark:hover:to-orange-900/30 rounded-lg transition-all duration-200 group border border-yellow-100 dark:border-yellow-800">
                            <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-105 transition-transform shadow-md">
                                <x-heroicon-o-gift class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Gift Vouchers</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Manage vouchers</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Width Widgets -->
        <div class="space-y-6">
            <x-filament-widgets::widgets
                :widgets="[
                    \App\Filament\Widgets\PeakHoursWidget::class,
                    \App\Filament\Widgets\NotificationCenterWidget::class,
                ]"
            />
        </div>
    </div>

    <style>
        /* Custom animations for cards */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-in-up {
            animation: slideInUp 0.5s ease-out;
        }
        
        /* Hover effects for cards */
        .group:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease-out;
        }
    </style>

    <script>
        // Simple animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('[class*="bg-gradient-to-br"]');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-slide-in-up');
                }, index * 100);
            });
        });
    </script>
</x-filament-panels::page>