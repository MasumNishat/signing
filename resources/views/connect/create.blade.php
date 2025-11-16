<x-layout.app title="Create Webhook">
    <div x-data="{
        step: 1,
        loading: false,
        webhookData: {
            name: '',
            url: '',
            events: [],
            status: 'active'
        },
        availableEvents: [
            { id: 'envelope-sent', name: 'Envelope Sent' },
            { id: 'envelope-delivered', name: 'Envelope Delivered' },
            { id: 'envelope-completed', name: 'Envelope Completed' },
            { id: 'envelope-declined', name: 'Envelope Declined' },
            { id: 'envelope-voided', name: 'Envelope Voided' },
            { id: 'recipient-sent', name: 'Recipient Sent' },
            { id: 'recipient-delivered', name: 'Recipient Delivered' },
            { id: 'recipient-completed', name: 'Recipient Completed' },
            { id: 'recipient-declined', name: 'Recipient Declined' },
            { id: 'recipient-authentication-failed', name: 'Recipient Auth Failed' },
            { id: 'template-created', name: 'Template Created' },
            { id: 'template-modified', name: 'Template Modified' },
            { id: 'template-deleted', name: 'Template Deleted' }
        ],
        errors: {},
        toggleEvent(eventId) {
            const index = this.webhookData.events.indexOf(eventId);
            if (index === -1) {
                this.webhookData.events.push(eventId);
            } else {
                this.webhookData.events.splice(index, 1);
            }
        },
        isEventSelected(eventId) {
            return this.webhookData.events.includes(eventId);
        },
        async createWebhook() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/connect`,
                    this.webhookData
                );

                $store.toast.success('Webhook created successfully');
                window.location.href = `/connect/${response.data.id}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to create webhook');
            } finally {
                this.loading = false;
            }
        },
        nextStep() {
            if (this.step === 1 && (!this.webhookData.name || !this.webhookData.url)) {
                $store.toast.error('Please fill in all required fields');
                return;
            }
            if (this.step === 2 && this.webhookData.events.length === 0) {
                $store.toast.error('Please select at least one event');
                return;
            }
            this.step++;
        },
        prevStep() {
            this.step--;
        }
    }">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Create Webhook</h1>
            <p class="mt-1 text-sm text-text-secondary">Configure a webhook to receive real-time event notifications</p>
        </div>

        <!-- Step Indicator -->
        <div class="mb-6">
            <div class="flex items-center">
                <div class="flex items-center" :class="step >= 1 ? 'text-primary-600' : 'text-text-secondary'">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2" :class="step >= 1 ? 'border-primary-600 bg-primary-600 text-white' : 'border-border-primary'">
                        <span class="text-sm font-medium">1</span>
                    </div>
                    <span class="ml-2 text-sm font-medium">Configuration</span>
                </div>
                <div class="mx-4 h-0.5 w-16 bg-border-primary"></div>
                <div class="flex items-center" :class="step >= 2 ? 'text-primary-600' : 'text-text-secondary'">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2" :class="step >= 2 ? 'border-primary-600 bg-primary-600 text-white' : 'border-border-primary'">
                        <span class="text-sm font-medium">2</span>
                    </div>
                    <span class="ml-2 text-sm font-medium">Events</span>
                </div>
                <div class="mx-4 h-0.5 w-16 bg-border-primary"></div>
                <div class="flex items-center" :class="step >= 3 ? 'text-primary-600' : 'text-text-secondary'">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2" :class="step >= 3 ? 'border-primary-600 bg-primary-600 text-white' : 'border-border-primary'">
                        <span class="text-sm font-medium">3</span>
                    </div>
                    <span class="ml-2 text-sm font-medium">Review</span>
                </div>
            </div>
        </div>

        <!-- Step 1: Configuration -->
        <div x-show="step === 1">
            <x-ui.card class="mb-6 max-w-2xl">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Webhook Configuration</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Webhook Name *</label>
                        <x-ui.input type="text" x-model="webhookData.name" placeholder="Production Webhook" required />
                        <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name?.[0]"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Endpoint URL *</label>
                        <x-ui.input type="url" x-model="webhookData.url" placeholder="https://yourapp.com/webhooks/docusign" required />
                        <p class="mt-1 text-xs text-text-secondary">Must be a valid HTTPS URL</p>
                        <p x-show="errors.url" class="mt-1 text-sm text-red-600" x-text="errors.url?.[0]"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Status</label>
                        <x-ui.select x-model="webhookData.status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </x-ui.select>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Step 2: Events -->
        <div x-show="step === 2">
            <x-ui.card class="mb-6 max-w-2xl">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Select Events</h3>
                <p class="text-sm text-text-secondary mb-4">Choose which events should trigger this webhook</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <template x-for="event in availableEvents" :key="event.id">
                        <div class="flex items-center p-3 border border-border-primary rounded-md hover:bg-bg-secondary cursor-pointer" @click="toggleEvent(event.id)">
                            <input type="checkbox" :checked="isEventSelected(event.id)" class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label class="ml-2 text-sm text-text-primary cursor-pointer" x-text="event.name"></label>
                        </div>
                    </template>
                </div>
                <p class="mt-2 text-sm text-text-secondary">
                    Selected: <span class="font-medium" x-text="webhookData.events.length"></span> events
                </p>
            </x-ui.card>
        </div>

        <!-- Step 3: Review -->
        <div x-show="step === 3">
            <x-ui.card class="mb-6 max-w-2xl">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Review Webhook</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Webhook Name</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="webhookData.name"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Endpoint URL</dt>
                        <dd class="mt-1 text-sm text-text-primary font-mono" x-text="webhookData.url"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Status</dt>
                        <dd class="mt-1"><x-ui.badge x-bind:variant="webhookData.status === 'active' ? 'success' : 'secondary'" x-text="webhookData.status?.toUpperCase()"></x-ui.badge></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Events (<span x-text="webhookData.events.length"></span>)</dt>
                        <dd class="mt-2 flex flex-wrap gap-2">
                            <template x-for="eventId in webhookData.events" :key="eventId">
                                <span class="px-2 py-1 text-xs bg-bg-secondary border border-border-primary rounded-md text-text-primary" x-text="availableEvents.find(e => e.id === eventId)?.name"></span>
                            </template>
                        </dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between max-w-2xl">
            <div>
                <x-ui.button variant="secondary" onclick="window.location.href='/connect'" x-show="step === 1">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="secondary" @click="prevStep()" x-show="step > 1">
                    Back
                </x-ui.button>
            </div>
            <div>
                <x-ui.button variant="primary" @click="nextStep()" x-show="step < 3">
                    Continue
                </x-ui.button>
                <x-ui.button variant="primary" @click="createWebhook()" x-show="step === 3" :disabled="loading">
                    <span x-show="!loading">Create Webhook</span>
                    <span x-show="loading">Creating...</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
