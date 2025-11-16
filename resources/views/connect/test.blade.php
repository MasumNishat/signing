<x-layout.app title="Test Webhook">
    <div x-data="{
        loading: false,
        webhook: null,
        testResult: null,
        testPayload: {
            event_type: 'envelope-sent',
            test_mode: true
        },
        availableEvents: [
            'envelope-sent',
            'envelope-delivered',
            'envelope-completed',
            'envelope-declined',
            'envelope-voided',
            'recipient-sent',
            'recipient-delivered',
            'recipient-completed'
        ],
        async init() {
            await this.loadWebhook();
        },
        async loadWebhook() {
            try {
                const webhookId = '{{ $webhookId }}';
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/connect/${webhookId}`);
                this.webhook = response.data;
            } catch (error) {
                $store.toast.error('Failed to load webhook');
            }
        },
        async sendTest() {
            this.loading = true;
            this.testResult = null;

            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/connect/${this.webhook.id}/test`,
                    this.testPayload
                );

                this.testResult = {
                    success: true,
                    status_code: response.data.status_code || 200,
                    response_time: response.data.response_time || 0,
                    response_body: response.data.response_body || 'OK',
                    message: 'Test webhook delivered successfully'
                };

                $store.toast.success('Test webhook sent successfully');
            } catch (error) {
                this.testResult = {
                    success: false,
                    status_code: error.response?.status || 0,
                    response_time: 0,
                    error_message: error.response?.data?.message || error.message,
                    message: 'Test webhook delivery failed'
                };

                $store.toast.error('Test webhook failed');
            } finally {
                this.loading = false;
            }
        },
        formatJson(obj) {
            return JSON.stringify(obj, null, 2);
        }
    }" x-init="init()">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center space-x-2">
                <a href="/connect" class="text-text-secondary hover:text-text-primary">Webhooks</a>
                <span class="text-text-secondary">/</span>
                <a :href="`/connect/${webhook?.id}`" class="text-text-secondary hover:text-text-primary" x-text="webhook?.name"></a>
                <span class="text-text-secondary">/</span>
                <span class="text-text-primary">Test</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold text-text-primary">Test Webhook</h1>
            <p class="mt-1 text-sm text-text-secondary">Send a test webhook to verify your endpoint</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Test Configuration -->
            <div>
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Test Configuration</h3>

                    <div class="space-y-4">
                        <!-- Webhook URL -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Webhook URL</label>
                            <div class="p-3 bg-bg-secondary border border-border-primary rounded-md">
                                <p class="text-sm font-mono text-text-primary" x-text="webhook?.url"></p>
                            </div>
                        </div>

                        <!-- Event Type -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Event Type</label>
                            <x-ui.select x-model="testPayload.event_type">
                                <template x-for="event in availableEvents" :key="event">
                                    <option x-bind:value="event" x-text="event"></option>
                                </template>
                            </x-ui.select>
                            <p class="mt-1 text-xs text-text-secondary">Select which event to simulate</p>
                        </div>

                        <!-- Test Mode -->
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                x-model="testPayload.test_mode"
                                id="test-mode"
                                class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            >
                            <label for="test-mode" class="ml-2 text-sm text-text-primary">
                                Test Mode (includes test flag in payload)
                            </label>
                        </div>

                        <!-- Send Button -->
                        <div class="pt-4">
                            <x-ui.button variant="primary" @click="sendTest()" :disabled="loading" class="w-full">
                                <span x-show="!loading">Send Test Webhook</span>
                                <span x-show="loading">Sending...</span>
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>

                <!-- Sample Payload -->
                <x-ui.card class="mt-6">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Sample Payload</h3>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 border border-border-primary rounded-md overflow-x-auto">
                        <pre class="text-xs text-text-primary" x-text="formatJson({
                            event: testPayload.event_type,
                            timestamp: new Date().toISOString(),
                            account_id: '$store.auth.user.account_id',
                            envelope: {
                                id: 'env_test123',
                                subject: 'Test Envelope',
                                status: 'sent',
                                sent_at: new Date().toISOString()
                            },
                            test_mode: testPayload.test_mode
                        })"></pre>
                    </div>
                </x-ui.card>
            </div>

            <!-- Test Result -->
            <div>
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Test Result</h3>

                    <div x-show="!testResult" class="text-center py-12 text-text-secondary">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <p class="mt-2">No test results yet. Send a test webhook to see the result here.</p>
                    </div>

                    <div x-show="testResult" class="space-y-4">
                        <!-- Success/Failure Alert -->
                        <div x-show="testResult?.success" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <p class="text-sm font-medium text-green-800 dark:text-green-200" x-text="testResult?.message"></p>
                            </div>
                        </div>

                        <div x-show="testResult && !testResult?.success" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <p class="text-sm font-medium text-red-800 dark:text-red-200" x-text="testResult?.message"></p>
                            </div>
                        </div>

                        <!-- Result Details -->
                        <div>
                            <h4 class="text-sm font-medium text-text-primary mb-2">Response Details</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-text-secondary">Status Code</dt>
                                    <dd class="text-sm font-mono" :class="testResult?.success ? 'text-green-600' : 'text-red-600'" x-text="testResult?.status_code"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-text-secondary">Response Time</dt>
                                    <dd class="text-sm text-text-primary" x-text="`${testResult?.response_time}ms`"></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Response Body -->
                        <div>
                            <h4 class="text-sm font-medium text-text-primary mb-2">Response Body</h4>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900 border border-border-primary rounded-md overflow-x-auto">
                                <pre class="text-xs text-text-primary" x-text="testResult?.response_body || testResult?.error_message"></pre>
                            </div>
                        </div>

                        <!-- Try Again -->
                        <div class="pt-4">
                            <x-ui.button variant="secondary" @click="testResult = null" class="w-full">
                                Clear Result
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>

                <!-- Testing Tips -->
                <x-ui.card class="mt-6">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Testing Tips</h3>
                    <ul class="space-y-2 text-sm text-text-secondary">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Your endpoint should return a 200-299 status code for successful delivery</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Test webhooks include a test_mode flag in the payload</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Failed deliveries will be automatically retried with exponential backoff</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-primary-600 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Check the logs tab to see all webhook delivery attempts</span>
                        </li>
                    </ul>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layout.app>
