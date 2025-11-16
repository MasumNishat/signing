<x-layout.app title="Recipients">
    <div x-data="{
        loading: true,
        recipients: [],
        filters: {
            search: '',
            type: 'all',
            status: 'all'
        },
        pagination: {
            current_page: 1,
            per_page: 10,
            total: 0,
            last_page: 1
        },
        selectedRecipients: [],
        async init() {
            await this.loadRecipients();
        },
        async loadRecipients(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                    search: this.filters.search,
                    type: this.filters.type,
                    status: this.filters.status
                });

                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/recipients?${params}`);
                this.recipients = response.data.data;
                this.pagination = response.data.meta;
                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load recipients');
                this.loading = false;
            }
        },
        async deleteRecipient(id) {
            if (!confirm('Delete this recipient?')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/recipients/${id}`);
                $store.toast.success('Recipient deleted');
                this.loadRecipients(this.pagination.current_page);
            } catch (error) {
                $store.toast.error('Failed to delete recipient');
            }
        },
        toggleSelectAll() {
            if (this.selectedRecipients.length === this.recipients.length) {
                this.selectedRecipients = [];
            } else {
                this.selectedRecipients = this.recipients.map(r => r.id);
            }
        },
        async bulkDelete() {
            if (this.selectedRecipients.length === 0) return;
            if (!confirm(`Delete ${this.selectedRecipients.length} recipient(s)?`)) return;

            try {
                await Promise.all(
                    this.selectedRecipients.map(id =>
                        $api.delete(`/accounts/${$store.auth.user.account_id}/recipients/${id}`)
                    )
                );
                $store.toast.success(`${this.selectedRecipients.length} recipient(s) deleted`);
                this.selectedRecipients = [];
                this.loadRecipients(this.pagination.current_page);
            } catch (error) {
                $store.toast.error('Failed to delete recipients');
            }
        }
    }"
    x-init="init()">

        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Recipients</h1>
                <p class="mt-1 text-sm text-text-secondary">Manage envelope recipients and contacts</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/recipients/create'">
                Add Recipient
            </x-ui.button>
        </div>

        <!-- Filters -->
        <x-ui.card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <x-form.input
                        label="Search"
                        placeholder="Search by name or email..."
                        x-model="filters.search"
                        @keyup.enter="loadRecipients(1)"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Type</label>
                    <select x-model="filters.type" class="w-full rounded-md border-border-primary bg-bg-primary text-text-primary">
                        <option value="all">All Types</option>
                        <option value="signer">Signer</option>
                        <option value="cc">CC</option>
                        <option value="in_person_signer">In-Person Signer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Status</label>
                    <select x-model="filters.status" class="w-full rounded-md border-border-primary bg-bg-primary text-text-primary">
                        <option value="all">All Status</option>
                        <option value="created">Created</option>
                        <option value="sent">Sent</option>
                        <option value="delivered">Delivered</option>
                        <option value="signed">Signed</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex justify-end space-x-2">
                <x-ui.button variant="secondary" @click="filters = { search: '', type: 'all', status: 'all' }; loadRecipients(1)">
                    Clear
                </x-ui.button>
                <x-ui.button variant="primary" @click="loadRecipients(1)">
                    Apply Filters
                </x-ui.button>
            </div>
        </x-ui.card>

        <!-- Bulk Actions -->
        <div x-show="selectedRecipients.length > 0" class="mb-4 flex items-center justify-between bg-primary-50 dark:bg-primary-900/20 px-4 py-3 rounded-lg">
            <span class="text-sm font-medium text-text-primary" x-text="`${selectedRecipients.length} recipient(s) selected`"></span>
            <x-ui.button variant="danger" size="sm" @click="bulkDelete()">
                Delete Selected
            </x-ui.button>
        </div>

        <!-- Recipients Table -->
        <x-ui.card :padding="false">
            <div x-show="loading" class="p-6 space-y-4">
                <x-ui.skeleton type="text" class="h-12 w-full" />
                <x-ui.skeleton type="text" class="h-12 w-full" />
                <x-ui.skeleton type="text" class="h-12 w-full" />
            </div>

            <div x-show="!loading">
                <table class="min-w-full divide-y divide-border-primary">
                    <thead class="bg-bg-secondary">
                        <tr>
                            <th scope="col" class="w-12 px-6 py-3">
                                <input type="checkbox"
                                       :checked="selectedRecipients.length === recipients.length && recipients.length > 0"
                                       @change="toggleSelectAll()"
                                       class="rounded border-gray-300">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-bg-primary divide-y divide-border-primary">
                        <template x-for="recipient in recipients" :key="recipient.id">
                            <tr class="hover:bg-bg-hover">
                                <td class="px-6 py-4">
                                    <input type="checkbox"
                                           :value="recipient.id"
                                           x-model="selectedRecipients"
                                           class="rounded border-gray-300">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-text-primary" x-text="recipient.name"></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-text-secondary" x-text="recipient.email"></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                          x-text="recipient.recipient_type"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                          :class="{
                                              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': recipient.status === 'completed',
                                              'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': recipient.status === 'sent',
                                              'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': recipient.status === 'created'
                                          }"
                                          x-text="recipient.status"></span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="window.location.href=`/recipients/${recipient.id}/edit`"
                                            class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button @click="deleteRecipient(recipient.id)"
                                            class="text-red-600 hover:text-red-500 text-sm font-medium">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="recipients.length === 0" class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-primary">No recipients found</h3>
                    <p class="mt-1 text-sm text-text-secondary">Get started by adding a new recipient.</p>
                    <div class="mt-6">
                        <x-ui.button variant="primary" onclick="window.location.href='/recipients/create'">
                            Add Recipient
                        </x-ui.button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div x-show="!loading && recipients.length > 0" class="px-6 py-4 border-t border-card-border flex items-center justify-between">
                <div class="text-sm text-text-secondary">
                    Showing <span x-text="(pagination.current_page - 1) * pagination.per_page + 1"></span>
                    to <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                    of <span x-text="pagination.total"></span> recipients
                </div>
                <div class="flex space-x-2">
                    <x-ui.button variant="secondary" size="sm" @click="loadRecipients(pagination.current_page - 1)" :disabled="pagination.current_page === 1">
                        Previous
                    </x-ui.button>
                    <x-ui.button variant="secondary" size="sm" @click="loadRecipients(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page">
                        Next
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layout.app>
