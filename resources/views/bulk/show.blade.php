<x-layout.app :title="'Bulk Send: ' . ($batch->batch_name ?? 'Details')">
    <div x-data="{
        loading: true,
        batch: null,
        recipients: [],
        async init() {
            await this.loadBatch();
        },
        async loadBatch() {
            this.loading = true;
            try {
                const batchId = '{{ $batchId }}';
                const accountId = $store.auth.user.account_id;

                // Load batch details
                const batchResponse = await $api.get(`/accounts/${accountId}/bulk_send_batches/${batchId}`);
                this.batch = batchResponse.data;

                // Load recipients
                const recipientsResponse = await $api.get(`/accounts/${accountId}/bulk_send_batches/${batchId}/recipients`);
                this.recipients = recipientsResponse.data.data || recipientsResponse.data;
            } catch (error) {
                $store.toast.error('Failed to load batch details');
            } finally {
                this.loading = false;
            }
        },
        async startBatch() {
            if (!confirm('Start this bulk send batch now?')) return;

            try {
                await $api.post(`/accounts/${$store.auth.user.account_id}/bulk_send_batches/${this.batch.id}/send`);
                $store.toast.success('Batch started successfully');
                await this.loadBatch();
            } catch (error) {
                $store.toast.error('Failed to start batch');
            }
        },
        getStatusColor(status) {
            const colors = {
                'pending': 'secondary',
                'processing': 'primary',
                'sent': 'success',
                'failed': 'danger'
            };
            return colors[status] || 'secondary';
        },
        getBatchStatusColor(status) {
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
        },
        getProgress() {
            if (!this.batch || !this.batch.total_recipients) return 0;
            const processed = (this.batch.sent_count || 0) + (this.batch.failed_count || 0);
            return Math.round((processed / this.batch.total_recipients) * 100);
        }
    }" x-init="init()">
        <!-- Loading State -->
        <div x-show="loading" class="space-y-6">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Content -->
        <div x-show="!loading && batch" class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-text-primary" x-text="batch?.batch_name"></h1>
                        <x-ui.badge x-bind:variant="getBatchStatusColor(batch?.status)" x-text="batch?.status?.toUpperCase()"></x-ui.badge>
                    </div>
                    <p class="mt-1 text-sm text-text-secondary">
                        Template: <span x-text="batch?.template_name || 'N/A'"></span>
                    </p>
                    <p class="mt-1 text-xs text-text-secondary">
                        Created on <span x-text="formatDate(batch?.created_at)"></span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <x-ui.button variant="primary" @click="startBatch()" x-show="batch?.status === 'pending'">
                        Start Batch
                    </x-ui.button>
                    <x-ui.button variant="secondary" onclick="window.location.href='/bulk'">
                        Back to List
                    </x-ui.button>
                </div>
            </div>

            <!-- Progress Card -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Progress</h2>
                <div class="space-y-4">
                    <!-- Progress Bar -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-text-secondary">Overall Progress</span>
                            <span class="text-sm font-medium text-text-primary" x-text="getProgress() + '%'"></span>
                        </div>
                        <div class="w-full bg-bg-secondary rounded-full h-2">
                            <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" x-bind:style="`width: ${getProgress()}%`"></div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                        <div class="p-4 bg-bg-secondary rounded-lg">
                            <dt class="text-sm font-medium text-text-secondary">Total Recipients</dt>
                            <dd class="mt-1 text-2xl font-bold text-text-primary" x-text="batch?.total_recipients || 0"></dd>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <dt class="text-sm font-medium text-green-600">Sent</dt>
                            <dd class="mt-1 text-2xl font-bold text-green-600" x-text="batch?.sent_count || 0"></dd>
                        </div>
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <dt class="text-sm font-medium text-red-600">Failed</dt>
                            <dd class="mt-1 text-2xl font-bold text-red-600" x-text="batch?.failed_count || 0"></dd>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                            <dt class="text-sm font-medium text-text-secondary">Pending</dt>
                            <dd class="mt-1 text-2xl font-bold text-text-primary" x-text="(batch?.total_recipients || 0) - ((batch?.sent_count || 0) + (batch?.failed_count || 0))"></dd>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Recipients Table -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Recipients (<span x-text="recipients.length"></span>)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-primary">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Envelope</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-primary">
                            <template x-for="(recipient, index) in recipients" :key="recipient.id">
                                <tr>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="index + 1"></td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="recipient.name"></td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="recipient.email"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <x-ui.badge variant="secondary" x-text="recipient.role?.toUpperCase()"></x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <x-ui.badge x-bind:variant="getStatusColor(recipient.status)" x-text="recipient.status?.toUpperCase()"></x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <template x-if="recipient.envelope_id">
                                            <a :href="`/envelopes/${recipient.envelope_id}`" class="text-primary-600 hover:underline">View</a>
                                        </template>
                                        <template x-if="!recipient.envelope_id">
                                            <span class="text-text-secondary">-</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="recipients.length === 0" class="text-center py-8 text-text-secondary">
                        No recipients in this batch
                    </div>
                </div>
            </x-ui.card>

            <!-- Batch Details -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Batch Details</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Batch ID</dt>
                        <dd class="mt-1 text-sm text-text-primary font-mono" x-text="batch?.id"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Template</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="batch?.template_name || 'N/A'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Created</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="formatDate(batch?.created_at)"></dd>
                    </div>
                    <div x-show="batch?.started_at">
                        <dt class="text-sm font-medium text-text-secondary">Started</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="formatDate(batch?.started_at)"></dd>
                    </div>
                    <div x-show="batch?.completed_at">
                        <dt class="text-sm font-medium text-text-secondary">Completed</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="formatDate(batch?.completed_at)"></dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
