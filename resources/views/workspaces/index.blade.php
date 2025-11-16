<x-layout.app title="Workspaces">
    <div x-data="{
        workspaces: [],
        loading: true,
        async init() {
            await this.loadWorkspaces();
        },
        async loadWorkspaces() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/workspaces`);
                this.workspaces = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load workspaces');
            } finally {
                this.loading = false;
            }
        },
        async deleteWorkspace(id) {
            if (!confirm('Delete this workspace?')) return;
            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/workspaces/${id}`);
                $store.toast.success('Workspace deleted');
                await this.loadWorkspaces();
            } catch (error) {
                $store.toast.error('Failed to delete workspace');
            }
        }
    }" x-init="init()">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Workspaces</h1>
                <p class="mt-1 text-sm text-text-secondary">Manage document workspaces and files</p>
            </div>
            <x-ui.button variant="primary" onclick="window.location.href='/workspaces/create'">New Workspace</x-ui.button>
        </div>

        <div x-show="loading"><x-ui.skeleton type="card" class="h-32" /></div>

        <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="workspace in workspaces" :key="workspace.id">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary" x-text="workspace.name"></h3>
                    <p class="mt-2 text-sm text-text-secondary" x-text="workspace.description || 'No description'"></p>
                    <div class="mt-3 text-sm">
                        <span class="text-text-secondary">Files: </span>
                        <span class="font-medium text-text-primary" x-text="workspace.file_count || 0"></span>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/workspaces/${workspace.id}'`">Open</x-ui.button>
                        <x-ui.button variant="danger" size="sm" @click="deleteWorkspace(workspace.id)">Delete</x-ui.button>
                    </div>
                </x-ui.card>
            </template>
            <div x-show="workspaces.length === 0" class="col-span-full text-center py-12 text-text-secondary">No workspaces yet</div>
        </div>
    </div>
</x-layout.app>
