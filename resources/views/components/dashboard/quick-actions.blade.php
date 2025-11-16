@props([
    'actions' => []
])

@php
$defaultActions = [
    [
        'title' => 'Send Envelope',
        'description' => 'Create and send new envelope',
        'icon' => 'envelope',
        'url' => '/envelopes/create',
        'color' => 'primary'
    ],
    [
        'title' => 'Create Template',
        'description' => 'Save time with reusable templates',
        'icon' => 'template',
        'url' => '/templates/create',
        'color' => 'purple'
    ],
    [
        'title' => 'Manage Recipients',
        'description' => 'Add and organize contacts',
        'icon' => 'users',
        'url' => '/users',
        'color' => 'blue'
    ],
];

$actionsList = !empty($actions) ? $actions : $defaultActions;

$iconPaths = [
    'envelope' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'template' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'users' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'folder' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    'settings' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
];

$colorClasses = [
    'primary' => 'bg-primary-100 group-hover:bg-primary-200 text-primary-600',
    'purple' => 'bg-purple-100 group-hover:bg-purple-200 text-purple-600',
    'blue' => 'bg-blue-100 group-hover:bg-blue-200 text-blue-600',
    'green' => 'bg-green-100 group-hover:bg-green-200 text-green-600',
    'orange' => 'bg-orange-100 group-hover:bg-orange-200 text-orange-600',
];
@endphp

<x-ui.card>
    <h3 class="text-lg font-semibold text-text-primary mb-4">Quick Actions</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($actionsList as $action)
            <a href="{{ $action['url'] }}"
               class="flex items-center p-4 rounded-lg border-2 border-dashed border-border-primary hover:border-{{ $action['color'] }}-500 hover:bg-{{ $action['color'] }}-50 dark:hover:bg-{{ $action['color'] }}-900/10 transition-colors group">
                <div class="p-2 rounded-lg {{ $colorClasses[$action['color']] ?? $colorClasses['primary'] }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths[$action['icon']] ?? $iconPaths['envelope'] }}" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="font-medium text-text-primary">{{ $action['title'] }}</p>
                    <p class="text-sm text-text-secondary">{{ $action['description'] }}</p>
                </div>
            </a>
        @endforeach
    </div>
</x-ui.card>
