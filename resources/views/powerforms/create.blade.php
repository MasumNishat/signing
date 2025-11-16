<x-layout.app title="Create PowerForm">
    <div x-data="{
        step: 1,
        loading: false,
        templates: [],
        powerformData: {
            name: '',
            template_id: '',
            status: 'active',
            signing_mode: 'email',
            allow_multiple_submissions: false
        },
        emailSettings: {
            subject: '',
            message: ''
        },
        errors: {},
        async init() {
            await this.loadTemplates();
        },
        async loadTemplates() {
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/templates`);
                this.templates = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load templates');
            }
        },
        async createPowerForm() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/powerforms`,
                    {
                        ...this.powerformData,
                        ...this.emailSettings
                    }
                );

                $store.toast.success('PowerForm created successfully');
                window.location.href = `/powerforms/${response.data.id}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to create PowerForm');
            } finally {
                this.loading = false;
            }
        },
        nextStep() {
            if (this.step === 1 && (!this.powerformData.name || !this.powerformData.template_id)) {
                $store.toast.error('Please fill in all required fields');
                return;
            }
            this.step++;
        },
        prevStep() {
            this.step--;
        }
    }" x-init="init()">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Create PowerForm</h1>
            <p class="mt-1 text-sm text-text-secondary">Create a public form for document signing</p>
        </div>

        <!-- Step Indicator -->
        <div class="mb-6">
            <div class="flex items-center">
                <div class="flex items-center" :class="step >= 1 ? 'text-primary-600' : 'text-text-secondary'">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2" :class="step >= 1 ? 'border-primary-600 bg-primary-600 text-white' : 'border-border-primary'">
                        <span class="text-sm font-medium">1</span>
                    </div>
                    <span class="ml-2 text-sm font-medium">Basic Info</span>
                </div>
                <div class="mx-4 h-0.5 w-16 bg-border-primary"></div>
                <div class="flex items-center" :class="step >= 2 ? 'text-primary-600' : 'text-text-secondary'">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2" :class="step >= 2 ? 'border-primary-600 bg-primary-600 text-white' : 'border-border-primary'">
                        <span class="text-sm font-medium">2</span>
                    </div>
                    <span class="ml-2 text-sm font-medium">Email Settings</span>
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

        <!-- Step 1: Basic Information -->
        <div x-show="step === 1">
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">PowerForm Information</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">PowerForm Name *</label>
                        <x-ui.input
                            type="text"
                            x-model="powerformData.name"
                            placeholder="Employee Onboarding Form"
                            required
                        />
                        <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name?.[0]"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Select Template *</label>
                        <x-ui.select x-model="powerformData.template_id" required>
                            <option value="">Choose a template...</option>
                            <template x-for="template in templates" :key="template.id">
                                <option x-bind:value="template.id" x-text="template.name"></option>
                            </template>
                        </x-ui.select>
                        <p x-show="errors.template_id" class="mt-1 text-sm text-red-600" x-text="errors.template_id?.[0]"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Signing Mode</label>
                        <x-ui.select x-model="powerformData.signing_mode">
                            <option value="email">Email (requires email address)</option>
                            <option value="direct">Direct (no email required)</option>
                        </x-ui.select>
                        <p class="mt-1 text-xs text-text-secondary">Determines if recipient email is required</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Status</label>
                        <x-ui.select x-model="powerformData.status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </x-ui.select>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            x-model="powerformData.allow_multiple_submissions"
                            id="allow-multiple"
                            class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <label for="allow-multiple" class="ml-2 text-sm text-text-primary">
                            Allow multiple submissions from same email
                        </label>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Step 2: Email Settings -->
        <div x-show="step === 2">
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Email Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Email Subject</label>
                        <x-ui.input
                            type="text"
                            x-model="emailSettings.subject"
                            placeholder="Please complete this document"
                        />
                        <p class="mt-1 text-xs text-text-secondary">Subject line for email notifications</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Email Message</label>
                        <textarea
                            x-model="emailSettings.message"
                            rows="4"
                            placeholder="Thank you for completing this form..."
                            class="w-full px-3 py-2 border border-border-primary rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 bg-bg-primary text-text-primary"
                        ></textarea>
                        <p class="mt-1 text-xs text-text-secondary">Message included in email notifications</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Step 3: Review -->
        <div x-show="step === 3">
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Review PowerForm</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">PowerForm Name</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="powerformData.name"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Template</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="templates.find(t => t.id === powerformData.template_id)?.name || 'N/A'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Signing Mode</dt>
                        <dd class="mt-1">
                            <x-ui.badge variant="secondary" x-text="powerformData.signing_mode === 'email' ? 'Email Required' : 'Direct Signing'"></x-ui.badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Status</dt>
                        <dd class="mt-1">
                            <x-ui.badge x-bind:variant="powerformData.status === 'active' ? 'success' : 'secondary'" x-text="powerformData.status?.toUpperCase()"></x-ui.badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Multiple Submissions</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="powerformData.allow_multiple_submissions ? 'Allowed' : 'Not Allowed'"></dd>
                    </div>
                    <div x-show="emailSettings.subject">
                        <dt class="text-sm font-medium text-text-secondary">Email Subject</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="emailSettings.subject"></dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <div>
                <x-ui.button variant="secondary" onclick="window.location.href='/powerforms'" x-show="step === 1">
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
                <x-ui.button variant="primary" @click="createPowerForm()" x-show="step === 3" :disabled="loading">
                    <span x-show="!loading">Create PowerForm</span>
                    <span x-show="loading">Creating...</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
