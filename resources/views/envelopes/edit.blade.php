<x-layout.app :title="'Edit Envelope: ' . ($envelope->email_subject ?? 'Draft')">
    <div x-data="{
        loading: true,
        currentStep: 1,
        envelopeData: {
            email_subject: '',
            email_blurb: ''
        },
        documents: [],
        recipients: [],
        errors: {},
        async loadEnvelope() {
            this.loading = true;
            try {
                const envelopeId = '{{ $envelopeId }}';
                const accountId = $store.auth.user.account_id;

                // Load envelope details
                const envResponse = await $api.get(`/accounts/${accountId}/envelopes/${envelopeId}`);
                this.envelopeData = {
                    email_subject: envResponse.data.email_subject || '',
                    email_blurb: envResponse.data.email_blurb || ''
                };

                // Check if draft
                if (envResponse.data.status !== 'draft') {
                    $store.toast.error('Only draft envelopes can be edited');
                    window.location.href = `/envelopes/${envelopeId}`;
                    return;
                }

                // Load documents
                const docsResponse = await $api.get(`/accounts/${accountId}/envelopes/${envelopeId}/documents`);
                this.documents = docsResponse.data.data || docsResponse.data;

                // Load recipients
                const recipientsResponse = await $api.get(`/accounts/${accountId}/envelopes/${envelopeId}/recipients`);
                this.recipients = recipientsResponse.data.data || recipientsResponse.data;

                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load envelope');
                this.loading = false;
            }
        },
        async updateEnvelope() {
            this.loading = true;
            this.errors = {};

            try {
                const envelopeId = '{{ $envelopeId }}';
                const accountId = $store.auth.user.account_id;

                // Update envelope details
                await $api.put(`/accounts/${accountId}/envelopes/${envelopeId}`, {
                    email_subject: this.envelopeData.email_subject,
                    email_blurb: this.envelopeData.email_blurb
                });

                $store.toast.success('Envelope updated successfully');
                window.location.href = `/envelopes/${envelopeId}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                    $store.toast.error('Please fix the errors and try again');
                } else {
                    $store.toast.error('Failed to update envelope');
                }
                this.loading = false;
            }
        }
    }" x-init="loadEnvelope()">

        <!-- Loading State -->
        <div x-show="loading" class="space-y-6">
            <x-ui.skeleton type="card" class="h-64" />
        </div>

        <!-- Content -->
        <div x-show="!loading" class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-text-primary">Edit Envelope</h1>
                    <p class="mt-1 text-sm text-text-secondary">Update envelope details (draft only)</p>
                </div>
                <x-ui.button variant="secondary" @click="window.location.href='/envelopes/{{ $envelopeId }}'">
                    Cancel
                </x-ui.button>
            </div>

            <!-- Documents (Read-only) -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Documents</h2>
                <div class="space-y-3">
                    <template x-for="doc in documents" :key="doc.id">
                        <div class="flex items-center justify-between p-4 border border-border-primary rounded-lg">
                            <div class="flex items-center space-x-4">
                                <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-text-primary" x-text="doc.name"></p>
                                    <p class="text-sm text-text-secondary">Order: <span x-text="doc.order"></span></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="mt-4 text-sm text-text-secondary">Documents cannot be changed after creation</p>
            </x-ui.card>

            <!-- Recipients (Read-only) -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Recipients</h2>
                <div class="space-y-3">
                    <template x-for="recipient in recipients" :key="recipient.id">
                        <div class="p-4 border border-border-primary rounded-lg">
                            <p class="font-medium text-text-primary" x-text="recipient.name"></p>
                            <p class="text-sm text-text-secondary" x-text="recipient.email"></p>
                            <p class="text-sm text-text-secondary mt-1">
                                Role: <span x-text="recipient.recipient_type ? recipient.recipient_type.replace('_', ' ').charAt(0).toUpperCase() + recipient.recipient_type.slice(1).replace('_', ' ') : ''"></span>
                            </p>
                        </div>
                    </template>
                </div>
                <p class="mt-4 text-sm text-text-secondary">Recipients cannot be changed after creation</p>
            </x-ui.card>

            <!-- Envelope Details (Editable) -->
            <x-ui.card>
                <h2 class="text-lg font-semibold text-text-primary mb-4">Envelope Details</h2>
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

            <!-- Actions -->
            <div class="flex gap-2 justify-end">
                <x-ui.button variant="secondary" @click="window.location.href='/envelopes/{{ $envelopeId }}'">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="updateEnvelope()" :loading="loading">
                    Save Changes
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
