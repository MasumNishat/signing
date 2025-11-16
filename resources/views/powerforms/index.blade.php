<x-layout.app title="PowerForms">
    <div x-data="{
        powerforms: [],
        loading: true,
        filter: {
            status: '',
            search: ''
        },
        async init() {
            await this.loadPowerForms();
        },
        async loadPowerForms() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filter.status) params.append('status', this.filter.status);
                if (this.filter.search) params.append('search', this.filter.search);

                const response = await $api.get(
                    `/accounts/${$store.auth.user.account_id}/powerforms?${params.toString()}`
                );
                this.powerforms = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load PowerForms');
            } finally {
                this.loading = false;
            }
        },
        async deletePowerForm(id) {
            if (!confirm('Delete this PowerForm? This cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/powerforms/${id}`);
                $store.toast.success('PowerForm deleted successfully');
                await this.loadPowerForms();
            } catch (error) {
                $store.toast.error('Failed to delete PowerForm');
            }
        },
        copyPublicUrl(url) {
            navigator.clipboard.writeText(url);
            $store.toast.success('Public URL copied to clipboard');
        },
        getStatusColor(status) {
            const colors = {
                'active': 'success',
                'inactive': 'secondary',
                'disabled': 'danger'
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
                <h1 class="text-2xl font-bold text-text-primary">PowerForms</h1>
                <p class="mt-1 text-sm text-text-secondary">Create public forms for document signing</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/powerforms/create'">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New PowerForm
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
                        @input.debounce.500ms="loadPowerForms()"
                        placeholder="Search PowerForms..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Status</label>
                    <x-ui.select x-model="filter.status" @change="loadPowerForms()">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="disabled">Disabled</option>
                    </x-ui.select>
                </div>
                <div class="flex items-end">
                    <x-ui.button variant="secondary" @click="filter = { status: '', search: '' }; loadPowerForms()">
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

        <!-- PowerForms List -->
        <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="powerform in powerforms" :key="powerform.id">
                <x-ui.card>
                    <div class="flex flex-col h-full">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-text-primary" x-text="powerform.name"></h3>
                                <x-ui.badge class="mt-2" x-bind:variant="getStatusColor(powerform.status)" x-text="powerform.status?.toUpperCase()"></x-ui.badge>
                            </div>
                        </div>

                        <p class="text-sm text-text-secondary mb-4 flex-1" x-text="powerform.template_name || 'No template'"></p>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-text-secondary">Submissions:</span>
                                <span class="font-medium text-text-primary" x-text="powerform.submission_count || 0"></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-text-secondary">Created:</span>
                                <span class="text-text-primary" x-text="formatDate(powerform.created_at)"></span>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2 pt-4 border-t border-border-primary">
                            <x-ui.button variant="secondary" size="sm" @click="copyPublicUrl(powerform.public_url)" x-show="powerform.public_url">
                                Copy Public URL
                            </x-ui.button>
                            <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/powerforms/${powerform.id}'`">
                                View Details
                            </x-ui.button>
                            <x-ui.button variant="danger" size="sm" @click="deletePowerForm(powerform.id)">
                                Delete
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            </template>

            <!-- Empty State -->
            <div x-show="powerforms.length === 0" class="col-span-full text-center py-12">
                <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-text-primary">No PowerForms</h3>
                <p class="mt-1 text-sm text-text-secondary">Get started by creating your first PowerForm</p>
                <div class="mt-6">
                    <x-ui.button variant="primary" onclick="window.location.href='/powerforms/create'">
                        Create PowerForm
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
