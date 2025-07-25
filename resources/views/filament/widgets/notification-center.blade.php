<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-bell class="h-5 w-5" />
                    <span>Notification Center</span>
                    @if($unread_count > 0)
                        <x-filament::badge color="danger">
                            {{ $unread_count }}
                        </x-filament::badge>
                    @endif
                </div>
                
                @if($unread_count > 0)
                    <x-filament::button 
                        size="sm" 
                        color="gray"
                        wire:click="markAllAsRead"
                    >
                        Mark all as read
                    </x-filament::button>
                @endif
            </div>
        </x-slot>

        <div class="space-y-3 max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div class="flex items-start gap-3 p-3 rounded-lg border {{ $notification['read'] ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-800 shadow-sm' }} transition-all duration-200">
                    <div class="flex-shrink-0">
                        <div class="p-2 rounded-full {{ $notification['color'] === 'success' ? 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400' : '' }}
                                                   {{ $notification['color'] === 'warning' ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400' : '' }}
                                                   {{ $notification['color'] === 'danger' ? 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400' : '' }}
                                                   {{ $notification['color'] === 'info' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : '' }}
                                                   {{ $notification['color'] === 'gray' ? 'bg-gray-100 text-gray-600 dark:bg-gray-900/20 dark:text-gray-400' : '' }}">
                            @svg($notification['icon'], 'h-4 w-4')
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $notification['title'] }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $notification['message'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                    {{ $notification['time'] }}
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-1 ml-2">
                                @if($notification['priority'] === 'high')
                                    <x-filament::badge color="danger" size="xs">
                                        High
                                    </x-filament::badge>
                                @elseif($notification['priority'] === 'medium')
                                    <x-filament::badge color="warning" size="xs">
                                        Medium
                                    </x-filament::badge>
                                @endif
                                
                                @if(!$notification['read'])
                                    <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 mt-3">
                            @if(!$notification['read'])
                                <x-filament::button 
                                    size="xs" 
                                    color="gray"
                                    wire:click="markAsRead('{{ $notification['id'] }}')"
                                >
                                    Mark as read
                                </x-filament::button>
                            @endif
                            
                            <x-filament::button 
                                size="xs" 
                                color="danger"
                                wire:click="deleteNotification('{{ $notification['id'] }}')"
                            >
                                Delete
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <x-heroicon-o-bell-slash class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No notifications yet</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Request notification permissions
    if ('Notification' in window) {
        Notification.requestPermission();
    }
    
    // Listen for broadcast notifications
    if (typeof Echo !== 'undefined') {
        Echo.private('App.Models.User.' + @js(auth()->id()))
            .notification((notification) => {
                // Play notification sound
                playNotificationSound(notification.sound);
                
                // Show browser notification
                if (Notification.permission === 'granted') {
                    new Notification(notification.title, {
                        body: notification.message,
                        icon: '/favicon.ico',
                        tag: 'wellness-notification'
                    });
                }
                
                // Refresh the widget
                Livewire.dispatch('$refresh');
            });
    }
});

function playNotificationSound(soundType) {
    const sounds = {
        'default': '/sounds/notification-default.mp3',
        'confirmation': '/sounds/notification-confirmation.mp3',
        'success': '/sounds/notification-success.mp3',
        'warning': '/sounds/notification-warning.mp3',
        'alert': '/sounds/notification-alert.mp3',
        'payment': '/sounds/notification-payment.mp3',
        'booking': '/sounds/notification-booking.mp3'
    };
    
    const audio = new Audio(sounds[soundType] || sounds['default']);
    audio.volume = 0.3;
    audio.play().catch(e => console.log('Could not play notification sound:', e));
}
</script>