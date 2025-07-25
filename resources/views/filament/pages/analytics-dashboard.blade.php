<x-filament-panels::page>
    <div class="space-y-6" x-data="{ analyticsData: {} }" 
         x-init="
            analyticsData = @js($this->getAnalyticsData());
            $wire.on('filtersUpdated', () => {
                setTimeout(() => {
                    analyticsData = @js($this->getAnalyticsData());
                }, 100);
            });
         ">
        
        <!-- Filters Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            {{ $this->form }}
        </div>

        <!-- Period Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold" x-text="analyticsData.period || 'Analytics Dashboard'"></h2>
                    <p class="text-blue-100 mt-1">Comprehensive business insights and metrics</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar-square class="h-8 w-8 text-blue-200" />
                </div>
            </div>
        </div>

        <!-- Alert Banners -->
        <div x-show="analyticsData.metrics?.high_cancellation_alert || analyticsData.metrics?.low_completion_alert || analyticsData.metrics?.payment_pending_alert" class="space-y-3">
            <div x-show="analyticsData.metrics?.high_cancellation_alert" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400" />
                    <div>
                        <h4 class="font-medium text-red-900 dark:text-red-100">High Cancellation Alert</h4>
                        <p class="text-sm text-red-700 dark:text-red-300">Cancellation rate is above 15%. Consider reviewing booking policies or following up with clients.</p>
                    </div>
                </div>
            </div>

            <div x-show="analyticsData.metrics?.low_completion_alert" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                    <div>
                        <h4 class="font-medium text-yellow-900 dark:text-yellow-100">Low Completion Rate</h4>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">Completion rate is below 75%. Review service delivery and client satisfaction.</p>
                    </div>
                </div>
            </div>

            <div x-show="analyticsData.metrics?.payment_pending_alert" class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                    <div>
                        <h4 class="font-medium text-orange-900 dark:text-orange-100">High Pending Payments</h4>
                        <p class="text-sm text-orange-700 dark:text-orange-300">Over KES 50,000 in pending payments. Follow up on outstanding transactions.</p>
                    </div>
                </div>
            </div>

            <div x-show="analyticsData.metrics?.low_stock_alert" class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    <div>
                        <h4 class="font-medium text-purple-900 dark:text-purple-100">Low Stock Alert</h4>
                        <p class="text-sm text-purple-700 dark:text-purple-300">Multiple items are running low. Check inventory and reorder supplies.</p>
                    </div>
                </div>
            </div>

            <div x-show="analyticsData.metrics?.high_expense_alert" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400" />
                    <div>
                        <h4 class="font-medium text-red-900 dark:text-red-100">High Expense Alert</h4>
                        <p class="text-sm text-red-700 dark:text-red-300">Expenses are over 70% of revenue. Review spending and optimize costs.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Key Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8 gap-4">
            <!-- Revenue with Growth -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-green-600" x-text="'KES ' + (analyticsData.metrics?.total_revenue || 0).toLocaleString()"></p>
                    </div>
                    <x-heroicon-o-banknotes class="h-8 w-8 text-green-500" />
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs font-medium" 
                          :class="(analyticsData.metrics?.revenue_growth || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
                          x-text="((analyticsData.metrics?.revenue_growth || 0) >= 0 ? '↗ ' : '↘ ') + Math.abs(analyticsData.metrics?.revenue_growth || 0).toFixed(1) + '%'">
                    </span>
                    <span class="text-xs text-gray-500">vs previous period</span>
                </div>
            </div>

            <!-- Bookings with Growth -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Bookings</p>
                        <p class="text-2xl font-bold text-blue-600" x-text="analyticsData.metrics?.total_bookings || 0"></p>
                    </div>
                    <x-heroicon-o-calendar-days class="h-8 w-8 text-blue-500" />
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs font-medium" 
                          :class="(analyticsData.metrics?.booking_growth || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
                          x-text="((analyticsData.metrics?.booking_growth || 0) >= 0 ? '↗ ' : '↘ ') + Math.abs(analyticsData.metrics?.booking_growth || 0).toFixed(1) + '%'">
                    </span>
                    <span class="text-xs text-gray-500">vs previous period</span>
                </div>
            </div>

            <!-- Completion Rate -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Completion Rate</p>
                        <p class="text-2xl font-bold" 
                           :class="(analyticsData.metrics?.completion_rate || 0) >= 75 ? 'text-green-600' : 'text-yellow-600'"
                           x-text="(analyticsData.metrics?.completion_rate || 0).toFixed(1) + '%'">
                        </p>
                    </div>
                    <x-heroicon-o-chart-pie class="h-8 w-8 text-blue-500" />
                </div>
                <div class="text-xs text-gray-500" x-text="analyticsData.metrics?.completed_bookings + ' completed'"></div>
            </div>

            <!-- Average Booking Value -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Avg. Booking Value</p>
                        <p class="text-2xl font-bold text-purple-600" x-text="'KES ' + (analyticsData.metrics?.average_booking_value || 0).toFixed(0)"></p>
                    </div>
                    <x-heroicon-o-currency-dollar class="h-8 w-8 text-purple-500" />
                </div>
                <div class="text-xs text-gray-500">Per completed booking</div>
            </div>

            <!-- Client Retention -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Client Retention</p>
                        <p class="text-2xl font-bold" 
                           :class="(analyticsData.metrics?.client_retention_rate || 0) >= 60 ? 'text-green-600' : 'text-yellow-600'"
                           x-text="(analyticsData.metrics?.client_retention_rate || 0).toFixed(1) + '%'">
                        </p>
                    </div>
                    <x-heroicon-o-heart class="h-8 w-8 text-pink-500" />
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs font-medium" 
                          :class="(analyticsData.metrics?.client_growth || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
                          x-text="((analyticsData.metrics?.client_growth || 0) >= 0 ? '↗ ' : '↘ ') + Math.abs(analyticsData.metrics?.client_growth || 0).toFixed(1) + '%'">
                    </span>
                    <span class="text-xs text-gray-500">client growth</span>
                </div>
            </div>

            <!-- Staff Utilization -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Staff Utilization</p>
                        <p class="text-2xl font-bold" 
                           :class="(analyticsData.metrics?.staff_utilization || 0) >= 70 ? 'text-green-600' : 'text-yellow-600'"
                           x-text="(analyticsData.metrics?.staff_utilization || 0).toFixed(1) + '%'">
                        </p>
                    </div>
                    <x-heroicon-o-users class="h-8 w-8 text-indigo-500" />
                </div>
                <div class="text-xs text-gray-500">Average efficiency</div>
            </div>

            <!-- Total Expenses -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Expenses</p>
                        <p class="text-2xl font-bold text-red-600" x-text="'KES ' + (analyticsData.metrics?.total_expenses || 0).toLocaleString()"></p>
                    </div>
                    <x-heroicon-o-arrow-trending-down class="h-8 w-8 text-red-500" />
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs font-medium" 
                          :class="(analyticsData.metrics?.expense_growth || 0) >= 0 ? 'text-red-600' : 'text-green-600'"
                          x-text="((analyticsData.metrics?.expense_growth || 0) >= 0 ? '↗ ' : '↘ ') + Math.abs(analyticsData.metrics?.expense_growth || 0).toFixed(1) + '%'">
                    </span>
                    <span class="text-xs text-gray-500">vs previous period</span>
                </div>
            </div>

            <!-- Profit Margin -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Profit Margin</p>
                        <p class="text-2xl font-bold" 
                           :class="(analyticsData.metrics?.profit_margin || 0) >= 20 ? 'text-green-600' : 'text-yellow-600'"
                           x-text="(analyticsData.metrics?.profit_margin || 0).toFixed(1) + '%'">
                        </p>
                    </div>
                    <x-heroicon-o-chart-bar class="h-8 w-8 text-green-500" />
                </div>
                <div class="text-xs text-gray-500">Revenue minus expenses</div>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 rounded-lg p-6 border border-indigo-200 dark:border-indigo-800">
            <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-4 flex items-center gap-2">
                <x-heroicon-o-light-bulb class="h-5 w-5" />
                Actionable Insights
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Peak Hour</h4>
                    <p class="text-2xl font-bold text-indigo-600" x-text="analyticsData.metrics?.peak_hour || '12:00'"></p>
                    <p class="text-xs text-gray-500">Busiest time of day</p>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Avg. Service Time</h4>
                    <p class="text-2xl font-bold text-indigo-600" x-text="(analyticsData.metrics?.average_service_duration || 0).toFixed(0) + ' min'"></p>
                    <p class="text-xs text-gray-500">Per completed service</p>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">No-Show Rate</h4>
                    <p class="text-2xl font-bold" 
                       :class="(analyticsData.metrics?.no_show_rate || 0) <= 5 ? 'text-green-600' : 'text-red-600'"
                       x-text="(analyticsData.metrics?.no_show_rate || 0).toFixed(1) + '%'">
                    </p>
                    <p class="text-xs text-gray-500" x-text="analyticsData.metrics?.no_show_bookings + ' no-shows'"></p>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Conversion Rate</h4>
                    <p class="text-2xl font-bold" 
                       :class="(analyticsData.metrics?.booking_conversion_rate || 0) >= 80 ? 'text-green-600' : 'text-yellow-600'"
                       x-text="(analyticsData.metrics?.booking_conversion_rate || 0).toFixed(1) + '%'">
                    </p>
                    <p class="text-xs text-gray-500">Bookings to completion</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <!-- Booking Status Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Booking Status Distribution</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="bookingStatusChart" width="300" height="200"></canvas>
                </div>
            </div>

            <!-- Payment Methods Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Revenue by Payment Method</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="paymentMethodsChart" width="300" height="200"></canvas>
                </div>
            </div>

            <!-- Expense Categories Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Expenses by Category</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="expenseCategoriesChart" width="300" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Profit/Loss Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Revenue vs Expenses Analysis</h3>
            <div class="h-80 flex items-center justify-center">
                <canvas id="profitLossChart" width="800" height="300"></canvas>
            </div>
        </div>

        <!-- Detailed Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Popular Services -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Popular Services</h3>
                <div class="space-y-3">
                    <template x-for="(service, index) in Object.values(analyticsData.popular_services || {})" :key="index">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-gray-100" x-text="service.name"></p>
                                <p class="text-sm text-gray-500" x-text="service.count + ' bookings'"></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600" x-text="'KES ' + (service.revenue || 0).toLocaleString()"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Staff Performance -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Staff Performance</h3>
                <div class="space-y-3">
                    <template x-for="(staff, index) in Object.values(analyticsData.staff_performance || {})" :key="index">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-gray-100" x-text="staff.name"></p>
                                <p class="text-sm text-gray-500" x-text="staff.completed_bookings + '/' + staff.total_bookings + ' completed'"></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-blue-600" x-text="(staff.completion_rate || 0).toFixed(1) + '%'"></p>
                                <p class="text-sm text-green-600" x-text="'KES ' + (staff.revenue || 0).toLocaleString()"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Client Analytics & Financial Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Client Analytics -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Client Analytics</h3>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600" x-text="analyticsData.client_analytics?.total_clients || 0"></p>
                        <p class="text-sm text-blue-700 dark:text-blue-300">Total Clients</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-green-600" x-text="analyticsData.client_analytics?.new_clients || 0"></p>
                        <p class="text-sm text-green-700 dark:text-green-300">New Clients</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600" x-text="analyticsData.client_analytics?.returning_clients || 0"></p>
                        <p class="text-sm text-purple-700 dark:text-purple-300">Returning</p>
                    </div>
                </div>
                
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Top Clients</h4>
                <div class="space-y-2">
                    <template x-for="(client, index) in Object.values(analyticsData.client_analytics?.top_clients || {})" :key="index">
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                            <div>
                                <p class="font-medium text-sm text-gray-900 dark:text-gray-100" x-text="client.name"></p>
                                <p class="text-xs text-gray-500" x-text="client.booking_count + ' bookings'"></p>
                            </div>
                            <p class="text-sm font-bold text-green-600" x-text="'KES ' + (client.total_spent || 0).toLocaleString()"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Financial Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Financial Breakdown</h3>
                <div class="space-y-4">
                    <!-- Revenue Status -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <p class="text-lg font-bold text-green-600" x-text="'KES ' + (analyticsData.financial_breakdown?.total_revenue || 0).toLocaleString()"></p>
                            <p class="text-xs text-green-700 dark:text-green-300">Completed</p>
                        </div>
                        <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <p class="text-lg font-bold text-yellow-600" x-text="'KES ' + (analyticsData.financial_breakdown?.pending_amount || 0).toLocaleString()"></p>
                            <p class="text-xs text-yellow-700 dark:text-yellow-300">Pending</p>
                        </div>
                        <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <p class="text-lg font-bold text-red-600" x-text="'KES ' + (analyticsData.financial_breakdown?.failed_amount || 0).toLocaleString()"></p>
                            <p class="text-xs text-red-700 dark:text-red-300">Failed</p>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">By Payment Method</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Cash</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="'KES ' + (analyticsData.financial_breakdown?.by_method?.cash || 0).toLocaleString()"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">M-Pesa</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="'KES ' + (analyticsData.financial_breakdown?.by_method?.mpesa || 0).toLocaleString()"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Card</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="'KES ' + (analyticsData.financial_breakdown?.by_method?.card || 0).toLocaleString()"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Bank Transfer</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="'KES ' + (analyticsData.financial_breakdown?.by_method?.bank_transfer || 0).toLocaleString()"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Stats -->
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Transactions</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100" x-text="analyticsData.financial_breakdown?.transaction_count || 0"></span>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Average Transaction</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100" x-text="'KES ' + (analyticsData.financial_breakdown?.average_transaction || 0).toFixed(2)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense & Inventory Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Expense Analytics -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Expense Breakdown</h3>
                
                <!-- Expense Summary -->
                <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-red-900 dark:text-red-100">Total Expenses</span>
                        <span class="text-lg font-bold text-red-600" x-text="'KES ' + (analyticsData.expense_analytics?.total_expenses || 0).toLocaleString()"></span>
                    </div>
                </div>

                <!-- Top Categories -->
                <div class="space-y-3">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Top Categories</h4>
                    <template x-for="(category, index) in Object.values(analyticsData.expense_analytics?.by_category || {})" :key="index">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-gray-100" x-text="category.name"></p>
                                <p class="text-sm text-gray-500" x-text="category.count + ' transactions'"></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-red-600" x-text="'KES ' + (category.amount || 0).toLocaleString()"></p>
                                <p class="text-xs text-gray-500" x-text="(category.percentage || 0).toFixed(1) + '%'"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Inventory Analytics -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Inventory Status</h3>
                
                <!-- Inventory Summary -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-lg font-bold text-blue-600" x-text="analyticsData.inventory_analytics?.total_items || 0"></p>
                        <p class="text-xs text-blue-700 dark:text-blue-300">Total Items</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <p class="text-lg font-bold text-purple-600" x-text="'KES ' + (analyticsData.inventory_analytics?.total_value || 0).toLocaleString()"></p>
                        <p class="text-xs text-purple-700 dark:text-purple-300">Total Value</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <p class="text-lg font-bold text-red-600" x-text="analyticsData.inventory_analytics?.low_stock_count || 0"></p>
                        <p class="text-xs text-red-700 dark:text-red-300">Low Stock</p>
                    </div>
                </div>

                <!-- Low Stock Items -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Low Stock Items</h4>
                    <template x-for="(item, index) in Object.values(analyticsData.inventory_analytics?.low_stock_items || {})" :key="index">
                        <div class="flex items-center justify-between p-2 bg-red-50 dark:bg-red-900/20 rounded border-l-4 border-red-400">
                            <div>
                                <p class="font-medium text-sm text-gray-900 dark:text-gray-100" x-text="item.name"></p>
                                <p class="text-xs text-red-600" x-text="'Stock: ' + item.current_stock + ' (Min: ' + item.minimum_stock + ')'"></p>
                            </div>
                            <span class="text-xs px-2 py-1 bg-red-600 text-white rounded" x-text="item.status.replace('_', ' ').toUpperCase()"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let bookingStatusChart = null;
            let paymentMethodsChart = null;
            let expenseCategoriesChart = null;
            let profitLossChart = null;

            function updateCharts() {
                const analyticsData = @json($this->getAnalyticsData());
                
                // Booking Status Chart
                const bookingCtx = document.getElementById('bookingStatusChart');
                if (bookingCtx && analyticsData.charts?.booking_status_chart) {
                    if (bookingStatusChart) bookingStatusChart.destroy();
                    bookingStatusChart = new Chart(bookingCtx, {
                        type: 'doughnut',
                        data: {
                            labels: analyticsData.charts.booking_status_chart.labels,
                            datasets: [{
                                data: analyticsData.charts.booking_status_chart.data,
                                backgroundColor: analyticsData.charts.booking_status_chart.colors,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }

                // Payment Methods Chart  
                const paymentCtx = document.getElementById('paymentMethodsChart');
                if (paymentCtx && analyticsData.charts?.payment_methods) {
                    if (paymentMethodsChart) paymentMethodsChart.destroy();
                    paymentMethodsChart = new Chart(paymentCtx, {
                        type: 'bar',
                        data: {
                            labels: analyticsData.charts.payment_methods.labels,
                            datasets: [{
                                label: 'Revenue (KES)',
                                data: analyticsData.charts.payment_methods.data,
                                backgroundColor: analyticsData.charts.payment_methods.colors,
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'KES ' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Expense Categories Chart
                const expenseCtx = document.getElementById('expenseCategoriesChart');
                if (expenseCtx && analyticsData.charts?.expense_categories) {
                    if (expenseCategoriesChart) expenseCategoriesChart.destroy();
                    expenseCategoriesChart = new Chart(expenseCtx, {
                        type: 'doughnut',
                        data: {
                            labels: analyticsData.charts.expense_categories.labels,
                            datasets: [{
                                data: analyticsData.charts.expense_categories.data,
                                backgroundColor: analyticsData.charts.expense_categories.colors,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }

                // Profit/Loss Chart
                const profitCtx = document.getElementById('profitLossChart');
                if (profitCtx && analyticsData.charts?.profit_loss_chart) {
                    if (profitLossChart) profitLossChart.destroy();
                    profitLossChart = new Chart(profitCtx, {
                        type: 'bar',
                        data: {
                            labels: analyticsData.charts.profit_loss_chart.labels,
                            datasets: [{
                                label: 'Amount (KES)',
                                data: analyticsData.charts.profit_loss_chart.data,
                                backgroundColor: analyticsData.charts.profit_loss_chart.colors,
                                borderWidth: 1,
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'KES ' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            updateCharts();
            
            // Listen for filter updates
            window.addEventListener('filtersUpdated', updateCharts);
        });
    </script>
</x-filament-panels::page>