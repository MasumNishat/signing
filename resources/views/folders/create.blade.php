<x-layout.app title="Create Folder">
    <div x-data="{
        loading: false,
        folderData: {
            name: '',
            parent_folder_id: null,
            type: 'custom'
        },
        parentFolders: [],
        errors: {},
        async init() {
            await this.loadParentFolders();
        },
        async loadParentFolders() {
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/folders`);
                this.parentFolders = (response.data.data || response.data).filter(f => f.type === 'custom');
            } catch (error) {
                $store.toast.error('Failed to load folders');
            }
        },
        async createFolder() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/folders`,
                    this.folderData
                );

                $store.toast.success('Folder created successfully');
                window.location.href = '/folders';
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to create folder');
            } finally {
                this.loading = false;
            }
        }
    }" x-init="init()">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Create Folder</h1>
            <p class="mt-1 text-sm text-text-secondary">Organize your envelopes into folders</p>
        </div>

        <!-- Form -->
        <x-ui.card class="max-w-2xl">
            <form @submit.prevent="createFolder()">
                <div class="space-y-6">
                    <!-- Folder Name -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">
                            Folder Name <span class="text-red-500">*</span>
                        </label>
                        <x-ui.input
                            type="text"
                            x-model="folderData.name"
                            placeholder="Enter folder name"
                            required
                        />
                        <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name?.[0]"></p>
                    </div>

                    <!-- Parent Folder -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Parent Folder (Optional)</label>
                        <x-ui.select x-model="folderData.parent_folder_id">
                            <option value="">None (Top Level)</option>
                            <template x-for="folder in parentFolders" :key="folder.id">
                                <option x-bind:value="folder.id" x-text="folder.name"></option>
                            </template>
                        </x-ui.select>
                        <p class="mt-1 text-xs text-text-secondary">Create a subfolder under an existing folder</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3">
                        <x-ui.button variant="secondary" type="button" onclick="window.location.href='/folders'">
                            Cancel
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit" :disabled="loading">
                            <span x-show="!loading">Create Folder</span>
                            <span x-show="loading">Creating...</span>
                        </x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layout.app>
