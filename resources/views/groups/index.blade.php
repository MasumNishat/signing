<x-layout.app title="Groups Management">
    <div x-data="{
        activeTab: 'signing',
        signingGroups: [],
        userGroups: [],
        loading: true,
        async init() {
            await this.loadGroups();
        },
        async loadGroups() {
            this.loading = true;
            try {
                const [signingResponse, userResponse] = await Promise.all([
                    $api.get(`/accounts/${$store.auth.user.account_id}/signing_groups`),
                    $api.get(`/accounts/${$store.auth.user.account_id}/groups`)
                ]);
                this.signingGroups = signingResponse.data.data || signingResponse.data;
                this.userGroups = userResponse.data.data || userResponse.data;
            } catch (error) {
                $store.toast.error('Failed to load groups');
            } finally {
                this.loading = false;
            }
        },
        async deleteSigningGroup(id) {
            if (!confirm('Delete this signing group?')) return;
            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/signing_groups/${id}`);
                $store.toast.success('Signing group deleted');
                await this.loadGroups();
            } catch (error) {
                $store.toast.error('Failed to delete group');
            }
        },
        async deleteUserGroup(id) {
            if (!confirm('Delete this user group?')) return;
            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/groups/${id}`);
                $store.toast.success('User group deleted');
                await this.loadGroups();
            } catch (error) {
                $store.toast.error('Failed to delete group');
            }
        }
    }" x-init="init()">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Groups Management</h1>
            <p class="mt-1 text-sm text-text-secondary">Manage signing groups and user groups</p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-border-primary mb-6">
            <nav class="flex space-x-8">
                <button @click="activeTab = 'signing'" :class="activeTab === 'signing' ? 'border-primary-600 text-primary-600' : 'border-transparent text-text-secondary'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Signing Groups
                </button>
                <button @click="activeTab = 'user'" :class="activeTab === 'user' ? 'border-primary-600 text-primary-600' : 'border-transparent text-text-secondary'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    User Groups
                </button>
            </nav>
        </div>

        <!-- Signing Groups Tab -->
        <div x-show="activeTab === 'signing'">
            <div class="mb-4 flex justify-end">
                <x-ui.button variant="primary" onclick="window.location.href='/groups/signing/create'">New Signing Group</x-ui.button>
            </div>
            <div x-show="loading"><x-ui.skeleton type="card" class="h-32" /></div>
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <template x-for="group in signingGroups" :key="group.id">
                    <x-ui.card>
                        <h3 class="text-lg font-semibold" x-text="group.name"></h3>
                        <p class="text-sm text-text-secondary mt-2"><span x-text="group.member_count || 0"></span> members</p>
                        <div class="mt-4 flex space-x-2">
                            <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/groups/signing/${group.id}'`">Manage</x-ui.button>
                            <x-ui.button variant="danger" size="sm" @click="deleteSigningGroup(group.id)">Delete</x-ui.button>
                        </div>
                    </x-ui.card>
                </template>
            </div>
        </div>

        <!-- User Groups Tab -->
        <div x-show="activeTab === 'user'">
            <div class="mb-4 flex justify-end">
                <x-ui.button variant="primary" onclick="window.location.href='/groups/user/create'">New User Group</x-ui.button>
            </div>
            <div x-show="loading"><x-ui.skeleton type="card" class="h-32" /></div>
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <template x-for="group in userGroups" :key="group.id">
                    <x-ui.card>
                        <h3 class="text-lg font-semibold" x-text="group.name"></h3>
                        <p class="text-sm text-text-secondary mt-2"><span x-text="group.user_count || 0"></span> users</p>
                        <div class="mt-4 flex space-x-2">
                            <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/groups/user/${group.id}'`">Manage</x-ui.button>
                            <x-ui.button variant="danger" size="sm" @click="deleteUserGroup(group.id)">Delete</x-ui.button>
                        </div>
                    </x-ui.card>
                </template>
            </div>
        </div>
    </div>
</x-layout.app>
