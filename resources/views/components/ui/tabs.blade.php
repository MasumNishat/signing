@props([
    'defaultTab' => 1,
])

<div x-data="{ activeTab: {{ $defaultTab }} }" {{ $attributes }}>
    {{ $slot }}
</div>

{{--
Usage Examples:

1. Basic tabs:
<x-ui.tabs>
    <!-- Tab Headers -->
    <div class="border-b border-border-primary">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button @click="activeTab = 1"
                    :class="activeTab === 1 ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border-secondary'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Details
            </button>
            <button @click="activeTab = 2"
                    :class="activeTab === 2 ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border-secondary'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Recipients
            </button>
            <button @click="activeTab = 3"
                    :class="activeTab === 3 ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border-secondary'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Documents
            </button>
        </nav>
    </div>

    <!-- Tab Panels -->
    <div class="mt-6">
        <div x-show="activeTab === 1" x-transition>
            <h3 class="text-lg font-medium mb-4">Envelope Details</h3>
            <p class="text-text-secondary">Details content...</p>
        </div>

        <div x-show="activeTab === 2" x-transition>
            <h3 class="text-lg font-medium mb-4">Recipients</h3>
            <p class="text-text-secondary">Recipients content...</p>
        </div>

        <div x-show="activeTab === 3" x-transition>
            <h3 class="text-lg font-medium mb-4">Documents</h3>
            <p class="text-text-secondary">Documents content...</p>
        </div>
    </div>
</x-ui.tabs>

2. Tabs with icons:
<x-ui.tabs default-tab="1">
    <div class="border-b border-border-primary">
        <nav class="flex space-x-4">
            <button @click="activeTab = 1"
                    :class="activeTab === 1 ? 'border-primary-500 text-primary-600 bg-primary-50' : 'border-transparent text-text-secondary hover:bg-bg-hover'"
                    class="flex items-center px-4 py-3 border-b-2 font-medium text-sm rounded-t-lg transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Overview
            </button>
            <button @click="activeTab = 2"
                    :class="activeTab === 2 ? 'border-primary-500 text-primary-600 bg-primary-50' : 'border-transparent text-text-secondary hover:bg-bg-hover'"
                    class="flex items-center px-4 py-3 border-b-2 font-medium text-sm rounded-t-lg transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Documents
            </button>
            <button @click="activeTab = 3"
                    :class="activeTab === 3 ? 'border-primary-500 text-primary-600 bg-primary-50' : 'border-transparent text-text-secondary hover:bg-bg-hover'"
                    class="flex items-center px-4 py-3 border-b-2 font-medium text-sm rounded-t-lg transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Analytics
            </button>
        </nav>
    </div>

    <div class="py-6">
        <div x-show="activeTab === 1">Overview content</div>
        <div x-show="activeTab === 2">Documents content</div>
        <div x-show="activeTab === 3">Analytics content</div>
    </div>
</x-ui.tabs>

3. Pill-style tabs:
<x-ui.tabs>
    <div class="flex space-x-2 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
        <button @click="activeTab = 1"
                :class="activeTab === 1 ? 'bg-white dark:bg-gray-700 text-text-primary shadow' : 'text-text-secondary hover:text-text-primary'"
                class="flex-1 px-4 py-2 text-sm font-medium rounded-lg transition-all">
                Active
        </button>
        <button @click="activeTab = 2"
                :class="activeTab === 2 ? 'bg-white dark:bg-gray-700 text-text-primary shadow' : 'text-text-secondary hover:text-text-primary'"
                class="flex-1 px-4 py-2 text-sm font-medium rounded-lg transition-all">
                Completed
        </button>
        <button @click="activeTab = 3"
                :class="activeTab === 3 ? 'bg-white dark:bg-gray-700 text-text-primary shadow' : 'text-text-secondary hover:text-text-primary'"
                class="flex-1 px-4 py-2 text-sm font-medium rounded-lg transition-all">
                Archived
        </button>
    </div>

    <div class="mt-6">
        <div x-show="activeTab === 1">Active envelopes...</div>
        <div x-show="activeTab === 2">Completed envelopes...</div>
        <div x-show="activeTab === 3">Archived envelopes...</div>
    </div>
</x-ui.tabs>

4. Tabs with badges:
<x-ui.tabs>
    <div class="border-b border-border-primary">
        <nav class="flex space-x-8">
            <button @click="activeTab = 1"
                    :class="activeTab === 1 ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary'"
                    class="flex items-center py-4 px-1 border-b-2 font-medium text-sm">
                Inbox
                <x-ui.badge variant="primary" size="xs" class="ml-2">3</x-ui.badge>
            </button>
            <button @click="activeTab = 2"
                    :class="activeTab === 2 ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary'"
                    class="flex items-center py-4 px-1 border-b-2 font-medium text-sm">
                Sent
                <x-ui.badge variant="gray" size="xs" class="ml-2">12</x-ui.badge>
            </button>
            <button @click="activeTab = 3"
                    :class="activeTab === 3 ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary'"
                    class="flex items-center py-4 px-1 border-b-2 font-medium text-sm">
                Draft
                <x-ui.badge variant="warning" size="xs" class="ml-2">1</x-ui.badge>
            </button>
        </nav>
    </div>

    <div class="mt-6">
        <div x-show="activeTab === 1">Inbox...</div>
        <div x-show="activeTab === 2">Sent...</div>
        <div x-show="activeTab === 3">Draft...</div>
    </div>
</x-ui.tabs>

5. Vertical tabs:
<x-ui.tabs>
    <div class="flex gap-6">
        <!-- Vertical Tab Nav -->
        <nav class="flex flex-col space-y-2 w-48">
            <button @click="activeTab = 1"
                    :class="activeTab === 1 ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-text-secondary hover:bg-bg-hover border-transparent'"
                    class="text-left px-4 py-3 rounded-lg border-l-4 transition-all">
                General
            </button>
            <button @click="activeTab = 2"
                    :class="activeTab === 2 ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-text-secondary hover:bg-bg-hover border-transparent'"
                    class="text-left px-4 py-3 rounded-lg border-l-4 transition-all">
                Security
            </button>
            <button @click="activeTab = 3"
                    :class="activeTab === 3 ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-text-secondary hover:bg-bg-hover border-transparent'"
                    class="text-left px-4 py-3 rounded-lg border-l-4 transition-all">
                Notifications
            </button>
        </nav>

        <!-- Tab Panels -->
        <div class="flex-1">
            <div x-show="activeTab === 1">General settings...</div>
            <div x-show="activeTab === 2">Security settings...</div>
            <div x-show="activeTab === 3">Notification settings...</div>
        </div>
    </div>
</x-ui.tabs>

6. With dynamic content loading:
<div x-data="{
        activeTab: 1,
        tabData: {},
        async loadTab(tabId) {
            if (!this.tabData[tabId]) {
                this.tabData[tabId] = await $api.get(`/envelopes/tab/${tabId}`);
            }
            this.activeTab = tabId;
        }
    }">
    <div class="border-b">
        <nav class="flex space-x-8">
            <button @click="loadTab(1)"
                    :class="activeTab === 1 ? 'border-primary-500 text-primary-600' : 'border-transparent'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                Tab 1
            </button>
            <button @click="loadTab(2)"
                    :class="activeTab === 2 ? 'border-primary-500 text-primary-600' : 'border-transparent'"
                    class="py-4 px-1 border-b-2 font-medium text-sm">
                Tab 2
            </button>
        </nav>
    </div>

    <div class="mt-6">
        <div x-show="activeTab === 1">
            <div x-show="!tabData[1]">
                <x-ui.loading-spinner />
            </div>
            <div x-show="tabData[1]" x-html="tabData[1]"></div>
        </div>
        <div x-show="activeTab === 2">
            <div x-show="!tabData[2]">
                <x-ui.loading-spinner />
            </div>
            <div x-show="tabData[2]" x-html="tabData[2]"></div>
        </div>
    </div>
</div>
--}}
