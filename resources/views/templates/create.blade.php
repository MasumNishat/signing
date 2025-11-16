<x-layout.app title="Create Template">
    <div x-data="{
        loading: false,
        templateData: {
            name: '',
            description: '',
            email_subject: '',
            email_blurb: ''
        },
        documents: [],
        recipients: [],
        errors: {},
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
        async saveTemplate() {
            this.loading = true;
            this.errors = {};

            try {
                const accountId = $store.auth.user.account_id;

                const templatePayload = {
                    ...this.templateData,
                    documents: this.documents.map(doc => ({
                        name: doc.name,
                        file_extension: doc.file_extension,
                        order: doc.order
                    })),
                    recipients: this.recipients.map(r => ({
                        role_name: r.role_name,
                        recipient_type: r.recipient_type,
                        routing_order: r.routing_order
                    }))
                };

                const response = await $api.post(`/accounts/${accountId}/templates`, templatePayload);
                $store.toast.success('Template created successfully');
                window.location.href = `/templates/${response.data.id}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                    $store.toast.error('Please fix the errors and try again');
                } else {
                    $store.toast.error('Failed to create template');
                }
                this.loading = false;
            }
        }
    }">

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Create Template</h1>
                <p class="mt-1 text-sm text-text-secondary">Create a reusable template for frequently used documents</p>
            </div>
            <x-ui.button variant="secondary" onclick="window.location.href='/templates'">
                Cancel
            </x-ui.button>
        </div>

        <div class="space-y-6">
            <!-- Template Details -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Template Details</h2>
                <div class="space-y-4">
                    <x-form.input
                        name="name"
                        label="Template Name"
                        x-model="templateData.name"
                        placeholder="Sales Agreement Template"
                        :required="true"
                        x-bind:error="errors.name?.[0]"
                    />

                    <x-form.textarea
                        name="description"
                        label="Description"
                        x-model="templateData.description"
                        placeholder="Describe what this template is for..."
                        rows="3"
                    />

                    <x-form.input
                        name="email_subject"
                        label="Default Email Subject"
                        x-model="templateData.email_subject"
                        placeholder="Please sign this document"
                    />

                    <x-form.textarea
                        name="email_blurb"
                        label="Default Email Message"
                        x-model="templateData.email_blurb"
                        placeholder="Default message for envelopes created from this template"
                        rows="3"
                    />
                </div>
            </x-ui.card>

            <!-- Documents -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Documents</h2>
                <p class="text-sm text-text-secondary mb-4">Add the documents that will be included in this template</p>

                <!-- Upload Area -->
                <div class="mb-6">
                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-border-primary rounded-lg cursor-pointer hover:bg-bg-hover">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-10 h-10 mb-3 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="mb-2 text-sm text-text-secondary"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                            <p class="text-xs text-text-secondary">PDF, DOC, DOCX up to 25MB</p>
                        </div>
                        <input type="file" class="hidden" accept=".pdf,.doc,.docx" multiple @change="uploadDocument($event)" />
                    </label>
                </div>

                <!-- Documents List -->
                <div class="space-y-3" x-show="documents.length > 0">
                    <template x-for="doc in documents" :key="doc.id">
                        <div class="flex items-center justify-between p-4 border border-border-primary rounded-lg">
                            <div class="flex items-center space-x-4">
                                <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-text-primary" x-text="doc.name"></p>
                                    <p class="text-sm text-text-secondary">
                                        Order: <span x-text="doc.order"></span> •
                                        <span x-text="(doc.size / 1024 / 1024).toFixed(2)"></span> MB
                                    </p>
                                </div>
                            </div>
                            <button @click="removeDocument(doc.id)" class="p-2 text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div x-show="documents.length === 0" class="text-center py-8 text-text-secondary">
                    No documents uploaded yet
                </div>
            </x-ui.card>

            <!-- Recipient Roles -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Recipient Roles</h2>
                <p class="text-sm text-text-secondary mb-4">Define placeholder roles for recipients (actual recipients will be specified when using the template)</p>

                <!-- Recipients List -->
                <div class="space-y-4 mb-6">
                    <template x-for="(recipient, index) in recipients" :key="recipient.id">
                        <div class="p-4 border border-border-primary rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <x-form.input
                                    :name="'role_name_' + index"
                                    label="Role Name"
                                    x-model="recipient.role_name"
                                    placeholder="Buyer, Seller, Manager..."
                                    :required="true"
                                />
                                <x-form.select
                                    :name="'recipient_type_' + index"
                                    label="Type"
                                    x-model="recipient.recipient_type"
                                    :options="[
                                        'signer' => 'Signer',
                                        'cc' => 'CC (Receives Copy)',
                                        'in_person_signer' => 'In-Person Signer'
                                    ]"
                                />
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary mb-1">Routing Order</label>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-text-primary" x-text="recipient.routing_order"></span>
                                        <button @click="removeRecipient(recipient.id)" class="ml-auto text-sm text-red-600 hover:text-red-700">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <x-ui.button variant="secondary" @click="addRecipientRole()">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Recipient Role
                </x-ui.button>

                <div x-show="recipients.length === 0" class="text-center py-8 text-text-secondary">
                    No recipient roles defined yet
                </div>
            </x-ui.card>

            <!-- Actions -->
            <div class="flex gap-2 justify-end">
                <x-ui.button variant="secondary" onclick="window.location.href='/templates'">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="saveTemplate()" :loading="loading">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Create Template
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
