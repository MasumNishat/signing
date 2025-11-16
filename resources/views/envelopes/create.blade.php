<x-layout.app title="Create Envelope">
    <div x-data="{
        currentStep: 1,
        loading: false,
        envelopeData: {
            email_subject: '',
            email_blurb: '',
            status: 'draft'
        },
        documents: [],
        recipients: [],
        errors: {},
        // Step 1: Documents
        async uploadDocument(event) {
            const files = event.target.files;
            for (let file of files) {
                const formData = new FormData();
                formData.append('file', file);

                try {
                    // In production, this would upload to storage and return document info
                    this.documents.push({
                        id: 'temp-' + Date.now() + Math.random(),
                        name: file.name,
                        file_extension: file.name.split('.').pop(),
                        size: file.size,
                        order: this.documents.length + 1,
                        file: file
                    });
                } catch (error) {
                    $store.toast.error(`Failed to upload ${file.name}`);
                }
            }
            event.target.value = '';
        },
        removeDocument(docId) {
            this.documents = this.documents.filter(d => d.id !== docId);
            // Reorder remaining documents
            this.documents.forEach((doc, index) => {
                doc.order = index + 1;
            });
        },
        moveDocumentUp(docId) {
            const index = this.documents.findIndex(d => d.id === docId);
            if (index > 0) {
                [this.documents[index], this.documents[index - 1]] = [this.documents[index - 1], this.documents[index]];
                this.documents.forEach((doc, i) => {
                    doc.order = i + 1;
                });
            }
        },
        moveDocumentDown(docId) {
            const index = this.documents.findIndex(d => d.id === docId);
            if (index < this.documents.length - 1) {
                [this.documents[index], this.documents[index + 1]] = [this.documents[index + 1], this.documents[index]];
                this.documents.forEach((doc, i) => {
                    doc.order = i + 1;
                });
            }
        },
        // Step 2: Recipients
        addRecipient() {
            this.recipients.push({
                id: 'temp-' + Date.now() + Math.random(),
                name: '',
                email: '',
                recipient_type: 'signer',
                routing_order: this.recipients.length + 1
            });
        },
        removeRecipient(recipientId) {
            this.recipients = this.recipients.filter(r => r.id !== recipientId);
            // Reorder remaining recipients
            this.recipients.forEach((recipient, index) => {
                recipient.routing_order = index + 1;
            });
        },
        // Navigation
        canProceed(step) {
            switch(step) {
                case 1:
                    return this.documents.length > 0;
                case 2:
                    return this.recipients.length > 0 && this.recipients.every(r => r.name && r.email);
                case 3:
                    return this.envelopeData.email_subject;
                default:
                    return true;
            }
        },
        nextStep() {
            if (this.canProceed(this.currentStep)) {
                this.currentStep++;
            } else {
                $store.toast.error('Please complete all required fields');
            }
        },
        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },
        // Submit
        async saveDraft() {
            await this.submitEnvelope('draft');
        },
        async sendEnvelope() {
            if (!confirm('Send this envelope to all recipients?')) return;
            await this.submitEnvelope('sent');
        },
        async submitEnvelope(action) {
            this.loading = true;
            this.errors = {};

            try {
                const accountId = $store.auth.user.account_id;

                // Create envelope
                const envelopePayload = {
                    ...this.envelopeData,
                    status: 'draft',
                    documents: this.documents.map(doc => ({
                        name: doc.name,
                        file_extension: doc.file_extension,
                        order: doc.order
                    })),
                    recipients: this.recipients.map(r => ({
                        name: r.name,
                        email: r.email,
                        recipient_type: r.recipient_type,
                        routing_order: r.routing_order
                    }))
                };

                const response = await $api.post(`/accounts/${accountId}/envelopes`, envelopePayload);
                const envelopeId = response.data.id;

                // Send if requested
                if (action === 'sent') {
                    await $api.post(`/accounts/${accountId}/envelopes/${envelopeId}/send`);
                    $store.toast.success('Envelope sent successfully!');
                } else {
                    $store.toast.success('Envelope saved as draft');
                }

                // Redirect to envelope details
                window.location.href = `/envelopes/${envelopeId}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                    $store.toast.error('Please fix the errors and try again');
                } else {
                    $store.toast.error('Failed to create envelope');
                }
                this.loading = false;
            }
        }
    }">

        <!-- Progress Steps -->
        <div class="mb-8">
            <nav aria-label="Progress">
                <ol class="flex items-center">
                    <li class="relative pr-8 sm:pr-20" :class="currentStep >= 1 ? 'flex-1' : 'flex-shrink-0'">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="h-0.5 w-full" :class="currentStep > 1 ? 'bg-primary-600' : 'bg-gray-200'"></div>
                        </div>
                        <div class="relative flex h-8 w-8 items-center justify-center rounded-full"
                             :class="currentStep >= 1 ? 'bg-primary-600' : 'bg-white border-2 border-gray-300'">
                            <span class="text-sm font-semibold" :class="currentStep >= 1 ? 'text-white' : 'text-gray-500'">1</span>
                        </div>
                        <span class="mt-2 block text-xs font-medium" :class="currentStep >= 1 ? 'text-primary-600' : 'text-gray-500'">Documents</span>
                    </li>

                    <li class="relative pr-8 sm:pr-20" :class="currentStep >= 2 ? 'flex-1' : 'flex-shrink-0'">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="h-0.5 w-full" :class="currentStep > 2 ? 'bg-primary-600' : 'bg-gray-200'"></div>
                        </div>
                        <div class="relative flex h-8 w-8 items-center justify-center rounded-full"
                             :class="currentStep >= 2 ? 'bg-primary-600' : 'bg-white border-2 border-gray-300'">
                            <span class="text-sm font-semibold" :class="currentStep >= 2 ? 'text-white' : 'text-gray-500'">2</span>
                        </div>
                        <span class="mt-2 block text-xs font-medium" :class="currentStep >= 2 ? 'text-primary-600' : 'text-gray-500'">Recipients</span>
                    </li>

                    <li class="relative pr-8 sm:pr-20" :class="currentStep >= 3 ? 'flex-1' : 'flex-shrink-0'">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="h-0.5 w-full" :class="currentStep > 3 ? 'bg-primary-600' : 'bg-gray-200'"></div>
                        </div>
                        <div class="relative flex h-8 w-8 items-center justify-center rounded-full"
                             :class="currentStep >= 3 ? 'bg-primary-600' : 'bg-white border-2 border-gray-300'">
                            <span class="text-sm font-semibold" :class="currentStep >= 3 ? 'text-white' : 'text-gray-500'">3</span>
                        </div>
                        <span class="mt-2 block text-xs font-medium" :class="currentStep >= 3 ? 'text-primary-600' : 'text-gray-500'">Details</span>
                    </li>

                    <li class="relative">
                        <div class="relative flex h-8 w-8 items-center justify-center rounded-full"
                             :class="currentStep >= 4 ? 'bg-primary-600' : 'bg-white border-2 border-gray-300'">
                            <span class="text-sm font-semibold" :class="currentStep >= 4 ? 'text-white' : 'text-gray-500'">4</span>
                        </div>
                        <span class="mt-2 block text-xs font-medium" :class="currentStep >= 4 ? 'text-primary-600' : 'text-gray-500'">Review</span>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Step 1: Upload Documents -->
        <x-ui.card x-show="currentStep === 1">
            <h2 class="text-xl font-semibold text-text-primary mb-4">Upload Documents</h2>
            <p class="text-sm text-text-secondary mb-6">Add the documents that need to be signed</p>

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
                        <div class="flex items-center space-x-4 flex-1">
                            <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-text-primary" x-text="doc.name"></p>
                                <p class="text-sm text-text-secondary">
                                    Order: <span x-text="doc.order"></span> •
                                    <span x-text="(doc.size / 1024 / 1024).toFixed(2)"></span> MB
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="moveDocumentUp(doc.id)" class="p-2 text-text-secondary hover:text-text-primary" :disabled="doc.order === 1">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button @click="moveDocumentDown(doc.id)" class="p-2 text-text-secondary hover:text-text-primary" :disabled="doc.order === documents.length">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <button @click="removeDocument(doc.id)" class="p-2 text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="documents.length === 0" class="text-center py-8 text-text-secondary">
                No documents uploaded yet
            </div>
        </x-ui.card>

        <!-- Step 2: Add Recipients -->
        <x-ui.card x-show="currentStep === 2">
            <h2 class="text-xl font-semibold text-text-primary mb-4">Add Recipients</h2>
            <p class="text-sm text-text-secondary mb-6">Specify who needs to sign or view this envelope</p>

            <!-- Recipients List -->
            <div class="space-y-4 mb-6">
                <template x-for="(recipient, index) in recipients" :key="recipient.id">
                    <div class="p-4 border border-border-primary rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-form.input
                                :name="'recipient_name_' + index"
                                label="Name"
                                x-model="recipient.name"
                                placeholder="John Doe"
                                :required="true"
                            />
                            <x-form.input
                                :name="'recipient_email_' + index"
                                label="Email"
                                type="email"
                                x-model="recipient.email"
                                placeholder="john@example.com"
                                :required="true"
                            />
                            <div>
                                <x-form.select
                                    :name="'recipient_type_' + index"
                                    label="Role"
                                    x-model="recipient.recipient_type"
                                    :options="[
                                        'signer' => 'Signer',
                                        'cc' => 'CC (Receives Copy)',
                                        'in_person_signer' => 'In-Person Signer'
                                    ]"
                                />
                                <button @click="removeRecipient(recipient.id)" class="mt-2 text-sm text-red-600 hover:text-red-700">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <x-ui.button variant="secondary" @click="addRecipient()">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Recipient
            </x-ui.button>

            <div x-show="recipients.length === 0" class="text-center py-8 text-text-secondary">
                No recipients added yet
            </div>
        </x-ui.card>

        <!-- Step 3: Envelope Details -->
        <x-ui.card x-show="currentStep === 3">
            <h2 class="text-xl font-semibold text-text-primary mb-4">Envelope Details</h2>
            <p class="text-sm text-text-secondary mb-6">Provide a subject and message for this envelope</p>

            <div class="space-y-4">
                <x-form.input
                    name="email_subject"
                    label="Email Subject"
                    x-model="envelopeData.email_subject"
                    placeholder="Please sign this document"
                    :required="true"
                    x-bind:error="errors.email_subject?.[0]"
                />

                <x-form.textarea
                    name="email_blurb"
                    label="Email Message"
                    x-model="envelopeData.email_blurb"
                    placeholder="Please review and sign the attached document(s)"
                    rows="4"
                />
            </div>
        </x-ui.card>

        <!-- Step 4: Review -->
        <x-ui.card x-show="currentStep === 4">
            <h2 class="text-xl font-semibold text-text-primary mb-4">Review & Send</h2>
            <p class="text-sm text-text-secondary mb-6">Review your envelope before sending</p>

            <div class="space-y-6">
                <!-- Documents Summary -->
                <div>
                    <h3 class="font-medium text-text-primary mb-2">Documents (<span x-text="documents.length"></span>)</h3>
                    <ul class="list-disc list-inside text-sm text-text-secondary space-y-1">
                        <template x-for="doc in documents" :key="doc.id">
                            <li x-text="doc.name"></li>
                        </template>
                    </ul>
                </div>

                <!-- Recipients Summary -->
                <div>
                    <h3 class="font-medium text-text-primary mb-2">Recipients (<span x-text="recipients.length"></span>)</h3>
                    <ul class="list-disc list-inside text-sm text-text-secondary space-y-1">
                        <template x-for="recipient in recipients" :key="recipient.id">
                            <li>
                                <span x-text="recipient.name"></span> (<span x-text="recipient.email"></span>) -
                                <span x-text="recipient.recipient_type.replace('_', ' ').charAt(0).toUpperCase() + recipient.recipient_type.slice(1).replace('_', ' ')"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <!-- Envelope Details Summary -->
                <div>
                    <h3 class="font-medium text-text-primary mb-2">Details</h3>
                    <dl class="text-sm text-text-secondary space-y-1">
                        <div class="flex">
                            <dt class="font-medium w-32">Subject:</dt>
                            <dd x-text="envelopeData.email_subject"></dd>
                        </div>
                        <div class="flex" x-show="envelopeData.email_blurb">
                            <dt class="font-medium w-32">Message:</dt>
                            <dd x-text="envelopeData.email_blurb"></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </x-ui.card>

        <!-- Navigation Buttons -->
        <div class="mt-6 flex items-center justify-between">
            <x-ui.button variant="secondary" @click="prevStep()" x-show="currentStep > 1">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Previous
            </x-ui.button>

            <div class="flex gap-2 ml-auto">
                <x-ui.button variant="secondary" @click="saveDraft()" x-show="currentStep === 4" :loading="loading">
                    Save as Draft
                </x-ui.button>

                <x-ui.button variant="primary" @click="nextStep()" x-show="currentStep < 4" :disabled="!canProceed(currentStep)">
                    Next
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </x-ui.button>

                <x-ui.button variant="primary" @click="sendEnvelope()" x-show="currentStep === 4" :loading="loading">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Send Envelope
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
