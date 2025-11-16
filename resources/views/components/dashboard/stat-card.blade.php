@props([
    'title' => '',
    'value' => 0,
    'change' => null,
    'changeType' => 'positive', // positive, negative, neutral
    'icon' => 'document',
    'color' => 'primary', // primary, green, blue, red, purple, orange
    'loading' => false
])

@php
$colorClasses = [
    'primary' => 'bg-primary-100 dark:bg-primary-900/30 text-primary-600',
    'green' => 'bg-green-100 dark:bg-green-900/30 text-green-600',
    'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
    'red' => 'bg-red-100 dark:bg-red-900/30 text-red-600',
    'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
    'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
];

$iconPaths = [
    'document' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    'envelope' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'users' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'chart' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
];
@endphp

<x-ui.card>
    <div x-show="{{ $loading ? 'true' : 'false' }}">
        <x-ui.skeleton type="text" class="h-4 w-24 mb-2" />
        <x-ui.skeleton type="text" class="h-8 w-16 mb-2" />
        <x-ui.skeleton type="text" class="h-3 w-32" />
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}" class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-text-secondary">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-text-primary">{{ $value }}</p>

            @if($change !== null)
                <p class="mt-2 text-xs text-text-secondary">
                    <span class="font-medium {{ $changeType === 'positive' ? 'text-green-600' : ($changeType === 'negative' ? 'text-red-600' : 'text-gray-600') }}">
                        {{ $changeType === 'positive' ? '+' : '' }}{{ $change }}
                    </span>
                    from last month
                </p>
            @endif
        </div>

        <div class="p-3 rounded-full {{ $colorClasses[$color] ?? $colorClasses['primary'] }}">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths[$icon] ?? $iconPaths['document'] }}" />
            </svg>
        </div>
    </div>
</x-ui.card>
