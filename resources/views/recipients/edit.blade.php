<x-layout.app title="Edit Recipient">
    <div x-data="{
        accountId: {{ auth()->user()->account_id ?? 1 }},
        recipientId: {{ $recipientId }},
        loading: true,
        saving: false,
        formData: {
            email: '',
            name: '',
            first_name: '',
            last_name: '',
            company_name: '',
            phone_number: '',
            mobile_phone: ''
        },
        errors: {},
        async init() {
            await this.loadRecipient();
        },
        async loadRecipient() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${this.accountId}/contacts/${this.recipientId}`);
                this.formData = {
                    email: response.data.data.email || '',
                    name: response.data.data.name || '',
                    first_name: response.data.data.first_name || '',
                    last_name: response.data.data.last_name || '',
                    company_name: response.data.data.company_name || '',
                    phone_number: response.data.data.phone_number || '',
                    mobile_phone: response.data.data.mobile_phone || ''
                };
                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load recipient');
                setTimeout(() => window.location.href = '/recipients', 2000);
            }
        },
        async updateRecipient() {
            this.saving = true;
            this.errors = {};

            try {
                // Use import endpoint which handles create/update by email
                await $api.post(`/accounts/${this.accountId}/contacts`, {
                    contacts: [this.formData]
                });
                $store.toast.success('Recipient updated successfully');
                window.location.href = '/recipients';
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                    $store.toast.error('Please fix the errors and try again');
                } else {
                    $store.toast.error(error.response?.data?.message || 'Failed to update recipient');
                }
                this.saving = false;
            }
        },
        async deleteRecipient() {
            if (!confirm('Are you sure you want to delete this recipient? This action cannot be undone.')) {
                return;
            }

            try {
                await $api.delete(`/accounts/${this.accountId}/contacts/${this.recipientId}`);
                $store.toast.success('Recipient deleted successfully');
                window.location.href = '/recipients';
            } catch (error) {
                $store.toast.error('Failed to delete recipient');
            }
        }
    }"
    x-init="init()">

        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-3">
                    <a href="/recipients" class="text-text-secondary hover:text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Recipient</h1>
                </div>
                <x-ui.button
                    type="button"
                    variant="danger"
                    @click="deleteRecipient()"
                    x-show="!loading"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Recipient
                </x-ui.button>
            </div>
            <p class="text-sm text-text-secondary ml-9">Update recipient information</p>
        </div>

        <!-- Loading State -->
        <x-ui.card x-show="loading">
            <div class="space-y-4">
                <x-ui.skeleton type="text" class="h-10 w-full" />
                <x-ui.skeleton type="text" class="h-10 w-full" />
                <x-ui.skeleton type="text" class="h-10 w-full" />
                <x-ui.skeleton type="text" class="h-10 w-full" />
            </div>
        </x-ui.card>

        <!-- Form -->
        <x-ui.card x-show="!loading" x-cloak>
            <form @submit.prevent="updateRecipient()" class="space-y-6">
                <!-- Contact Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Contact Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-form.input
                                name="email"
                                label="Email Address"
                                type="email"
                                x-model="formData.email"
                                placeholder="john.doe@example.com"
                                required
                                x-bind:error="errors.email?.[0]"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <x-form.input
                                name="name"
                                label="Full Name"
                                x-model="formData.name"
                                placeholder="John Doe"
                                required
                                x-bind:error="errors.name?.[0]"
                                help="This name will appear on envelope communications"
                            />
                        </div>

                        <x-form.input
                            name="first_name"
                            label="First Name"
                            x-model="formData.first_name"
                            placeholder="John"
                            x-bind:error="errors.first_name?.[0]"
                        />

                        <x-form.input
                            name="last_name"
                            label="Last Name"
                            x-model="formData.last_name"
                            placeholder="Doe"
                            x-bind:error="errors.last_name?.[0]"
                        />

                        <div class="md:col-span-2">
                            <x-form.input
                                name="company_name"
                                label="Company Name"
                                x-model="formData.company_name"
                                placeholder="Acme Corporation"
                                x-bind:error="errors.company_name?.[0]"
                            />
                        </div>
                    </div>
                </div>

                <!-- Phone Numbers -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Phone Numbers</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.input
                            name="phone_number"
                            label="Phone Number"
                            type="tel"
                            x-model="formData.phone_number"
                            placeholder="+1 (555) 123-4567"
                            x-bind:error="errors.phone_number?.[0]"
                        />

                        <x-form.input
                            name="mobile_phone"
                            label="Mobile Phone"
                            type="tel"
                            x-model="formData.mobile_phone"
                            placeholder="+1 (555) 987-6543"
                            x-bind:error="errors.mobile_phone?.[0]"
                        />
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-border-primary">
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        @click="window.location.href='/recipients'"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                        variant="primary"
                        x-bind:loading="saving"
                        x-bind:disabled="saving || !formData.email || !formData.name"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Update Recipient
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layout.app>
