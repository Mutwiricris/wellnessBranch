class NotificationSystem {
    constructor() {
        this.sounds = {
            'new-booking': '/sounds/new-booking.mp3',
            'booking-confirmed': '/sounds/booking-confirmed.mp3',
            'booking-completed': '/sounds/booking-completed.mp3',
            'payment-received': '/sounds/payment-received.mp3',
            'booking-cancelled': '/sounds/booking-cancelled.mp3',
            'reminder': '/sounds/reminder.mp3',
            'error': '/sounds/error.mp3'
        };
        
        this.settings = {
            soundEnabled: localStorage.getItem('notification-sound') !== 'false',
            browserNotificationsEnabled: localStorage.getItem('browser-notifications') !== 'false',
            volume: parseFloat(localStorage.getItem('notification-volume') || '0.7')
        };
        
        this.initializeBrowserNotifications();
        this.createAudioElements();
        this.createToastContainer();
        this.bindEvents();
    }

    async initializeBrowserNotifications() {
        if ('Notification' in window && this.settings.browserNotificationsEnabled) {
            if (Notification.permission === 'default') {
                await Notification.requestPermission();
            }
        }
    }

    createAudioElements() {
        this.audioElements = {};
        Object.entries(this.sounds).forEach(([key, src]) => {
            const audio = new Audio(src);
            audio.volume = this.settings.volume;
            audio.preload = 'auto';
            this.audioElements[key] = audio;
        });
    }

    createToastContainer() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            container.style.maxWidth = '400px';
            document.body.appendChild(container);
        }
    }

    bindEvents() {
        // Listen for Livewire events
        if (typeof Livewire !== 'undefined') {
            Livewire.on('notification', (data) => {
                this.show(data);
            });
            
            Livewire.on('play-sound', (sound) => {
                this.playSound(sound);
            });
        }

        // Listen for custom events
        document.addEventListener('show-notification', (event) => {
            this.show(event.detail);
        });

        document.addEventListener('play-notification-sound', (event) => {
            this.playSound(event.detail.sound);
        });
    }

    show(notification) {
        // Show toast notification
        this.showToast(notification);
        
        // Play sound if enabled
        if (this.settings.soundEnabled && notification.sound) {
            this.playSound(notification.sound);
        }
        
        // Show browser notification if enabled and permitted
        if (this.settings.browserNotificationsEnabled && 
            'Notification' in window && 
            Notification.permission === 'granted') {
            this.showBrowserNotification(notification);
        }
    }

    showToast(notification) {
        const toast = document.createElement('div');
        toast.className = `
            bg-white border border-gray-200 rounded-lg shadow-lg p-4 mb-2
            transform transition-all duration-300 ease-in-out
            opacity-0 translate-x-full
        `;
        
        const typeColors = {
            success: 'border-l-4 border-l-green-500',
            error: 'border-l-4 border-l-red-500',
            warning: 'border-l-4 border-l-yellow-500',
            info: 'border-l-4 border-l-blue-500'
        };
        
        toast.className += ` ${typeColors[notification.type] || typeColors.info}`;
        
        toast.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <span class="text-2xl">${notification.icon || '📢'}</span>
                </div>
                <div class="ml-3 flex-1">
                    <h4 class="text-sm font-semibold text-gray-900">${notification.title}</h4>
                    <p class="text-sm text-gray-600 mt-1">${notification.message}</p>
                    <p class="text-xs text-gray-400 mt-1">${new Date(notification.timestamp).toLocaleTimeString()}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button class="text-gray-400 hover:text-gray-600 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        const container = document.getElementById('notification-container');
        container.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('opacity-0', 'translate-x-full');
            toast.classList.add('opacity-100', 'translate-x-0');
        }, 100);

        // Auto remove after delay
        const autoRemoveDelay = notification.type === 'error' ? 10000 : 5000;
        setTimeout(() => {
            this.removeToast(toast);
        }, autoRemoveDelay);
    }

    removeToast(toast) {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    showBrowserNotification(notification) {
        const browserNotification = new Notification(notification.title, {
            body: notification.message,
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: `notification-${Date.now()}`,
            requireInteraction: notification.type === 'error'
        });

        browserNotification.onclick = () => {
            window.focus();
            browserNotification.close();
            
            // Navigate to related resource if available
            if (notification.booking_id) {
                // Navigate to booking details
                window.location.href = `/admin/bookings/${notification.booking_id}`;
            } else if (notification.payment_id) {
                // Navigate to payment details
                window.location.href = `/admin/payments/${notification.payment_id}`;
            }
        };

        // Auto close after 5 seconds
        setTimeout(() => {
            browserNotification.close();
        }, 5000);
    }

    playSound(soundKey) {
        if (!this.settings.soundEnabled) return;
        
        const audio = this.audioElements[soundKey];
        if (audio) {
            audio.currentTime = 0;
            audio.volume = this.settings.volume;
            audio.play().catch(error => {
                console.warn('Could not play notification sound:', error);
            });
        }
    }

    updateSettings(settings) {
        this.settings = { ...this.settings, ...settings };
        
        // Save to localStorage
        localStorage.setItem('notification-sound', this.settings.soundEnabled);
        localStorage.setItem('browser-notifications', this.settings.browserNotificationsEnabled);
        localStorage.setItem('notification-volume', this.settings.volume);
        
        // Update audio volumes
        Object.values(this.audioElements).forEach(audio => {
            audio.volume = this.settings.volume;
        });
        
        // Request permission if browser notifications are enabled
        if (this.settings.browserNotificationsEnabled && 'Notification' in window) {
            if (Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
    }

    getSettings() {
        return { ...this.settings };
    }

    // Test methods for settings panel
    testSound(soundKey = 'reminder') {
        this.playSound(soundKey);
    }

    testNotification() {
        this.show({
            title: 'Test Notification',
            message: 'This is a test notification to verify your settings.',
            type: 'info',
            sound: 'reminder',
            icon: '🔔',
            timestamp: new Date().toISOString()
        });
    }

    // Daily report notification
    showDailyReport(reportData) {
        const completionRate = reportData.completion_rate || 0;
        const emoji = completionRate >= 90 ? '🎉' : completionRate >= 75 ? '👍' : '📊';
        
        this.show({
            title: 'Daily Report Summary',
            message: `${reportData.completed_bookings}/${reportData.total_bookings} appointments completed (${completionRate}%). Revenue: KES ${(reportData.total_revenue || 0).toLocaleString()}`,
            type: completionRate >= 75 ? 'success' : 'info',
            sound: 'reminder',
            icon: emoji,
            timestamp: new Date().toISOString()
        });
    }
}

// Initialize notification system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.notificationSystem = new NotificationSystem();
});

// Global helper functions
window.showNotification = (notification) => {
    if (window.notificationSystem) {
        window.notificationSystem.show(notification);
    }
};

window.playNotificationSound = (sound) => {
    if (window.notificationSystem) {
        window.notificationSystem.playSound(sound);
    }
};

// CSS for toast notifications
const notificationStyles = `
    #notification-container {
        pointer-events: none;
    }
    
    #notification-container > div {
        pointer-events: auto;
    }
    
    .notification-toast-enter {
        opacity: 0;
        transform: translateX(100%);
    }
    
    .notification-toast-enter-active {
        opacity: 1;
        transform: translateX(0);
        transition: all 300ms ease-in-out;
    }
    
    .notification-toast-exit {
        opacity: 1;
        transform: translateX(0);
    }
    
    .notification-toast-exit-active {
        opacity: 0;
        transform: translateX(100%);
        transition: all 300ms ease-in-out;
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);