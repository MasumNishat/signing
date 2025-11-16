<x-layout.auth title="Login">
    <div x-data="{
        formData: {
            email: '',
            password: '',
            remember: false
        },
        errors: {},
        loading: false,
        async login() {
            this.loading = true;
            this.errors = {};

            try {
                // Request OAuth token
                const response = await $api.post('/oauth/token', {
                    grant_type: 'password',
                    client_id: '{{ config('app.passport_client_id') }}',
                    client_secret: '{{ config('app.passport_client_secret') }}',
                    username: this.formData.email,
                    password: this.formData.password,
                    scope: '*'
                });

                // Store token and user data
                $store.auth.token = response.data.access_token;

                // Fetch user data
                const userResponse = await $api.get('/user');
                $store.auth.user = userResponse.data;

                $store.toast.success('Login successful!');

                // Redirect to dashboard
                window.location.href = '/dashboard';
            } catch (error) {
                if (error.response?.status === 401) {
                    this.errors.email = 'Invalid credentials';
                    $store.toast.error('Invalid email or password');
                } else if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    $store.toast.error('Login failed. Please try again.');
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
            <h2 class="text-2xl font-bold text-text-primary">Welcome back</h2>
            <p class="mt-2 text-sm text-text-secondary">Sign in to your account to continue</p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="login()" class="space-y-6">
            <!-- Email -->
            <x-form.input
                name="email"
                label="Email Address"
                type="email"
                placeholder="you@example.com"
                x-model="formData.email"
                :required="true"
                x-bind:error="errors.email?.[0]"
                icon="left">
                <x-slot name="iconSlot">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </x-slot>
            </x-form.input>

            <!-- Password -->
            <x-form.input
                name="password"
                label="Password"
                type="password"
                placeholder="••••••••"
                x-model="formData.password"
                :required="true"
                x-bind:error="errors.password?.[0]"
                icon="left">
                <x-slot name="iconSlot">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </x-slot>
            </x-form.input>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between">
                <x-form.checkbox
                    name="remember"
                    label="Remember me"
                    x-model="formData.remember"
                />

                <a href="/forgot-password" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                    Forgot password?
                </a>
            </div>

            <!-- Submit Button -->
            <x-ui.button
                type="submit"
                variant="primary"
                class="w-full"
                :loading="loading"
                x-bind:disabled="loading">
                Sign in
            </x-ui.button>
        </form>

        <!-- Divider -->
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-border-primary"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-card-bg text-text-secondary">Or continue with</span>
                </div>
            </div>

            <!-- Social Login Buttons -->
            <div class="mt-6 grid grid-cols-2 gap-3">
                <x-ui.button variant="outline" type="button" class="w-full">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6.29 18.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0020 3.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.073 4.073 0 01.8 7.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84"/>
                    </svg>
                    Google
                </x-ui.button>

                <x-ui.button variant="outline" type="button" class="w-full">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"/>
                    </svg>
                    GitHub
                </x-ui.button>
            </div>
        </div>

        <!-- Sign Up Link -->
        <p class="mt-6 text-center text-sm text-text-secondary">
            Don't have an account?
            <a href="/register" class="font-medium text-primary-600 hover:text-primary-500">
                Sign up
            </a>
        </p>
    </div>
</x-layout.auth>
