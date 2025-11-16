<x-layout.app :title="'Workspace: ' . ($workspace->name ?? 'Details')">
    <div x-data="{
        loading: true,
        workspace: null,
        files: [],
        uploadProgress: 0,
        async init() {
            await this.loadWorkspace();
        },
        async loadWorkspace() {
            this.loading = true;
            try {
                const workspaceId = '{{ $workspaceId }}';
                const accountId = $store.auth.user.account_id;

                const response = await $api.get(`/accounts/${accountId}/workspaces/${workspaceId}`);
                this.workspace = response.data;

                // Load workspace files
                const filesResponse = await $api.get(`/accounts/${accountId}/workspaces/${workspaceId}/files`);
                this.files = filesResponse.data.data || filesResponse.data;
            } catch (error) {
                $store.toast.error('Failed to load workspace');
            } finally {
                this.loading = false;
            }
        },
        async uploadFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.uploadProgress = 0;
            const formData = new FormData();
            formData.append('file', file);

            try {
                await $api.post(
                    `/accounts/${$store.auth.user.account_id}/workspaces/${this.workspace.id}/files`,
                    formData,
                    {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (progressEvent) => {
                            this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        }
                    }
                );

                $store.toast.success('File uploaded successfully');
                await this.loadWorkspace();
            } catch (error) {
                $store.toast.error('Failed to upload file');
            } finally {
                this.uploadProgress = 0;
                event.target.value = '';
            }
        },
        async deleteFile(fileId) {
            if (!confirm('Delete this file?')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/workspaces/${this.workspace.id}/files/${fileId}`);
                $store.toast.success('File deleted');
                await this.loadWorkspace();
            } catch (error) {
                $store.toast.error('Failed to delete file');
            }
        },
        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        },
        formatDate(date) {
            return date ? new Date(date).toLocaleString() : 'N/A';
        }
    }" x-init="init()">
        <!-- Loading State -->
        <div x-show="loading" class="space-y-6">
            <x-ui.skeleton type="card" class="h-32" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Content -->
        <div x-show="!loading && workspace" class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-text-primary" x-text="workspace?.name"></h1>
                    <p class="mt-1 text-sm text-text-secondary" x-text="workspace?.description || 'No description'"></p>
                    <p class="mt-1 text-xs text-text-secondary">
                        Created on <span x-text="formatDate(workspace?.created_at)"></span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <label class="cursor-pointer">
                        <x-ui.button variant="primary" tag="span">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Upload File
                        </x-ui.button>
                        <input type="file" class="hidden" @change="uploadFile($event)" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    </label>
                </div>
            </div>

            <!-- Upload Progress -->
            <div x-show="uploadProgress > 0" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-text-primary">Uploading...</span>
                    <span class="text-sm text-text-primary" x-text="`${uploadProgress}%`"></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all" :style="`width: ${uploadProgress}%`"></div>
                </div>
            </div>

            <!-- Files List -->
            <x-ui.card>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-text-primary">Files</h2>
                    <span class="text-sm text-text-secondary"><span x-text="files.length"></span> files</span>
                </div>

                <div x-show="files.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-primary">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Size</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Uploaded</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-primary">
                            <template x-for="file in files" :key="file.id">
                                <tr class="hover:bg-bg-secondary">
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-text-primary" x-text="file.name"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="formatFileSize(file.size)"></td>
                                    <td class="px-4 py-3 text-sm text-text-primary" x-text="formatDate(file.created_at)"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center space-x-2">
                                            <a :href="`/api/v2.1/accounts/${$store.auth.user.account_id}/workspaces/${workspace.id}/files/${file.id}/download`" class="text-primary-600 hover:underline">Download</a>
                                            <button @click="deleteFile(file.id)" class="text-red-600 hover:underline">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div x-show="files.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-text-primary">No files</h3>
                    <p class="mt-1 text-sm text-text-secondary">Upload files to this workspace</p>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
