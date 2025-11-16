<x-layout.app title="User Details">
    <div x-data="{
        user: null,
        loading: true,
        async init() {
            await this.loadUser();
        },
        async loadUser() {
            this.loading = true;
            try {
                const userId = window.location.pathname.split('/')[2];
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/users/${userId}`);
                this.user = response.data;
            } catch (error) {
                $store.toast.error('Failed to load user');
                setTimeout(() => {
                    window.location.href = '/users';
                }, 2000);
            } finally {
                this.loading = false;
            }
        },
        async deleteUser() {
            if (!confirm('Delete this user? This action cannot be undone.')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/users/${this.user.id}`);
                $store.toast.success('User deleted successfully');
                window.location.href = '/users';
            } catch (error) {
                $store.toast.error('Failed to delete user');
            }
        },
        getRoleBadgeColor(role) {
            const colors = {
                'super_admin': 'danger',
                'account_admin': 'primary',
                'manager': 'success',
                'sender': 'secondary',
                'signer': 'secondary',
                'viewer': 'secondary'
            };
            return colors[role] || 'secondary';
        },
        getStatusBadgeColor(status) {
            const colors = {
                'active': 'success',
                'inactive': 'secondary',
                'suspended': 'danger'
            };
            return colors[status] || 'secondary';
        }
    }"
    x-init="init()">
        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <x-ui.skeleton type="text" class="h-8 w-64" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- User Details -->
        <div x-show="!loading && user">
            <!-- Header -->
            <div class="mb-6 flex items-start justify-between">
                <div class="flex items-center">
                    <div class="h-20 w-20 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mr-4">
                        <span class="text-3xl font-bold text-primary-600" x-text="user?.name?.charAt(0).toUpperCase()"></span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-text-primary" x-text="user?.name"></h1>
                        <p class="text-sm text-text-secondary" x-text="user?.email"></p>
                        <div class="flex items-center space-x-2 mt-2">
                            <x-ui.badge x-bind:variant="getRoleBadgeColor(user?.role)" x-text="user?.role?.toUpperCase()"></x-ui.badge>
                            <x-ui.badge x-bind:variant="getStatusBadgeColor(user?.status)" x-text="user?.status?.toUpperCase()"></x-ui.badge>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <x-ui.button variant="secondary" x-bind:onclick="`window.location.href='/users/${user?.id}/edit'`">
                        Edit User
                    </x-ui.button>
                    <x-ui.button variant="danger" @click="deleteUser()">
                        Delete User
                    </x-ui.button>
                </div>
            </div>

            <!-- User Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Contact Information -->
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Contact Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Email</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="user?.email"></dd>
                        </div>
                        <div x-show="user?.phone">
                            <dt class="text-sm font-medium text-text-secondary">Phone</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="user?.phone || 'Not provided'"></dd>
                        </div>
                        <div x-show="user?.title">
                            <dt class="text-sm font-medium text-text-secondary">Job Title</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="user?.title || 'Not provided'"></dd>
                        </div>
                        <div x-show="user?.company">
                            <dt class="text-sm font-medium text-text-secondary">Company</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="user?.company || 'Not provided'"></dd>
                        </div>
                    </dl>
                </x-ui.card>

                <!-- Account Details -->
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Account Details</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">User ID</dt>
                            <dd class="mt-1 text-sm text-text-primary font-mono" x-text="user?.id"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Created</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="user?.created_at ? new Date(user.created_at).toLocaleString() : 'N/A'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Last Login</dt>
                            <dd class="mt-1 text-sm text-text-primary" x-text="user?.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Email Verified</dt>
                            <dd class="mt-1">
                                <x-ui.badge x-bind:variant="user?.email_verified_at ? 'success' : 'secondary'" x-text="user?.email_verified_at ? 'Verified' : 'Not Verified'"></x-ui.badge>
                            </dd>
                        </div>
                    </dl>
                </x-ui.card>
            </div>

            <!-- Permissions -->
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Permissions</h3>
                <div x-show="user?.permissions && user.permissions.length > 0" class="flex flex-wrap gap-2">
                    <template x-for="permission in user?.permissions" :key="permission">
                        <x-ui.badge variant="secondary" x-text="permission.replace('_', ' ').toUpperCase()"></x-ui.badge>
                    </template>
                </div>
                <p x-show="!user?.permissions || user.permissions.length === 0" class="text-sm text-text-secondary">
                    No specific permissions assigned. User inherits role-based permissions.
                </p>
            </x-ui.card>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <x-ui.button variant="secondary" onclick="window.location.href='/users'">
                    Back to Users
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
