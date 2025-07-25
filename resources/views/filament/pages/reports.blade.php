<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Report Configuration
            </h2>
            
            {{ $this->form }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm">Available Reports</p>
                        <p class="text-2xl font-bold">4</p>
                    </div>
                    <x-heroicon-o-document-chart-bar class="h-8 w-8 text-blue-200" />
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm">Export Formats</p>
                        <p class="text-2xl font-bold">3</p>
                    </div>
                    <x-heroicon-o-arrow-down-tray class="h-8 w-8 text-green-200" />
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm">Real-time Data</p>
                        <p class="text-2xl font-bold">Live</p>
                    </div>
                    <x-heroicon-o-arrow-path class="h-8 w-8 text-purple-200" />
                </div>
            </div>

            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm">Scheduled Reports</p>
                        <p class="text-2xl font-bold">Soon</p>
                    </div>
                    <x-heroicon-o-clock class="h-8 w-8 text-orange-200" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Report Types Overview
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                            <x-heroicon-o-document-text class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Detailed Report</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Complete booking information with client details, service history, and comprehensive data analysis.
                    </p>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                            <x-heroicon-o-chart-bar class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Summary Report</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Overview statistics and key metrics with performance indicators and trend analysis.
                    </p>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                            <x-heroicon-o-banknotes class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Financial Report</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Revenue, payments, and financial analytics with payment method breakdowns and trends.
                    </p>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                            <x-heroicon-o-users class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Staff Report</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Performance metrics and utilization rates for staff members with productivity insights.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <x-heroicon-o-information-circle class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                <div>
                    <h4 class="font-medium text-blue-900 dark:text-blue-100">Report Generation Tips</h4>
                    <ul class="text-sm text-blue-700 dark:text-blue-300 mt-2 space-y-1">
                        <li>• Use "Preview Data" to see a summary before generating the full report</li>
                        <li>• Select appropriate date ranges for meaningful insights</li>
                        <li>• PDF format is recommended for sharing and printing</li>
                        <li>• Include additional notes for context and reference</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>