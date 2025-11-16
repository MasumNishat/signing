@props([
    'variant' => 'primary', // primary, secondary, outline, ghost, danger, success, warning
    'size' => 'md', // xs, sm, md, lg, xl
    'type' => 'button', // button, submit, reset
    'disabled' => false,
    'loading' => false,
    'icon' => null, // 'left' or 'right' for icon position
    'iconOnly' => false, // For buttons with only an icon
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variantClasses = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 active:bg-primary-800',
        'secondary' => 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500 active:bg-gray-400 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600',
        'outline' => 'border-2 border-primary-600 text-primary-600 hover:bg-primary-50 focus:ring-primary-500 active:bg-primary-100 dark:hover:bg-primary-900/20',
        'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-gray-500 active:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 active:bg-red-800',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 active:bg-green-800',
        'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500 active:bg-yellow-700',
    ];

    $sizeClasses = [
        'xs' => $iconOnly ? 'p-1' : 'px-2 py-1 text-xs',
        'sm' => $iconOnly ? 'p-1.5' : 'px-3 py-1.5 text-sm',
        'md' => $iconOnly ? 'p-2' : 'px-4 py-2 text-sm',
        'lg' => $iconOnly ? 'p-2.5' : 'px-5 py-2.5 text-base',
        'xl' => $iconOnly ? 'p-3' : 'px-6 py-3 text-base',
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled || $loading) disabled @endif
>
    @if($loading)
        <!-- Loading Spinner -->
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif($icon === 'left' && isset($iconSlot))
        <!-- Icon Left -->
        <span class="mr-2">{{ $iconSlot }}</span>
    @endif

    {{ $slot }}

    @if($icon === 'right' && isset($iconSlot))
        <!-- Icon Right -->
        <span class="ml-2">{{ $iconSlot }}</span>
    @endif
</button>

{{--
Usage Examples:

1. Primary Button:
<x-ui.button>Click Me</x-ui.button>

2. Button with variant and size:
<x-ui.button variant="danger" size="lg">Delete</x-ui.button>

3. Loading button:
<x-ui.button :loading="true">Saving...</x-ui.button>

4. Disabled button:
<x-ui.button :disabled="true">Disabled</x-ui.button>

5. Button with Alpine.js click handler:
<x-ui.button @click="saveForm()">Save</x-ui.button>

6. Icon-only button:
<x-ui.button icon-only variant="ghost" size="sm">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
</x-ui.button>

7. Submit button in form:
<x-ui.button type="submit" variant="primary" size="lg">
    Submit Form
</x-ui.button>

8. With Alpine.js loading state:
<x-ui.button :loading="$wire.loading">
    Process Payment
</x-ui.button>

9. Outline button with custom classes:
<x-ui.button variant="outline" class="w-full">
    Full Width Button
</x-ui.button>

10. All variants showcase:
<x-ui.button variant="primary">Primary</x-ui.button>
<x-ui.button variant="secondary">Secondary</x-ui.button>
<x-ui.button variant="outline">Outline</x-ui.button>
<x-ui.button variant="ghost">Ghost</x-ui.button>
<x-ui.button variant="danger">Danger</x-ui.button>
<x-ui.button variant="success">Success</x-ui.button>
<x-ui.button variant="warning">Warning</x-ui.button>
--}}
