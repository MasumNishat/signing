<x-layout.auth title="Forgot Password">
    <div x-data="{
        email: '',
        errors: {},
        loading: false,
        emailSent: false,
        async sendResetLink() {
            this.loading = true;
            this.errors = {};

            try {
                await $api.post('/forgot-password', { email: this.email });
                this.emailSent = true;
                $store.toast.success('Password reset link sent to your email!');
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    $store.toast.error('Failed to send reset link. Please try again.');
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
            <h2 class="text-2xl font-bold text-text-primary">Forgot your password?</h2>
            <p class="mt-2 text-sm text-text-secondary">Enter your email and we'll send you a reset link</p>
        </div>

        <!-- Success Message -->
        <div x-show="emailSent" class="mb-6">
            <x-ui.alert variant="success" title="Email Sent!">
                We've sent a password reset link to your email address. Please check your inbox and follow the instructions.
            </x-ui.alert>
        </div>

        <!-- Forgot Password Form -->
        <form x-show="!emailSent" @submit.prevent="sendResetLink()" class="space-y-6">
            <!-- Email -->
            <x-form.input
                name="email"
                label="Email Address"
                type="email"
                placeholder="you@example.com"
                x-model="email"
                :required="true"
                x-bind:error="errors.email?.[0]"
                icon="left">
                <x-slot name="iconSlot">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </x-slot>
            </x-form.input>

            <!-- Submit Button -->
            <x-ui.button
                type="submit"
                variant="primary"
                class="w-full"
                :loading="loading"
                x-bind:disabled="loading">
                Send reset link
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
