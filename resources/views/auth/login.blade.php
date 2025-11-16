<x-layout.auth title="Login">
    <div>
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-lg mb-4">
                <span class="text-3xl font-bold text-white">D</span>
            </div>
            <h2 class="text-2xl font-bold text-text-primary">Sign in to your account</h2>
            <p class="mt-2 text-sm text-text-secondary">Welcome back! Please enter your details.</p>
        </div>

        <!-- Session-based Login Form (NOT OAuth) -->
        <form method="POST" action="{{ route('login.post') }}" class="space-y-6">
            @csrf

            <!-- Success Message -->
            @if (session('status'))
                <div class="rounded-md bg-success-50 p-4">
                    <p class="text-sm font-medium text-success-800">{{ session('status') }}</p>
                </div>
            @endif

            <!-- Error Message -->
            @if ($errors->any())
                <div class="rounded-md bg-error-50 p-4">
                    <p class="text-sm font-medium text-error-800">
                        {{ $errors->first('email') ?? 'Please check your credentials and try again.' }}
                    </p>
                </div>
            @endif

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-text-primary">
                    Email Address
                </label>
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        autofocus
                        placeholder="you@example.com"
                        class="block w-full pl-10 pr-3 py-2 border border-border-primary rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-input-bg text-input-text placeholder-input-placeholder"
                    />
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-text-primary">
                    Password
                </label>
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="block w-full pl-10 pr-3 py-2 border border-border-primary rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-input-bg text-input-text placeholder-input-placeholder"
                    />
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        value="1"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-border-primary rounded"
                    />
                    <label for="remember" class="ml-2 block text-sm text-text-primary">
                        Remember me
                    </label>
                </div>

                <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                    Forgot password?
                </a>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                Sign in
            </button>
        </form>

        <!-- Sign Up Link -->
        <p class="mt-6 text-center text-sm text-text-secondary">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-500">
                Sign up
            </a>
        </p>
    </div>
</x-layout.auth>
