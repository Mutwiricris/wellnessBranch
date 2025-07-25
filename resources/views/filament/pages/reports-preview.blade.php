<div class="space-y-6">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Report Preview</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($data['total_bookings'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Bookings</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    KES {{ number_format($data['total_revenue'] ?? 0, 2) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ $data['branch_name'] ?? 'N/A' }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Branch</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                    {{ $data['date_range'] ?? 'N/A' }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Date Range</div>
            </div>
        </div>
    </div>
    
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center gap-2">
            <x-heroicon-o-information-circle class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            <span class="text-blue-800 dark:text-blue-200 font-medium">Preview Summary</span>
        </div>
        <p class="text-blue-700 dark:text-blue-300 text-sm mt-2">
            This preview shows basic metrics for your selected date range. The full report will include detailed breakdowns, charts, and comprehensive analytics.
        </p>
    </div>
</div>