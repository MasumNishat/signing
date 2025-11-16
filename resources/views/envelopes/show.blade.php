<x-layout.app :title="'Envelope: ' . ($envelope->email_subject ?? 'Details')">
    <div x-data="{
        loading: true,
        envelope: {},
        documents: [],
        recipients: [],
        auditEvents: [],
        activeTab: 'details',
        async loadEnvelope() {
            this.loading = true;
            try {
                const envelopeId = '{{ $envelopeId }}';
                const accountId = $store.auth.user.account_id;

                // Load envelope details
                const envResponse = await $api.get(`/accounts/${accountId}/envelopes/${envelopeId}`);
                this.envelope = envResponse.data;

                // Load documents
                const docsResponse = await $api.get(`/accounts/${accountId}/envelopes/${envelopeId}/documents`);
                this.documents = docsResponse.data.data || docsResponse.data;

                // Load recipients
                const recipientsResponse = await $api.get(`/accounts/${accountId}/envelopes/${envelopeId}/recipients`);
                this.recipients = recipientsResponse.data.data || recipientsResponse.data;

                // Load audit events
                const auditResponse = await $api.get(`/accounts/${accountId}/envelopes/${envelopeId}/audit_events`);
                this.auditEvents = auditResponse.data.data || auditResponse.data;

                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load envelope details');
                this.loading = false;
            }
        },
        async sendEnvelope() {
            if (!confirm('Send this envelope to all recipients?')) return;

            try {
                await $api.post(`/accounts/${$store.auth.user.account_id}/envelopes/${this.envelope.id}/send`);
                $store.toast.success('Envelope sent successfully');
                this.loadEnvelope();
            } catch (error) {
                $store.toast.error('Failed to send envelope');
            }
        },
        async voidEnvelope() {
            const reason = prompt('Enter void reason:');
            if (!reason) return;

            try {
                await $api.post(`/accounts/${$store.auth.user.account_id}/envelopes/${this.envelope.id}/void`, {
                    voided_reason: reason
                });
                $store.toast.success('Envelope voided');
                this.loadEnvelope();
            } catch (error) {
                $store.toast.error('Failed to void envelope');
            }
        },
        async downloadEnvelope() {
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/envelopes/${this.envelope.id}/documents/combined`, {
                    responseType: 'blob'
                });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `envelope-${this.envelope.id}.pdf`);
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                $store.toast.error('Failed to download envelope');
            }
        },
        getStatusColor(status) {
            const colors = {
                'draft': 'bg-gray-100 text-gray-800',
                'sent': 'bg-blue-100 text-blue-800',
                'delivered': 'bg-indigo-100 text-indigo-800',
                'completed': 'bg-green-100 text-green-800',
                'voided': 'bg-red-100 text-red-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        },
        getRecipientStatusColor(status) {
            const colors = {
                'created': 'bg-gray-100 text-gray-800',
                'sent': 'bg-blue-100 text-blue-800',
                'delivered': 'bg-indigo-100 text-indigo-800',
                'signed': 'bg-green-100 text-green-800',
                'completed': 'bg-green-600 text-white',
                'declined': 'bg-red-100 text-red-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }
    }" x-init="loadEnvelope()">

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
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-bold text-text-primary" x-text="envelope.email_subject"></h1>
                        <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full"
                              :class="getStatusColor(envelope.status)"
                              x-text="envelope.status ? envelope.status.charAt(0).toUpperCase() + envelope.status.slice(1) : ''">
                        </span>
                    </div>
                    <p class="text-sm text-text-secondary">
                        Created on <span x-text="envelope.created_at ? new Date(envelope.created_at).toLocaleDateString() : ''"></span>
                        <span x-show="envelope.sent_at">
                            • Sent on <span x-text="new Date(envelope.sent_at).toLocaleDateString()"></span>
                        </span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <x-ui.button variant="secondary" @click="downloadEnvelope()">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download
                    </x-ui.button>

                    <x-ui.button variant="primary" @click="sendEnvelope()" x-show="envelope.status === 'draft'">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Send
                    </x-ui.button>

                    <x-ui.button variant="danger" @click="voidEnvelope()" x-show="['sent', 'delivered'].includes(envelope.status)">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Void
                    </x-ui.button>

                    <x-ui.button variant="secondary" @click="window.location.href=`/envelopes/${envelope.id}/edit`" x-show="envelope.status === 'draft'">
                        Edit
                    </x-ui.button>
                </div>
            </div>

            <!-- Tabs -->
            <x-ui.card :padding="false">
                <div class="border-b border-card-border">
                    <nav class="flex -mb-px">
                        <button @click="activeTab = 'details'"
                                :class="activeTab === 'details' ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-gray-300'"
                                class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                            Details
                        </button>
                        <button @click="activeTab = 'documents'"
                                :class="activeTab === 'documents' ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-gray-300'"
                                class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                            Documents (<span x-text="documents.length"></span>)
                        </button>
                        <button @click="activeTab = 'recipients'"
                                :class="activeTab === 'recipients' ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-gray-300'"
                                class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                            Recipients (<span x-text="recipients.length"></span>)
                        </button>
                        <button @click="activeTab = 'history'"
                                :class="activeTab === 'history' ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-gray-300'"
                                class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                            History
                        </button>
                    </nav>
                </div>

                <!-- Details Tab -->
                <div x-show="activeTab === 'details'" class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Envelope ID</dt>
                            <dd class="mt-1 text-sm text-text-primary font-mono" x-text="envelope.envelope_id"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                      :class="getStatusColor(envelope.status)"
                                      x-text="envelope.status ? envelope.status.charAt(0).toUpperCase() + envelope.status.slice(1) : ''">
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Subject</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="envelope.email_subject"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Message</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="envelope.email_blurb || 'No message'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Created</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="envelope.created_at ? new Date(envelope.created_at).toLocaleString() : ''"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Sent</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="envelope.sent_at ? new Date(envelope.sent_at).toLocaleString() : 'Not sent'"></dd>
                        </div>
                        <div x-show="envelope.completed_at">
                            <dt class="text-sm font-medium text-text-secondary">Completed</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="envelope.completed_at ? new Date(envelope.completed_at).toLocaleString() : ''"></dd>
                        </div>
                        <div x-show="envelope.voided_at">
                            <dt class="text-sm font-medium text-text-secondary">Voided</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="envelope.voided_at ? new Date(envelope.voided_at).toLocaleString() : ''"></dd>
                        </div>
                        <div x-show="envelope.voided_reason" class="md:col-span-2">
                            <dt class="text-sm font-medium text-text-secondary">Void Reason</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="envelope.voided_reason"></dd>
                        </div>
                    </dl>
                </div>

                <!-- Documents Tab -->
                <div x-show="activeTab === 'documents'" class="p-6">
                    <div class="space-y-4">
                        <template x-for="doc in documents" :key="doc.id">
                            <div class="flex items-center justify-between p-4 border border-border-primary rounded-lg hover:bg-bg-hover">
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
                                <div class="flex gap-2">
                                    <x-ui.icon-button tooltip="View" size="sm">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </x-ui.icon-button>
                                    <x-ui.icon-button tooltip="Download" size="sm">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </x-ui.icon-button>
                                </div>
                            </div>
                        </template>

                        <div x-show="documents.length === 0" class="text-center py-8 text-text-secondary">
                            No documents attached
                        </div>
                    </div>
                </div>

                <!-- Recipients Tab -->
                <div x-show="activeTab === 'recipients'" class="p-6">
                    <div class="space-y-4">
                        <template x-for="recipient in recipients" :key="recipient.id">
                            <div class="p-4 border border-border-primary rounded-lg">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <p class="font-medium text-text-primary" x-text="recipient.name"></p>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="getRecipientStatusColor(recipient.status)"
                                                  x-text="recipient.status ? recipient.status.charAt(0).toUpperCase() + recipient.status.slice(1) : ''">
                                            </span>
                                        </div>
                                        <p class="text-sm text-text-secondary" x-text="recipient.email"></p>
                                        <p class="text-sm text-text-secondary mt-1">
                                            Role: <span x-text="recipient.recipient_type ? recipient.recipient_type.replace('_', ' ').charAt(0).toUpperCase() + recipient.recipient_type.slice(1).replace('_', ' ') : ''"></span>
                                            <span x-show="recipient.routing_order"> • Routing Order: <span x-text="recipient.routing_order"></span></span>
                                        </p>
                                        <div x-show="recipient.signed_at" class="mt-2 text-sm text-green-600">
                                            ✓ Signed on <span x-text="new Date(recipient.signed_at).toLocaleString()"></span>
                                        </div>
                                        <div x-show="recipient.delivered_at && !recipient.signed_at" class="mt-2 text-sm text-blue-600">
                                            Delivered on <span x-text="new Date(recipient.delivered_at).toLocaleString()"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="recipients.length === 0" class="text-center py-8 text-text-secondary">
                            No recipients added
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div x-show="activeTab === 'history'" class="p-6">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            <template x-for="(event, index) in auditEvents" :key="event.id">
                                <li>
                                    <div class="relative pb-8">
                                        <span x-show="index < auditEvents.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center ring-8 ring-bg-primary">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div>
                                                    <p class="text-sm font-medium text-text-primary" x-text="event.event_type"></p>
                                                    <p class="text-sm text-text-secondary" x-text="event.user_name || 'System'"></p>
                                                </div>
                                                <p class="mt-2 text-sm text-text-primary" x-text="event.description"></p>
                                                <p class="mt-1 text-xs text-text-secondary" x-text="new Date(event.timestamp).toLocaleString()"></p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        <div x-show="auditEvents.length === 0" class="text-center py-8 text-text-secondary">
                            No activity recorded
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
