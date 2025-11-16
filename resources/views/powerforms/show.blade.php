<x-layout.app :title="'PowerForm: ' . ($powerform->name ?? 'Details')">
    <div x-data="{
        loading: true,
        powerform: null,
        submissions: [],
        async init() {
            await this.loadPowerForm();
        },
        async loadPowerForm() {
            this.loading = true;
            try {
                const powerformId = '{{ $powerformId }}';
                const accountId = $store.auth.user.account_id;

                // Load PowerForm details
                const response = await $api.get(`/accounts/${accountId}/powerforms/${powerformId}`);
                this.powerform = response.data;

                // Load submissions
                const submissionsResponse = await $api.get(`/accounts/${accountId}/powerforms/${powerformId}/submissions`);
                this.submissions = submissionsResponse.data.data || submissionsResponse.data;
            } catch (error) {
                $store.toast.error('Failed to load PowerForm');
            } finally {
                this.loading = false;
            }
        },
        async toggleStatus() {
            const newStatus = this.powerform.status === 'active' ? 'inactive' : 'active';

            try {
                await $api.put(
                    `/accounts/${$store.auth.user.account_id}/powerforms/${this.powerform.id}`,
                    { status: newStatus }
                );
                this.powerform.status = newStatus;
                $store.toast.success(`PowerForm ${newStatus === 'active' ? 'activated' : 'deactivated'}`);
            } catch (error) {
                $store.toast.error('Failed to update status');
            }
        },
        async deletePowerForm() {
            if (!confirm('Delete this PowerForm? This cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/powerforms/${this.powerform.id}`);
                $store.toast.success('PowerForm deleted successfully');
                window.location.href = '/powerforms';
            } catch (error) {
                $store.toast.error('Failed to delete PowerForm');
            }
        },
        copyPublicUrl() {
            navigator.clipboard.writeText(this.powerform.public_url);
            $store.toast.success('Public URL copied to clipboard');
        },
        copyEmbedCode() {
            const embedCode = `<iframe src=\"${this.powerform.public_url}\" width=\"100%\" height=\"600\" frameborder=\"0\"></iframe>`;
            navigator.clipboard.writeText(embedCode);
            $store.toast.success('Embed code copied to clipboard');
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
        <!-- Loading State -->
        <div x-show="loading" class="space-y-6">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Content -->
        <div x-show="!loading && powerform" class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-text-primary" x-text="powerform?.name"></h1>
                        <x-ui.badge x-bind:variant="getStatusColor(powerform?.status)" x-text="powerform?.status?.toUpperCase()"></x-ui.badge>
                    </div>
                    <p class="mt-1 text-sm text-text-secondary">
                        Template: <span x-text="powerform?.template_name || 'N/A'"></span>
                    </p>
                    <p class="mt-1 text-xs text-text-secondary">
                        Created on <span x-text="formatDate(powerform?.created_at)"></span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <x-ui.button variant="primary" @click="toggleStatus()">
                        <span x-text="powerform?.status === 'active' ? 'Deactivate' : 'Activate'"></span>
                    </x-ui.button>
                    <x-ui.button variant="secondary" x-bind:onclick="`window.location.href='/powerforms/${powerform?.id}/edit'`">
                        Edit
                    </x-ui.button>
                    <x-ui.button variant="danger" @click="deletePowerForm()">
                        Delete
                    </x-ui.button>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-primary-600" x-text="powerform?.submission_count || 0"></p>
                        <p class="mt-1 text-sm text-text-secondary">Total Submissions</p>
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-green-600" x-text="powerform?.completed_count || 0"></p>
                        <p class="mt-1 text-sm text-text-secondary">Completed</p>
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-yellow-600" x-text="powerform?.pending_count || 0"></p>
                        <p class="mt-1 text-sm text-text-secondary">Pending</p>
                    </div>
                </x-ui.card>
            </div>

            <!-- Public URL & Embed Code -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Public Access</h2>
                <div class="space-y-4">
                    <!-- Public URL -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Public URL</label>
                        <div class="flex items-center space-x-2">
                            <x-ui.input
                                type="text"
                                x-bind:value="powerform?.public_url"
                                readonly
                                class="flex-1"
                            />
                            <x-ui.button variant="secondary" @click="copyPublicUrl()">
                                Copy
                            </x-ui.button>
                            <x-ui.button variant="secondary" x-bind:onclick="`window.open('${powerform?.public_url}', '_blank')`">
                                Open
                            </x-ui.button>
                        </div>
                    </div>

                    <!-- Embed Code -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Embed Code</label>
                        <div class="flex items-center space-x-2">
                            <textarea
                                readonly
                                rows="3"
                                class="flex-1 px-3 py-2 border border-border-primary rounded-md bg-bg-secondary text-text-primary font-mono text-xs"
                                x-text="`<iframe src=\"${powerform?.public_url}\" width=\"100%\" height=\"600\" frameborder=\"0\"></iframe>`"
                            ></textarea>
                            <x-ui.button variant="secondary" @click="copyEmbedCode()">
                                Copy
                            </x-ui.button>
                        </div>
                        <p class="mt-1 text-xs text-text-secondary">Paste this code into your website to embed the PowerForm</p>
                    </div>
                </div>
            </x-ui.card>

            <!-- PowerForm Details -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">PowerForm Details</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">PowerForm ID</dt>
                        <dd class="mt-1 text-sm text-text-primary font-mono" x-text="powerform?.id"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Template</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="powerform?.template_name || 'N/A'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Signing Mode</dt>
                        <dd class="mt-1">
                            <x-ui.badge variant="secondary" x-text="powerform?.signing_mode === 'email' ? 'Email Required' : 'Direct Signing'"></x-ui.badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Multiple Submissions</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="powerform?.allow_multiple_submissions ? 'Allowed' : 'Not Allowed'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Created</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="formatDate(powerform?.created_at)"></dd>
                    </div>
                    <div x-show="powerform?.email_subject">
                        <dt class="text-sm font-medium text-text-secondary">Email Subject</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="powerform?.email_subject"></dd>
                    </div>
                </dl>
            </x-ui.card>

            <!-- Recent Submissions -->
            <x-ui.card>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-text-primary">Recent Submissions</h2>
                    <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/powerforms/${powerform?.id}/submissions'`">
                        View All
                    </x-ui.button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-primary">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Submitted By</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Submitted</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Envelope</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-primary">
                            <template x-for="submission in submissions.slice(0, 5)" :key="submission.id">
                                <tr>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="submission.submitted_by || 'Anonymous'"></td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="submission.submitted_email || 'N/A'"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <x-ui.badge
                                            x-bind:variant="submission.status === 'completed' ? 'success' : 'secondary'"
                                            x-text="submission.status?.toUpperCase()"
                                        ></x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="formatDate(submission.created_at)"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <template x-if="submission.envelope_id">
                                            <a :href="`/envelopes/${submission.envelope_id}`" class="text-primary-600 hover:underline">View</a>
                                        </template>
                                        <template x-if="!submission.envelope_id">
                                            <span class="text-text-secondary">-</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="submissions.length === 0" class="text-center py-8 text-text-secondary">
                        No submissions yet
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
