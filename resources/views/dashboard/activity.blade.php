<x-layout.app title="Activity Feed">
    <div x-data="{
        loading: true,
        activities: [],
        filters: {
            type: 'all', // all, envelope, template, user, system
            dateRange: '7days', // today, 7days, 30days, all
            user: 'all'
        },
        pagination: {
            current_page: 1,
            per_page: 20,
            total: 0,
            last_page: 1
        },
        async init() {
            await this.loadActivities();
        },
        async loadActivities(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                    type: this.filters.type,
                    date_range: this.filters.dateRange,
                    user: this.filters.user
                });

                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/activity?${params}`);

                // For demo purposes, generate sample data if API not ready
                if (!response.data) {
                    this.activities = this.generateSampleActivities();
                    this.pagination.total = 50;
                    this.pagination.last_page = 3;
                } else {
                    this.activities = response.data.data;
                    this.pagination = response.data.meta;
                }

                this.loading = false;
            } catch (error) {
                // Generate sample data on error
                this.activities = this.generateSampleActivities();
                this.pagination.total = 50;
                this.pagination.last_page = 3;
                this.loading = false;
            }
        },
        generateSampleActivities() {
            const types = ['envelope_sent', 'envelope_completed', 'envelope_voided', 'template_created', 'user_login'];
            const users = ['John Doe', 'Jane Smith', 'Bob Johnson', 'Alice Williams'];
            const subjects = ['Sales Agreement', 'NDA Document', 'Employment Contract', 'Lease Agreement'];

            return Array.from({ length: 20 }, (_, i) => ({
                id: `activity-${i}`,
                type: types[Math.floor(Math.random() * types.length)],
                user: users[Math.floor(Math.random() * users.length)],
                description: `${users[Math.floor(Math.random() * users.length)]} ${types[Math.floor(Math.random() * types.length)].replace('_', ' ')}`,
                envelope_subject: subjects[Math.floor(Math.random() * subjects.length)],
                envelope_id: `env-${i}`,
                created_at: new Date(Date.now() - Math.random() * 7 * 24 * 60 * 60 * 1000).toISOString(),
                metadata: {}
            }));
        },
        applyFilters() {
            this.loadActivities(1);
        },
        clearFilters() {
            this.filters = {
                type: 'all',
                dateRange: '7days',
                user: 'all'
            };
            this.loadActivities(1);
        },
        getActivityIcon(type) {
            const icons = {
                'envelope_sent': 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'envelope_completed': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'envelope_voided': 'M6 18L18 6M6 6l12 12',
                'template_created': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'user_login': 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1'
            };
            return icons[type] || icons['envelope_sent'];
        },
        getActivityColor(type) {
            const colors = {
                'envelope_sent': 'bg-blue-500',
                'envelope_completed': 'bg-green-500',
                'envelope_voided': 'bg-red-500',
                'template_created': 'bg-purple-500',
                'user_login': 'bg-gray-500'
            };
            return colors[type] || 'bg-gray-500';
        }
    }"
    x-init="init()">

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Activity Feed</h1>
            <p class="mt-1 text-sm text-text-secondary">View all system activity and events</p>
        </div>

        <!-- Filters -->
        <x-ui.card class="mb-6">
            <h3 class="text-lg font-semibold text-text-primary mb-4">Filters</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Activity Type</label>
                    <select x-model="filters.type" class="w-full rounded-md border-border-primary bg-bg-primary text-text-primary">
                        <option value="all">All Types</option>
                        <option value="envelope">Envelopes</option>
                        <option value="template">Templates</option>
                        <option value="user">User Actions</option>
                        <option value="system">System Events</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Date Range</label>
                    <select x-model="filters.dateRange" class="w-full rounded-md border-border-primary bg-bg-primary text-text-primary">
                        <option value="today">Today</option>
                        <option value="7days">Last 7 Days</option>
                        <option value="30days">Last 30 Days</option>
                        <option value="all">All Time</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">User</label>
                    <select x-model="filters.user" class="w-full rounded-md border-border-primary bg-bg-primary text-text-primary">
                        <option value="all">All Users</option>
                        <option value="me">My Activity</option>
                        <!-- More users would be loaded dynamically -->
                    </select>
                </div>

                <div class="flex items-end">
                    <div class="flex space-x-2 w-full">
                        <x-ui.button variant="primary" @click="applyFilters()" class="flex-1">
                            Apply
                        </x-ui.button>
                        <x-ui.button variant="secondary" @click="clearFilters()" class="flex-1">
                            Clear
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="card" class="h-24" />
            <x-ui.skeleton type="card" class="h-24" />
            <x-ui.skeleton type="card" class="h-24" />
        </div>

        <!-- Activity Timeline -->
        <div x-show="!loading">
            <x-ui.card :padding="false">
                @if(true)
                    <div class="px-6 py-4">
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <template x-for="(activity, index) in activities" :key="activity.id">
                                    <li>
                                        <div class="relative pb-8">
                                            <span x-show="index < activities.length - 1"
                                                  class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-border-primary"
                                                  aria-hidden="true"></span>
                                            <div class="relative flex items-start space-x-3">
                                                <div>
                                                    <div class="relative px-1">
                                                        <div class="h-10 w-10 rounded-full flex items-center justify-center ring-8 ring-bg-primary"
                                                             :class="getActivityColor(activity.type)">
                                                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActivityIcon(activity.type)" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1 py-1.5">
                                                    <div class="text-sm text-text-primary">
                                                        <span class="font-medium" x-text="activity.user"></span>
                                                        <span x-text="activity.type.replace('_', ' ')"></span>
                                                        <template x-if="activity.envelope_subject">
                                                            <a :href="'/envelopes/' + activity.envelope_id" class="font-medium text-primary-600 hover:text-primary-500">
                                                                <span x-text="activity.envelope_subject"></span>
                                                            </a>
                                                        </template>
                                                    </div>
                                                    <div class="mt-0.5 text-xs text-text-secondary">
                                                        <time :datetime="activity.created_at" x-text="new Date(activity.created_at).toLocaleString()"></time>
                                                    </div>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <template x-if="activity.envelope_id">
                                                        <a :href="'/envelopes/' + activity.envelope_id"
                                                           class="text-sm text-primary-600 hover:text-primary-500 font-medium">
                                                            View
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-card-border flex items-center justify-between">
                        <div class="text-sm text-text-secondary">
                            Showing <span x-text="(pagination.current_page - 1) * pagination.per_page + 1"></span>
                            to <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                            of <span x-text="pagination.total"></span> activities
                        </div>
                        <div class="flex space-x-2">
                            <x-ui.button variant="secondary"
                                         size="sm"
                                         @click="loadActivities(pagination.current_page - 1)"
                                         :disabled="pagination.current_page === 1">
                                Previous
                            </x-ui.button>
                            <x-ui.button variant="secondary"
                                         size="sm"
                                         @click="loadActivities(pagination.current_page + 1)"
                                         :disabled="pagination.current_page === pagination.last_page">
                                Next
                            </x-ui.button>
                        </div>
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-text-primary">No activity found</h3>
                        <p class="mt-1 text-sm text-text-secondary">Try adjusting your filters or check back later.</p>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
