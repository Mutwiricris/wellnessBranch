<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🔔 Notification Settings & Testing
        </x-slot>

        <div class="space-y-6">
            {{-- Browser Notification Permission --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">Browser Notifications</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-800">Enable desktop notifications to receive alerts even when this tab is not active.</p>
                        <p class="text-sm text-blue-600 mt-1">Status: <span id="notification-permission-status">Checking...</span></p>
                    </div>
                    <button 
                        wire:click="enableBrowserNotifications"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Enable Notifications
                    </button>
                </div>
            </div>

            {{-- Notification Testing --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🧪 Test Notifications</h3>
                <p class="text-gray-600 mb-4">Test different types of notifications to ensure they're working correctly:</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {{-- Basic Notification Test --}}
                    <div class="border border-gray-200 rounded-lg p-4 text-center">
                        <div class="text-4xl mb-2">🔔</div>
                        <h4 class="font-medium text-gray-900 mb-2">Basic Notification</h4>
                        <p class="text-sm text-gray-600 mb-3">Test standard notification display</p>
                        <button 
                            wire:click="testNotification"
                            class="w-full px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                        >
                            Test Notification
                        </button>
                    </div>

                    {{-- Sound Test --}}
                    <div class="border border-gray-200 rounded-lg p-4 text-center">
                        <div class="text-4xl mb-2">🔊</div>
                        <h4 class="font-medium text-gray-900 mb-2">Sound Test</h4>
                        <p class="text-sm text-gray-600 mb-3">Test notification sound</p>
                        <button 
                            wire:click="testSound"
                            class="w-full px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors"
                        >
                            Test Sound
                        </button>
                    </div>

                    {{-- New Booking Notification --}}
                    <div class="border border-green-200 rounded-lg p-4 text-center bg-green-50">
                        <div class="text-4xl mb-2">📅</div>
                        <h4 class="font-medium text-green-900 mb-2">New Booking</h4>
                        <p class="text-sm text-green-700 mb-3">Test booking notification</p>
                        <button 
                            wire:click="showBookingNotification"
                            class="w-full px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                        >
                            Test Booking Alert
                        </button>
                    </div>

                    {{-- Payment Notification --}}
                    <div class="border border-emerald-200 rounded-lg p-4 text-center bg-emerald-50">
                        <div class="text-4xl mb-2">💰</div>
                        <h4 class="font-medium text-emerald-900 mb-2">Payment Received</h4>
                        <p class="text-sm text-emerald-700 mb-3">Test payment notification</p>
                        <button 
                            wire:click="showPaymentNotification"
                            class="w-full px-3 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition-colors"
                        >
                            Test Payment Alert
                        </button>
                    </div>

                    {{-- Reminder Notification --}}
                    <div class="border border-yellow-200 rounded-lg p-4 text-center bg-yellow-50">
                        <div class="text-4xl mb-2">⏰</div>
                        <h4 class="font-medium text-yellow-900 mb-2">Reminder</h4>
                        <p class="text-sm text-yellow-700 mb-3">Test reminder notification</p>
                        <button 
                            wire:click="showReminderNotification"
                            class="w-full px-3 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors"
                        >
                            Test Reminder
                        </button>
                    </div>

                    {{-- Error Notification --}}
                    <div class="border border-red-200 rounded-lg p-4 text-center bg-red-50">
                        <div class="text-4xl mb-2">❌</div>
                        <h4 class="font-medium text-red-900 mb-2">Error Alert</h4>
                        <p class="text-sm text-red-700 mb-3">Test error notification</p>
                        <button 
                            wire:click="showErrorNotification"
                            class="w-full px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
                        >
                            Test Error Alert
                        </button>
                    </div>
                </div>
            </div>

            {{-- Audio Settings --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🎵 Audio Settings</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Enable Sound Notifications</label>
                            <p class="text-xs text-gray-500">Play audio alerts for important notifications</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="sound-enabled" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Volume Level</label>
                            <p class="text-xs text-gray-500">Adjust notification sound volume</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">🔉</span>
                            <input 
                                type="range" 
                                id="volume-slider" 
                                min="0" 
                                max="100" 
                                value="70"
                                class="w-24 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                            >
                            <span class="text-sm text-gray-500">🔊</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">⚡ Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button 
                        onclick="clearAllNotifications()"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        Clear All Notifications
                    </button>
                    <button 
                        onclick="resetNotificationSettings()"
                        class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
                    >
                        Reset Settings
                    </button>
                    <button 
                        onclick="testAllNotifications()"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                    >
                        Test All Types
                    </button>
                </div>
            </div>
        </div>
    </x-filament::section>

    @push('scripts')
    <script>
        // Initialize notification system
        document.addEventListener('DOMContentLoaded', function() {
            checkNotificationPermission();
            
            // Listen for Livewire events
            Livewire.on('play-notification-sound', (event) => {
                if (window.notificationSystem) {
                    window.notificationSystem.playSound(event.sound);
                }
            });
            
            Livewire.on('request-notification-permission', () => {
                if ('Notification' in window) {
                    Notification.requestPermission().then(function(permission) {
                        checkNotificationPermission();
                    });
                }
            });
        });

        function checkNotificationPermission() {
            const statusElement = document.getElementById('notification-permission-status');
            if (statusElement && 'Notification' in window) {
                let status = Notification.permission;
                let statusText = '';
                
                switch (status) {
                    case 'granted':
                        statusText = '✅ Enabled';
                        break;
                    case 'denied':
                        statusText = '❌ Blocked';
                        break;
                    case 'default':
                        statusText = '⏳ Not requested';
                        break;
                }
                
                statusElement.textContent = statusText;
            }
        }

        function clearAllNotifications() {
            // Clear any existing toast notifications
            const container = document.getElementById('notification-container');
            if (container) {
                container.innerHTML = '';
            }
            
            // Show confirmation
            if (window.notificationSystem) {
                window.notificationSystem.show({
                    title: 'Notifications Cleared',
                    message: 'All notifications have been cleared',
                    type: 'info',
                    sound: 'reminder',
                    icon: '🧹',
                    timestamp: new Date().toISOString()
                });
            }
        }

        function resetNotificationSettings() {
            // Reset audio settings
            document.getElementById('sound-enabled').checked = true;
            document.getElementById('volume-slider').value = 70;
            
            // Update notification system settings
            if (window.notificationSystem) {
                window.notificationSystem.updateSettings({
                    soundEnabled: true,
                    volume: 0.7,
                    browserNotificationsEnabled: true
                });
                
                window.notificationSystem.show({
                    title: 'Settings Reset',
                    message: 'Notification settings have been reset to defaults',
                    type: 'success',
                    sound: 'reminder',
                    icon: '🔄',
                    timestamp: new Date().toISOString()
                });
            }
        }

        function testAllNotifications() {
            const notifications = [
                { type: 'booking', delay: 0 },
                { type: 'payment', delay: 1500 },
                { type: 'reminder', delay: 3000 },
                { type: 'error', delay: 4500 }
            ];
            
            notifications.forEach(({ type, delay }) => {
                setTimeout(() => {
                    @this.call('show' + type.charAt(0).toUpperCase() + type.slice(1) + 'Notification');
                }, delay);
            });
        }

        // Volume slider handler
        document.addEventListener('input', function(e) {
            if (e.target.id === 'volume-slider') {
                const volume = e.target.value / 100;
                if (window.notificationSystem) {
                    window.notificationSystem.updateSettings({ volume: volume });
                }
            }
        });

        // Sound enabled toggle handler
        document.addEventListener('change', function(e) {
            if (e.target.id === 'sound-enabled') {
                if (window.notificationSystem) {
                    window.notificationSystem.updateSettings({ 
                        soundEnabled: e.target.checked 
                    });
                }
            }
        });
    </script>
    @endpush
</x-filament-widgets::widget>