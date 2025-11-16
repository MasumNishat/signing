<x-layout.auth title="Reset Password">
    <div x-data="{
        formData: {
            token: '{{ $token ?? '' }}',
            email: '{{ $email ?? '' }}',
            password: '',
            password_confirmation: ''
        },
        errors: {},
        loading: false,
        passwordStrength: 0,
        checkPasswordStrength() {
            let strength = 0;
            const password = this.formData.password;

            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;

            this.passwordStrength = strength;
        },
        async resetPassword() {
            this.loading = true;
            this.errors = {};

            try {
                await $api.post('/reset-password', this.formData);
                $store.toast.success('Password reset successful! Please login.');
                window.location.href = '/login';
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    $store.toast.error('Password reset failed. Please try again.');
                }
                this.loading = false;
            }
        }
    }">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-lg mb-4">
                <span class="text-3xl font-bold text-white">D</span>
            </div>
            <h2 class="text-2xl font-bold text-text-primary">Reset your password</h2>
            <p class="mt-2 text-sm text-text-secondary">Enter your new password below</p>
        </div>

        <!-- Reset Password Form -->
        <form @submit.prevent="resetPassword()" class="space-y-5">
            <!-- Email (readonly) -->
            <x-form.input
                name="email"
                label="Email Address"
                type="email"
                x-model="formData.email"
                :disabled="true"
            />

            <!-- New Password -->
            <div>
                <x-form.input
                    name="password"
                    label="New Password"
                    type="password"
                    placeholder="••••••••"
                    x-model="formData.password"
                    @input="checkPasswordStrength()"
                    :required="true"
                    x-bind:error="errors.password?.[0]"
                />

                <!-- Password Strength Indicator -->
                <div class="mt-2">
                    <div class="flex items-center space-x-1">
                        <div class="flex-1 h-2 rounded-full" :class="{
                            'bg-red-500': passwordStrength === 1,
                            'bg-orange-500': passwordStrength === 2,
                            'bg-yellow-500': passwordStrength === 3,
                            'bg-green-500': passwordStrength === 4,
                            'bg-green-600': passwordStrength === 5,
                            'bg-gray-200': passwordStrength === 0
                        }"></div>
                    </div>
                    <p class="text-xs text-text-secondary mt-1" x-show="formData.password.length > 0">
                        <span x-show="passwordStrength === 0">Very weak</span>
                        <span x-show="passwordStrength === 1">Weak</span>
                        <span x-show="passwordStrength === 2">Fair</span>
                        <span x-show="passwordStrength === 3">Good</span>
                        <span x-show="passwordStrength === 4">Strong</span>
                        <span x-show="passwordStrength === 5">Very strong</span>
                    </p>
                </div>
            </div>

            <!-- Confirm Password -->
            <x-form.input
                name="password_confirmation"
                label="Confirm New Password"
                type="password"
                placeholder="••••••••"
                x-model="formData.password_confirmation"
                :required="true"
            />

            <!-- Submit Button -->
            <x-ui.button
                type="submit"
                variant="primary"
                class="w-full"
                :loading="loading"
                x-bind:disabled="loading">
                Reset password
            </x-ui.button>
        </form>

        <!-- Back to Login -->
        <div class="mt-6 text-center">
            <a href="/login" class="text-sm font-medium text-primary-600 hover:text-primary-500 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to login
            </a>
        </div>
    </div>
</x-layout.auth>
