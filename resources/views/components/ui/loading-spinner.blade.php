@props([
    'size' => 'md', // xs, sm, md, lg, xl
    'color' => 'primary', // primary, white, gray, black
    'text' => null,
])

@php
    $sizeClasses = [
        'xs' => 'h-3 w-3',
        'sm' => 'h-4 w-4',
        'md' => 'h-6 w-6',
        'lg' => 'h-8 w-8',
        'xl' => 'h-12 w-12',
    ];

    $colorClasses = [
        'primary' => 'text-primary-600',
        'white' => 'text-white',
        'gray' => 'text-gray-500',
        'black' => 'text-gray-900',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    <svg class="animate-spin {{ $sizeClasses[$size] }} {{ $colorClasses[$color] }}"
         xmlns="http://www.w3.org/2000/svg"
         fill="none"
         viewBox="0 0 24 24">
        <circle class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"></circle>
        <path class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>

    @if($text)
        <span class="ml-2 text-sm {{ $colorClasses[$color] }}">{{ $text }}</span>
    @endif
</div>

{{--
Usage Examples:

1. Default spinner:
<x-ui.loading-spinner />

2. With text:
<x-ui.loading-spinner text="Loading..." />

3. Different sizes:
<x-ui.loading-spinner size="xs" />
<x-ui.loading-spinner size="sm" />
<x-ui.loading-spinner size="md" />
<x-ui.loading-spinner size="lg" />
<x-ui.loading-spinner size="xl" />

4. Different colors:
<x-ui.loading-spinner color="primary" />
<x-ui.loading-spinner color="white" />
<x-ui.loading-spinner color="gray" />
<x-ui.loading-spinner color="black" />

5. Full page loading:
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 text-center">
        <x-ui.loading-spinner size="xl" />
        <p class="mt-4 text-text-primary">Processing your request...</p>
    </div>
</div>

6. Inline with button:
<x-ui.button :disabled="loading">
    <x-ui.loading-spinner v-if="loading" size="sm" color="white" />
    {{ loading ? 'Saving...' : 'Save' }}
</x-ui.button>

7. With Alpine.js loading state:
<div x-data="{ loading: false }">
    <x-ui.button @click="loading = true; submitForm()">
        <span x-show="loading">
            <x-ui.loading-spinner size="sm" color="white" />
        </span>
        <span x-text="loading ? 'Processing...' : 'Submit'"></span>
    </x-ui.button>
</div>

8. Card loading state:
<x-ui.card>
    <div x-show="loading" class="flex justify-center py-12">
        <x-ui.loading-spinner size="lg" text="Loading data..." />
    </div>

    <div x-show="!loading">
        <!-- Card content -->
    </div>
</x-ui.card>

9. Table loading overlay:
<div class="relative">
    <div x-show="loading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
        <x-ui.loading-spinner size="lg" text="Loading results..." />
    </div>

    <table>
        <!-- Table content -->
    </table>
</div>

10. Centered loading:
<div class="flex justify-center items-center h-64">
    <x-ui.loading-spinner size="xl" color="primary" />
</div>
--}}
