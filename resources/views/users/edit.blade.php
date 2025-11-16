<x-layout.app title="Edit User">
    <div x-data="{
        loading: true,
        user: null,
        userData: {
            name: '',
            email: '',
            role: '',
            phone: '',
            title: '',
            company: '',
            status: 'active'
        },
        errors: {},
        async init() {
            await this.loadUser();
        },
        async loadUser() {
            this.loading = true;
            try {
                const userId = window.location.pathname.split('/')[2];
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/users/${userId}`);
                this.user = response.data;
                
                // Populate form
                this.userData = {
                    name: this.user.name || '',
                    email: this.user.email || '',
                    role: this.user.role || 'sender',
                    phone: this.user.phone || '',
                    title: this.user.title || '',
                    company: this.user.company || '',
                    status: this.user.status || 'active'
                };
            } catch (error) {
                $store.toast.error('Failed to load user');
                setTimeout(() => {
                    window.location.href = '/users';
                }, 2000);
            } finally {
                this.loading = false;
            }
        },
        async updateUser() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.put(
                    `/accounts/${$store.auth.user.account_id}/users/${this.user.id}`,
                    this.userData
                );

                $store.toast.success('User updated successfully');
                window.location.href = `/users/${this.user.id}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to update user');
            } finally {
                this.loading = false;
            }
        }
    }"
    x-init="init()">
        <!-- Loading State -->
        <div x-show="loading && !user" class="space-y-4">
            <x-ui.skeleton type="text" class="h-8 w-64" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Edit Form -->
        <div x-show="!loading || user">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-text-primary">Edit User</h1>
                <p class="mt-1 text-sm text-text-secondary">Update user information and permissions</p>
            </div>

            <!-- Form -->
            <x-ui.card class="mb-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">User Information</h3>
                <div class="space-y-4">
                    <!-- Name & Email -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Full Name *</label>
                            <x-ui.input
                                type="text"
                                x-model="userData.name"
                                placeholder="John Doe"
                                required
                            />
                            <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name?.[0]"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Email *</label>
                            <x-ui.input
                                type="email"
                                x-model="userData.email"
                                placeholder="john@example.com"
                                required
                            />
                            <p x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email?.[0]"></p>
                        </div>
                    </div>

                    <!-- Role & Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Role *</label>
                            <x-ui.select x-model="userData.role">
                                <option value="viewer">Viewer</option>
                                <option value="sender">Sender</option>
                                <option value="manager">Manager</option>
                                <option value="account_admin">Account Admin</option>
                            </x-ui.select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Status *</label>
                            <x-ui.select x-model="userData.status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </x-ui.select>
                        </div>
                    </div>

                    <!-- Phone & Title -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Phone</label>
                            <x-ui.input
                                type="tel"
                                x-model="userData.phone"
                                placeholder="+1 (555) 123-4567"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Job Title</label>
                            <x-ui.input
                                type="text"
                                x-model="userData.title"
                                placeholder="Account Manager"
                            />
                        </div>
                    </div>

                    <!-- Company -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Company</label>
                        <x-ui.input
                            type="text"
                            x-model="userData.company"
                            placeholder="Acme Corp"
                        />
                    </div>
                </div>
            </x-ui.card>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <x-ui.button variant="secondary" x-bind:onclick="`window.location.href='/users/${user?.id}'`">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="updateUser()" :disabled="loading">
                    <span x-show="!loading">Update User</span>
                    <span x-show="loading">Updating...</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layout.app>
