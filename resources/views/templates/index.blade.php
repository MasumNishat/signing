<x-layout.app title="Templates">
    <div x-data="{
        loading: true,
        templates: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        },
        filters: {
            search: '',
            folder_id: ''
        },
        sortBy: 'created_at',
        sortDirection: 'desc',
        async loadTemplates(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                    sort_by: this.sortBy,
                    sort_direction: this.sortDirection
                });

                if (this.filters.search) params.append('search', this.filters.search);

                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/templates?${params}`);
                this.templates = response.data.data;
                this.pagination = response.data.meta;
                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load templates');
                this.loading = false;
            }
        },
        applyFilters() {
            this.loadTemplates(1);
        },
        clearFilters() {
            this.filters = { search: '', folder_id: '' };
            this.applyFilters();
        },
        async deleteTemplate(templateId) {
            if (!confirm('Delete this template? This cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/templates/${templateId}`);
                $store.toast.success('Template deleted');
                this.loadTemplates(this.pagination.current_page);
            } catch (error) {
                $store.toast.error('Failed to delete template');
            }
        },
        async useTemplate(templateId) {
            try {
                const response = await $api.post(`/accounts/${$store.auth.user.account_id}/templates/${templateId}/envelopes`);
                $store.toast.success('Envelope created from template');
                window.location.href = `/envelopes/${response.data.id}`;
            } catch (error) {
                $store.toast.error('Failed to create envelope from template');
            }
        }
    }" x-init="loadTemplates()">

        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Templates</h1>
                <p class="mt-1 text-sm text-text-secondary">Reusable document templates for faster envelope creation</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/templates/create'">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Template
            </x-ui.button>
        </div>

        <!-- Filters -->
        <x-ui.card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Search -->
                <x-form.input
                    name="search"
                    label="Search"
                    placeholder="Template name or description..."
                    x-model="filters.search"
                    @keyup.enter="applyFilters()"
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

        <!-- Loading State -->
        <div x-show="loading">
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Templates Grid -->
        <div x-show="!loading">
            <!-- Empty State -->
            <div x-show="templates.length === 0" class="text-center py-12">
                <x-ui.card>
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-primary">No templates found</h3>
                    <p class="mt-1 text-sm text-text-secondary">Create your first template to get started.</p>
                    <div class="mt-6">
                        <x-ui.button variant="primary" onclick="window.location.href='/templates/create'">
                            Create Template
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>

            <!-- Templates Grid -->
            <div x-show="templates.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="template in templates" :key="template.id">
                    <x-ui.card class="hover:shadow-lg transition-shadow cursor-pointer" @click="window.location.href='/templates/' + template.id">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <x-table.actions @click.stop="">
                                <button @click="useTemplate(template.id)" class="block w-full text-left px-4 py-2 text-sm hover:bg-dropdown-hover">
                                    Use Template
                                </button>
                                <a :href="`/templates/${template.id}/edit`" class="block px-4 py-2 text-sm hover:bg-dropdown-hover" @click.stop="">
                                    Edit
                                </a>
                                <button @click="deleteTemplate(template.id)" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-dropdown-hover" @click.stop="">
                                    Delete
                                </button>
                            </x-table.actions>
                        </div>

                        <h3 class="text-lg font-semibold text-text-primary mb-2" x-text="template.name"></h3>
                        <p class="text-sm text-text-secondary mb-4 line-clamp-2" x-text="template.description || 'No description'"></p>

                        <div class="flex items-center justify-between text-sm text-text-secondary">
                            <div class="flex items-center space-x-4">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span x-text="template.documents_count || 0"></span>
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span x-text="template.recipients_count || 0"></span>
                                </span>
                            </div>
                            <span class="text-xs" x-text="new Date(template.created_at).toLocaleDateString()"></span>
                        </div>

                        <div class="mt-4 pt-4 border-t border-card-border" @click.stop="">
                            <x-ui.button variant="primary" size="sm" @click="useTemplate(template.id)" class="w-full">
                                Use This Template
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                </template>
            </div>

            <!-- Pagination -->
            <div x-show="templates.length > 0" class="mt-6">
                <x-ui.card>
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
                                @click="loadTemplates(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1">
                                Previous
                            </x-ui.button>
                            <x-ui.button
                                size="sm"
                                variant="secondary"
                                @click="loadTemplates(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page">
                                Next
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layout.app>
