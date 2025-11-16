@props([
    'value' => 0, // 0-100
    'max' => 100,
    'size' => 'md', // sm, md, lg
    'variant' => 'primary', // primary, success, warning, danger, info
    'showLabel' => false,
    'animated' => false,
])

@php
    $percentage = min(100, max(0, ($value / $max) * 100));

    $sizeClasses = [
        'sm' => 'h-1',
        'md' => 'h-2',
        'lg' => 'h-3',
    ];

    $variantClasses = [
        'primary' => 'bg-primary-600',
        'success' => 'bg-green-600',
        'warning' => 'bg-yellow-500',
        'danger' => 'bg-red-600',
        'info' => 'bg-blue-600',
    ];
@endphp

<div {{ $attributes }}>
    @if($showLabel)
        <div class="flex justify-between mb-1">
            <span class="text-sm font-medium text-text-primary">{{ $slot }}</span>
            <span class="text-sm font-medium text-text-secondary">{{ round($percentage) }}%</span>
        </div>
    @endif

    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full {{ $sizeClasses[$size] }} overflow-hidden">
        <div class="{{ $variantClasses[$variant] }} {{ $sizeClasses[$size] }} rounded-full transition-all duration-300 {{ $animated ? 'animate-pulse' : '' }}"
             style="width: {{ $percentage }}%"
             role="progressbar"
             aria-valuenow="{{ $value }}"
             aria-valuemin="0"
             aria-valuemax="{{ $max }}">
        </div>
    </div>
</div>

{{--
Usage Examples:

1. Basic progress:
<x-ui.progress-bar :value="75" />

2. With label:
<x-ui.progress-bar :value="60" :show-label="true">
    Uploading document...
</x-ui.progress-bar>

3. Different variants:
<x-ui.progress-bar :value="85" variant="success" :show-label="true">Complete</x-ui.progress-bar>
<x-ui.progress-bar :value="50" variant="warning" :show-label="true">In Progress</x-ui.progress-bar>
<x-ui.progress-bar :value="25" variant="danger" :show-label="true">Low</x-ui.progress-bar>

4. Sizes:
<x-ui.progress-bar :value="70" size="sm" />
<x-ui.progress-bar :value="70" size="md" />
<x-ui.progress-bar :value="70" size="lg" />

5. Animated (for indeterminate progress):
<x-ui.progress-bar :value="100" :animated="true" variant="primary" />

6. File upload progress:
<div x-data="{ uploadProgress: 0 }">
    <x-ui.progress-bar :value="uploadProgress" :show-label="true" variant="success">
        Uploading...
    </x-ui.progress-bar>
</div>

7. Multi-step process:
<div x-data="{ currentStep: 2, totalSteps: 5 }">
    <x-ui.progress-bar
        :value="(currentStep / totalSteps) * 100"
        :show-label="true">
        Step <span x-text="currentStep"></span> of <span x-text="totalSteps"></span>
    </x-ui.progress-bar>
</div>

8. Storage usage:
<x-ui.progress-bar
    :value="750"
    :max="1000"
    :show-label="true"
    variant="info">
    Storage: 750 MB / 1000 MB
</x-ui.progress-bar>
--}}
