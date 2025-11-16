<x-layout.app title="Webhooks">
    <div x-data="{
        webhooks: [],
        loading: true,
        async init() {
            await this.loadWebhooks();
        },
        async loadWebhooks() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/connect`);
                this.webhooks = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load webhooks');
            } finally {
                this.loading = false;
            }
        },
        async deleteWebhook(id) {
            if (!confirm('Delete this webhook configuration?')) return;
            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/connect/${id}`);
                $store.toast.success('Webhook deleted');
                await this.loadWebhooks();
            } catch (error) {
                $store.toast.error('Failed to delete webhook');
            }
        },
        async toggleWebhook(webhook) {
            try {
                const newStatus = webhook.status === 'active' ? 'inactive' : 'active';
                await $api.put(`/accounts/${$store.auth.user.account_id}/connect/${webhook.id}`, {
                    status: newStatus
                });
                webhook.status = newStatus;
                $store.toast.success(`Webhook ${newStatus === 'active' ? 'activated' : 'deactivated'}`);
            } catch (error) {
                $store.toast.error('Failed to update webhook');
            }
        },
        getStatusColor(status) {
            return status === 'active' ? 'success' : 'secondary';
        }
    }" x-init="init()">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Webhooks</h1>
                <p class="mt-1 text-sm text-text-secondary">Configure webhooks to receive real-time event notifications</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/connect/create'">New Webhook</x-ui.button>
        </div>

        <div x-show="loading"><x-ui.skeleton type="card" class="h-32" /></div>

        <div x-show="!loading" class="space-y-4">
            <template x-for="webhook in webhooks" :key="webhook.id">
                <x-ui.card>
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-semibold text-text-primary" x-text="webhook.name"></h3>
                                <x-ui.badge x-bind:variant="getStatusColor(webhook.status)" x-text="webhook.status?.toUpperCase()"></x-ui.badge>
                            </div>
                            <p class="mt-1 text-sm text-text-secondary font-mono" x-text="webhook.url"></p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="event in webhook.events" :key="event">
                                    <span class="px-2 py-1 text-xs bg-bg-secondary border border-border-primary rounded-md text-text-primary" x-text="event"></span>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/connect/${webhook.id}'`">Details</x-ui.button>
                            <x-ui.button variant="secondary" size="sm" @click="toggleWebhook(webhook)">
                                <span x-text="webhook.status === 'active' ? 'Deactivate' : 'Activate'"></span>
                            </x-ui.button>
                            <x-ui.button variant="danger" size="sm" @click="deleteWebhook(webhook.id)">Delete</x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            </template>
            <div x-show="webhooks.length === 0" class="text-center py-12 text-text-secondary">No webhooks configured</div>
        </div>
    </div>
</x-layout.app>
