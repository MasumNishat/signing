<x-layout.app title="Create Workspace">
    <div x-data="{
        loading: false,
        workspaceData: {
            name: '',
            description: ''
        },
        errors: {},
        async createWorkspace() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/workspaces`,
                    this.workspaceData
                );

                $store.toast.success('Workspace created successfully');
                window.location.href = `/workspaces/${response.data.id}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to create workspace');
            } finally {
                this.loading = false;
            }
        }
    }">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Create Workspace</h1>
            <p class="mt-1 text-sm text-text-secondary">Create a new workspace for document collaboration</p>
        </div>

        <!-- Form -->
        <x-ui.card class="max-w-2xl">
            <form @submit.prevent="createWorkspace()">
                <div class="space-y-6">
                    <!-- Workspace Name -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">
                            Workspace Name <span class="text-red-500">*</span>
                        </label>
                        <x-ui.input
                            type="text"
                            x-model="workspaceData.name"
                            placeholder="Enter workspace name"
                            required
                        />
                        <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name?.[0]"></p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Description</label>
                        <textarea
                            x-model="workspaceData.description"
                            rows="4"
                            placeholder="Enter workspace description (optional)"
                            class="w-full px-3 py-2 border border-border-primary rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 bg-bg-primary text-text-primary"
                        ></textarea>
                        <p x-show="errors.description" class="mt-1 text-sm text-red-600" x-text="errors.description?.[0]"></p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3">
                        <x-ui.button variant="secondary" type="button" onclick="window.location.href='/workspaces'">
                            Cancel
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit" :disabled="loading">
                            <span x-show="!loading">Create Workspace</span>
                            <span x-show="loading">Creating...</span>
                        </x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layout.app>
