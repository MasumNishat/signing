@props(['title' => 'Login'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ themeStore: $store.theme }" x-init="themeStore.init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} - DocuSign Clone</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-primary-50 to-primary-100">
    <!-- Theme Toggle (Top Right) -->
    <div class="fixed top-4 right-4 z-50">
        <button @click="$store.theme.toggleMode()"
                class="p-2 rounded-lg bg-white shadow-md hover:shadow-lg transition-shadow">
            <svg x-show="$store.theme.mode === 'light'" class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <svg x-show="$store.theme.mode === 'dark'" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </button>
    </div>

    <!-- Main Content -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-primary-600">DocuSign</h1>
                <p class="mt-2 text-gray-600">Electronic Signature Platform</p>
            </div>

            <!-- Card -->
            <div class="bg-card-bg rounded-lg shadow-xl p-8 border border-card-border">
                {{ $slot }}
            </div>

            <!-- Footer Links -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <a href="/privacy" class="hover:text-gray-900 hover:underline">Privacy Policy</a>
                <span class="mx-2">•</span>
                <a href="/terms" class="hover:text-gray-900 hover:underline">Terms of Service</a>
                <span class="mx-2">•</span>
                <a href="/help" class="hover:text-gray-900 hover:underline">Help</a>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div x-data
         class="fixed bottom-4 right-4 z-50 space-y-2"
         style="max-width: 24rem;">
        <template x-for="notification in $store.toast.notifications" :key="notification.id">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform translate-y-2"
                 class="rounded-lg shadow-lg overflow-hidden"
                 :class="{
                     'bg-green-50 border-l-4 border-green-500': notification.type === 'success',
                     'bg-red-50 border-l-4 border-red-500': notification.type === 'error',
                     'bg-yellow-50 border-l-4 border-yellow-500': notification.type === 'warning',
                     'bg-blue-50 border-l-4 border-blue-500': notification.type === 'info'
                 }">
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg x-show="notification.type === 'success'" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <svg x-show="notification.type === 'error'" class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <svg x-show="notification.type === 'warning'" class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <svg x-show="notification.type === 'info'" class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p x-show="notification.title"
                               x-text="notification.title"
                               class="text-sm font-medium"
                               :class="{
                                   'text-green-800': notification.type === 'success',
                                   'text-red-800': notification.type === 'error',
                                   'text-yellow-800': notification.type === 'warning',
                                   'text-blue-800': notification.type === 'info'
                               }"></p>
                            <p x-text="notification.message"
                               class="text-sm"
                               :class="{
                                   'text-green-700': notification.type === 'success',
                                   'text-red-700': notification.type === 'error',
                                   'text-yellow-700': notification.type === 'warning',
                                   'text-blue-700': notification.type === 'info'
                               }"></p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button @click="$store.toast.remove(notification.id)"
                                    class="inline-flex rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    :class="{
                                        'text-green-500 hover:text-green-600 focus:ring-green-500': notification.type === 'success',
                                        'text-red-500 hover:text-red-600 focus:ring-red-500': notification.type === 'error',
                                        'text-yellow-500 hover:text-yellow-600 focus:ring-yellow-500': notification.type === 'warning',
                                        'text-blue-500 hover:text-blue-600 focus:ring-blue-500': notification.type === 'info'
                                    }">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</body>
</html>
