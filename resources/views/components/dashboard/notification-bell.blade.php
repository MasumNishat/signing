@props([
    'notifications' => [],
    'unreadCount' => 0
])

<div x-data="{
    open: false,
    notifications: {{ json_encode($notifications) }},
    unreadCount: {{ $unreadCount }},
    markAsRead(id) {
        // Mark notification as read
        this.notifications = this.notifications.map(n => {
            if (n.id === id) {
                n.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            return n;
        });
    },
    markAllAsRead() {
        this.notifications = this.notifications.map(n => ({ ...n, read: true }));
        this.unreadCount = 0;
    }
}" class="relative">
    <!-- Notification Bell Button -->
    <button @click="open = !open"
            class="relative p-2 text-text-secondary hover:text-text-primary hover:bg-bg-hover rounded-lg transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        <!-- Unread Badge -->
        <span x-show="unreadCount > 0"
              x-text="unreadCount"
              class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
        </span>
    </button>

    <!-- Notifications Dropdown -->
    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-bg-primary rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">

        <!-- Header -->
        <div class="px-4 py-3 border-b border-card-border flex items-center justify-between">
            <h3 class="text-sm font-semibold text-text-primary">Notifications</h3>
            <button @click="markAllAsRead()"
                    x-show="unreadCount > 0"
                    class="text-xs text-primary-600 hover:text-primary-500">
                Mark all read
            </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-8 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="mt-2 text-sm text-text-secondary">No notifications</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div @click="markAsRead(notification.id)"
                     class="px-4 py-3 hover:bg-bg-hover cursor-pointer border-b border-card-border last:border-b-0"
                     :class="!notification.read ? 'bg-primary-50 dark:bg-primary-900/10' : ''">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                 :class="{
                                     'bg-blue-100 text-blue-600': notification.type === 'envelope',
                                     'bg-green-100 text-green-600': notification.type === 'signature',
                                     'bg-purple-100 text-purple-600': notification.type === 'template',
                                     'bg-gray-100 text-gray-600': !notification.type
                                 }">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text-primary" x-text="notification.title"></p>
                            <p class="text-xs text-text-secondary mt-1" x-text="notification.message"></p>
                            <p class="text-xs text-text-secondary mt-1" x-text="notification.time"></p>
                        </div>
                        <div x-show="!notification.read" class="flex-shrink-0">
                            <span class="inline-block h-2 w-2 rounded-full bg-primary-600"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-card-border text-center">
            <a href="/notifications" class="text-sm text-primary-600 hover:text-primary-500 font-medium">
                View all notifications
            </a>
        </div>
    </div>
</div>
