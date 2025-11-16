<header class="fixed top-0 left-0 right-0 h-16 bg-header-bg border-b border-header-border z-40"
        x-data="{
            userMenuOpen: false,
            notificationsOpen: false,
            searchQuery: '',
        }">
    <div class="h-full px-4 flex items-center justify-between">
        <!-- Left Section -->
        <div class="flex items-center space-x-4">
            <!-- Mobile Menu Toggle -->
            <button @click="$store.sidebar.toggle()"
                    class="lg:hidden p-2 rounded-lg hover:bg-bg-hover text-header-text">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Logo -->
            <a href="/dashboard" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">D</span>
                </div>
                <span class="text-xl font-bold text-header-text hidden sm:inline">DocuSign</span>
            </a>
        </div>

        <!-- Center Section - Search -->
        <div class="hidden md:flex flex-1 max-w-xl mx-4">
            <div class="relative w-full">
                <input type="search"
                       x-model="searchQuery"
                       placeholder="Search envelopes, templates..."
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-input-border bg-input-bg text-input-text placeholder-input-placeholder focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <!-- Right Section -->
        <div class="flex items-center space-x-2">
            <!-- Theme Toggle -->
            <button @click="$store.theme.toggleMode()"
                    class="p-2 rounded-lg hover:bg-bg-hover text-header-text transition-colors">
                <svg x-show="$store.theme.mode === 'light'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <svg x-show="$store.theme.mode === 'dark'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>

            <!-- Notifications -->
            <div class="relative" @click.away="notificationsOpen = false">
                <button @click="notificationsOpen = !notificationsOpen"
                        class="relative p-2 rounded-lg hover:bg-bg-hover text-header-text transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <!-- Notification Badge -->
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <!-- Notifications Dropdown -->
                <div x-show="notificationsOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-80 bg-dropdown-bg rounded-lg shadow-xl border border-dropdown-border overflow-hidden"
                     style="display: none;">
                    <!-- Notifications Header -->
                    <div class="px-4 py-3 border-b border-dropdown-border">
                        <h3 class="text-sm font-semibold text-text-primary">Notifications</h3>
                    </div>
                    <!-- Notifications List -->
                    <div class="max-h-96 overflow-y-auto">
                        <a href="#" class="block px-4 py-3 hover:bg-dropdown-hover transition-colors border-b border-dropdown-border">
                            <p class="text-sm font-medium text-text-primary">Envelope awaiting signature</p>
                            <p class="text-xs text-text-secondary mt-1">Contract Agreement needs your signature</p>
                            <p class="text-xs text-text-tertiary mt-1">5 minutes ago</p>
                        </a>
                        <a href="#" class="block px-4 py-3 hover:bg-dropdown-hover transition-colors">
                            <p class="text-sm font-medium text-text-primary">Envelope completed</p>
                            <p class="text-xs text-text-secondary mt-1">NDA Document has been signed by all parties</p>
                            <p class="text-xs text-text-tertiary mt-1">1 hour ago</p>
                        </a>
                    </div>
                    <!-- View All -->
                    <div class="px-4 py-3 border-t border-dropdown-border">
                        <a href="/notifications" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all notifications</a>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="relative" @click.away="userMenuOpen = false">
                <button @click="userMenuOpen = !userMenuOpen"
                        class="flex items-center space-x-2 p-1 rounded-lg hover:bg-bg-hover transition-colors">
                    <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                        <span class="text-primary-700 font-semibold text-sm" x-text="($store.auth.user?.name || 'U').charAt(0).toUpperCase()"></span>
                    </div>
                    <span class="hidden md:inline text-sm font-medium text-header-text" x-text="$store.auth.user?.name || 'User'"></span>
                    <svg class="w-4 h-4 text-header-text" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- User Dropdown -->
                <div x-show="userMenuOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-56 bg-dropdown-bg rounded-lg shadow-xl border border-dropdown-border overflow-hidden"
                     style="display: none;">
                    <!-- User Info -->
                    <div class="px-4 py-3 border-b border-dropdown-border">
                        <p class="text-sm font-medium text-text-primary" x-text="$store.auth.user?.name || 'User'"></p>
                        <p class="text-xs text-text-secondary truncate" x-text="$store.auth.user?.email || ''"></p>
                    </div>
                    <!-- Menu Items -->
                    <div class="py-1">
                        <a href="/profile" class="block px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover transition-colors">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profile
                            </div>
                        </a>
                        <a href="/settings" class="block px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover transition-colors">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Settings
                            </div>
                        </a>
                        <a href="/help" class="block px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover transition-colors">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Help
                            </div>
                        </a>
                    </div>
                    <!-- Logout -->
                    <div class="border-t border-dropdown-border">
                        <button @click="$store.auth.logout()"
                                class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-dropdown-hover transition-colors">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
