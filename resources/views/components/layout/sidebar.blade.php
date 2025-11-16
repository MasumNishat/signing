<aside class="fixed left-0 top-16 bottom-0 w-64 bg-sidebar-bg border-r border-sidebar-border transition-transform duration-300 z-30"
       x-data="{ activeMenu: null }"
       :class="$store.sidebar.isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    <!-- Sidebar Content -->
    <div class="h-full flex flex-col">
        <!-- Primary Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <!-- Dashboard -->
            <a href="/dashboard"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors"
               :class="window.location.pathname === '/dashboard' ? 'bg-sidebar-active text-sidebar-active-text' : ''">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <!-- Envelopes (Collapsible) -->
            <div>
                <button @click="activeMenu = activeMenu === 'envelopes' ? null : 'envelopes'"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Envelopes
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200"
                         :class="activeMenu === 'envelopes' ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Submenu -->
                <div x-show="activeMenu === 'envelopes'"
                     x-collapse
                     class="ml-8 mt-1 space-y-1">
                    <a href="/envelopes" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">All Envelopes</a>
                    <a href="/envelopes/create" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Send Envelope</a>
                    <a href="/envelopes/inbox" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Inbox</a>
                    <a href="/envelopes/sent" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Sent</a>
                    <a href="/envelopes/draft" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Drafts</a>
                    <a href="/envelopes/completed" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Completed</a>
                </div>
            </div>

            <!-- Templates -->
            <div>
                <button @click="activeMenu = activeMenu === 'templates' ? null : 'templates'"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Templates
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200"
                         :class="activeMenu === 'templates' ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="activeMenu === 'templates'"
                     x-collapse
                     class="ml-8 mt-1 space-y-1">
                    <a href="/templates" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">All Templates</a>
                    <a href="/templates/create" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Create Template</a>
                    <a href="/templates/favorites" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Favorites</a>
                </div>
            </div>

            <!-- Recipients -->
            <a href="/recipients"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors"
               :class="window.location.pathname.startsWith('/recipients') ? 'bg-sidebar-active text-sidebar-active-text' : ''">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Recipients
            </a>

            <!-- Documents -->
            <a href="/documents"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors"
               :class="window.location.pathname.startsWith('/documents') ? 'bg-sidebar-active text-sidebar-active-text' : ''">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Documents
            </a>

            <!-- Folders -->
            <a href="/folders"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors"
               :class="window.location.pathname.startsWith('/folders') ? 'bg-sidebar-active text-sidebar-active-text' : ''">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Folders
            </a>

            <div class="border-t border-sidebar-border my-3"></div>

            <!-- Admin Section (Conditional) -->
            <template x-if="$store.auth.hasRole('admin') || $store.auth.hasRole('account_admin')">
                <div class="space-y-1">
                    <div class="px-3 py-2 text-xs font-semibold text-sidebar-text uppercase tracking-wider">
                        Administration
                    </div>

                    <a href="/users"
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Users
                    </a>

                    <a href="/groups"
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Groups
                    </a>

                    <a href="/billing"
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Billing
                    </a>

                    <a href="/branding"
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                        Branding
                    </a>

                    <a href="/settings"
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                    </a>
                </div>
            </template>
        </nav>

        <!-- Sidebar Footer -->
        <div class="border-t border-sidebar-border p-3 space-y-2">
            <!-- Theme Switcher Button -->
            <button @click="$dispatch('open-theme-switcher')"
                    class="w-full flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                </svg>
                Themes
            </button>

            <!-- Storage Usage -->
            <div class="px-3 py-2 text-xs text-sidebar-text">
                <div class="flex justify-between mb-1">
                    <span>Storage</span>
                    <span x-text="($store.auth.user?.storage_used || 0) + ' MB / ' + ($store.auth.user?.storage_limit || 1000) + ' MB'">0 MB / 1000 MB</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                    <div class="bg-primary-600 h-1.5 rounded-full"
                         :style="'width: ' + (($store.auth.user?.storage_used || 0) / ($store.auth.user?.storage_limit || 1000) * 100) + '%'"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Overlay Close Button -->
    <button @click="$store.sidebar.close()"
            class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-20"
            x-show="$store.sidebar.isOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="display: none;"
            aria-label="Close sidebar"></button>
</aside>
