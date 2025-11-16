<x-layout.app title="Envelopes">
    <div x-data="{
        loading: true,
        envelopes: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        },
        filters: {
            status: '',
            search: '',
            from_date: '',
            to_date: '',
            folder_id: ''
        },
        sortBy: 'created_at',
        sortDirection: 'desc',
        selectedEnvelopes: [],
        bulkActionLoading: false,
        async loadEnvelopes(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                    sort_by: this.sortBy,
                    sort_direction: this.sortDirection
                });

                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.from_date) params.append('from_date', this.filters.from_date);
                if (this.filters.to_date) params.append('to_date', this.filters.to_date);

                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/envelopes?${params}`);
                this.envelopes = response.data.data;
                this.pagination = response.data.meta;
                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load envelopes');
                this.loading = false;
            }
        },
        applyFilters() {
            this.selectedEnvelopes = [];
            this.loadEnvelopes(1);
        },
        clearFilters() {
            this.filters = { status: '', search: '', from_date: '', to_date: '', folder_id: '' };
            this.applyFilters();
        },
        toggleSelectAll() {
            if (this.selectedEnvelopes.length === this.envelopes.length) {
                this.selectedEnvelopes = [];
            } else {
                this.selectedEnvelopes = this.envelopes.map(e => e.id);
            }
        },
        async bulkSend() {
            if (this.selectedEnvelopes.length === 0) return;

            if (!confirm(`Send ${this.selectedEnvelopes.length} envelope(s)?`)) return;

            this.bulkActionLoading = true;
            try {
                await Promise.all(
                    this.selectedEnvelopes.map(id =>
                        $api.post(`/accounts/${$store.auth.user.account_id}/envelopes/${id}/send`)
                    )
                );
                $store.toast.success(`${this.selectedEnvelopes.length} envelope(s) sent successfully`);
                this.selectedEnvelopes = [];
                this.loadEnvelopes(this.pagination.current_page);
            } catch (error) {
                $store.toast.error('Failed to send envelopes');
            } finally {
                this.bulkActionLoading = false;
            }
        },
        async bulkVoid() {
            if (this.selectedEnvelopes.length === 0) return;

            const reason = prompt('Enter void reason:');
            if (!reason) return;

            this.bulkActionLoading = true;
            try {
                await Promise.all(
                    this.selectedEnvelopes.map(id =>
                        $api.post(`/accounts/${$store.auth.user.account_id}/envelopes/${id}/void`, { voided_reason: reason })
                    )
                );
                $store.toast.success(`${this.selectedEnvelopes.length} envelope(s) voided`);
                this.selectedEnvelopes = [];
                this.loadEnvelopes(this.pagination.current_page);
            } catch (error) {
                $store.toast.error('Failed to void envelopes');
            } finally {
                this.bulkActionLoading = false;
            }
        },
        async bulkDelete() {
            if (this.selectedEnvelopes.length === 0) return;

            if (!confirm(`Delete ${this.selectedEnvelopes.length} envelope(s)? This cannot be undone.`)) return;

            this.bulkActionLoading = true;
            try {
                await Promise.all(
                    this.selectedEnvelopes.map(id =>
                        $api.delete(`/accounts/${$store.auth.user.account_id}/envelopes/${id}`)
                    )
                );
                $store.toast.success(`${this.selectedEnvelopes.length} envelope(s) deleted`);
                this.selectedEnvelopes = [];
                this.loadEnvelopes(this.pagination.current_page);
            } catch (error) {
                $store.toast.error('Failed to delete envelopes');
            } finally {
                this.bulkActionLoading = false;
            }
        },
        getStatusColor(status) {
            const colors = {
                'draft': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                'sent': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                'delivered': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                'completed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'voided': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }
    }" x-init="loadEnvelopes()">

        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Envelopes</h1>
                <p class="mt-1 text-sm text-text-secondary">Manage and track your document envelopes</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/envelopes/create'">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Envelope
            </x-ui.button>
        </div>

        <!-- Filters -->
        <x-ui.card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <x-form.input
                    name="search"
                    label="Search"
                    placeholder="Subject or recipient..."
                    x-model="filters.search"
                    @keyup.enter="applyFilters()"
                />

                <!-- Status Filter -->
                <x-form.select
                    name="status"
                    label="Status"
                    x-model="filters.status"
                    :options="[
                        '' => 'All Statuses',
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'voided' => 'Voided'
                    ]"
                />

                <!-- Date From -->
                <x-form.input
                    name="from_date"
                    label="From Date"
                    type="date"
                    x-model="filters.from_date"
                />

                <!-- Date To -->
                <x-form.input
                    name="to_date"
                    label="To Date"
                    type="date"
                    x-model="filters.to_date"
                />
            </div>

            <div class="mt-4 flex gap-2">
                <x-ui.button variant="primary" @click="applyFilters()">
                    Apply Filters
                </x-ui.button>
                <x-ui.button variant="secondary" @click="clearFilters()">
                    Clear
                </x-ui.button>
            </div>
        </x-ui.card>

        <!-- Bulk Actions -->
        <div x-show="selectedEnvelopes.length > 0" class="mb-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-text-primary">
                    <span x-text="selectedEnvelopes.length"></span> envelope(s) selected
                </span>
                <div class="flex gap-2">
                    <x-ui.button size="sm" variant="primary" @click="bulkSend()" :loading="bulkActionLoading">
                        Send Selected
                    </x-ui.button>
                    <x-ui.button size="sm" variant="secondary" @click="bulkVoid()" :loading="bulkActionLoading">
                        Void Selected
                    </x-ui.button>
                    <x-ui.button size="sm" variant="danger" @click="bulkDelete()" :loading="bulkActionLoading">
                        Delete Selected
                    </x-ui.button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading">
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Envelopes Table -->
        <div x-show="!loading">
            <x-ui.card :padding="false">
                <!-- Empty State -->
                <div x-show="envelopes.length === 0" class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-primary">No envelopes found</h3>
                    <p class="mt-1 text-sm text-text-secondary">Try adjusting your filters or create a new envelope.</p>
                    <div class="mt-6">
                        <x-ui.button variant="primary" onclick="window.location.href='/envelopes/create'">
                            Create Envelope
                        </x-ui.button>
                    </div>
                </div>

                <!-- Table -->
                <div x-show="envelopes.length > 0" class="overflow-x-auto">
                    <x-table.table>
                        <x-table.thead>
                            <x-table.row>
                                <th class="px-6 py-3 w-12">
                                    <input type="checkbox"
                                           @change="toggleSelectAll()"
                                           :checked="selectedEnvelopes.length === envelopes.length && envelopes.length > 0"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </th>
                                <x-table.sortable-header column="email_subject">Subject</x-table.sortable-header>
                                <x-table.sortable-header column="status">Status</x-table.sortable-header>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Recipients</th>
                                <x-table.sortable-header column="created_at">Created</x-table.sortable-header>
                                <x-table.sortable-header column="sent_at">Sent</x-table.sortable-header>
                                <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">Actions</th>
                            </x-table.row>
                        </x-table.thead>
                        <x-table.tbody>
                            <template x-for="envelope in envelopes" :key="envelope.id">
                                <x-table.row class="hover:bg-bg-hover">
                                    <x-table.cell>
                                        <input type="checkbox"
                                               :value="envelope.id"
                                               x-model="selectedEnvelopes"
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    </x-table.cell>
                                    <x-table.cell>
                                        <a :href="`/envelopes/${envelope.id}`" class="font-medium text-primary-600 hover:text-primary-500" x-text="envelope.email_subject"></a>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                              :class="getStatusColor(envelope.status)"
                                              x-text="envelope.status.charAt(0).toUpperCase() + envelope.status.slice(1)">
                                        </span>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span x-text="envelope.recipients_count + ' recipient' + (envelope.recipients_count !== 1 ? 's' : '')"></span>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span x-text="new Date(envelope.created_at).toLocaleDateString()"></span>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span x-text="envelope.sent_at ? new Date(envelope.sent_at).toLocaleDateString() : '-'"></span>
                                    </x-table.cell>
                                    <x-table.cell align="right">
                                        <x-table.actions>
                                            <a :href="`/envelopes/${envelope.id}`" class="block px-4 py-2 text-sm hover:bg-dropdown-hover">View</a>
                                            <a :href="`/envelopes/${envelope.id}/edit`"
                                               x-show="envelope.status === 'draft'"
                                               class="block px-4 py-2 text-sm hover:bg-dropdown-hover">Edit</a>
                                            <button @click="window.location.href=`/envelopes/${envelope.id}`"
                                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-dropdown-hover">
                                                Download
                                            </button>
                                        </x-table.actions>
                                    </x-table.cell>
                                </x-table.row>
                            </template>
                        </x-table.tbody>
                    </x-table.table>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-card-border">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-text-secondary">
                                Showing
                                <span class="font-medium" x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span>
                                to
                                <span class="font-medium" x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                                of
                                <span class="font-medium" x-text="pagination.total"></span>
                                results
                            </div>
                            <div class="flex gap-2">
                                <x-ui.button
                                    size="sm"
                                    variant="secondary"
                                    @click="loadEnvelopes(pagination.current_page - 1)"
                                    :disabled="pagination.current_page === 1">
                                    Previous
                                </x-ui.button>
                                <x-ui.button
                                    size="sm"
                                    variant="secondary"
                                    @click="loadEnvelopes(pagination.current_page + 1)"
                                    :disabled="pagination.current_page === pagination.last_page">
                                    Next
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
