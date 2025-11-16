<x-layout.app title="Request Logs">
    <div x-data="{
        loading: true,
        logs: [],
        filter: {
            method: '',
            status_code: '',
            endpoint: '',
            user_id: '',
            date_from: '',
            date_to: '',
            min_duration: ''
        },
        pagination: {
            current_page: 1,
            per_page: 50,
            total: 0
        },
        async init() {
            await this.loadLogs();
        },
        async loadLogs(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);

                // Add filters
                Object.keys(this.filter).forEach(key => {
                    if (this.filter[key]) {
                        params.append(key, this.filter[key]);
                    }
                });

                const response = await $api.get(
                    `/accounts/${$store.auth.user.account_id}/diagnostics/logs?${params.toString()}`
                );

                this.logs = response.data.data || response.data;
                this.pagination = response.data.meta || this.pagination;
            } catch (error) {
                $store.toast.error('Failed to load logs');
            } finally {
                this.loading = false;
            }
        },
        async exportLogs() {
            try {
                const params = new URLSearchParams();
                Object.keys(this.filter).forEach(key => {
                    if (this.filter[key]) {
                        params.append(key, this.filter[key]);
                    }
                });

                const response = await $api.get(
                    `/accounts/${$store.auth.user.account_id}/diagnostics/logs/export?${params.toString()}`,
                    { responseType: 'blob' }
                );

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const a = document.createElement('a');
                a.href = url;
                a.download = `request-logs-${Date.now()}.csv`;
                a.click();
                window.URL.revokeObjectURL(url);

                $store.toast.success('Logs exported successfully');
            } catch (error) {
                $store.toast.error('Failed to export logs');
            }
        },
        async clearOldLogs() {
            if (!confirm('Delete all logs older than 30 days? This cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/diagnostics/logs/cleanup`);
                $store.toast.success('Old logs cleared successfully');
                await this.loadLogs();
            } catch (error) {
                $store.toast.error('Failed to clear logs');
            }
        },
        getMethodColor(method) {
            const colors = {
                'GET': 'info',
                'POST': 'success',
                'PUT': 'warning',
                'PATCH': 'warning',
                'DELETE': 'danger'
            };
            return colors[method] || 'secondary';
        },
        getStatusColor(code) {
            if (code >= 200 && code < 300) return 'success';
            if (code >= 300 && code < 400) return 'info';
            if (code >= 400 && code < 500) return 'warning';
            if (code >= 500) return 'danger';
            return 'secondary';
        },
        formatDuration(ms) {
            if (ms < 1000) return `${ms}ms`;
            return `${(ms / 1000).toFixed(2)}s`;
        },
        formatDate(date) {
            return date ? new Date(date).toLocaleString() : 'N/A';
        }
    }" x-init="init()">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Request Logs</h1>
                <p class="mt-1 text-sm text-text-secondary">Monitor API requests and performance</p>
            </div>
            <div class="flex gap-2">
                <x-ui.button variant="secondary" @click="exportLogs()">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </x-ui.button>
                <x-ui.button variant="danger" @click="clearOldLogs()">
                    Clear Old Logs
                </x-ui.button>
            </div>
        </div>

        <!-- Filters -->
        <x-ui.card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Method</label>
                    <x-ui.select x-model="filter.method" @change="loadLogs()">
                        <option value="">All Methods</option>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="PATCH">PATCH</option>
                        <option value="DELETE">DELETE</option>
                    </x-ui.select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Status</label>
                    <x-ui.select x-model="filter.status_code" @change="loadLogs()">
                        <option value="">All Statuses</option>
                        <option value="200">200 OK</option>
                        <option value="201">201 Created</option>
                        <option value="400">400 Bad Request</option>
                        <option value="401">401 Unauthorized</option>
                        <option value="404">404 Not Found</option>
                        <option value="500">500 Server Error</option>
                    </x-ui.select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Endpoint</label>
                    <x-ui.input
                        type="text"
                        x-model="filter.endpoint"
                        @input.debounce.500ms="loadLogs()"
                        placeholder="/api/v2.1/..."
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Min Duration (ms)</label>
                    <x-ui.input
                        type="number"
                        x-model="filter.min_duration"
                        @input.debounce.500ms="loadLogs()"
                        placeholder="1000"
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
                <x-ui.button
                    variant="secondary"
                    size="sm"
                    @click="filter = { method: '', status_code: '', endpoint: '', user_id: '', date_from: '', date_to: '', min_duration: '' }; loadLogs()"
                >
                    Clear Filters
                </x-ui.button>
            </div>
        </x-ui.card>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="card" class="h-24" />
            <x-ui.skeleton type="card" class="h-24" />
            <x-ui.skeleton type="card" class="h-24" />
        </div>

        <!-- Logs Table -->
        <x-ui.card x-show="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-primary">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Timestamp</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Method</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Endpoint</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-primary">
                        <template x-for="log in logs" :key="log.id">
                            <tr class="hover:bg-bg-secondary cursor-pointer" @click="$refs[`details-${log.id}`].classList.toggle('hidden')">
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="formatDate(log.created_at)"></td>
                                <td class="px-4 py-3 text-sm">
                                    <x-ui.badge x-bind:variant="getMethodColor(log.method)" x-text="log.method"></x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-sm font-mono text-text-primary" x-text="log.endpoint"></td>
                                <td class="px-4 py-3 text-sm">
                                    <x-ui.badge x-bind:variant="getStatusColor(log.status_code)" x-text="log.status_code"></x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="formatDuration(log.duration)"></td>
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="log.user_email || 'Guest'"></td>
                                <td class="px-4 py-3 text-sm font-mono text-text-primary" x-text="log.ip_address"></td>
                            </tr>
                            <!-- Expandable Details Row -->
                            <tr :x-ref="`details-${log.id}`" class="hidden bg-bg-secondary">
                                <td colspan="7" class="px-4 py-4">
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <h4 class="font-semibold text-text-primary mb-2">Request Headers</h4>
                                            <pre class="text-xs bg-bg-primary p-2 rounded border border-border-primary overflow-auto max-h-32" x-text="JSON.stringify(log.request_headers || {}, null, 2)"></pre>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-text-primary mb-2">Response Headers</h4>
                                            <pre class="text-xs bg-bg-primary p-2 rounded border border-border-primary overflow-auto max-h-32" x-text="JSON.stringify(log.response_headers || {}, null, 2)"></pre>
                                        </div>
                                        <div x-show="log.request_body">
                                            <h4 class="font-semibold text-text-primary mb-2">Request Body</h4>
                                            <pre class="text-xs bg-bg-primary p-2 rounded border border-border-primary overflow-auto max-h-32" x-text="JSON.stringify(log.request_body || {}, null, 2)"></pre>
                                        </div>
                                        <div x-show="log.response_body">
                                            <h4 class="font-semibold text-text-primary mb-2">Response Body</h4>
                                            <pre class="text-xs bg-bg-primary p-2 rounded border border-border-primary overflow-auto max-h-32" x-text="JSON.stringify(log.response_body || {}, null, 2)"></pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="logs.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-primary">No logs found</h3>
                    <p class="mt-1 text-sm text-text-secondary">Try adjusting your filters</p>
                </div>
            </div>

            <!-- Pagination -->
            <div x-show="pagination.total > pagination.per_page" class="mt-4 flex items-center justify-between border-t border-border-primary pt-4">
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
