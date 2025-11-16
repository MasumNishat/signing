<x-layout.app title="Share Template">
    <div x-data="{
        template: null,
        loading: true,
        sharedUsers: [],
        newUserEmail: '',
        canEdit: false,
        async init() {
            await this.loadTemplate();
            await this.loadSharedAccess();
        },
        async loadTemplate() {
            this.loading = true;
            try {
                const templateId = window.location.pathname.split('/')[2];
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/templates/${templateId}`);
                this.template = response.data;
            } catch (error) {
                $store.toast.error('Failed to load template');
            } finally {
                this.loading = false;
            }
        },
        async loadSharedAccess() {
            try {
                const response = await $api.get(
                    `/accounts/${$store.auth.user.account_id}/shared_access?item_type=template&item_id=${this.template.id}`
                );
                this.sharedUsers = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load shared access');
            }
        },
        async shareTemplate() {
            if (!this.newUserEmail) {
                $store.toast.error('Please enter an email address');
                return;
            }

            try {
                await $api.post(
                    `/accounts/${$store.auth.user.account_id}/shared_access`,
                    {
                        item_type: 'template',
                        item_id: this.template.id,
                        shared_with_email: this.newUserEmail,
                        can_edit: this.canEdit
                    }
                );

                $store.toast.success('Template shared successfully');
                this.newUserEmail = '';
                this.canEdit = false;
                await this.loadSharedAccess();
            } catch (error) {
                $store.toast.error('Failed to share template');
            }
        },
        async removeAccess(userId) {
            if (!confirm('Remove access for this user?')) return;

            try {
                await $api.delete(
                    `/accounts/${$store.auth.user.account_id}/shared_access/${userId}?item_type=template&item_id=${this.template.id}`
                );

                $store.toast.success('Access removed');
                await this.loadSharedAccess();
            } catch (error) {
                $store.toast.error('Failed to remove access');
            }
        },
        async updatePermission(userId, canEdit) {
            try {
                await $api.put(
                    `/accounts/${$store.auth.user.account_id}/shared_access/${userId}`,
                    {
                        item_type: 'template',
                        item_id: this.template.id,
                        can_edit: canEdit
                    }
                );

                $store.toast.success('Permission updated');
                await this.loadSharedAccess();
            } catch (error) {
                $store.toast.error('Failed to update permission');
            }
        }
    }"
    x-init="init()">
        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="text" class="h-8 w-64" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Share Template -->
        <div x-show="!loading && template">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-text-primary">Share Template: <span x-text="template?.name"></span></h1>
                <p class="mt-1 text-sm text-text-secondary">Share this template with other users in your account</p>
            </div>

            <!-- Add User -->
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Share with User</h3>
                <div class="flex items-end space-x-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-text-primary mb-1">User Email</label>
                        <x-ui.input
                            type="email"
                            x-model="newUserEmail"
                            placeholder="user@example.com"
                            @keydown.enter="shareTemplate()"
                        />
                    </div>
                    <div class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            x-model="canEdit"
                            id="can-edit"
                            class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <label for="can-edit" class="text-sm text-text-primary">Can Edit</label>
                    </div>
                    <x-ui.button variant="primary" @click="shareTemplate()">
                        Share
                    </x-ui.button>
                </div>
            </x-ui.card>

            <!-- Shared Users List -->
            <x-ui.card>
                <h3 class="text-lg font-semibold text-text-primary mb-4">Shared With</h3>
                <div x-show="sharedUsers.length > 0" class="space-y-3">
                    <template x-for="user in sharedUsers" :key="user.id">
                        <div class="flex items-center justify-between p-4 border border-border-primary rounded-lg">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mr-3">
                                    <span class="font-semibold text-primary-600" x-text="user.shared_with_email?.charAt(0).toUpperCase()"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-text-primary" x-text="user.shared_with_name || user.shared_with_email"></p>
                                    <p class="text-xs text-text-secondary" x-text="user.shared_with_email"></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <x-ui.select
                                    x-model="user.can_edit"
                                    @change="updatePermission(user.shared_with_user_id, user.can_edit)"
                                    class="text-sm"
                                >
                                    <option :value="false">View Only</option>
                                    <option :value="true">Can Edit</option>
                                </x-ui.select>
                                <button
                                    @click="removeAccess(user.shared_with_user_id)"
                                    class="text-red-600 hover:text-red-500"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="sharedUsers.length === 0">
                    <x-ui.empty-state
                        icon="user"
                        title="Not shared with anyone"
                        description="Share this template with other users to collaborate"
                    />
                </div>
            </x-ui.card>

            <!-- Actions -->
            <div class="mt-6 flex items-center justify-between">
                <x-ui.button variant="secondary" onclick="window.location.href='/templates'">
                    Back to Templates
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
