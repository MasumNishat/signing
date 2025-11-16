<x-layout.app title="Workflow Builder">
    <div x-data="{
        loading: false,
        workflow: {
            name: '',
            description: '',
            type: 'sequential',
            scheduled_send_at: null,
            steps: []
        },
        recipients: [],
        availableActions: [
            { id: 'sign', name: 'Sign', icon: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z' },
            { id: 'approve', name: 'Approve', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
            { id: 'view', name: 'View', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' },
            { id: 'certify', name: 'Certify', icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' }
        ],
        selectedStep: null,
        errors: {},
        async init() {
            await this.loadRecipients();
        },
        async loadRecipients() {
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/recipients`);
                this.recipients = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load recipients');
            }
        },
        addStep() {
            const newStep = {
                id: Date.now(),
                order: this.workflow.steps.length + 1,
                action: 'sign',
                recipient_id: null,
                parallel_with_previous: false,
                delay_days: 0,
                conditions: []
            };
            this.workflow.steps.push(newStep);
            this.selectedStep = newStep;
        },
        removeStep(stepId) {
            const index = this.workflow.steps.findIndex(s => s.id === stepId);
            if (index !== -1) {
                this.workflow.steps.splice(index, 1);
                // Reorder steps
                this.workflow.steps.forEach((step, idx) => {
                    step.order = idx + 1;
                });
            }
            if (this.selectedStep?.id === stepId) {
                this.selectedStep = null;
            }
        },
        moveStepUp(stepId) {
            const index = this.workflow.steps.findIndex(s => s.id === stepId);
            if (index > 0) {
                const temp = this.workflow.steps[index];
                this.workflow.steps[index] = this.workflow.steps[index - 1];
                this.workflow.steps[index - 1] = temp;
                // Reorder
                this.workflow.steps.forEach((step, idx) => {
                    step.order = idx + 1;
                });
            }
        },
        moveStepDown(stepId) {
            const index = this.workflow.steps.findIndex(s => s.id === stepId);
            if (index < this.workflow.steps.length - 1) {
                const temp = this.workflow.steps[index];
                this.workflow.steps[index] = this.workflow.steps[index + 1];
                this.workflow.steps[index + 1] = temp;
                // Reorder
                this.workflow.steps.forEach((step, idx) => {
                    step.order = idx + 1;
                });
            }
        },
        selectStep(step) {
            this.selectedStep = step;
        },
        getActionIcon(actionId) {
            return this.availableActions.find(a => a.id === actionId)?.icon || '';
        },
        getRecipientName(recipientId) {
            return this.recipients.find(r => r.id === recipientId)?.name || 'Unknown';
        },
        async saveWorkflow() {
            this.loading = true;
            this.errors = {};

            if (this.workflow.steps.length === 0) {
                $store.toast.error('Please add at least one workflow step');
                this.loading = false;
                return;
            }

            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/workflows`,
                    this.workflow
                );

                $store.toast.success('Workflow created successfully');
                window.location.href = '/envelopes/create';
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to create workflow');
            } finally {
                this.loading = false;
            }
        }
    }" x-init="init()">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Workflow Builder</h1>
            <p class="mt-1 text-sm text-text-secondary">Design custom routing workflows for your documents</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Workflow Configuration -->
            <div class="lg:col-span-1">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Workflow Settings</h3>

                    <div class="space-y-4">
                        <!-- Workflow Name -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">
                                Workflow Name <span class="text-red-500">*</span>
                            </label>
                            <x-ui.input
                                type="text"
                                x-model="workflow.name"
                                placeholder="Sequential Approval"
                                required
                            />
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Description</label>
                            <textarea
                                x-model="workflow.description"
                                rows="3"
                                placeholder="Optional description"
                                class="w-full px-3 py-2 border border-border-primary rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 bg-bg-primary text-text-primary"
                            ></textarea>
                        </div>

                        <!-- Routing Type -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Routing Type</label>
                            <x-ui.select x-model="workflow.type">
                                <option value="sequential">Sequential (one at a time)</option>
                                <option value="parallel">Parallel (all at once)</option>
                                <option value="mixed">Mixed (custom order)</option>
                            </x-ui.select>
                        </div>

                        <!-- Scheduled Send -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Schedule Send (Optional)</label>
                            <x-ui.input
                                type="datetime-local"
                                x-model="workflow.scheduled_send_at"
                            />
                            <p class="mt-1 text-xs text-text-secondary">Leave blank to send immediately</p>
                        </div>

                        <!-- Add Step Button -->
                        <div class="pt-4 border-t border-border-primary">
                            <x-ui.button variant="primary" @click="addStep()" class="w-full">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Step
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>

                <!-- Actions -->
                <x-ui.card class="mt-6">
                    <div class="space-y-3">
                        <x-ui.button variant="primary" @click="saveWorkflow()" :disabled="loading" class="w-full">
                            <span x-show="!loading">Save & Create Envelope</span>
                            <span x-show="loading">Saving...</span>
                        </x-ui.button>
                        <x-ui.button variant="secondary" onclick="window.location.href='/envelopes'" class="w-full">
                            Cancel
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>

            <!-- Middle: Visual Workflow -->
            <div class="lg:col-span-1">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Workflow Steps</h3>

                    <div x-show="workflow.steps.length === 0" class="text-center py-12 text-text-secondary">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <p class="mt-2">No steps yet. Add a step to get started.</p>
                    </div>

                    <div x-show="workflow.steps.length > 0" class="space-y-3">
                        <template x-for="(step, index) in workflow.steps" :key="step.id">
                            <div
                                @click="selectStep(step)"
                                class="p-4 border-2 rounded-md cursor-pointer transition-colors"
                                :class="selectedStep?.id === step.id ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'border-border-primary hover:border-primary-300'"
                            >
                                <!-- Step Header -->
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-white text-sm font-medium" x-text="step.order"></div>
                                        <div>
                                            <p class="text-sm font-medium text-text-primary capitalize" x-text="step.action"></p>
                                            <p class="text-xs text-text-secondary" x-text="step.recipient_id ? getRecipientName(step.recipient_id) : 'No recipient'"></p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-1">
                                        <button @click.stop="moveStepUp(step.id)" :disabled="index === 0" class="p-1 text-text-secondary hover:text-text-primary disabled:opacity-30">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </button>
                                        <button @click.stop="moveStepDown(step.id)" :disabled="index === workflow.steps.length - 1" class="p-1 text-text-secondary hover:text-text-primary disabled:opacity-30">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <button @click.stop="removeStep(step.id)" class="p-1 text-red-600 hover:text-red-700">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step Info -->
                                <div class="flex items-center space-x-2 text-xs">
                                    <span x-show="step.parallel_with_previous" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">Parallel</span>
                                    <span x-show="step.delay_days > 0" class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded" x-text="`Delay: ${step.delay_days}d`"></span>
                                </div>

                                <!-- Arrow (if not last) -->
                                <div x-show="index < workflow.steps.length - 1 && !workflow.steps[index + 1]?.parallel_with_previous" class="flex justify-center my-2">
                                    <svg class="w-6 h-6 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.card>
            </div>

            <!-- Right: Step Configuration -->
            <div class="lg:col-span-1">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Step Configuration</h3>

                    <div x-show="!selectedStep" class="text-center py-12 text-text-secondary">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="mt-2">Select a step to configure</p>
                    </div>

                    <div x-show="selectedStep" class="space-y-4">
                        <!-- Action Type -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Action</label>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="action in availableActions" :key="action.id">
                                    <button
                                        @click="selectedStep.action = action.id"
                                        type="button"
                                        class="p-3 border-2 rounded-md text-left transition-colors"
                                        :class="selectedStep?.action === action.id ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'border-border-primary hover:border-primary-300'"
                                    >
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x-bind:d="action.icon" />
                                            </svg>
                                            <span class="text-sm font-medium text-text-primary" x-text="action.name"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Recipient -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Recipient</label>
                            <x-ui.select x-model="selectedStep.recipient_id">
                                <option value="">Select recipient...</option>
                                <template x-for="recipient in recipients" :key="recipient.id">
                                    <option x-bind:value="recipient.id" x-text="recipient.name"></option>
                                </template>
                            </x-ui.select>
                        </div>

                        <!-- Parallel Execution -->
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                x-model="selectedStep.parallel_with_previous"
                                :disabled="selectedStep.order === 1"
                                id="parallel"
                                class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            >
                            <label for="parallel" class="ml-2 text-sm text-text-primary">
                                Run in parallel with previous step
                            </label>
                        </div>

                        <!-- Delay -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Delay (days)</label>
                            <x-ui.input
                                type="number"
                                x-model="selectedStep.delay_days"
                                min="0"
                                max="365"
                                placeholder="0"
                            />
                            <p class="mt-1 text-xs text-text-secondary">Wait before routing to this step</p>
                        </div>
                    </div>
                </x-ui.card>

                <!-- Workflow Info -->
                <x-ui.card class="mt-6">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Routing Guide</h3>
                    <ul class="space-y-2 text-sm text-text-secondary">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong>Sequential:</strong> Recipients receive in order</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong>Parallel:</strong> All recipients receive simultaneously</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong>Mixed:</strong> Combine sequential and parallel steps</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Use delays to control timing between steps</span>
                        </li>
                    </ul>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layout.app>
