<x-layout.app title="My Profile">
    <div x-data="{
        loading: false,
        user: $store.auth.user,
        profileData: {
            name: '',
            email: '',
            phone: '',
            title: '',
            company: ''
        },
        passwordData: {
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
        },
        errors: {},
        activeTab: 'profile',
        profileImage: null,
        uploadingImage: false,
        async init() {
            await this.loadProfile();
        },
        async loadProfile() {
            this.loading = true;
            try {
                const response = await $api.get(`/accounts/${$store.auth.user.account_id}/users/${$store.auth.user.id}`);
                this.user = response.data;

                // Populate form
                this.profileData = {
                    name: this.user.name || '',
                    email: this.user.email || '',
                    phone: this.user.phone || '',
                    title: this.user.title || '',
                    company: this.user.company || ''
                };
            } catch (error) {
                $store.toast.error('Failed to load profile');
            } finally {
                this.loading = false;
            }
        },
        async updateProfile() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.put(
                    `/accounts/${$store.auth.user.account_id}/users/${$store.auth.user.id}`,
                    this.profileData
                );

                $store.auth.user = response.data;
                $store.toast.success('Profile updated successfully');
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to update profile');
            } finally {
                this.loading = false;
            }
        },
        async uploadProfileImage() {
            if (!this.profileImage) return;

            this.uploadingImage = true;
            try {
                const formData = new FormData();
                formData.append('image', this.profileImage);

                await $api.post(
                    `/accounts/${$store.auth.user.account_id}/users/${$store.auth.user.id}/profile/image`,
                    formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    }
                );

                $store.toast.success('Profile image updated successfully');
                await this.loadProfile();
            } catch (error) {
                $store.toast.error('Failed to upload profile image');
            } finally {
                this.uploadingImage = false;
                this.profileImage = null;
            }
        },
        async deleteProfileImage() {
            if (!confirm('Delete your profile image?')) return;

            try {
                await $api.delete(`/accounts/${$store.auth.user.account_id}/users/${$store.auth.user.id}/profile/image`);
                $store.toast.success('Profile image deleted successfully');
                await this.loadProfile();
            } catch (error) {
                $store.toast.error('Failed to delete profile image');
            }
        },
        async changePassword() {
            this.loading = true;
            this.errors = {};

            try {
                await $api.put(
                    `/accounts/${$store.auth.user.account_id}/users/${$store.auth.user.id}/password`,
                    this.passwordData
                );

                $store.toast.success('Password changed successfully');
                this.passwordData = {
                    current_password: '',
                    new_password: '',
                    new_password_confirmation: ''
                };
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                $store.toast.error('Failed to change password');
            } finally {
                this.loading = false;
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
        <div x-show="loading && !user" class="space-y-4">
            <x-ui.skeleton type="text" class="h-8 w-64" />
            <x-ui.skeleton type="card" class="h-96" />
        </div>

        <!-- Profile Content -->
        <div x-show="!loading || user">
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
            </div>

            <!-- Tabs -->
            <div class="border-b border-border-primary mb-6">
                <nav class="flex space-x-8">
                    <button
                        @click="activeTab = 'profile'"
                        :class="activeTab === 'profile' ? 'border-primary-600 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border-primary'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Profile Information
                    </button>
                    <button
                        @click="activeTab = 'image'"
                        :class="activeTab === 'image' ? 'border-primary-600 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border-primary'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Profile Image
                    </button>
                    <button
                        @click="activeTab = 'password'"
                        :class="activeTab === 'password' ? 'border-primary-600 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border-primary'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Change Password
                    </button>
                    <button
                        @click="activeTab = 'account'"
                        :class="activeTab === 'account' ? 'border-primary-600 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border-primary'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Account Details
                    </button>
                </nav>
            </div>

            <!-- Profile Information Tab -->
            <div x-show="activeTab === 'profile'">
                <x-ui.card class="mb-6">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Update Profile Information</h3>
                    <div class="space-y-4">
                        <!-- Name & Email -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-text-primary mb-1">Full Name *</label>
                                <x-ui.input
                                    type="text"
                                    x-model="profileData.name"
                                    placeholder="John Doe"
                                    required
                                />
                                <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name?.[0]"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text-primary mb-1">Email *</label>
                                <x-ui.input
                                    type="email"
                                    x-model="profileData.email"
                                    placeholder="john@example.com"
                                    required
                                />
                                <p x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email?.[0]"></p>
                            </div>
                        </div>

                        <!-- Phone & Title -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-text-primary mb-1">Phone</label>
                                <x-ui.input
                                    type="tel"
                                    x-model="profileData.phone"
                                    placeholder="+1 (555) 123-4567"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text-primary mb-1">Job Title</label>
                                <x-ui.input
                                    type="text"
                                    x-model="profileData.title"
                                    placeholder="Account Manager"
                                />
                            </div>
                        </div>

                        <!-- Company -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Company</label>
                            <x-ui.input
                                type="text"
                                x-model="profileData.company"
                                placeholder="Acme Corp"
                            />
                        </div>
                    </div>
                </x-ui.card>

                <!-- Actions -->
                <div class="flex items-center justify-end">
                    <x-ui.button variant="primary" @click="updateProfile()" :disabled="loading">
                        <span x-show="!loading">Save Changes</span>
                        <span x-show="loading">Saving...</span>
                    </x-ui.button>
                </div>
            </div>

            <!-- Profile Image Tab -->
            <div x-show="activeTab === 'image'">
                <x-ui.card class="mb-6">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Profile Image</h3>

                    <!-- Current Image -->
                    <div class="mb-6 text-center">
                        <div class="inline-block h-32 w-32 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                            <span class="text-6xl font-bold text-primary-600" x-text="user?.name?.charAt(0).toUpperCase()"></span>
                        </div>
                        <p class="mt-2 text-sm text-text-secondary">Current profile image</p>
                    </div>

                    <!-- Upload New Image -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Upload New Image</label>
                        <input
                            type="file"
                            @change="profileImage = $event.target.files[0]"
                            accept="image/png,image/jpeg,image/jpg"
                            class="block w-full text-sm text-text-secondary
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-primary-50 file:text-primary-700
                                hover:file:bg-primary-100
                                dark:file:bg-primary-900/30 dark:file:text-primary-400"
                        />
                        <p class="mt-1 text-xs text-text-secondary">PNG, JPG up to 10MB</p>
                        <p x-show="profileImage" class="mt-2 text-sm text-text-primary">
                            Selected: <span x-text="profileImage?.name"></span>
                        </p>
                    </div>
                </x-ui.card>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <x-ui.button variant="danger" @click="deleteProfileImage()">
                        Delete Current Image
                    </x-ui.button>
                    <x-ui.button variant="primary" @click="uploadProfileImage()" :disabled="!profileImage || uploadingImage">
                        <span x-show="!uploadingImage">Upload Image</span>
                        <span x-show="uploadingImage">Uploading...</span>
                    </x-ui.button>
                </div>
            </div>

            <!-- Change Password Tab -->
            <div x-show="activeTab === 'password'">
                <x-ui.card class="mb-6">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Change Password</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Current Password *</label>
                            <x-ui.input
                                type="password"
                                x-model="passwordData.current_password"
                                placeholder="Enter current password"
                                required
                            />
                            <p x-show="errors.current_password" class="mt-1 text-sm text-red-600" x-text="errors.current_password?.[0]"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">New Password *</label>
                            <x-ui.input
                                type="password"
                                x-model="passwordData.new_password"
                                placeholder="Enter new password"
                                required
                            />
                            <p x-show="errors.new_password" class="mt-1 text-sm text-red-600" x-text="errors.new_password?.[0]"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Confirm New Password *</label>
                            <x-ui.input
                                type="password"
                                x-model="passwordData.new_password_confirmation"
                                placeholder="Confirm new password"
                                required
                            />
                        </div>
                    </div>
                </x-ui.card>

                <!-- Actions -->
                <div class="flex items-center justify-end">
                    <x-ui.button variant="primary" @click="changePassword()" :disabled="loading">
                        <span x-show="!loading">Change Password</span>
                        <span x-show="loading">Changing...</span>
                    </x-ui.button>
                </div>
            </div>

            <!-- Account Details Tab -->
            <div x-show="activeTab === 'account'">
                <x-ui.card class="mb-6">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Account Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">User ID</dt>
                            <dd class="mt-1 text-sm text-text-primary font-mono" x-text="user?.id"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Role</dt>
                            <dd class="mt-1">
                                <x-ui.badge x-bind:variant="getRoleBadgeColor(user?.role)" x-text="user?.role?.toUpperCase()"></x-ui.badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-text-secondary">Status</dt>
                            <dd class="mt-1">
                                <x-ui.badge x-bind:variant="getStatusBadgeColor(user?.status)" x-text="user?.status?.toUpperCase()"></x-ui.badge>
                            </dd>
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

                <!-- Permissions -->
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-text-primary mb-4">My Permissions</h3>
                    <div x-show="user?.permissions && user.permissions.length > 0" class="flex flex-wrap gap-2">
                        <template x-for="permission in user?.permissions" :key="permission">
                            <x-ui.badge variant="secondary" x-text="permission.replace('_', ' ').toUpperCase()"></x-ui.badge>
                        </template>
                    </div>
                    <p x-show="!user?.permissions || user.permissions.length === 0" class="text-sm text-text-secondary">
                        You inherit permissions based on your role.
                    </p>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layout.app>
