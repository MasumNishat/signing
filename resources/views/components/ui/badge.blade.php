@props([
    'variant' => 'default', // default, primary, success, warning, danger, info, gray
    'size' => 'md', // xs, sm, md, lg
    'rounded' => true, // pill shape
    'dot' => false, // show dot indicator
    'removable' => false, // show close button
])

@php
    $baseClasses = 'inline-flex items-center font-medium';

    $variantClasses = [
        'default' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'primary' => 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'gray' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    ];

    $sizeClasses = [
        'xs' => 'px-2 py-0.5 text-xs',
        'sm' => 'px-2.5 py-0.5 text-xs',
        'md' => 'px-3 py-1 text-sm',
        'lg' => 'px-3.5 py-1.5 text-base',
    ];

    $roundedClass = $rounded ? 'rounded-full' : 'rounded';

    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size] . ' ' . $roundedClass;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <!-- Status Dot -->
        <span class="w-2 h-2 rounded-full mr-1.5 {{ $variantClasses[$variant] }}"></span>
    @endif

    {{ $slot }}

    @if($removable)
        <!-- Remove Button -->
        <button type="button"
                class="ml-1.5 -mr-1 p-0.5 rounded-full hover:bg-black/10 dark:hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent"
                @if(isset($onRemove))
                    @click="{{ $onRemove }}"
                @endif>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</span>

{{--
Usage Examples:

1. Default badge:
<x-ui.badge>New</x-ui.badge>

2. Colored variants:
<x-ui.badge variant="primary">Primary</x-ui.badge>
<x-ui.badge variant="success">Active</x-ui.badge>
<x-ui.badge variant="warning">Pending</x-ui.badge>
<x-ui.badge variant="danger">Failed</x-ui.badge>
<x-ui.badge variant="info">Info</x-ui.badge>

3. Sizes:
<x-ui.badge size="xs">Extra Small</x-ui.badge>
<x-ui.badge size="sm">Small</x-ui.badge>
<x-ui.badge size="md">Medium</x-ui.badge>
<x-ui.badge size="lg">Large</x-ui.badge>

4. With dot indicator:
<x-ui.badge :dot="true" variant="success">Online</x-ui.badge>
<x-ui.badge :dot="true" variant="danger">Offline</x-ui.badge>

5. Square/rounded:
<x-ui.badge :rounded="false">Square Badge</x-ui.badge>

6. Removable badge:
<x-ui.badge :removable="true" @click="removeTag()">Tag Name</x-ui.badge>

7. Status badges (common use case):
@if($envelope->status === 'sent')
    <x-ui.badge variant="primary" :dot="true">Sent</x-ui.badge>
@elseif($envelope->status === 'completed')
    <x-ui.badge variant="success" :dot="true">Completed</x-ui.badge>
@elseif($envelope->status === 'voided')
    <x-ui.badge variant="danger" :dot="true">Voided</x-ui.badge>
@elseif($envelope->status === 'draft')
    <x-ui.badge variant="gray" :dot="true">Draft</x-ui.badge>
@endif

8. Count badge:
<x-ui.badge variant="danger" size="xs" class="ml-2">5</x-ui.badge>

9. Tag list with removable badges:
<div class="flex flex-wrap gap-2">
    <x-ui.badge :removable="true" variant="primary">Marketing</x-ui.badge>
    <x-ui.badge :removable="true" variant="success">Approved</x-ui.badge>
    <x-ui.badge :removable="true" variant="warning">Review</x-ui.badge>
</div>
--}}
