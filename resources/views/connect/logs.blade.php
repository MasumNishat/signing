<x-layout.app :title="'Webhook Logs'">
    <div x-data="{
        loading: true,
        webhook: null,
        logs: [],
        filter: {
            status: '',
            event_type: '',
            date_from: '',
            date_to: ''
        },
        pagination: {
            current_page: 1,
            per_page: 25,
            total: 0
        },
        async init() {
            await this.loadWebhook();
            await this.loadLogs();
        },
        async loadWebhook() {
            try {
                const webhookId = '{{ $webhookId }}';
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/connect/${webhookId}`);
                this.webhook = response.data;
            } catch (error) {
                $store.toast.error('Failed to load webhook');
            }
        },
        async loadLogs(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                if (this.filter.status) params.append('status', this.filter.status);
                if (this.filter.event_type) params.append('event_type', this.filter.event_type);
                if (this.filter.date_from) params.append('date_from', this.filter.date_from);
                if (this.filter.date_to) params.append('date_to', this.filter.date_to);

                const response = await $api.get(
                    `/accounts/${$store.auth.user.account_id}/connect/${this.webhook.id}/logs?${params.toString()}`
                );
                this.logs = response.data.data || response.data;
                this.pagination = response.data.meta || this.pagination;
            } catch (error) {
                $store.toast.error('Failed to load logs');
            } finally {
                this.loading = false;
            }
        },
        async retryFailed() {
            if (!confirm('Retry all failed deliveries?')) return;
            try {
                await $api.post(`/accounts/${$store.auth.user.account_id}/connect/${this.webhook.id}/retry`);
                $store.toast.success('Retry initiated for failed deliveries');
                await this.loadLogs();
            } catch (error) {
                $store.toast.error('Failed to retry deliveries');
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
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-2">
                    <a href="/connect" class="text-text-secondary hover:text-text-primary">Webhooks</a>
                    <span class="text-text-secondary">/</span>
                    <a :href="`/connect/${webhook?.id}`" class="text-text-secondary hover:text-text-primary" x-text="webhook?.name"></a>
                    <span class="text-text-secondary">/</span>
                    <span class="text-text-primary">Logs</span>
                </div>
                <h1 class="mt-2 text-2xl font-bold text-text-primary">Delivery Logs</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    <span x-text="pagination.total"></span> total deliveries
                </p>
            </div>
            <x-ui.button variant="secondary" @click="retryFailed()">
                Retry Failed
            </x-ui.button>
        </div>

        <!-- Filters -->
        <x-ui.card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Status</label>
                    <x-ui.select x-model="filter.status" @change="loadLogs()">
                        <option value="">All Statuses</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                        <option value="pending">Pending</option>
                    </x-ui.select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Event Type</label>
                    <x-ui.input
                        type="text"
                        x-model="filter.event_type"
                        @input.debounce.500ms="loadLogs()"
                        placeholder="envelope-sent"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">From Date</label>
                    <x-ui.input type="date" x-model="filter.date_from" @change="loadLogs()" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">To Date</label>
                    <x-ui.input type="date" x-model="filter.date_to" @change="loadLogs()" />
                </div>
            </div>
            <div class="mt-4">
                <x-ui.button variant="secondary" size="sm" @click="filter = { status: '', event_type: '', date_from: '', date_to: '' }; loadLogs()">
                    Clear Filters
                </x-ui.button>
            </div>
        </x-ui.card>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-32" />
        </div>

        <!-- Logs Table -->
        <x-ui.card x-show="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-primary">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Response Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Timestamp</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Retries</th>
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
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="log.response_time ? `${log.response_time}ms` : '-'"></td>
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="formatDate(log.created_at)"></td>
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="log.retry_count || 0"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="logs.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-primary">No logs</h3>
                    <p class="mt-1 text-sm text-text-secondary">No delivery logs found</p>
                </div>
            </div>

            <!-- Pagination -->
            <div x-show="pagination.total > pagination.per_page" class="mt-4 flex items-center justify-between">
                <div class="text-sm text-text-secondary">
                    Showing <span x-text="(pagination.current_page - 1) * pagination.per_page + 1"></span>
                    to <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                    of <span x-text="pagination.total"></span> results
                </div>
                <div class="flex space-x-2">
                    <x-ui.button variant="secondary" size="sm" @click="loadLogs(pagination.current_page - 1)" :disabled="pagination.current_page === 1">
                        Previous
                    </x-ui.button>
                    <x-ui.button variant="secondary" size="sm" @click="loadLogs(pagination.current_page + 1)" :disabled="pagination.current_page * pagination.per_page >= pagination.total">
                        Next
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layout.app>
