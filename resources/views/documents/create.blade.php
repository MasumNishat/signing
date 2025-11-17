<x-layout.app title="Create Document">
    <div x-data="{
        formData: {
            name: '',
            description: '',
            file: null,
            folder_id: null,
            tags: []
        },
        uploading: false,
        uploadProgress: 0,
        errors: {},
        async submitForm() {
            this.uploading = true;
            this.errors = {};

            try {
                const formDataObj = new FormData();
                formDataObj.append('name', this.formData.name);
                formDataObj.append('description', this.formData.description);
                if (this.formData.file) {
                    formDataObj.append('file', this.formData.file);
                }
                if (this.formData.folder_id) {
                    formDataObj.append('folder_id', this.formData.folder_id);
                }

                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/documents`,
                    formDataObj,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        },
                        onUploadProgress: (progressEvent) => {
                            this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        }
                    }
                );

                $store.toast.success('Document created successfully');
                window.location.href = `/documents/${response.data.data.id}/viewer`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error(error.response?.data?.message || 'Failed to create document');
            } finally {
                this.uploading = false;
                this.uploadProgress = 0;
            }
        },
        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.formData.file = file;
                if (!this.formData.name) {
                    this.formData.name = file.name;
                }
            }
        }
    }">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center space-x-3 mb-2">
                <a href="/documents" class="text-text-secondary hover:text-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-primary">Create Document</h1>
            </div>
            <p class="text-sm text-text-secondary">Upload a new document to your library</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm()">
            <x-ui.card class="max-w-3xl">
                <div class="space-y-6">
                    <!-- File Upload -->
                    <div>
                        <x-form.label for="file" required>Document File</x-form.label>
                        <input
                            type="file"
                            id="file"
                            @change="handleFileChange($event)"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.gif"
                            class="block w-full text-sm text-text-secondary
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-lg file:border-0
                                file:text-sm file:font-semibold
                                file:bg-primary-50 file:text-primary-700
                                hover:file:bg-primary-100
                                dark:file:bg-primary-900 dark:file:text-primary-300"
                            required
                        />
                        <x-form.help-text for="file">
                            Supported formats: PDF, Word, Excel, PowerPoint, Images (Max 50MB)
                        </x-form.help-text>
                        <span x-show="errors.file" class="text-red-600 text-sm" x-text="errors.file?.[0]"></span>
                    </div>

                    <!-- Document Name -->
                    <div>
                        <x-form.input
                            name="name"
                            label="Document Name"
                            placeholder="e.g., Contract Agreement"
                            x-model="formData.name"
                            required
                        />
                        <span x-show="errors.name" class="text-red-600 text-sm" x-text="errors.name?.[0]"></span>
                    </div>

                    <!-- Description -->
                    <div>
                        <x-form.textarea
                            name="description"
                            label="Description"
                            placeholder="Optional description..."
                            rows="3"
                            x-model="formData.description"
                        />
                        <span x-show="errors.description" class="text-red-600 text-sm" x-text="errors.description?.[0]"></span>
                    </div>

                    <!-- Upload Progress -->
                    <div x-show="uploading" class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text-secondary">Uploading...</span>
                            <span class="font-medium text-primary" x-text="`${uploadProgress}%`"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                                 :style="`width: ${uploadProgress}%`"></div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex items-center justify-between pt-6 border-t border-border-primary">
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        onclick="window.location.href='/documents'"
                        :disabled="uploading"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                        variant="primary"
                        :loading="uploading"
                        :disabled="uploading"
                    >
                        <span x-show="!uploading">Create Document</span>
                        <span x-show="uploading">Uploading...</span>
                    </x-ui.button>
                </div>
            </x-ui.card>
        </form>
    </div>
</x-layout.app>
