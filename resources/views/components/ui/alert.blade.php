@props([
    'variant' => 'info', // success, error, warning, info
    'title' => null,
    'dismissible' => false,
    'icon' => true,
])

@php
    $baseClasses = 'rounded-lg p-4 border';

    $variantClasses = [
        'success' => 'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900',
        'error' => 'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900',
        'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-900',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900',
    ];

    $iconClasses = [
        'success' => 'text-green-400 dark:text-green-500',
        'error' => 'text-red-400 dark:text-red-500',
        'warning' => 'text-yellow-400 dark:text-yellow-500',
        'info' => 'text-blue-400 dark:text-blue-500',
    ];

    $icons = [
        'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}
     x-data="{ show: true }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-90"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-90"
     role="alert">

    <div class="flex">
        @if($icon)
            <!-- Icon -->
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 {{ $iconClasses[$variant] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    {!! $icons[$variant] !!}
                </svg>
            </div>
        @endif

        <!-- Content -->
        <div class="@if($icon) ml-3 @endif flex-1">
            @if($title)
                <h3 class="text-sm font-medium mb-1">{{ $title }}</h3>
            @endif

            <div class="text-sm">
                {{ $slot }}
            </div>
        </div>

        @if($dismissible)
            <!-- Close Button -->
            <div class="ml-auto pl-3">
                <button @click="show = false"
                        type="button"
                        class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 hover:bg-black/5 dark:hover:bg-white/5">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
    </div>
</div>

{{--
Usage Examples:

1. Success alert:
<x-ui.alert variant="success" title="Success!">
    Your envelope has been sent successfully.
</x-ui.alert>

2. Error alert:
<x-ui.alert variant="error" title="Error">
    Failed to send envelope. Please try again.
</x-ui.alert>

3. Warning alert:
<x-ui.alert variant="warning" title="Warning">
    This action cannot be undone.
</x-ui.alert>

4. Info alert:
<x-ui.alert variant="info">
    This is an informational message.
</x-ui.alert>

5. Dismissible alert:
<x-ui.alert variant="success" :dismissible="true">
    Settings saved successfully!
</x-ui.alert>

6. Alert without icon:
<x-ui.alert variant="info" :icon="false" title="Note">
    Please review the document before signing.
</x-ui.alert>

7. Alert without title:
<x-ui.alert variant="warning">
    Your session will expire in 5 minutes.
</x-ui.alert>

8. Form validation errors:
@if($errors->any())
    <x-ui.alert variant="error" title="Validation Errors" :dismissible="true">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif

9. With Alpine.js:
<div x-data="{ showAlert: true }">
    <x-ui.alert variant="success" x-show="showAlert">
        Action completed!
    </x-ui.alert>
</div>
--}}
