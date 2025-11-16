<x-layout.app title="Bulk Send">
    <div x-data="{
        batches: [],
        loading: true,
        filter: {
            status: '',
            search: ''
        },
        async init() {
            await this.loadBatches();
        },
        async loadBatches() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filter.status) params.append('status', this.filter.status);
                if (this.filter.search) params.append('search', this.filter.search);

                const response = await $api.get(
                    `/accounts/${$store.auth.user.account_id}/bulk_send_batches?${params.toString()}`
                );
                this.batches = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load bulk send batches');
            } finally {
                this.loading = false;
            }
        },
        async deleteBatch(batchId) {
            if (!confirm('Delete this bulk send batch? This cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/bulk_send_batches/${batchId}`);
                $store.toast.success('Batch deleted successfully');
                await this.loadBatches();
            } catch (error) {
                $store.toast.error('Failed to delete batch');
            }
        },
        getStatusColor(status) {
            const colors = {
                'pending': 'secondary',
                'processing': 'primary',
                'completed': 'success',
                'failed': 'danger'
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
                <h1 class="text-2xl font-bold text-text-primary">Bulk Send</h1>
                <p class="mt-1 text-sm text-text-secondary">Send envelopes to multiple recipients at once</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/bulk/create'">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Bulk Send
            </x-ui.button>
        </div>

        <!-- Filters -->
        <x-ui.card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Search</label>
                    <x-ui.input
                        type="text"
                        x-model="filter.search"
                        @input.debounce.500ms="loadBatches()"
                        placeholder="Search batches..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Status</label>
                    <x-ui.select x-model="filter.status" @change="loadBatches()">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </x-ui.select>
                </div>
                <div class="flex items-end">
                    <x-ui.button variant="secondary" @click="filter = { status: '', search: '' }; loadBatches()">
                        Clear Filters
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-32" />
        </div>

        <!-- Batches List -->
        <div x-show="!loading" class="space-y-4">
            <template x-for="batch in batches" :key="batch.id">
                <x-ui.card>
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-semibold text-text-primary" x-text="batch.batch_name"></h3>
                                <x-ui.badge x-bind:variant="getStatusColor(batch.status)" x-text="batch.status?.toUpperCase()"></x-ui.badge>
                            </div>
                            <p class="mt-1 text-sm text-text-secondary" x-text="batch.template_name || 'No template'"></p>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-text-secondary">Total Recipients</dt>
                                    <dd class="mt-1 text-sm font-semibold text-text-primary" x-text="batch.total_recipients || 0"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-text-secondary">Sent</dt>
                                    <dd class="mt-1 text-sm font-semibold text-green-600" x-text="batch.sent_count || 0"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-text-secondary">Failed</dt>
                                    <dd class="mt-1 text-sm font-semibold text-red-600" x-text="batch.failed_count || 0"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-text-secondary">Created</dt>
                                    <dd class="mt-1 text-sm text-text-primary" x-text="formatDate(batch.created_at)"></dd>
                                </div>
                            </div>
                        </div>
                        <div class="ml-4 flex flex-col space-y-2">
                            <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/bulk/${batch.id}'`">
                                View Details
                            </x-ui.button>
                            <x-ui.button variant="danger" size="sm" @click="deleteBatch(batch.id)">
                                Delete
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            </template>

            <!-- Empty State -->
            <div x-show="batches.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-text-primary">No bulk send batches</h3>
                <p class="mt-1 text-sm text-text-secondary">Get started by creating your first bulk send</p>
                <div class="mt-6">
                    <x-ui.button variant="primary" onclick="window.location.href='/bulk/create'">
                        Create Bulk Send
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
