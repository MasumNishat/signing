@props([
    'text' => '',
    'position' => 'top', // top, bottom, left, right
    'theme' => 'dark', // dark, light
])

@php
    $positionClasses = [
        'top' => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
        'left' => 'right-full top-1/2 -translate-y-1/2 mr-2',
        'right' => 'left-full top-1/2 -translate-y-1/2 ml-2',
    ];

    $arrowClasses = [
        'top' => 'top-full left-1/2 -translate-x-1/2 border-t',
        'bottom' => 'bottom-full left-1/2 -translate-x-1/2 border-b',
        'left' => 'left-full top-1/2 -translate-y-1/2 border-l',
        'right' => 'right-full top-1/2 -translate-y-1/2 border-r',
    ];

    $themeClasses = [
        'dark' => 'bg-gray-900 text-white border-gray-900',
        'light' => 'bg-white text-gray-900 border-gray-200 shadow-lg',
    ];
@endphp

<div class="relative inline-flex"
     x-data="{ show: false }"
     @mouseenter="show = true"
     @mouseleave="show = false"
     @focus="show = true"
     @blur="show = false">

    <!-- Trigger Element -->
    <div>
        {{ $slot }}
    </div>

    <!-- Tooltip -->
    <div x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute {{ $positionClasses[$position] }} px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap z-50 pointer-events-none {{ $themeClasses[$theme] }}"
         style="display: none;"
         role="tooltip">

        {{ $text }}

        <!-- Arrow -->
        <div class="absolute {{ $arrowClasses[$position] }} w-2 h-2 rotate-45 {{ $themeClasses[$theme] }}"></div>
    </div>
</div>

{{--
Usage Examples:

1. Basic tooltip (top):
<x-ui.tooltip text="Click to edit">
    <x-ui.button variant="ghost" size="sm">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    </x-ui.button>
</x-ui.tooltip>

2. Tooltip positions:
<div class="flex space-x-4">
    <x-ui.tooltip text="Top tooltip" position="top">
        <x-ui.button>Top</x-ui.button>
    </x-ui.tooltip>

    <x-ui.tooltip text="Bottom tooltip" position="bottom">
        <x-ui.button>Bottom</x-ui.button>
    </x-ui.tooltip>

    <x-ui.tooltip text="Left tooltip" position="left">
        <x-ui.button>Left</x-ui.button>
    </x-ui.tooltip>

    <x-ui.tooltip text="Right tooltip" position="right">
        <x-ui.button>Right</x-ui.button>
    </x-ui.tooltip>
</div>

3. Light theme tooltip:
<x-ui.tooltip text="Light tooltip" theme="light">
    <x-ui.button variant="primary">Hover me</x-ui.button>
</x-ui.tooltip>

4. On icons:
<div class="flex space-x-2">
    <x-ui.tooltip text="Download PDF">
        <button class="p-2 rounded-lg hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
        </button>
    </x-ui.tooltip>

    <x-ui.tooltip text="Share">
        <button class="p-2 rounded-lg hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
        </button>
    </x-ui.tooltip>

    <x-ui.tooltip text="Delete">
        <button class="p-2 rounded-lg hover:bg-gray-100 text-red-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </x-ui.tooltip>
</div>

5. On status badges:
<div class="flex items-center space-x-2">
    <x-ui.tooltip text="Envelope has been sent to all recipients" position="bottom">
        <x-ui.badge variant="primary" :dot="true">Sent</x-ui.badge>
    </x-ui.tooltip>

    <x-ui.tooltip text="All recipients have signed" position="bottom">
        <x-ui.badge variant="success" :dot="true">Completed</x-ui.badge>
    </x-ui.tooltip>

    <x-ui.tooltip text="Envelope was voided by sender" position="bottom">
        <x-ui.badge variant="danger" :dot="true">Voided</x-ui.badge>
    </x-ui.tooltip>
</div>

6. On form labels:
<div>
    <label class="flex items-center space-x-1">
        <span>Email Subject</span>
        <x-ui.tooltip text="This will be the subject line of the email sent to recipients" position="right">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-ui.tooltip>
    </label>
    <input type="text" class="mt-1 w-full border rounded-lg px-3 py-2">
</div>

7. Table action tooltips:
<tr>
    <td>Contract.pdf</td>
    <td>
        <div class="flex space-x-1">
            <x-ui.tooltip text="View">
                <button class="p-1 hover:bg-gray-100 rounded">👁️</button>
            </x-ui.tooltip>
            <x-ui.tooltip text="Download">
                <button class="p-1 hover:bg-gray-100 rounded">⬇️</button>
            </x-ui.tooltip>
            <x-ui.tooltip text="Delete">
                <button class="p-1 hover:bg-gray-100 rounded text-red-600">🗑️</button>
            </x-ui.tooltip>
        </div>
    </td>
</tr>

8. Disabled button with tooltip:
<x-ui.tooltip text="You don't have permission to delete this envelope">
    <span class="inline-block">
        <x-ui.button variant="danger" :disabled="true">
            Delete
        </x-ui.button>
    </span>
</x-ui.tooltip>

Note: Wrap disabled elements in a <span> for tooltips to work properly.
--}}
