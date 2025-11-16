<x-layout.app title="Advanced Search">
    <div x-data="{
        loading: false,
        results: [],
        savedSearches: [],
        searchCriteria: {
            query: '',
            status: [],
            date_from: '',
            date_to: '',
            sender: '',
            recipient: '',
            subject: '',
            custom_fields: {},
            tags: [],
            folder_id: '',
            has_attachments: null,
            has_comments: null,
            requires_action: null
        },
        showSaveDialog: false,
        saveName: '',
        async init() {
            await this.loadSavedSearches();
        },
        async loadSavedSearches() {
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/saved_searches`);
                this.savedSearches = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load saved searches');
            }
        },
        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams();

                // Add all non-empty criteria
                Object.keys(this.searchCriteria).forEach(key => {
                    const value = this.searchCriteria[key];
                    if (value !== null && value !== '' &&
                        !(Array.isArray(value) && value.length === 0)) {
                        if (Array.isArray(value)) {
                            value.forEach(v => params.append(`${key}[]`, v));
                        } else if (typeof value === 'object') {
                            params.append(key, JSON.stringify(value));
                        } else {
                            params.append(key, value);
                        }
                    }
                });

                const response = await $api.get(
                    `/accounts/${$store.auth.user.account_id}/envelopes/search?${params.toString()}`
                );
                this.results = response.data.data || response.data;
                $store.toast.success(`Found ${this.results.length} results`);
            } catch (error) {
                $store.toast.error('Search failed');
            } finally {
                this.loading = false;
            }
        },
        async saveSearch() {
            if (!this.saveName) {
                $store.toast.error('Please enter a name for this search');
                return;
            }

            try {
                await $api.post(`/accounts/${$store.auth.user.account_id}/saved_searches`, {
                    name: this.saveName,
                    criteria: this.searchCriteria
                });

                $store.toast.success('Search saved successfully');
                this.showSaveDialog = false;
                this.saveName = '';
                await this.loadSavedSearches();
            } catch (error) {
                $store.toast.error('Failed to save search');
            }
        },
        async loadSearch(search) {
            this.searchCriteria = { ...search.criteria };
            await this.search();
        },
        async deleteSearch(id) {
            if (!confirm('Delete this saved search?')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/saved_searches/${id}`);
                $store.toast.success('Search deleted');
                await this.loadSavedSearches();
            } catch (error) {
                $store.toast.error('Failed to delete search');
            }
        },
        clearCriteria() {
            this.searchCriteria = {
                query: '',
                status: [],
                date_from: '',
                date_to: '',
                sender: '',
                recipient: '',
                subject: '',
                custom_fields: {},
                tags: [],
                folder_id: '',
                has_attachments: null,
                has_comments: null,
                requires_action: null
            };
            this.results = [];
        },
        toggleStatus(status) {
            const index = this.searchCriteria.status.indexOf(status);
            if (index === -1) {
                this.searchCriteria.status.push(status);
            } else {
                this.searchCriteria.status.splice(index, 1);
            }
        },
        isStatusSelected(status) {
            return this.searchCriteria.status.includes(status);
        },
        getStatusColor(status) {
            const colors = {
                'draft': 'secondary',
                'sent': 'primary',
                'delivered': 'info',
                'completed': 'success',
                'declined': 'danger',
                'voided': 'secondary'
            };
            return colors[status] || 'secondary';
        },
        formatDate(date) {
            return date ? new Date(date).toLocaleString() : 'N/A';
        }
    }" x-init="init()">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Advanced Search</h1>
            <p class="mt-1 text-sm text-text-secondary">Search envelopes with advanced filters and criteria</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Sidebar: Search Criteria -->
            <div class="lg:col-span-1">
                <x-ui.card class="mb-6">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Search Filters</h3>

                    <div class="space-y-4">
                        <!-- Text Search -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Keywords</label>
                            <x-ui.input
                                type="text"
                                x-model="searchCriteria.query"
                                placeholder="Search all fields..."
                            />
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">Status</label>
                            <div class="space-y-2">
                                <template x-for="status in ['draft', 'sent', 'delivered', 'completed', 'declined', 'voided']" :key="status">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            :checked="isStatusSelected(status)"
                                            @change="toggleStatus(status)"
                                            class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                        >
                                        <span class="ml-2 text-sm text-text-primary capitalize" x-text="status"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Date Range</label>
                            <div class="space-y-2">
                                <x-ui.input type="date" x-model="searchCriteria.date_from" placeholder="From" />
                                <x-ui.input type="date" x-model="searchCriteria.date_to" placeholder="To" />
                            </div>
                        </div>

                        <!-- Sender -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Sender</label>
                            <x-ui.input
                                type="text"
                                x-model="searchCriteria.sender"
                                placeholder="Sender name or email"
                            />
                        </div>

                        <!-- Recipient -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Recipient</label>
                            <x-ui.input
                                type="text"
                                x-model="searchCriteria.recipient"
                                placeholder="Recipient name or email"
                            />
                        </div>

                        <!-- Subject -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Subject</label>
                            <x-ui.input
                                type="text"
                                x-model="searchCriteria.subject"
                                placeholder="Envelope subject"
                            />
                        </div>

                        <!-- Additional Filters -->
                        <div class="pt-4 border-t border-border-primary space-y-3">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="searchCriteria.has_attachments"
                                    class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                >
                                <span class="ml-2 text-sm text-text-primary">Has Attachments</span>
                            </label>

                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="searchCriteria.has_comments"
                                    class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                >
                                <span class="ml-2 text-sm text-text-primary">Has Comments</span>
                            </label>

                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="searchCriteria.requires_action"
                                    class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                >
                                <span class="ml-2 text-sm text-text-primary">Requires My Action</span>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 space-y-2">
                        <x-ui.button variant="primary" @click="search()" :disabled="loading" class="w-full">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span x-show="!loading">Search</span>
                            <span x-show="loading">Searching...</span>
                        </x-ui.button>
                        <x-ui.button variant="secondary" @click="clearCriteria()" class="w-full">
                            Clear All
                        </x-ui.button>
                        <x-ui.button variant="secondary" @click="showSaveDialog = true" class="w-full">
                            Save Search
                        </x-ui.button>
                    </div>
                </x-ui.card>

                <!-- Saved Searches -->
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Saved Searches</h3>

                    <div x-show="savedSearches.length === 0" class="text-center py-8 text-text-secondary text-sm">
                        No saved searches
                    </div>

                    <div x-show="savedSearches.length > 0" class="space-y-2">
                        <template x-for="search in savedSearches" :key="search.id">
                            <div class="p-3 border border-border-primary rounded-md hover:bg-bg-secondary">
                                <div class="flex items-center justify-between">
                                    <button
                                        @click="loadSearch(search)"
                                        class="flex-1 text-left text-sm font-medium text-text-primary hover:text-primary-600"
                                        x-text="search.name"
                                    ></button>
                                    <button
                                        @click="deleteSearch(search.id)"
                                        class="ml-2 text-red-600 hover:text-red-700"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.card>
            </div>

            <!-- Right: Search Results -->
            <div class="lg:col-span-3">
                <!-- Results Header -->
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-text-primary">
                        Search Results
                        <span x-show="results.length > 0" class="text-text-secondary font-normal">
                            (<span x-text="results.length"></span> found)
                        </span>
                    </h2>
                </div>

                <!-- Loading State -->
                <div x-show="loading" class="space-y-4">
                    <x-ui.skeleton type="card" class="h-32" />
                    <x-ui.skeleton type="card" class="h-32" />
                    <x-ui.skeleton type="card" class="h-32" />
                </div>

                <!-- Empty State -->
                <div x-show="!loading && results.length === 0" class="text-center py-12">
                    <x-ui.card>
                        <svg class="mx-auto h-16 w-16 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-text-primary">Start your search</h3>
                        <p class="mt-2 text-sm text-text-secondary">Use the filters on the left to search for envelopes</p>
                    </x-ui.card>
                </div>

                <!-- Results List -->
                <div x-show="!loading && results.length > 0" class="space-y-4">
                    <template x-for="envelope in results" :key="envelope.id">
                        <x-ui.card>
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <a
                                            :href="`/envelopes/${envelope.id}`"
                                            class="text-lg font-semibold text-text-primary hover:text-primary-600"
                                            x-text="envelope.subject || 'Untitled Envelope'"
                                        ></a>
                                        <x-ui.badge
                                            x-bind:variant="getStatusColor(envelope.status)"
                                            x-text="envelope.status?.toUpperCase()"
                                        ></x-ui.badge>
                                    </div>

                                    <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <span class="text-text-secondary">Sent by:</span>
                                            <span class="ml-1 text-text-primary" x-text="envelope.sender_name"></span>
                                        </div>
                                        <div>
                                            <span class="text-text-secondary">Created:</span>
                                            <span class="ml-1 text-text-primary" x-text="formatDate(envelope.created_at)"></span>
                                        </div>
                                        <div>
                                            <span class="text-text-secondary">Recipients:</span>
                                            <span class="ml-1 text-text-primary" x-text="envelope.recipients_count || 0"></span>
                                        </div>
                                        <div>
                                            <span class="text-text-secondary">Documents:</span>
                                            <span class="ml-1 text-text-primary" x-text="envelope.documents_count || 0"></span>
                                        </div>
                                    </div>

                                    <div x-show="envelope.message" class="mt-2 text-sm text-text-secondary" x-text="envelope.message"></div>
                                </div>

                                <div class="ml-4">
                                    <x-ui.button
                                        variant="secondary"
                                        size="sm"
                                        x-bind:onclick="`window.location.href='/envelopes/${envelope.id}'`"
                                    >
                                        View Details
                                    </x-ui.button>
                                </div>
                            </div>
                        </x-ui.card>
                    </template>
                </div>
            </div>
        </div>

        <!-- Save Search Dialog -->
        <div
            x-show="showSaveDialog"
            x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="showSaveDialog = false"
        >
            <x-ui.card class="max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Save Search</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-text-primary mb-1">Search Name</label>
                    <x-ui.input
                        type="text"
                        x-model="saveName"
                        placeholder="My saved search"
                        @keydown.enter="saveSearch()"
                    />
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <x-ui.button variant="secondary" @click="showSaveDialog = false">Cancel</x-ui.button>
                    <x-ui.button variant="primary" @click="saveSearch()">Save</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
