<x-layout.app :title="'Template: ' . ($template->name ?? 'Details')">
    <div x-data="{
        loading: true,
        template: {},
        documents: [],
        recipients: [],
        async loadTemplate() {
            this.loading = true;
            try {
                const templateId = '{{ $templateId }}';
                const accountId = $store.auth.user.account_id;

                // Load template details
                const templateResponse = await $api.get(`/accounts/${accountId}/templates/${templateId}`);
                this.template = templateResponse.data;

                // Load documents
                const docsResponse = await $api.get(`/accounts/${accountId}/templates/${templateId}/documents`);
                this.documents = docsResponse.data.data || docsResponse.data;

                // Load recipients
                const recipientsResponse = await $api.get(`/accounts/${accountId}/templates/${templateId}/recipients`);
                this.recipients = recipientsResponse.data.data || recipientsResponse.data;

                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load template');
                this.loading = false;
            }
        },
        async useTemplate() {
            try {
                const response = await $api.post(`/accounts/${$store.auth.user.account_id}/templates/${this.template.id}/envelopes`);
                $store.toast.success('Envelope created from template');
                window.location.href = `/envelopes/${response.data.id}`;
            } catch (error) {
                $store.toast.error('Failed to create envelope from template');
            }
        },
        async deleteTemplate() {
            if (!confirm('Delete this template? This cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/templates/${this.template.id}`);
                $store.toast.success('Template deleted');
                window.location.href = '/templates';
            } catch (error) {
                $store.toast.error('Failed to delete template');
            }
        }
    }" x-init="loadTemplate()">

        <!-- Loading State -->
        <div x-show="loading" class="space-y-6">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Content -->
        <div x-show="!loading" class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-text-primary mb-2" x-text="template.name"></h1>
                    <p class="text-sm text-text-secondary" x-text="template.description || 'No description'"></p>
                    <p class="mt-2 text-xs text-text-secondary">
                        Created on <span x-text="template.created_at ? new Date(template.created_at).toLocaleDateString() : ''"></span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <x-ui.button variant="primary" @click="useTemplate()">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Use Template
                    </x-ui.button>

                    <x-ui.button variant="secondary" @click="window.location.href=`/templates/${template.id}/edit`">
                        Edit
                    </x-ui.button>

                    <x-ui.button variant="danger" @click="deleteTemplate()">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </x-ui.button>
                </div>
            </div>

            <!-- Template Details -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Template Details</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Template ID</dt>
                        <dd class="mt-1 text-sm text-text-primary font-mono" x-text="template.template_id"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Name</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="template.name"></dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-text-secondary">Description</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="template.description || 'No description'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Default Email Subject</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="template.email_subject || 'Not set'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Created</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="template.created_at ? new Date(template.created_at).toLocaleString() : ''"></dd>
                    </div>
                    <div class="md:col-span-2" x-show="template.email_blurb">
                        <dt class="text-sm font-medium text-text-secondary">Default Email Message</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="template.email_blurb"></dd>
                    </div>
                </dl>
            </x-ui.card>

            <!-- Documents -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Documents (<span x-text="documents.length"></span>)</h2>
                <div class="space-y-4">
                    <template x-for="doc in documents" :key="doc.id">
                        <div class="flex items-center justify-between p-4 border border-border-primary rounded-lg">
                            <div class="flex items-center space-x-4">
                                <svg class="w-10 h-10 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-text-primary" x-text="doc.name"></p>
                                    <p class="text-sm text-text-secondary">
                                        Document #<span x-text="doc.document_id"></span>
                                        <span x-show="doc.file_extension"> • <span x-text="doc.file_extension.toUpperCase()"></span></span>
                                        <span x-show="doc.order"> • Order: <span x-text="doc.order"></span></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="documents.length === 0" class="text-center py-8 text-text-secondary">
                        No documents in this template
                    </div>
                </div>
            </x-ui.card>

            <!-- Recipient Roles -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Recipient Roles (<span x-text="recipients.length"></span>)</h2>
                <div class="space-y-4">
                    <template x-for="recipient in recipients" :key="recipient.id">
                        <div class="p-4 border border-border-primary rounded-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-text-primary" x-text="recipient.role_name || 'Unnamed Role'"></p>
                                    <p class="text-sm text-text-secondary mt-1">
                                        Type: <span x-text="recipient.recipient_type ? recipient.recipient_type.replace('_', ' ').charAt(0).toUpperCase() + recipient.recipient_type.slice(1).replace('_', ' ') : ''"></span>
                                        <span x-show="recipient.routing_order"> • Routing Order: <span x-text="recipient.routing_order"></span></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="recipients.length === 0" class="text-center py-8 text-text-secondary">
                        No recipient roles defined
                    </div>
                </div>
            </x-ui.card>

            <!-- Usage Stats (Placeholder) -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Usage Statistics</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-primary-600">0</p>
                        <p class="text-sm text-text-secondary mt-1">Envelopes Created</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-primary-600">0</p>
                        <p class="text-sm text-text-secondary mt-1">Total Recipients</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-primary-600">0%</p>
                        <p class="text-sm text-text-secondary mt-1">Completion Rate</p>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
