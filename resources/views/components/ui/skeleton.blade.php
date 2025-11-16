@props([
    'type' => 'text', // text, title, avatar, card, button, image
    'lines' => 3, // For text type
    'width' => 'full', // full, 1/2, 1/3, 1/4, 3/4
    'height' => null, // Custom height
])

@php
    $widthClasses = [
        'full' => 'w-full',
        '1/2' => 'w-1/2',
        '1/3' => 'w-1/3',
        '1/4' => 'w-1/4',
        '3/4' => 'w-3/4',
    ];

    $baseClasses = 'animate-pulse bg-gray-200 dark:bg-gray-700';
@endphp

@if($type === 'text')
    <div {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @for($i = 0; $i < $lines; $i++)
            <div class="{{ $baseClasses }} h-4 rounded {{ $i === $lines - 1 ? 'w-3/4' : 'w-full' }}"></div>
        @endfor
    </div>

@elseif($type === 'title')
    <div {{ $attributes->merge(['class' => "$baseClasses h-8 rounded {$widthClasses[$width]}"]) }}></div>

@elseif($type === 'avatar')
    <div {{ $attributes->merge(['class' => "$baseClasses w-12 h-12 rounded-full"]) }}></div>

@elseif($type === 'card')
    <div {{ $attributes->merge(['class' => 'border border-border-primary rounded-lg p-6']) }}>
        <div class="{{ $baseClasses }} h-48 rounded mb-4"></div>
        <div class="{{ $baseClasses }} h-6 rounded w-3/4 mb-3"></div>
        <div class="space-y-2">
            <div class="{{ $baseClasses }} h-4 rounded w-full"></div>
            <div class="{{ $baseClasses }} h-4 rounded w-full"></div>
            <div class="{{ $baseClasses }} h-4 rounded w-2/3"></div>
        </div>
    </div>

@elseif($type === 'button')
    <div {{ $attributes->merge(['class' => "$baseClasses h-10 rounded-lg {$widthClasses[$width]}"]) }}></div>

@elseif($type === 'image')
    <div {{ $attributes->merge(['class' => "$baseClasses rounded-lg"]) }}
         style="{{ $height ? "height: {$height}px" : '' }}"></div>

@else
    {{-- Custom skeleton --}}
    <div {{ $attributes->merge(['class' => $baseClasses]) }}>
        {{ $slot }}
    </div>
@endif

{{--
Usage Examples:

1. Text skeleton (loading paragraph):
<x-ui.skeleton type="text" :lines="5" />

2. Title skeleton:
<x-ui.skeleton type="title" width="1/2" />

3. Avatar skeleton:
<x-ui.skeleton type="avatar" class="w-16 h-16" />

4. Card skeleton:
<x-ui.skeleton type="card" />

5. Button skeleton:
<x-ui.skeleton type="button" width="1/3" />

6. Image skeleton:
<x-ui.skeleton type="image" class="w-full h-64" />

7. Table row skeleton:
<tr>
    <td><x-ui.skeleton class="h-4 rounded w-full" /></td>
    <td><x-ui.skeleton class="h-4 rounded w-3/4" /></td>
    <td><x-ui.skeleton class="h-4 rounded w-1/2" /></td>
    <td><x-ui.skeleton class="h-8 rounded w-24" /></td>
</tr>

8. User profile skeleton:
<div class="flex items-center space-x-4">
    <x-ui.skeleton type="avatar" />
    <div class="flex-1 space-y-2">
        <x-ui.skeleton type="title" width="1/3" />
        <x-ui.skeleton type="text" :lines="2" />
    </div>
</div>

9. List skeleton:
<div class="space-y-4">
    @for($i = 0; $i < 5; $i++)
        <div class="flex items-center space-x-4">
            <x-ui.skeleton class="w-12 h-12 rounded" />
            <div class="flex-1 space-y-2">
                <x-ui.skeleton class="h-4 rounded w-3/4" />
                <x-ui.skeleton class="h-3 rounded w-1/2" />
            </div>
        </div>
    @endfor
</div>

10. Envelope list skeleton:
<x-ui.card class="space-y-4">
    @for($i = 0; $i < 3; $i++)
        <div class="flex items-center justify-between pb-4 border-b last:border-0">
            <div class="flex-1">
                <x-ui.skeleton type="title" width="1/2" class="mb-2" />
                <x-ui.skeleton type="text" :lines="2" />
            </div>
            <x-ui.skeleton type="button" width="1/4" />
        </div>
    @endfor
</x-ui.card>

11. With Alpine.js loading state:
<div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 2000)">
    <template x-if="loading">
        <x-ui.skeleton type="card" />
    </template>

    <template x-if="!loading">
        <x-ui.card>
            <h3>Actual content</h3>
            <p>This appears after loading.</p>
        </x-ui.card>
    </template>
</div>
--}}
