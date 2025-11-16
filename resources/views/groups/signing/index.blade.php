<x-layout.app title="Signing Groups">
    <div x-data="{
        groups: [],
        loading: true,
        async init() {
            await this.loadGroups();
        },
        async loadGroups() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/signing_groups`);
                this.groups = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load signing groups');
            } finally {
                this.loading = false;
            }
        },
        async deleteGroup(id) {
            if (!confirm('Delete this signing group?')) return;
            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/signing_groups/${id}`);
                $store.toast.success('Group deleted');
                await this.loadGroups();
            } catch (error) {
                $store.toast.error('Failed to delete group');
            }
        }
    }" x-init="init()">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Signing Groups</h1>
                <p class="mt-1 text-sm text-text-secondary">Manage groups of signers for routing</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/groups/signing/create'">
                New Signing Group
            </x-ui.button>
        </div>

        <div x-show="loading"><x-ui.skeleton type="card" class="h-32" /></div>

        <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="group in groups" :key="group.id">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary" x-text="group.name"></h3>
                    <p class="mt-2 text-sm text-text-secondary">
                        <span x-text="group.member_count || 0"></span> members
                    </p>
                    <div class="mt-4 flex space-x-2">
                        <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/groups/signing/${group.id}'`">View</x-ui.button>
                        <x-ui.button variant="danger" size="sm" @click="deleteGroup(group.id)">Delete</x-ui.button>
                    </div>
                </x-ui.card>
            </template>
        </div>
    </div>
</x-layout.app>
