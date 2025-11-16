<x-layout.app :title="'PowerForm Submissions'">
    <div x-data="{
        loading: true,
        powerform: null,
        submissions: [],
        filter: {
            status: '',
            search: ''
        },
        async init() {
            await this.loadSubmissions();
        },
        async loadSubmissions() {
            this.loading = true;
            try {
                const powerformId = '{{ $powerformId }}';
                const accountId = $store.auth.user.account_id;

                // Load PowerForm details
                const powerformResponse = await $api.get(`/accounts/${accountId}/powerforms/${powerformId}`);
                this.powerform = powerformResponse.data;

                // Load submissions with filters
                const params = new URLSearchParams();
                if (this.filter.status) params.append('status', this.filter.status);
                if (this.filter.search) params.append('search', this.filter.search);

                const response = await $api.get(
                    `/accounts/${accountId}/powerforms/${powerformId}/submissions?${params.toString()}`
                );
                this.submissions = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load submissions');
            } finally {
                this.loading = false;
            }
        },
        getStatusColor(status) {
            const colors = {
                'pending': 'secondary',
                'completed': 'success',
                'declined': 'danger',
                'voided': 'secondary'
            };
            return colors[status] || 'secondary';
        },
        formatDate(date) {
            return date ? new Date(date).toLocaleString() : 'N/A';
        },
        exportToCSV() {
            // Prepare CSV data
            const headers = ['Name', 'Email', 'Status', 'Submitted Date', 'Envelope ID'];
            const rows = this.submissions.map(s => [
                s.submitted_by || 'Anonymous',
                s.submitted_email || 'N/A',
                s.status || 'N/A',
                this.formatDate(s.created_at),
                s.envelope_id || 'N/A'
            ]);

            // Create CSV content
            const csvContent = [
                headers.join(','),
                ...rows.map(row => row.map(cell => `\"${cell}\"`).join(','))
            ].join('\\n');

            // Download
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `powerform-submissions-${this.powerform?.id}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);

            $store.toast.success('Submissions exported to CSV');
        }
    }" x-init="init()">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-2">
                    <a href="/powerforms" class="text-text-secondary hover:text-text-primary">PowerForms</a>
                    <span class="text-text-secondary">/</span>
                    <a :href="`/powerforms/${powerform?.id}`" class="text-text-secondary hover:text-text-primary" x-text="powerform?.name"></a>
                    <span class="text-text-secondary">/</span>
                    <span class="text-text-primary">Submissions</span>
                </div>
                <h1 class="mt-2 text-2xl font-bold text-text-primary">Submissions</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    <span x-text="submissions.length"></span> total submissions
                </p>
            </div>
            <x-ui.button variant="secondary" @click="exportToCSV()" :disabled="submissions.length === 0">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export to CSV
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
                        @input.debounce.500ms="loadSubmissions()"
                        placeholder="Search by name or email..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Status</label>
                    <x-ui.select x-model="filter.status" @change="loadSubmissions()">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="declined">Declined</option>
                        <option value="voided">Voided</option>
                    </x-ui.select>
                </div>
                <div class="flex items-end">
                    <x-ui.button variant="secondary" @click="filter = { status: '', search: '' }; loadSubmissions()">
                        Clear Filters
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-32" />
        </div>

        <!-- Submissions Table -->
        <x-ui.card x-show="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-primary">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Submitted By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Envelope</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-primary">
                        <template x-for="(submission, index) in submissions" :key="submission.id">
                            <tr class="hover:bg-bg-secondary">
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="index + 1"></td>
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="submission.submitted_by || 'Anonymous'"></td>
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="submission.submitted_email || 'N/A'"></td>
                                <td class="px-4 py-3 text-sm">
                                    <x-ui.badge
                                        x-bind:variant="getStatusColor(submission.status)"
                                        x-text="submission.status?.toUpperCase()"
                                    ></x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-text-primary" x-text="formatDate(submission.created_at)"></td>
                                <td class="px-4 py-3 text-sm">
                                    <template x-if="submission.envelope_id">
                                        <a :href="`/envelopes/${submission.envelope_id}`" class="text-primary-600 hover:underline">
                                            View Envelope
                                        </a>
                                    </template>
                                    <template x-if="!submission.envelope_id">
                                        <span class="text-text-secondary">-</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="submissions.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-primary">No submissions</h3>
                    <p class="mt-1 text-sm text-text-secondary">No one has submitted this PowerForm yet</p>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layout.app>
