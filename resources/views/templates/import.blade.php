<x-layout.app title="Import Template">
    <div x-data="{
        step: 1,
        file: null,
        uploading: false,
        template: null,
        async init() {
            // Initialize
        },
        handleFileInput(e) {
            this.file = e.target.files[0];
            if (this.file) {
                // Validate file type
                const allowedTypes = ['application/json', 'application/xml', 'text/xml'];
                if (!allowedTypes.includes(this.file.type) && !this.file.name.endsWith('.docx')) {
                    $store.toast.error('Invalid file type. Please upload JSON, XML, or DOCX file.');
                    this.file = null;
                }
            }
        },
        async uploadTemplate() {
            if (!this.file) {
                $store.toast.error('Please select a file to import');
                return;
            }

            this.uploading = true;

            try {
                const formData = new FormData();
                formData.append('file', this.file);

                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/templates/import`,
                    formData,
                    {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    }
                );

                this.template = response.data;
                this.step = 2;
                $store.toast.success('Template imported successfully');
            } catch (error) {
                $store.toast.error('Failed to import template');
            } finally {
                this.uploading = false;
            }
        },
        goToTemplate() {
            if (this.template) {
                window.location.href = `/templates/${this.template.id}`;
            }
        }
    }"
    x-init="init()">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Import Template</h1>
            <p class="mt-1 text-sm text-text-secondary">Import a template from JSON, XML, or DOCX file</p>
        </div>

        <!-- Step Indicator -->
        <div class="mb-8">
            <div class="flex items-center">
                <div class="flex items-center" :class="step >= 1 ? 'text-primary-600' : 'text-gray-400'">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2"
                         :class="step >= 1 ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-400'">
                        1
                    </div>
                    <span class="ml-2 text-sm font-medium">Upload File</span>
                </div>
                <div class="flex-1 h-px mx-4" :class="step >= 2 ? 'bg-primary-600' : 'bg-gray-300'"></div>
                <div class="flex items-center" :class="step >= 2 ? 'text-primary-600' : 'text-gray-400'">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2"
                         :class="step >= 2 ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-400'">
                        2
                    </div>
                    <span class="ml-2 text-sm font-medium">Complete</span>
                </div>
            </div>
        </div>

        <!-- Step 1: Upload File -->
        <div x-show="step === 1">
            <x-ui.card>
                <h3 class="text-lg font-semibold text-text-primary mb-4">Select File to Import</h3>
                
                <!-- File Upload Area -->
                <div class="border-2 border-dashed border-border-primary rounded-lg p-12 text-center">
                    <input
                        type="file"
                        id="template-file"
                        @change="handleFileInput"
                        class="hidden"
                        accept=".json,.xml,.docx"
                    >

                    <div class="space-y-4">
                        <div class="flex justify-center">
                            <svg class="w-16 h-16 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>

                        <div>
                            <label for="template-file">
                                <x-ui.button variant="primary" as="span">
                                    Choose File
                                </x-ui.button>
                            </label>
                        </div>

                        <p x-show="file" class="text-sm text-text-primary font-medium" x-text="file?.name"></p>

                        <div class="text-xs text-text-secondary">
                            <p>Supported formats: JSON, XML, DOCX</p>
                            <p class="mt-1">Maximum file size: 10MB</p>
                        </div>
                    </div>
                </div>

                <!-- Import Instructions -->
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">Import Instructions:</h4>
                    <ul class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                        <li>• JSON files should follow the DocuSign template format</li>
                        <li>• XML files should be valid DocuSign template exports</li>
                        <li>• DOCX files will be converted to a basic template</li>
                        <li>• Template documents and fields will be imported automatically</li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex items-center justify-between">
                    <x-ui.button variant="secondary" onclick="window.location.href='/templates'">
                        Cancel
                    </x-ui.button>
                    <x-ui.button
                        variant="primary"
                        @click="uploadTemplate()"
                        :disabled="!file || uploading"
                    >
                        <span x-show="!uploading">Import Template</span>
                        <span x-show="uploading">Importing...</span>
                    </x-ui.button>
                </div>
            </x-ui.card>
        </div>

        <!-- Step 2: Success -->
        <div x-show="step === 2">
            <x-ui.card>
                <div class="text-center py-12">
                    <div class="flex justify-center mb-6">
                        <svg class="w-20 h-20 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <h3 class="text-2xl font-bold text-text-primary mb-2">Template Imported Successfully!</h3>
                    <p class="text-text-secondary mb-8">Your template has been imported and is ready to use</p>

                    <!-- Template Info -->
                    <div x-show="template" class="max-w-md mx-auto mb-8">
                        <div class="bg-bg-secondary rounded-lg p-6 text-left">
                            <h4 class="font-semibold text-text-primary mb-3" x-text="template?.name"></h4>
                            <div class="space-y-2 text-sm text-text-secondary">
                                <div class="flex justify-between">
                                    <span>Documents:</span>
                                    <span x-text="template?.documents?.length || 0"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Recipients:</span>
                                    <span x-text="template?.recipients?.length || 0"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Fields:</span>
                                    <span x-text="template?.tabs?.length || 0"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-center space-x-3">
                        <x-ui.button variant="secondary" onclick="window.location.href='/templates'">
                            Back to Templates
                        </x-ui.button>
                        <x-ui.button variant="primary" @click="goToTemplate()">
                            View Template
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout.app>
