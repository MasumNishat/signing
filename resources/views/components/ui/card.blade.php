@props([
    'padding' => true,
    'shadow' => 'md', // none, sm, md, lg, xl
    'border' => true,
])

@php
    $baseClasses = 'bg-card-bg rounded-lg';

    $shadowClasses = [
        'none' => '',
        'sm' => 'shadow-sm',
        'md' => 'shadow-md',
        'lg' => 'shadow-lg',
        'xl' => 'shadow-xl',
    ];

    $borderClass = $border ? 'border border-card-border' : '';
    $paddingClass = $padding ? 'p-6' : '';

    $classes = implode(' ', array_filter([
        $baseClasses,
        $shadowClasses[$shadow] ?? '',
        $borderClass,
        $paddingClass,
    ]));
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>

{{--
Usage Examples:

1. Basic card:
<x-ui.card>
    <h3 class="text-lg font-bold mb-2">Card Title</h3>
    <p class="text-text-secondary">Card content goes here.</p>
</x-ui.card>

2. Card without padding (for custom layouts):
<x-ui.card :padding="false">
    <div class="p-6 border-b border-card-border">
        <h3 class="text-lg font-bold">Header</h3>
    </div>
    <div class="p-6">
        <p>Content</p>
    </div>
</x-ui.card>

3. Card with different shadow:
<x-ui.card shadow="xl">
    <p>Card with extra large shadow</p>
</x-ui.card>

4. Card without border:
<x-ui.card :border="false">
    <p>Borderless card</p>
</x-ui.card>

5. Dashboard stat card:
<x-ui.card>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-text-secondary">Total Envelopes</p>
            <p class="text-3xl font-bold text-text-primary mt-1">1,234</p>
        </div>
        <div class="p-3 bg-primary-100 rounded-full">
            <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
    </div>
    <div class="mt-4 flex items-center text-sm">
        <span class="text-green-600 font-medium">+12.5%</span>
        <span class="text-text-secondary ml-2">from last month</span>
    </div>
</x-ui.card>

6. Card with header and footer:
<x-ui.card :padding="false">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-card-border">
        <h3 class="text-lg font-bold text-text-primary">Envelope Details</h3>
    </div>

    <!-- Body -->
    <div class="px-6 py-4">
        <dl class="space-y-3">
            <div>
                <dt class="text-sm text-text-secondary">Status</dt>
                <dd class="text-sm font-medium text-text-primary">Completed</dd>
            </div>
            <div>
                <dt class="text-sm text-text-secondary">Sent Date</dt>
                <dd class="text-sm font-medium text-text-primary">Jan 15, 2025</dd>
            </div>
        </dl>
    </div>

    <!-- Footer -->
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-card-border flex justify-end space-x-3">
        <x-ui.button variant="ghost" size="sm">Cancel</x-ui.button>
        <x-ui.button variant="primary" size="sm">Download</x-ui.button>
    </div>
</x-ui.card>

7. Grid of cards:
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <x-ui.card>
        <h3 class="font-bold mb-2">Card 1</h3>
        <p class="text-text-secondary">Content</p>
    </x-ui.card>
    <x-ui.card>
        <h3 class="font-bold mb-2">Card 2</h3>
        <p class="text-text-secondary">Content</p>
    </x-ui.card>
    <x-ui.card>
        <h3 class="font-bold mb-2">Card 3</h3>
        <p class="text-text-secondary">Content</p>
    </x-ui.card>
</div>

8. Clickable card:
<x-ui.card class="cursor-pointer hover:shadow-lg transition-shadow" @click="viewEnvelope({{ $envelope->id }})">
    <h3 class="font-bold mb-2">{{ $envelope->email_subject }}</h3>
    <p class="text-sm text-text-secondary">{{ $envelope->status }}</p>
</x-ui.card>

9. Empty state card:
<x-ui.card class="text-center py-12">
    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <h3 class="text-lg font-medium text-text-primary mb-2">No envelopes found</h3>
    <p class="text-sm text-text-secondary mb-6">Get started by sending your first envelope.</p>
    <x-ui.button variant="primary">Send Envelope</x-ui.button>
</x-ui.card>
--}}
