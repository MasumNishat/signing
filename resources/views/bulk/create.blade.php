<x-layout.app title="Create Bulk Send">
    <div x-data="{
        step: 1,
        loading: false,
        templates: [],
        bulkData: {
            batch_name: '',
            template_id: '',
            send_immediately: true
        },
        recipients: [],
        csvFile: null,
        uploadProgress: 0,
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
        async uploadCsv() {
            if (!this.csvFile) {
                $store.toast.error('Please select a CSV file');
                return;
            }

            this.loading = true;
            this.uploadProgress = 0;

            try {
                const formData = new FormData();
                formData.append('file', this.csvFile);

                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/bulk_send_lists`,
                    formData,
                    {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (progressEvent) => {
                            this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        }
                    }
                );

                this.recipients = response.data.recipients || [];
                this.step = 3;
                $store.toast.success(`Loaded ${this.recipients.length} recipients`);
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to upload CSV');
            } finally {
                this.loading = false;
                this.uploadProgress = 0;
            }
        },
        addRecipient() {
            this.recipients.push({
                name: '',
                email: '',
                role: 'signer'
            });
        },
        removeRecipient(index) {
            this.recipients.splice(index, 1);
        },
        async createBulkSend() {
            if (this.recipients.length === 0) {
                $store.toast.error('Please add at least one recipient');
                return;
            }

            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/bulk_send_batches`,
                    {
                        ...this.bulkData,
                        recipients: this.recipients
                    }
                );

                $store.toast.success('Bulk send created successfully');
                window.location.href = `/bulk/${response.data.id}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to create bulk send');
            } finally {
                this.loading = false;
            }
        },
        nextStep() {
            if (this.step === 1) {
                if (!this.bulkData.batch_name || !this.bulkData.template_id) {
                    $store.toast.error('Please fill in all required fields');
                    return;
                }
            }
            this.step++;
        },
        prevStep() {
            this.step--;
        }
    }" x-init="init()">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Create Bulk Send</h1>
            <p class="mt-1 text-sm text-text-secondary">Send an envelope to multiple recipients</p>
        </div>

        <!-- Step Indicator -->
        <div class="mb-6">
            <div class="flex items-center">
                <div class="flex items-center" :class="step >= 1 ? 'text-primary-600' : 'text-text-secondary'">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2" :class="step >= 1 ? 'border-primary-600 bg-primary-600 text-white' : 'border-border-primary'">
                        <span class="text-sm font-medium">1</span>
                    </div>
                    <span class="ml-2 text-sm font-medium">Select Template</span>
                </div>
                <div class="mx-4 h-0.5 w-16 bg-border-primary"></div>
                <div class="flex items-center" :class="step >= 2 ? 'text-primary-600' : 'text-text-secondary'">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2" :class="step >= 2 ? 'border-primary-600 bg-primary-600 text-white' : 'border-border-primary'">
                        <span class="text-sm font-medium">2</span>
                    </div>
                    <span class="ml-2 text-sm font-medium">Add Recipients</span>
                </div>
                <div class="mx-4 h-0.5 w-16 bg-border-primary"></div>
                <div class="flex items-center" :class="step >= 3 ? 'text-primary-600' : 'text-text-secondary'">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border-2" :class="step >= 3 ? 'border-primary-600 bg-primary-600 text-white' : 'border-border-primary'">
                        <span class="text-sm font-medium">3</span>
                    </div>
                    <span class="ml-2 text-sm font-medium">Review & Send</span>
                </div>
            </div>
        </div>

        <!-- Step 1: Select Template -->
        <div x-show="step === 1">
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Batch Information</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Batch Name *</label>
                        <x-ui.input
                            type="text"
                            x-model="bulkData.batch_name"
                            placeholder="Q1 2024 Customer Contracts"
                            required
                        />
                        <p x-show="errors.batch_name" class="mt-1 text-sm text-red-600" x-text="errors.batch_name?.[0]"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Select Template *</label>
                        <x-ui.select x-model="bulkData.template_id" required>
                            <option value="">Choose a template...</option>
                            <template x-for="template in templates" :key="template.id">
                                <option x-bind:value="template.id" x-text="template.name"></option>
                            </template>
                        </x-ui.select>
                        <p x-show="errors.template_id" class="mt-1 text-sm text-red-600" x-text="errors.template_id?.[0]"></p>
                    </div>
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            x-model="bulkData.send_immediately"
                            id="send-immediately"
                            class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <label for="send-immediately" class="ml-2 text-sm text-text-primary">
                            Send immediately after creation
                        </label>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Step 2: Add Recipients -->
        <div x-show="step === 2">
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Add Recipients</h3>

                <!-- CSV Upload -->
                <div class="mb-6 p-4 border-2 border-dashed border-border-primary rounded-lg">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <h4 class="mt-2 text-sm font-medium text-text-primary">Upload CSV File</h4>
                        <p class="mt-1 text-xs text-text-secondary">CSV must include Name, Email columns</p>
                        <input
                            type="file"
                            @change="csvFile = $event.target.files[0]"
                            accept=".csv"
                            class="mt-4 block w-full text-sm text-text-secondary
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-primary-50 file:text-primary-700
                                hover:file:bg-primary-100"
                        />
                        <p x-show="csvFile" class="mt-2 text-sm text-text-primary">
                            Selected: <span x-text="csvFile?.name"></span>
                        </p>
                        <x-ui.button variant="primary" class="mt-4" @click="uploadCsv()" :disabled="!csvFile || loading">
                            <span x-show="!loading">Upload CSV</span>
                            <span x-show="loading">Uploading... <span x-text="uploadProgress + '%'"></span></span>
                        </x-ui.button>
                    </div>
                </div>

                <div class="text-center text-sm text-text-secondary mb-6">OR</div>

                <!-- Manual Entry -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-medium text-text-primary">Manually Add Recipients</h4>
                        <x-ui.button variant="secondary" size="sm" @click="addRecipient()">
                            + Add Recipient
                        </x-ui.button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(recipient, index) in recipients" :key="index">
                            <div class="flex items-start space-x-4 p-4 bg-bg-secondary rounded-lg">
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-text-primary mb-1">Name *</label>
                                        <x-ui.input
                                            type="text"
                                            x-model="recipient.name"
                                            placeholder="John Doe"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-text-primary mb-1">Email *</label>
                                        <x-ui.input
                                            type="email"
                                            x-model="recipient.email"
                                            placeholder="john@example.com"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-text-primary mb-1">Role</label>
                                        <x-ui.select x-model="recipient.role">
                                            <option value="signer">Signer</option>
                                            <option value="approver">Approver</option>
                                            <option value="viewer">Viewer</option>
                                        </x-ui.select>
                                    </div>
                                </div>
                                <x-ui.button variant="danger" size="sm" @click="removeRecipient(index)">
                                    Remove
                                </x-ui.button>
                            </div>
                        </template>

                        <div x-show="recipients.length === 0" class="text-center py-6 text-sm text-text-secondary">
                            No recipients added yet. Click "Add Recipient" or upload a CSV file.
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Step 3: Review & Send -->
        <div x-show="step === 3">
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Review Bulk Send</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Batch Name</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="bulkData.batch_name"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Template</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="templates.find(t => t.id === bulkData.template_id)?.name || 'N/A'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Total Recipients</dt>
                        <dd class="mt-1 text-sm text-text-primary" x-text="recipients.length"></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-text-secondary">Send Immediately</dt>
                        <dd class="mt-1">
                            <x-ui.badge x-bind:variant="bulkData.send_immediately ? 'success' : 'secondary'" x-text="bulkData.send_immediately ? 'Yes' : 'No'"></x-ui.badge>
                        </dd>
                    </div>
                </dl>
            </x-ui.card>

            <!-- Recipients Preview -->
            <x-ui.card>
                <h3 class="text-lg font-semibold text-text-primary mb-4">Recipients Preview</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-primary">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-primary">
                            <template x-for="(recipient, index) in recipients.slice(0, 10)" :key="index">
                                <tr>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="index + 1"></td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="recipient.name"></td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="recipient.email"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <x-ui.badge variant="secondary" x-text="recipient.role?.toUpperCase()"></x-ui.badge>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p x-show="recipients.length > 10" class="mt-4 text-sm text-text-secondary text-center">
                        Showing 10 of <span x-text="recipients.length"></span> recipients
                    </p>
                </div>
            </x-ui.card>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex items-center justify-between">
            <div>
                <x-ui.button variant="secondary" onclick="window.location.href='/bulk'" x-show="step === 1">
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
                <x-ui.button variant="primary" @click="createBulkSend()" x-show="step === 3" :disabled="loading">
                    <span x-show="!loading">Create Bulk Send</span>
                    <span x-show="loading">Creating...</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
