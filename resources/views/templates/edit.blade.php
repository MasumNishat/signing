<x-layout.app title="Edit Template">
    <div x-data="{
        loading: true,
        template: null,
        templateData: {
            name: '',
            description: '',
            email_subject: '',
            email_blurb: ''
        },
        documents: [],
        recipients: [],
        errors: {},
        async init() {
            await this.loadTemplate();
        },
        async loadTemplate() {
            this.loading = true;
            try {
                const templateId = window.location.pathname.split('/')[2];
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/templates/${templateId}`);
                this.template = response.data;
                
                // Populate form data
                this.templateData = {
                    name: this.template.name || '',
                    description: this.template.description || '',
                    email_subject: this.template.email_subject || '',
                    email_blurb: this.template.email_blurb || ''
                };
                
                this.documents = this.template.documents || [];
                this.recipients = this.template.recipients || [];
            } catch (error) {
                $store.toast.error('Failed to load template');
                setTimeout(() => {
                    window.location.href = '/templates';
                }, 2000);
            } finally {
                this.loading = false;
            }
        },
        // Document management
        async uploadDocument(event) {
            const files = event.target.files;
            for (let file of files) {
                this.documents.push({
                    id: 'temp-' + Date.now() + Math.random(),
                    name: file.name,
                    file_extension: file.name.split('.').pop(),
                    size: file.size,
                    order: this.documents.length + 1,
                    file: file
                });
            }
            event.target.value = '';
        },
        removeDocument(docId) {
            this.documents = this.documents.filter(d => d.id !== docId);
            this.documents.forEach((doc, index) => {
                doc.order = index + 1;
            });
        },
        // Recipient management
        addRecipientRole() {
            this.recipients.push({
                id: 'temp-' + Date.now() + Math.random(),
                role_name: '',
                recipient_type: 'signer',
                routing_order: this.recipients.length + 1
            });
        },
        removeRecipient(recipientId) {
            this.recipients = this.recipients.filter(r => r.id !== recipientId);
            this.recipients.forEach((recipient, index) => {
                recipient.routing_order = index + 1;
            });
        },
        // Submit
        async updateTemplate() {
            this.loading = true;
            this.errors = {};

            try {
                const formData = new FormData();
                
                // Add template data
                formData.append('name', this.templateData.name);
                formData.append('description', this.templateData.description);
                formData.append('email_subject', this.templateData.email_subject);
                formData.append('email_blurb', this.templateData.email_blurb);
                
                // Add recipients
                formData.append('recipients', JSON.stringify(this.recipients));
                
                // Add new documents (files)
                this.documents.forEach((doc, index) => {
                    if (doc.file) {
                        formData.append(`documents[${index}]`, doc.file);
                    }
                });
                
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/templates/${this.template.id}`,
                    formData,
                    {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    }
                );
                
                $store.toast.success('Template updated successfully');
                window.location.href = `/templates/${response.data.id}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to update template');
            } finally {
                this.loading = false;
            }
        }
    }"
    x-init="init()">
        <!-- Loading State -->
        <div x-show="loading && !template" class="space-y-4">
            <x-ui.skeleton type="text" class="h-8 w-64" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Edit Form -->
        <div x-show="!loading || template">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-text-primary">Edit Template</h1>
                <p class="mt-1 text-sm text-text-secondary">Update template settings, documents, and recipient roles</p>
            </div>

            <!-- Template Info -->
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Template Information</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Template Name *</label>
                        <x-ui.input
                            type="text"
                            x-model="templateData.name"
                            placeholder="Enter template name"
                            required
                        />
                        <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name?.[0]"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Description</label>
                        <textarea
                            x-model="templateData.description"
                            rows="3"
                            class="w-full rounded-md border border-border-primary bg-bg-primary text-text-primary px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="Enter template description"
                        ></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Default Email Subject</label>
                            <x-ui.input
                                type="text"
                                x-model="templateData.email_subject"
                                placeholder="Email subject line"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Email Message</label>
                            <x-ui.input
                                type="text"
                                x-model="templateData.email_blurb"
                                placeholder="Email message"
                            />
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Documents -->
            <x-ui.card class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-text-primary">Documents</h3>
                    <label for="document-upload">
                        <x-ui.button variant="secondary" size="sm" as="span">
                            Add Document
                        </x-ui.button>
                    </label>
                    <input
                        type="file"
                        id="document-upload"
                        @change="uploadDocument"
                        class="hidden"
                        multiple
                        accept=".pdf,.doc,.docx"
                    >
                </div>

                <div x-show="documents.length > 0" class="space-y-3">
                    <template x-for="(doc, index) in documents" :key="doc.id">
                        <div class="flex items-center justify-between p-3 border border-border-primary rounded-lg">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3">📄</span>
                                <div>
                                    <p class="text-sm font-medium text-text-primary" x-text="doc.name"></p>
                                    <p class="text-xs text-text-secondary">Order: <span x-text="doc.order"></span></p>
                                </div>
                            </div>
                            <button
                                @click="removeDocument(doc.id)"
                                class="text-red-600 hover:text-red-500"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div x-show="documents.length === 0">
                    <x-ui.empty-state
                        icon="document"
                        title="No documents"
                        description="Add documents to this template"
                    />
                </div>
            </x-ui.card>

            <!-- Recipient Roles -->
            <x-ui.card class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-text-primary">Recipient Roles</h3>
                    <x-ui.button variant="secondary" size="sm" @click="addRecipientRole()">
                        Add Role
                    </x-ui.button>
                </div>

                <div x-show="recipients.length > 0" class="space-y-3">
                    <template x-for="(recipient, index) in recipients" :key="recipient.id">
                        <div class="p-4 border border-border-primary rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-text-primary mb-1">Role Name *</label>
                                    <x-ui.input
                                        type="text"
                                        x-model="recipient.role_name"
                                        placeholder="e.g., Signer 1"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text-primary mb-1">Type *</label>
                                    <x-ui.select x-model="recipient.recipient_type">
                                        <option value="signer">Signer</option>
                                        <option value="approver">Approver</option>
                                        <option value="viewer">Viewer</option>
                                        <option value="certified_delivery">Certified Delivery</option>
                                    </x-ui.select>
                                </div>
                                <div class="flex items-end justify-between">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-text-primary mb-1">Routing Order</label>
                                        <x-ui.input
                                            type="number"
                                            x-model="recipient.routing_order"
                                            min="1"
                                        />
                                    </div>
                                    <button
                                        @click="removeRecipient(recipient.id)"
                                        class="ml-3 text-red-600 hover:text-red-500 p-2"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="recipients.length === 0">
                    <x-ui.empty-state
                        icon="user"
                        title="No recipient roles"
                        description="Add recipient roles to this template"
                    />
                </div>
            </x-ui.card>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <x-ui.button variant="secondary" onclick="window.location.href='/templates'">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="updateTemplate()" :disabled="loading">
                    <span x-show="!loading">Update Template</span>
                    <span x-show="loading">Updating...</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
