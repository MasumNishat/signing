<x-layout.app title="Dashboard Widgets">
    <div x-data="{
        loading: true,
        widgets: [],
        availableWidgets: [
            { id: 'stats', name: 'Statistics', description: 'Envelope counts and metrics', enabled: true },
            { id: 'recent', name: 'Recent Envelopes', description: 'Latest envelope activity', enabled: true },
            { id: 'activity', name: 'Activity Feed', description: 'Recent system activity', enabled: true },
            { id: 'billing', name: 'Billing Summary', description: 'Billing and usage information', enabled: false },
            { id: 'folders', name: 'Folders', description: 'Quick folder access', enabled: false },
            { id: 'pending', name: 'Pending Actions', description: 'Items requiring attention', enabled: true },
            { id: 'team', name: 'Team Activity', description: 'Team member actions', enabled: false },
            { id: 'chart_status', name: 'Status Chart', description: 'Envelope status distribution', enabled: false },
            { id: 'chart_activity', name: 'Activity Chart', description: 'Signing activity over time', enabled: false },
        ],
        layout: 'grid', // grid or list
        async init() {
            // Load widget preferences
            await this.loadWidgets();
            this.loading = false;
        },
        async loadWidgets() {
            try {
                // Load from localStorage or API
                const saved = localStorage.getItem('dashboard_widgets');
                if (saved) {
                    this.availableWidgets = JSON.parse(saved);
                }
            } catch (error) {
                console.error('Failed to load widget preferences');
            }
        },
        toggleWidget(widgetId) {
            this.availableWidgets = this.availableWidgets.map(w => {
                if (w.id === widgetId) {
                    return { ...w, enabled: !w.enabled };
                }
                return w;
            });
            this.saveWidgets();
        },
        saveWidgets() {
            try {
                localStorage.setItem('dashboard_widgets', JSON.stringify(this.availableWidgets));
                $store.toast.success('Widget preferences saved');
            } catch (error) {
                $store.toast.error('Failed to save preferences');
            }
        },
        resetWidgets() {
            if (confirm('Reset all widgets to default configuration?')) {
                localStorage.removeItem('dashboard_widgets');
                this.availableWidgets = this.availableWidgets.map(w => ({
                    ...w,
                    enabled: ['stats', 'recent', 'activity', 'pending'].includes(w.id)
                }));
                this.saveWidgets();
            }
        }
    }"
    x-init="init()">

        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Dashboard Widgets</h1>
                <p class="mt-1 text-sm text-text-secondary">Customize your dashboard layout and widgets</p>
            </div>
            <div class="flex items-center space-x-3">
                <x-ui.button variant="secondary" @click="resetWidgets()">
                    Reset to Default
                </x-ui.button>
                <x-ui.button variant="primary" onclick="window.location.href='/dashboard'">
                    Back to Dashboard
                </x-ui.button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-6">
            <x-ui.skeleton type="card" class="h-64" />
        </div>

        <!-- Widget Configuration -->
        <div x-show="!loading" class="space-y-6">
            <!-- Layout Options -->
            <x-ui.card>
                <h3 class="text-lg font-semibold text-text-primary mb-4">Dashboard Layout</h3>
                <div class="flex items-center space-x-4">
                    <button @click="layout = 'grid'"
                            :class="layout === 'grid' ? 'bg-primary-100 text-primary-700 border-primary-500' : 'bg-bg-secondary text-text-secondary border-border-primary'"
                            class="flex items-center px-4 py-2 border-2 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Grid Layout
                    </button>
                    <button @click="layout = 'list'"
                            :class="layout === 'list' ? 'bg-primary-100 text-primary-700 border-primary-500' : 'bg-bg-secondary text-text-secondary border-border-primary'"
                            class="flex items-center px-4 py-2 border-2 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        List Layout
                    </button>
                </div>
            </x-ui.card>

            <!-- Available Widgets -->
            <x-ui.card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-text-primary">Available Widgets</h3>
                    <span class="text-sm text-text-secondary" x-text="availableWidgets.filter(w => w.enabled).length + ' active'"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="widget in availableWidgets" :key="widget.id">
                        <div class="p-4 rounded-lg border-2 transition-all"
                             :class="widget.enabled ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/10' : 'border-border-primary bg-bg-secondary'">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h4 class="font-medium text-text-primary" x-text="widget.name"></h4>
                                    <p class="text-sm text-text-secondary mt-1" x-text="widget.description"></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer ml-3">
                                    <input type="checkbox"
                                           :checked="widget.enabled"
                                           @change="toggleWidget(widget.id)"
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            <div x-show="widget.enabled" class="mt-3 pt-3 border-t border-card-border">
                                <div class="flex items-center justify-between text-xs text-text-secondary">
                                    <span>Visible on dashboard</span>
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>

            <!-- Widget Preview -->
            <x-ui.card>
                <h3 class="text-lg font-semibold text-text-primary mb-4">Dashboard Preview</h3>
                <p class="text-sm text-text-secondary mb-4">
                    This is how your dashboard will look with the current widget configuration:
                </p>

                <div class="border-2 border-dashed border-border-primary rounded-lg p-6 bg-bg-secondary">
                    <div :class="layout === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-4'">
                        <template x-for="widget in availableWidgets.filter(w => w.enabled)" :key="widget.id">
                            <div class="p-4 bg-bg-primary border border-card-border rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <div class="h-2 w-2 bg-primary-600 rounded-full"></div>
                                    <span class="text-sm font-medium text-text-primary" x-text="widget.name"></span>
                                </div>
                                <div class="mt-2 h-20 bg-bg-hover rounded"></div>
                            </div>
                        </template>
                    </div>

                    <div x-show="availableWidgets.filter(w => w.enabled).length === 0" class="text-center py-8">
                        <p class="text-sm text-text-secondary">No widgets enabled. Enable at least one widget above.</p>
                    </div>
                </div>
            </x-ui.card>

            <!-- Help Text -->
            <x-ui.card>
                <h3 class="text-lg font-semibold text-text-primary mb-2">How to use</h3>
                <ul class="space-y-2 text-sm text-text-secondary">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Toggle widgets on/off using the switches above</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Choose between grid or list layout for your dashboard</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Your preferences are saved automatically</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Click "Reset to Default" to restore the original configuration</span>
                    </li>
                </ul>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
