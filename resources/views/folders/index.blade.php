<x-layout.app title="Folders">
    <div x-data="{
        folders: [],
        envelopes: [],
        selectedFolder: null,
        loading: true,
        async init() {
            await this.loadFolders();
        },
        async loadFolders() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/folders`);
                this.folders = response.data.data || response.data;
                if (this.folders.length > 0) {
                    await this.selectFolder(this.folders[0].id);
                }
            } catch (error) {
                $store.toast.error('Failed to load folders');
            } finally {
                this.loading = false;
            }
        },
        async selectFolder(folderId) {
            this.selectedFolder = folderId;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/folders/${folderId}/envelopes`);
                this.envelopes = response.data.data || response.data;
            } catch (error) {
                $store.toast.error('Failed to load envelopes');
            }
        },
        async moveToFolder(envelopeId, folderId) {
            try {
                await $api.put(`/accounts/${$store.auth.user.account_id}/folders/${folderId}/envelopes`, {
                    envelope_ids: [envelopeId]
                });
                $store.toast.success('Envelope moved');
                await this.selectFolder(this.selectedFolder);
            } catch (error) {
                $store.toast.error('Failed to move envelope');
            }
        }
    }" x-init="init()">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Folders</h1>
            <p class="mt-1 text-sm text-text-secondary">Organize your envelopes with folders</p>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <!-- Folder Tree -->
            <div class="col-span-3">
                <x-ui.card>
                    <h3 class="font-semibold text-text-primary mb-4">Folders</h3>
                    <div class="space-y-2">
                        <template x-for="folder in folders" :key="folder.id">
                            <div @click="selectFolder(folder.id)" :class="selectedFolder === folder.id ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600' : 'text-text-primary hover:bg-bg-secondary'" class="px-3 py-2 rounded cursor-pointer">
                                <div class="flex items-center justify-between">
                                    <span x-text="folder.name"></span>
                                    <span class="text-xs text-text-secondary" x-text="folder.item_count || 0"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.card>
            </div>

            <!-- Envelope List -->
            <div class="col-span-9">
                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-text-primary">Envelopes</h3>
                    </div>
                    <div class="space-y-2">
                        <template x-for="envelope in envelopes" :key="envelope.id">
                            <div class="flex items-center justify-between p-3 border border-border-primary rounded hover:bg-bg-secondary">
                                <div>
                                    <p class="font-medium text-text-primary" x-text="envelope.email_subject"></p>
                                    <p class="text-sm text-text-secondary" x-text="new Date(envelope.created_at).toLocaleDateString()"></p>
                                </div>
                                <x-ui.button variant="secondary" size="sm" x-bind:onclick="`window.location.href='/envelopes/${envelope.id}'`">View</x-ui.button>
                            </div>
                        </template>
                        <div x-show="envelopes.length === 0" class="text-center py-8 text-text-secondary">No envelopes in this folder</div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layout.app>
