<x-layout.app title="Dashboard">
    <div x-data="{
        loading: true,
        statistics: {
            total: 0,
            sent: 0,
            completed: 0,
            voided: 0
        },
        recentEnvelopes: [],
        async loadDashboard() {
            try {
                // Load statistics
                const statsResponse = await $api.get(`/accounts/${$store.auth.user.account_id}/envelopes/statistics`);
                this.statistics = statsResponse.data;

                // Load recent envelopes
                const envelopesResponse = await $api.get(`/accounts/${$store.auth.user.account_id}/envelopes?per_page=5&sort_by=created_at&sort_direction=desc`);
                this.recentEnvelopes = envelopesResponse.data.data;

                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load dashboard data');
                this.loading = false;
            }
        }
    }"
    x-init="loadDashboard()">

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Dashboard</h1>
            <p class="mt-1 text-sm text-text-secondary">Welcome back, <span x-text="$store.auth.user?.name"></span></p>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-ui.skeleton type="card" class="h-32" />
                <x-ui.skeleton type="card" class="h-32" />
                <x-ui.skeleton type="card" class="h-32" />
                <x-ui.skeleton type="card" class="h-32" />
            </div>
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Dashboard Content -->
        <div x-show="!loading" class="space-y-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Envelopes -->
                <x-ui.card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-text-secondary">Total Envelopes</p>
                            <p class="mt-2 text-3xl font-bold text-text-primary" x-text="statistics.total"></p>
                            <p class="mt-2 text-xs text-text-secondary">
                                <span class="text-green-600 font-medium">+12.5%</span> from last month
                            </p>
                        </div>
                        <div class="p-3 bg-primary-100 dark:bg-primary-900/30 rounded-full">
                            <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </x-ui.card>

                <!-- Sent Envelopes -->
                <x-ui.card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-text-secondary">Sent</p>
                            <p class="mt-2 text-3xl font-bold text-text-primary" x-text="statistics.sent"></p>
                            <p class="mt-2 text-xs text-text-secondary">
                                Awaiting signatures
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                            <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                    </div>
                </x-ui.card>

                <!-- Completed Envelopes -->
                <x-ui.card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-text-secondary">Completed</p>
                            <p class="mt-2 text-3xl font-bold text-text-primary" x-text="statistics.completed"></p>
                            <p class="mt-2 text-xs text-text-secondary">
                                Fully executed
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </x-ui.card>

                <!-- Voided Envelopes -->
                <x-ui.card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-text-secondary">Voided</p>
                            <p class="mt-2 text-3xl font-bold text-text-primary" x-text="statistics.voided"></p>
                            <p class="mt-2 text-xs text-text-secondary">
                                Cancelled envelopes
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-full">
                            <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <!-- Quick Actions -->
            <x-ui.card>
                <h3 class="text-lg font-semibold text-text-primary mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="/envelopes/create" class="flex items-center p-4 rounded-lg border-2 border-dashed border-border-primary hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors group">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg group-hover:bg-primary-200 transition-colors">
                            <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-text-primary">Send Envelope</p>
                            <p class="text-sm text-text-secondary">Create and send new envelope</p>
                        </div>
                    </a>

                    <a href="/templates/create" class="flex items-center p-4 rounded-lg border-2 border-dashed border-border-primary hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors group">
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg group-hover:bg-green-200 transition-colors">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-text-primary">Create Template</p>
                            <p class="text-sm text-text-secondary">Save time with templates</p>
                        </div>
                    </a>

                    <a href="/recipients" class="flex items-center p-4 rounded-lg border-2 border-dashed border-border-primary hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors group">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg group-hover:bg-blue-200 transition-colors">
                            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-text-primary">Manage Recipients</p>
                            <p class="text-sm text-text-secondary">View and organize contacts</p>
                        </div>
                    </a>
                </div>
            </x-ui.card>

            <!-- Recent Envelopes -->
            <x-ui.card :padding="false">
                <div class="px-6 py-4 border-b border-card-border flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-text-primary">Recent Envelopes</h3>
                    <a href="/envelopes" class="text-sm font-medium text-primary-600 hover:text-primary-500">View all</a>
                </div>

                <div x-show="recentEnvelopes.length === 0" class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-primary">No envelopes yet</h3>
                    <p class="mt-1 text-sm text-text-secondary">Get started by sending your first envelope.</p>
                    <div class="mt-6">
                        <x-ui.button variant="primary" onclick="window.location.href='/envelopes/create'">
                            Send Envelope
                        </x-ui.button>
                    </div>
                </div>

                <div x-show="recentEnvelopes.length > 0" class="overflow-x-auto">
                    <x-table.table class="min-w-full">
                        <x-table.thead>
                            <x-table.row>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Recipients</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Created</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">Actions</th>
                            </x-table.row>
                        </x-table.thead>
                        <x-table.tbody>
                            <template x-for="envelope in recentEnvelopes" :key="envelope.id">
                                <x-table.row class="hover:bg-bg-hover cursor-pointer" @click="window.location.href='/envelopes/' + envelope.id">
                                    <x-table.cell>
                                        <p class="font-medium text-text-primary" x-text="envelope.email_subject"></p>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                              :class="{
                                                  'bg-primary-100 text-primary-800': envelope.status === 'sent',
                                                  'bg-green-100 text-green-800': envelope.status === 'completed',
                                                  'bg-red-100 text-red-800': envelope.status === 'voided',
                                                  'bg-gray-100 text-gray-800': envelope.status === 'draft'
                                              }"
                                              x-text="envelope.status.charAt(0).toUpperCase() + envelope.status.slice(1)">
                                        </span>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span x-text="envelope.recipients_count + ' recipients'"></span>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span x-text="new Date(envelope.created_at).toLocaleDateString()"></span>
                                    </x-table.cell>
                                    <x-table.cell align="right">
                                        <x-ui.icon-button tooltip="View details" size="sm" @click.stop="window.location.href='/envelopes/' + envelope.id">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </x-ui.icon-button>
                                    </x-table.cell>
                                </x-table.row>
                            </template>
                        </x-table.tbody>
                    </x-table.table>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
