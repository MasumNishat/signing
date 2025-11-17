<x-layout.app title="Edit Document">
    <div x-data="{
        documentId: '{{ $documentId }}',
        formData: {
            name: '',
            description: '',
            tags: []
        },
        loading: true,
        saving: false,
        errors: {},
        async init() {
            await this.loadDocument();
        },
        async loadDocument() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/documents/${this.documentId}`);
                const doc = response.data.data || response.data;
                this.formData.name = doc.name;
                this.formData.description = doc.description || '';
                this.formData.tags = doc.tags || [];
            } catch (error) {
                $store.toast.error('Failed to load document');
                window.location.href = '/documents';
            } finally {
                this.loading = false;
            }
        },
        async submitForm() {
            this.saving = true;
            this.errors = {};

            try {
                await $api.put(
                    `/accounts/${$store.auth.user.account_id}/documents/${this.documentId}`,
                    this.formData
                );

                $store.toast.success('Document updated successfully');
                window.location.href = `/documents/${this.documentId}/viewer`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error(error.response?.data?.message || 'Failed to update document');
            } finally {
                this.saving = false;
            }
        },
        async deleteDocument() {
            if (!confirm('Delete this document? This action cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/documents/${this.documentId}`);
                $store.toast.success('Document deleted successfully');
                window.location.href = '/documents';
            } catch (error) {
                $store.toast.error('Failed to delete document');
            }
        }
    }"
    x-init="init()">
        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="text" class="h-8 w-64" />
            <x-ui.card>
                <div class="space-y-4">
                    <x-ui.skeleton type="text" class="h-10 w-full" />
                    <x-ui.skeleton type="text" class="h-24 w-full" />
                </div>
            </x-ui.card>
        </div>

        <!-- Content -->
        <div x-show="!loading">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center space-x-3 mb-2">
                    <a href="/documents" class="text-text-secondary hover:text-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-primary">Edit Document</h1>
                </div>
                <p class="text-sm text-text-secondary">Update document information</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm()">
                <x-ui.card class="max-w-3xl">
                    <div class="space-y-6">
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

                        <!-- File Info -->
                        <div class="p-4 bg-bg-secondary rounded-lg border border-border-primary">
                            <p class="text-sm text-text-secondary">
                                <strong>Note:</strong> To replace the file, please upload a new document.
                                The current file cannot be modified.
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex items-center justify-between pt-6 border-t border-border-primary">
                        <div class="flex items-center space-x-3">
                            <x-ui.button
                                type="button"
                                variant="secondary"
                                onclick="window.location.href='/documents'"
                                :disabled="saving"
                            >
                                Cancel
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                variant="danger"
                                @click="deleteDocument()"
                                :disabled="saving"
                            >
                                Delete Document
                            </x-ui.button>
                        </div>

                        <x-ui.button
                            type="submit"
                            variant="primary"
                            :loading="saving"
                            :disabled="saving"
                        >
                            <span x-show="!saving">Save Changes</span>
                            <span x-show="saving">Saving...</span>
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </form>
        </div>
    </div>
</x-layout.app>
