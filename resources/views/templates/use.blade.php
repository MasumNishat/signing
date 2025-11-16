<x-layout.app title="Use Template">
    <div x-data="{
        template: null,
        loading: true,
        recipients: [],
        emailSubject: '',
        emailMessage: '',
        async init() {
            await this.loadTemplate();
        },
        async loadTemplate() {
            this.loading = true;
            try {
                const templateId = window.location.pathname.split('/')[2];
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/templates/${templateId}`);
                this.template = response.data;
                
                // Initialize recipients from template roles
                this.recipients = (this.template.recipients || []).map(role => ({
                    role_name: role.role_name,
                    recipient_type: role.recipient_type,
                    routing_order: role.routing_order,
                    name: '',
                    email: ''
                }));
                
                this.emailSubject = this.template.name || '';
            } catch (error) {
                $store.toast.error('Failed to load template');
                setTimeout(() => {
                    window.location.href = '/templates';
                }, 2000);
            } finally {
                this.loading = false;
            }
        },
        async createEnvelope() {
            // Validate recipients
            const emptyRecipients = this.recipients.filter(r => !r.name || !r.email);
            if (emptyRecipients.length > 0) {
                $store.toast.error('Please fill in all recipient information');
                return;
            }
            
            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/envelopes`,
                    {
                        template_id: this.template.id,
                        email_subject: this.emailSubject,
                        email_message: this.emailMessage,
                        recipients: this.recipients,
                        status: 'created'
                    }
                );
                
                $store.toast.success('Envelope created from template');
                window.location.href = `/envelopes/${response.data.id}`;
            } catch (error) {
                $store.toast.error('Failed to create envelope from template');
            }
        }
    }"
    x-init="init()">
        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="text" class="h-8 w-64" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Use Template Form -->
        <div x-show="!loading && template">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-text-primary">Use Template: <span x-text="template?.name"></span></h1>
                <p class="mt-1 text-sm text-text-secondary">Fill in recipient information to create an envelope from this template</p>
            </div>

            <!-- Email Settings -->
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Email Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Email Subject</label>
                        <x-ui.input
                            type="text"
                            x-model="emailSubject"
                            placeholder="Enter email subject"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Email Message (Optional)</label>
                        <textarea
                            x-model="emailMessage"
                            rows="4"
                            class="w-full rounded-md border border-border-primary bg-bg-primary text-text-primary px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="Enter a custom message for recipients"
                        ></textarea>
                    </div>
                </div>
            </x-ui.card>

            <!-- Recipients -->
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Assign Recipients</h3>
                <div class="space-y-4">
                    <template x-for="(recipient, index) in recipients" :key="index">
                        <div class="p-4 border border-border-primary rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                        <span class="font-semibold text-primary-600" x-text="recipient.role_name?.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-text-primary" x-text="recipient.role_name"></p>
                                        <p class="text-xs text-text-secondary">
                                            <span x-text="recipient.recipient_type.toUpperCase()"></span> • 
                                            Order: <span x-text="recipient.routing_order"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-text-primary mb-1">Name *</label>
                                    <x-ui.input
                                        type="text"
                                        x-model="recipient.name"
                                        placeholder="Recipient name"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text-primary mb-1">Email *</label>
                                    <x-ui.input
                                        type="email"
                                        x-model="recipient.email"
                                        placeholder="recipient@example.com"
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="recipients.length === 0">
                        <x-ui.empty-state
                            icon="user"
                            title="No recipient roles defined"
                            description="This template has no recipient roles. Please edit the template to add recipients."
                        />
                    </div>
                </div>
            </x-ui.card>

            <!-- Template Preview -->
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Template Contents</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Documents</p>
                        <p class="text-lg text-text-primary" x-text="`${template?.documents?.length || 0} document(s)`"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Form Fields</p>
                        <p class="text-lg text-text-primary" x-text="`${template?.tabs?.length || 0} field(s)`"></p>
                    </div>
                </div>
            </x-ui.card>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <x-ui.button variant="secondary" onclick="window.location.href='/templates'">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="createEnvelope()" :disabled="recipients.length === 0">
                    Create Envelope from Template
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
