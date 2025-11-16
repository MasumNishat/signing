<x-layout.app :title="'Webhook: ' . ($webhook->name ?? 'Details')">
    <div x-data="{
        loading: true,
        webhook: null,
        logs: [],
        stats: null,
        async init() {
            await this.loadWebhook();
            await this.loadLogs();
            await this.loadStats();
        },
        async loadWebhook() {
            this.loading = true;
            try {
                const webhookId = '{{ $webhookId }}';
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/connect/${webhookId}`);
                this.webhook = response.data;
            } catch (error) {
                $store.toast.error('Failed to load webhook');
            } finally {
                this.loading = false;
            }
        },
        async loadLogs() {
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/connect/${this.webhook.id}/logs?limit=10`);
                this.logs = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load logs');
            }
        },
        async loadStats() {
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/connect/${this.webhook.id}/statistics`);
                this.stats = response.data;
            } catch (error) {
                console.error('Failed to load statistics');
            }
        },
        async testWebhook() {
            try {
                await $api.post(`/accounts/${$store.auth.user.account_id}/connect/${this.webhook.id}/test`);
                $store.toast.success('Test webhook sent successfully');
                await this.loadLogs();
            } catch (error) {
                $store.toast.error('Failed to send test webhook');
            }
        },
        getStatusColor(status) {
            const colors = {
                'success': 'success',
                'failed': 'danger',
                'pending': 'secondary'
            };
            return colors[status] || 'secondary';
        },
        formatDate(date) {
            return date ? new Date(date).toLocaleString() : 'N/A';
        }
    }" x-init="init()">
        <!-- Loading State -->
        <div x-show="loading" class="space-y-6">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Content -->
        <div x-show="!loading && webhook" class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-text-primary" x-text="webhook?.name"></h1>
                        <x-ui.badge x-bind:variant="webhook?.status === 'active' ? 'success' : 'secondary'" x-text="webhook?.status?.toUpperCase()"></x-ui.badge>
                    </div>
                    <p class="mt-1 text-sm text-text-secondary font-mono" x-text="webhook?.url"></p>
                </div>

                <div class="flex gap-2">
                    <x-ui.button variant="secondary" @click="testWebhook()">Test Webhook</x-ui.button>
                    <x-ui.button variant="secondary" x-bind:onclick="`window.location.href='/connect/${webhook?.id}/logs'`">View All Logs</x-ui.button>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6" x-show="stats">
                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-text-primary" x-text="stats?.total_deliveries || 0"></p>
                        <p class="mt-1 text-sm text-text-secondary">Total Deliveries</p>
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-green-600" x-text="stats?.successful_deliveries || 0"></p>
                        <p class="mt-1 text-sm text-text-secondary">Successful</p>
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-red-600" x-text="stats?.failed_deliveries || 0"></p>
                        <p class="mt-1 text-sm text-text-secondary">Failed</p>
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-text-primary" x-text="stats?.success_rate || '0%'"></p>
                        <p class="mt-1 text-sm text-text-secondary">Success Rate</p>
                    </div>
                </x-ui.card>
            </div>

            <!-- Webhook Details -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Configuration</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Webhook ID</dt>
                        <dd class="mt-1 text-sm text-text-primary font-mono" x-text="webhook?.id"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Created</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="formatDate(webhook?.created_at)"></dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-text-secondary mb-2">Events (<span x-text="webhook?.events?.length || 0"></span>)</dt>
                        <dd class="flex flex-wrap gap-2">
                            <template x-for="event in webhook?.events" :key="event">
                                <span class="px-2 py-1 text-xs bg-bg-secondary border border-border-primary rounded-md text-text-primary" x-text="event"></span>
                            </template>
                        </dd>
                    </div>
                </dl>
            </x-ui.card>

            <!-- Recent Logs -->
            <x-ui.card>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-text-primary">Recent Deliveries</h2>
                    <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/connect/${webhook?.id}/logs'`">
                        View All
                    </x-ui.button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-primary">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Event</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-primary">
                            <template x-for="log in logs" :key="log.id">
                                <tr class="hover:bg-bg-secondary">
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="log.event_type"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <x-ui.badge x-bind:variant="getStatusColor(log.status)" x-text="log.status?.toUpperCase()"></x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="log.response_code || '-'"></td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="formatDate(log.created_at)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="logs.length === 0" class="text-center py-8 text-text-secondary">
                        No delivery logs yet
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
