<x-layout.app title="Billing">
    <div x-data="{
        loading: true,
        plan: {},
        invoices: [],
        usage: {},
        async init() {
            await this.loadBilling();
        },
        async loadBilling() {
            this.loading = true;
            try {
                const [planResp, invoicesResp, usageResp] = await Promise.all([
                    $api.get(`/accounts/${$store.auth.user.account_id}/billing/plan`),
                    $api.get(`/accounts/${$store.auth.user.account_id}/billing/invoices`),
                    $api.get(`/accounts/${$store.auth.user.account_id}/envelopes/statistics`)
                ]);
                this.plan = planResp.data;
                this.invoices = invoicesResp.data.data || invoicesResp.data;
                this.usage = usageResp.data;
                this.loading = false;
            } catch (error) {
                $store.toast.error('Failed to load billing information');
                this.loading = false;
            }
        }
    }"
    x-init="init()">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Billing & Usage</h1>
            <p class="mt-1 text-sm text-text-secondary">Manage your subscription and view invoices</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Current Plan -->
            <div class="lg:col-span-2 space-y-6">
                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-text-primary">Current Plan</h3>
                        <x-ui.button variant="primary" onclick="window.location.href='/billing/plans'">
                            Change Plan
                        </x-ui.button>
                    </div>
                    <div x-show="loading">
                        <x-ui.skeleton type="text" class="h-20 w-full" />
                    </div>
                    <div x-show="!loading" class="space-y-4">
                        <div>
                            <p class="text-sm text-text-secondary">Plan Name</p>
                            <p class="text-2xl font-bold text-text-primary" x-text="plan.plan_name || 'Free'"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-text-secondary">Monthly Price</p>
                                <p class="text-lg font-semibold text-text-primary" x-text="'$' + (plan.monthly_price || 0)"></p>
                            </div>
                            <div>
                                <p class="text-sm text-text-secondary">Envelopes Included</p>
                                <p class="text-lg font-semibold text-text-primary" x-text="plan.envelopes_included || 'Unlimited'"></p>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-text-secondary mb-2">Next Billing Date</p>
                            <p class="text-sm font-medium text-text-primary" x-text="plan.next_billing_date || 'N/A'"></p>
                        </div>
                    </div>
                </x-ui.card>

                <!-- Recent Invoices -->
                <x-ui.card :padding="false">
                    <div class="px-6 py-4 border-b border-card-border flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-text-primary">Recent Invoices</h3>
                        <a href="/billing/invoices" class="text-sm font-medium text-primary-600 hover:text-primary-500">View all</a>
                    </div>
                    <div x-show="loading" class="p-6 space-y-3">
                        <x-ui.skeleton type="text" class="h-12 w-full" />
                        <x-ui.skeleton type="text" class="h-12 w-full" />
                    </div>
                    <div x-show="!loading">
                        <table class="min-w-full divide-y divide-border-primary">
                            <thead class="bg-bg-secondary">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Invoice #</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Amount</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-bg-primary divide-y divide-border-primary">
                                <template x-for="invoice in invoices.slice(0, 5)" :key="invoice.id">
                                    <tr class="hover:bg-bg-hover">
                                        <td class="px-6 py-4 text-sm font-medium text-text-primary" x-text="invoice.invoice_number"></td>
                                        <td class="px-6 py-4 text-sm text-text-secondary" x-text="new Date(invoice.invoice_date).toLocaleDateString()"></td>
                                        <td class="px-6 py-4 text-sm font-medium text-text-primary" x-text="'$' + invoice.total_amount"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="invoice.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'"
                                                  x-text="invoice.status"></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                                                Download
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="invoices.length === 0" class="px-6 py-8 text-center">
                            <p class="text-sm text-text-secondary">No invoices yet</p>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <!-- Usage Summary -->
            <div class="lg:col-span-1 space-y-6">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Current Usage</h3>
                    <div x-show="loading">
                        <x-ui.skeleton type="text" class="h-32 w-full" />
                    </div>
                    <div x-show="!loading" class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm text-text-secondary">Envelopes Sent</p>
                                <p class="text-sm font-semibold text-text-primary" x-text="usage.sent || 0"></p>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-primary-500 h-2 rounded-full" :style="`width: ${(usage.sent / (plan.envelopes_included || 100)) * 100}%`"></div>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-card-border">
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-text-secondary">Completed</p>
                                <p class="text-lg font-semibold text-green-600" x-text="usage.completed || 0"></p>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-text-secondary">Voided</p>
                                <p class="text-lg font-semibold text-red-600" x-text="usage.voided || 0"></p>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Payment Method</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-text-primary">•••• •••• •••• 4242</p>
                                    <p class="text-xs text-text-secondary">Expires 12/25</p>
                                </div>
                            </div>
                        </div>
                        <x-ui.button variant="secondary" size="sm" class="w-full">
                            Update Payment Method
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layout.app>
