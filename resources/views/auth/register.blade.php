<x-layout.auth title="Register">
    <div x-data="{
        formData: {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            agree_terms: false
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
        async register() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await $api.post('/register', this.formData);

                $store.toast.success('Registration successful! Please login.');
                window.location.href = '/login';
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    $store.toast.error('Registration failed. Please try again.');
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
            <h2 class="text-2xl font-bold text-text-primary">Create your account</h2>
            <p class="mt-2 text-sm text-text-secondary">Start sending documents for signature</p>
        </div>

        <!-- Register Form -->
        <form @submit.prevent="register()" class="space-y-5">
            <!-- Full Name -->
            <x-form.input
                name="name"
                label="Full Name"
                type="text"
                placeholder="John Doe"
                x-model="formData.name"
                :required="true"
                x-bind:error="errors.name?.[0]"
            />

            <!-- Email -->
            <x-form.input
                name="email"
                label="Email Address"
                type="email"
                placeholder="you@example.com"
                x-model="formData.email"
                :required="true"
                x-bind:error="errors.email?.[0]"
            />

            <!-- Password -->
            <div>
                <x-form.input
                    name="password"
                    label="Password"
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
                label="Confirm Password"
                type="password"
                placeholder="••••••••"
                x-model="formData.password_confirmation"
                :required="true"
            />

            <!-- Terms & Conditions -->
            <x-form.checkbox
                name="agree_terms"
                x-model="formData.agree_terms"
                :required="true"
                x-bind:error="errors.agree_terms?.[0]">
                <x-slot name="label">
                    I agree to the
                    <a href="/terms" class="text-primary-600 hover:text-primary-500">Terms of Service</a>
                    and
                    <a href="/privacy" class="text-primary-600 hover:text-primary-500">Privacy Policy</a>
                </x-slot>
            </x-form.checkbox>

            <!-- Submit Button -->
            <x-ui.button
                type="submit"
                variant="primary"
                class="w-full"
                :loading="loading"
                x-bind:disabled="loading || !formData.agree_terms">
                Create account
            </x-ui.button>
        </form>

        <!-- Sign In Link -->
        <p class="mt-6 text-center text-sm text-text-secondary">
            Already have an account?
            <a href="/login" class="font-medium text-primary-600 hover:text-primary-500">
                Sign in
            </a>
        </p>
    </div>
</x-layout.auth>
