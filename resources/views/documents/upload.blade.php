<x-layout.app title="Upload Document">
    <div x-data="{
        files: [],
        uploading: false,
        uploadProgress: {},
        dragActive: false,
        async init() {
            // Initialize
        },
        handleDrop(e) {
            this.dragActive = false;
            const droppedFiles = Array.from(e.dataTransfer.files);
            this.addFiles(droppedFiles);
        },
        handleFileInput(e) {
            const selectedFiles = Array.from(e.target.files);
            this.addFiles(selectedFiles);
        },
        addFiles(newFiles) {
            newFiles.forEach(file => {
                // Check file size (max 50MB)
                if (file.size > 50 * 1024 * 1024) {
                    $store.toast.error(`File ${file.name} is too large. Maximum size is 50MB.`);
                    return;
                }

                // Check file type
                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/gif'
                ];

                if (!allowedTypes.includes(file.type)) {
                    $store.toast.error(`File ${file.name} has an unsupported format.`);
                    return;
                }

                this.files.push({
                    file: file,
                    id: Date.now() + Math.random(),
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    status: 'pending' // pending, uploading, completed, failed
                });
            });
        },
        removeFile(fileId) {
            this.files = this.files.filter(f => f.id !== fileId);
        },
        async uploadFiles() {
            if (this.files.length === 0) return;

            this.uploading = true;

            for (const fileObj of this.files) {
                if (fileObj.status === 'completed') continue;

                fileObj.status = 'uploading';
                this.uploadProgress[fileObj.id] = 0;

                try {
                    const formData = new FormData();
                    formData.append('file', fileObj.file);
                    formData.append('name', fileObj.name);

                    const response = await $api.post(
                        `/accounts/${$store.auth.user.account_id}/documents`,
                        formData,
                        {
                            headers: { 'Content-Type': 'multipart/form-data' },
                            onUploadProgress: (progressEvent) => {
                                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                                this.uploadProgress[fileObj.id] = percentCompleted;
                            }
                        }
                    );

                    fileObj.status = 'completed';
                    this.uploadProgress[fileObj.id] = 100;
                } catch (error) {
                    fileObj.status = 'failed';
                    $store.toast.error(`Failed to upload ${fileObj.name}`);
                }
            }

            this.uploading = false;

            // If all files uploaded successfully, redirect to documents list
            const allCompleted = this.files.every(f => f.status === 'completed');
            if (allCompleted) {
                $store.toast.success('All documents uploaded successfully');
                setTimeout(() => {
                    window.location.href = '/documents';
                }, 1500);
            }
        },
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },
        getFileIcon(type) {
            const icons = {
                'application/pdf': '📄',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝',
                'application/msword': '📝',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊',
                'application/vnd.ms-excel': '📊',
                'image/png': '🖼️',
                'image/jpeg': '🖼️',
                'image/jpg': '🖼️',
                'image/gif': '🖼️'
            };
            return icons[type] || '📎';
        }
    }"
    x-init="init()">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Upload Documents</h1>
            <p class="mt-1 text-sm text-text-secondary">Upload one or more documents (max 50MB each)</p>
        </div>

        <!-- Upload Area -->
        <x-ui.card class="mb-6">
            <div
                @drop.prevent="handleDrop"
                @dragover.prevent="dragActive = true"
                @dragleave.prevent="dragActive = false"
                :class="{ 'border-primary-500 bg-primary-50 dark:bg-primary-900/10': dragActive }"
                class="border-2 border-dashed border-border-primary rounded-lg p-12 text-center transition-colors"
            >
                <input
                    type="file"
                    id="file-input"
                    multiple
                    @change="handleFileInput"
                    class="hidden"
                    accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/*"
                >

                <div class="space-y-4">
                    <!-- Upload Icon -->
                    <div class="flex justify-center">
                        <svg class="w-16 h-16 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>

                    <!-- Instructions -->
                    <div>
                        <p class="text-lg font-medium text-text-primary">
                            Drag and drop files here
                        </p>
                        <p class="mt-1 text-sm text-text-secondary">
                            or
                        </p>
                    </div>

                    <!-- Browse Button -->
                    <div>
                        <label for="file-input">
                            <x-ui.button variant="primary" as="span">
                                Browse Files
                            </x-ui.button>
                        </label>
                    </div>

                    <!-- Supported Formats -->
                    <div class="text-xs text-text-secondary">
                        <p>Supported formats: PDF, Word, Excel, Images (PNG, JPG, GIF)</p>
                        <p class="mt-1">Maximum file size: 50MB per file</p>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <!-- Files List -->
        <div x-show="files.length > 0">
            <x-ui.card>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-text-primary">
                        Files (<span x-text="files.length"></span>)
                    </h3>
                    <div class="flex items-center space-x-3">
                        <x-ui.button variant="secondary" size="sm" @click="files = []" :disabled="uploading">
                            Clear All
                        </x-ui.button>
                        <x-ui.button variant="primary" size="sm" @click="uploadFiles()" :disabled="uploading || files.length === 0">
                            <span x-show="!uploading">Upload All</span>
                            <span x-show="uploading">Uploading...</span>
                        </x-ui.button>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="fileObj in files" :key="fileObj.id">
                        <div class="flex items-center justify-between p-4 border border-border-primary rounded-lg">
                            <div class="flex items-center flex-1">
                                <!-- Icon -->
                                <div class="text-3xl mr-4" x-text="getFileIcon(fileObj.type)"></div>

                                <!-- File Info -->
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-text-primary" x-text="fileObj.name"></p>
                                    <p class="text-xs text-text-secondary" x-text="formatFileSize(fileObj.size)"></p>

                                    <!-- Progress Bar -->
                                    <div x-show="fileObj.status === 'uploading'" class="mt-2">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-primary-500 h-2 rounded-full transition-all duration-300"
                                                 :style="`width: ${uploadProgress[fileObj.id] || 0}%`"></div>
                                        </div>
                                        <p class="text-xs text-text-secondary mt-1">
                                            <span x-text="uploadProgress[fileObj.id] || 0"></span>% uploaded
                                        </p>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="ml-4">
                                    <x-ui.badge
                                        x-show="fileObj.status === 'pending'"
                                        variant="secondary"
                                    >
                                        Pending
                                    </x-ui.badge>
                                    <x-ui.badge
                                        x-show="fileObj.status === 'uploading'"
                                        variant="primary"
                                    >
                                        Uploading
                                    </x-ui.badge>
                                    <x-ui.badge
                                        x-show="fileObj.status === 'completed'"
                                        variant="success"
                                    >
                                        Completed
                                    </x-ui.badge>
                                    <x-ui.badge
                                        x-show="fileObj.status === 'failed'"
                                        variant="danger"
                                    >
                                        Failed
                                    </x-ui.badge>
                                </div>

                                <!-- Remove Button -->
                                <button
                                    @click="removeFile(fileObj.id)"
                                    :disabled="uploading"
                                    class="ml-4 text-red-600 hover:text-red-500 disabled:opacity-50"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex items-center justify-between">
            <x-ui.button variant="secondary" onclick="window.location.href='/documents'">
                Cancel
            </x-ui.button>
        </div>
    </div>
</x-layout.app>
