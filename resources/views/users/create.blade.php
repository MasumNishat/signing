<x-layout.app title="Create User">
    <div x-data="{
        loading: false,
        userData: {
            name: '',
            email: '',
            role: 'sender',
            phone: '',
            title: '',
            company: '',
            send_activation_email: true
        },
        errors: {},
        async createUser() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.post(
                    `/accounts/${$store.auth.user.account_id}/users`,
                    this.userData
                );

                $store.toast.success('User created successfully');
                window.location.href = `/users/${response.data.id}`;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to create user');
            } finally {
                this.loading = false;
            }
        }
    }">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-text-primary">Create New User</h1>
            <p class="mt-1 text-sm text-text-secondary">Add a new user to your account</p>
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

                <!-- Role & Phone -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Role *</label>
                        <x-ui.select x-model="userData.role">
                            <option value="viewer">Viewer</option>
                            <option value="sender">Sender</option>
                            <option value="manager">Manager</option>
                            <option value="account_admin">Account Admin</option>
                        </x-ui.select>
                        <p class="mt-1 text-xs text-text-secondary">Determines user permissions</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Phone</label>
                        <x-ui.input
                            type="tel"
                            x-model="userData.phone"
                            placeholder="+1 (555) 123-4567"
                        />
                    </div>
                </div>

                <!-- Title & Company -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Job Title</label>
                        <x-ui.input
                            type="text"
                            x-model="userData.title"
                            placeholder="Account Manager"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Company</label>
                        <x-ui.input
                            type="text"
                            x-model="userData.company"
                            placeholder="Acme Corp"
                        />
                    </div>
                </div>

                <!-- Activation Email -->
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        x-model="userData.send_activation_email"
                        id="activation-email"
                        class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    >
                    <label for="activation-email" class="ml-2 text-sm text-text-primary">
                        Send activation email to user
                    </label>
                </div>
            </div>
        </x-ui.card>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <x-ui.button variant="secondary" onclick="window.location.href='/users'">
                Cancel
            </x-ui.button>
            <x-ui.button variant="primary" @click="createUser()" :disabled="loading">
                <span x-show="!loading">Create User</span>
                <span x-show="loading">Creating...</span>
            </x-ui.button>
        </div>
    </div>
</x-layout.app>
