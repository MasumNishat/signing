<!-- Mobile Menu Overlay -->
<div x-data="{ open: false }"
     @open-mobile-menu.window="open = true"
     @close-mobile-menu.window="open = false"
     @keydown.escape.window="open = false">

    <!-- Backdrop -->
    <div x-show="open"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
         style="display: none;"
         aria-hidden="true"></div>

    <!-- Mobile Menu Panel -->
    <div x-show="open"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 w-full max-w-xs bg-sidebar-bg z-50 lg:hidden flex flex-col shadow-xl"
         style="display: none;"
         @click.outside="open = false">

        <!-- Mobile Menu Header -->
        <div class="flex items-center justify-between h-16 px-4 border-b border-sidebar-border">
            <!-- Logo -->
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">D</span>
                </div>
                <span class="text-xl font-bold text-text-primary">DocuSign</span>
            </div>

            <!-- Close Button -->
            <button @click="open = false"
                    class="p-2 rounded-lg hover:bg-sidebar-hover text-text-primary transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Mobile Menu Content (Scrollable) -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1"
             x-data="{ activeMenu: null }">

            <!-- Dashboard -->
            <a href="/dashboard"
               @click="open = false"
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

                <div x-show="activeMenu === 'envelopes'"
                     x-collapse
                     class="ml-8 mt-1 space-y-1">
                    <a href="/envelopes" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">All Envelopes</a>
                    <a href="/envelopes/create" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Send Envelope</a>
                    <a href="/envelopes/inbox" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Inbox</a>
                    <a href="/envelopes/sent" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Sent</a>
                    <a href="/envelopes/draft" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Drafts</a>
                    <a href="/envelopes/completed" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Completed</a>
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
                    <a href="/templates" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">All Templates</a>
                    <a href="/templates/create" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Create Template</a>
                    <a href="/templates/favorites" @click="open = false" class="block px-3 py-2 text-sm text-sidebar-text hover:text-sidebar-active-text rounded-lg hover:bg-sidebar-hover">Favorites</a>
                </div>
            </div>

            <!-- Recipients -->
            <a href="/recipients"
               @click="open = false"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Recipients
            </a>

            <!-- Documents -->
            <a href="/documents"
               @click="open = false"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Documents
            </a>

            <!-- Folders -->
            <a href="/folders"
               @click="open = false"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Folders
            </a>

            <div class="border-t border-sidebar-border my-3"></div>

            <!-- User Profile -->
            <a href="/profile"
               @click="open = false"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Profile
            </a>

            <!-- Settings -->
            <a href="/settings"
               @click="open = false"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Settings
            </a>

            <!-- Help -->
            <a href="/help"
               @click="open = false"
               class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Help
            </a>
        </nav>

        <!-- Mobile Menu Footer -->
        <div class="border-t border-sidebar-border p-4">
            <!-- Logout Button -->
            <button @click="$store.auth.logout()"
                    class="w-full flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </button>
        </div>
    </div>
</div>
