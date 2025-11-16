<x-layout.app title="System Health">
    <div x-data="{
        loading: true,
        health: null,
        autoRefresh: false,
        refreshInterval: null,
        async init() {
            await this.loadHealth();
            this.startAutoRefresh();
        },
        async loadHealth() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/diagnostics/health`);
                this.health = response.data;
            } catch (error) {
                $store.toast.error('Failed to load system health');
            } finally {
                this.loading = false;
            }
        },
        startAutoRefresh() {
            if (this.autoRefresh && !this.refreshInterval) {
                this.refreshInterval = setInterval(() => {
                    this.loadHealth();
                }, 30000); // Refresh every 30 seconds
            } else if (!this.autoRefresh && this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },
        getStatusColor(status) {
            const colors = {
                'healthy': 'success',
                'degraded': 'warning',
                'unhealthy': 'danger',
                'unknown': 'secondary'
            };
            return colors[status] || 'secondary';
        },
        formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
        },
        formatUptime(seconds) {
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);

            if (days > 0) return `${days}d ${hours}h ${minutes}m`;
            if (hours > 0) return `${hours}h ${minutes}m`;
            return `${minutes}m`;
        },
        getPercentageColor(percentage) {
            if (percentage >= 90) return 'text-red-600';
            if (percentage >= 70) return 'text-yellow-600';
            return 'text-green-600';
        }
    }" x-init="init()" x-effect="startAutoRefresh()">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">System Health</h1>
                <p class="mt-1 text-sm text-text-secondary">Monitor system status and performance</p>
            </div>
            <div class="flex items-center gap-4">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        x-model="autoRefresh"
                        class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    >
                    <span class="ml-2 text-sm text-text-primary">Auto-refresh (30s)</span>
                </label>
                <x-ui.button variant="secondary" @click="loadHealth()" :disabled="loading">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </x-ui.button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading && !health" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-32" />
        </div>

        <!-- Health Overview -->
        <div x-show="health" class="space-y-6">
            <!-- Overall Status -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.card>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-3" :class="{
                            'bg-green-100 dark:bg-green-900/20': health?.status === 'healthy',
                            'bg-yellow-100 dark:bg-yellow-900/20': health?.status === 'degraded',
                            'bg-red-100 dark:bg-red-900/20': health?.status === 'unhealthy'
                        }">
                            <svg x-show="health?.status === 'healthy'" class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg x-show="health?.status === 'degraded'" class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <svg x-show="health?.status === 'unhealthy'" class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-text-primary capitalize" x-text="health?.status || 'Unknown'"></h3>
                        <p class="mt-1 text-sm text-text-secondary">Overall System Status</p>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-text-primary" x-text="formatUptime(health?.uptime || 0)"></p>
                        <p class="mt-1 text-sm text-text-secondary">System Uptime</p>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-text-primary" x-text="health?.request_count?.toLocaleString() || 0"></p>
                        <p class="mt-1 text-sm text-text-secondary">Total Requests (24h)</p>
                    </div>
                </x-ui.card>
            </div>

            <!-- Service Health -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Service Health</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Database -->
                    <div class="p-4 border border-border-primary rounded-md">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-text-primary">Database</h3>
                            <x-ui.badge x-bind:variant="getStatusColor(health?.services?.database?.status)" x-text="health?.services?.database?.status?.toUpperCase()"></x-ui.badge>
                        </div>
                        <div class="space-y-1 text-sm text-text-secondary">
                            <div>Response: <span class="text-text-primary" x-text="health?.services?.database?.response_time || 'N/A'"></span>ms</div>
                            <div>Connections: <span class="text-text-primary" x-text="health?.services?.database?.connections || 'N/A'"></span></div>
                        </div>
                    </div>

                    <!-- Cache (Redis) -->
                    <div class="p-4 border border-border-primary rounded-md">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-text-primary">Cache (Redis)</h3>
                            <x-ui.badge x-bind:variant="getStatusColor(health?.services?.cache?.status)" x-text="health?.services?.cache?.status?.toUpperCase()"></x-ui.badge>
                        </div>
                        <div class="space-y-1 text-sm text-text-secondary">
                            <div>Response: <span class="text-text-primary" x-text="health?.services?.cache?.response_time || 'N/A'"></span>ms</div>
                            <div>Memory: <span class="text-text-primary" x-text="formatBytes(health?.services?.cache?.memory_used || 0)"></span></div>
                        </div>
                    </div>

                    <!-- Storage -->
                    <div class="p-4 border border-border-primary rounded-md">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-text-primary">Storage</h3>
                            <x-ui.badge x-bind:variant="getStatusColor(health?.services?.storage?.status)" x-text="health?.services?.storage?.status?.toUpperCase()"></x-ui.badge>
                        </div>
                        <div class="space-y-1 text-sm text-text-secondary">
                            <div>Free Space: <span class="text-text-primary" x-text="formatBytes(health?.services?.storage?.free_space || 0)"></span></div>
                            <div>Total: <span class="text-text-primary" x-text="formatBytes(health?.services?.storage?.total_space || 0)"></span></div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- System Resources -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">System Resources</h2>
                <div class="space-y-4">
                    <!-- CPU Usage -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-text-primary">CPU Usage</span>
                            <span class="text-sm" :class="getPercentageColor(health?.resources?.cpu_usage || 0)" x-text="`${Math.round(health?.resources?.cpu_usage || 0)}%`"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all" :style="`width: ${health?.resources?.cpu_usage || 0}%`" :class="{
                                'bg-green-600': (health?.resources?.cpu_usage || 0) < 70,
                                'bg-yellow-600': (health?.resources?.cpu_usage || 0) >= 70 && (health?.resources?.cpu_usage || 0) < 90,
                                'bg-red-600': (health?.resources?.cpu_usage || 0) >= 90
                            }"></div>
                        </div>
                    </div>

                    <!-- Memory Usage -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-text-primary">Memory Usage</span>
                            <span class="text-sm" :class="getPercentageColor(health?.resources?.memory_usage || 0)">
                                <span x-text="formatBytes(health?.resources?.memory_used || 0)"></span> /
                                <span x-text="formatBytes(health?.resources?.memory_total || 0)"></span>
                                (<span x-text="Math.round(health?.resources?.memory_usage || 0)"></span>%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all" :style="`width: ${health?.resources?.memory_usage || 0}%`" :class="{
                                'bg-green-600': (health?.resources?.memory_usage || 0) < 70,
                                'bg-yellow-600': (health?.resources?.memory_usage || 0) >= 70 && (health?.resources?.memory_usage || 0) < 90,
                                'bg-red-600': (health?.resources?.memory_usage || 0) >= 90
                            }"></div>
                        </div>
                    </div>

                    <!-- Disk Usage -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-text-primary">Disk Usage</span>
                            <span class="text-sm" :class="getPercentageColor(health?.resources?.disk_usage || 0)">
                                <span x-text="formatBytes(health?.resources?.disk_used || 0)"></span> /
                                <span x-text="formatBytes(health?.resources?.disk_total || 0)"></span>
                                (<span x-text="Math.round(health?.resources?.disk_usage || 0)"></span>%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all" :style="`width: ${health?.resources?.disk_usage || 0}%`" :class="{
                                'bg-green-600': (health?.resources?.disk_usage || 0) < 70,
                                'bg-yellow-600': (health?.resources?.disk_usage || 0) >= 70 && (health?.resources?.disk_usage || 0) < 90,
                                'bg-red-600': (health?.resources?.disk_usage || 0) >= 90
                            }"></div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Queue Statistics -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Queue Statistics</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 border border-border-primary rounded-md">
                        <p class="text-2xl font-bold text-text-primary" x-text="health?.queues?.pending || 0"></p>
                        <p class="text-sm text-text-secondary">Pending Jobs</p>
                    </div>
                    <div class="text-center p-4 border border-border-primary rounded-md">
                        <p class="text-2xl font-bold text-green-600" x-text="health?.queues?.processed || 0"></p>
                        <p class="text-sm text-text-secondary">Processed (24h)</p>
                    </div>
                    <div class="text-center p-4 border border-border-primary rounded-md">
                        <p class="text-2xl font-bold text-red-600" x-text="health?.queues?.failed || 0"></p>
                        <p class="text-sm text-text-secondary">Failed (24h)</p>
                    </div>
                    <div class="text-center p-4 border border-border-primary rounded-md">
                        <p class="text-2xl font-bold text-text-primary" x-text="`${Math.round(health?.queues?.success_rate || 0)}%`"></p>
                        <p class="text-sm text-text-secondary">Success Rate</p>
                    </div>
                </div>
            </x-ui.card>

            <!-- Recent Issues -->
            <x-ui.card x-show="health?.issues && health.issues.length > 0">
                <h2 class="text-lg font-semibold text-text-primary mb-4">Recent Issues</h2>
                <div class="space-y-3">
                    <template x-for="issue in health?.issues || []" :key="issue.id">
                        <div class="p-3 border-l-4 rounded" :class="{
                            'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20': issue.severity === 'warning',
                            'border-red-500 bg-red-50 dark:bg-red-900/20': issue.severity === 'critical'
                        }">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium" :class="{
                                        'text-yellow-800 dark:text-yellow-200': issue.severity === 'warning',
                                        'text-red-800 dark:text-red-200': issue.severity === 'critical'
                                    }" x-text="issue.message"></p>
                                    <p class="mt-1 text-sm" :class="{
                                        'text-yellow-600 dark:text-yellow-300': issue.severity === 'warning',
                                        'text-red-600 dark:text-red-300': issue.severity === 'critical'
                                    }" x-text="issue.details"></p>
                                </div>
                                <span class="ml-4 text-xs" :class="{
                                    'text-yellow-600 dark:text-yellow-300': issue.severity === 'warning',
                                    'text-red-600 dark:text-red-300': issue.severity === 'critical'
                                }" x-text="issue.timestamp"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
