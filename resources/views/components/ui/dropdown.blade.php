@props([
    'align' => 'right', // left, right, center
    'width' => '48', // Width in rem (12, 24, 32, 48, 64, 96)
    'contentClasses' => 'py-1 bg-dropdown-bg border border-dropdown-border',
])

@php
    $alignmentClasses = [
        'left' => 'origin-top-left left-0',
        'right' => 'origin-top-right right-0',
        'center' => 'origin-top left-1/2 -translate-x-1/2',
    ];

    $widthClasses = [
        '12' => 'w-12',
        '24' => 'w-24',
        '32' => 'w-32',
        '48' => 'w-48',
        '64' => 'w-64',
        '96' => 'w-96',
    ];
@endphp

<div class="relative"
     x-data="{ open: false }"
     @click.outside="open = false"
     @close.stop="open = false">

    <!-- Trigger -->
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <!-- Dropdown Content -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 mt-2 {{ $widthClasses[$width] ?? $widthClasses['48'] }} rounded-lg shadow-lg {{ $alignmentClasses[$align] ?? $alignmentClasses['right'] }}"
         style="display: none;"
         @click="open = false">
        <div class="rounded-lg ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>

{{--
Usage Examples:

1. Basic dropdown:
<x-ui.dropdown>
    <x-slot name="trigger">
        <x-ui.button variant="ghost">
            Options
            <svg class="ml-2 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </x-ui.button>
    </x-slot>

    <x-slot name="content">
        <a href="/profile" class="block px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover">Profile</a>
        <a href="/settings" class="block px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover">Settings</a>
        <a href="/logout" class="block px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover">Logout</a>
    </x-slot>
</x-ui.dropdown>

2. User dropdown (with dividers):
<x-ui.dropdown align="right">
    <x-slot name="trigger">
        <button class="flex items-center space-x-2">
            <img src="/avatar.jpg" class="w-8 h-8 rounded-full" alt="User">
            <span>John Doe</span>
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="px-4 py-3 border-b border-dropdown-border">
            <p class="text-sm font-medium text-text-primary">John Doe</p>
            <p class="text-xs text-text-secondary">john@example.com</p>
        </div>

        <a href="/profile" class="block px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover">
            Profile
        </a>
        <a href="/settings" class="block px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover">
            Settings
        </a>

        <div class="border-t border-dropdown-border">
            <button @click="$store.auth.logout()"
                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-dropdown-hover">
                Logout
            </button>
        </div>
    </x-slot>
</x-ui.dropdown>

3. Actions dropdown (left aligned):
<x-ui.dropdown align="left" width="48">
    <x-slot name="trigger">
        <button class="p-2 rounded-lg hover:bg-gray-100">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
            </svg>
        </button>
    </x-slot>

    <x-slot name="content">
        <button @click="editItem()" class="block w-full text-left px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover">
            Edit
        </button>
        <button @click="duplicateItem()" class="block w-full text-left px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover">
            Duplicate
        </button>
        <button @click="archiveItem()" class="block w-full text-left px-4 py-2 text-sm text-text-primary hover:bg-dropdown-hover">
            Archive
        </button>
        <div class="border-t border-dropdown-border my-1"></div>
        <button @click="deleteItem()" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-dropdown-hover">
            Delete
        </button>
    </x-slot>
</x-ui.dropdown>

4. Filter dropdown with checkboxes:
<x-ui.dropdown width="64">
    <x-slot name="trigger">
        <x-ui.button variant="ghost">
            Filter
            <svg class="ml-2 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
        </x-ui.button>
    </x-slot>

    <x-slot name="content">
        <div class="px-4 py-3 border-b border-dropdown-border">
            <p class="text-sm font-medium text-text-primary">Filter by Status</p>
        </div>

        <div class="px-4 py-2 space-y-2">
            <label class="flex items-center">
                <input type="checkbox" class="rounded" x-model="filters.sent">
                <span class="ml-2 text-sm text-text-primary">Sent</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" class="rounded" x-model="filters.draft">
                <span class="ml-2 text-sm text-text-primary">Draft</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" class="rounded" x-model="filters.completed">
                <span class="ml-2 text-sm text-text-primary">Completed</span>
            </label>
        </div>

        <div class="px-4 py-3 border-t border-dropdown-border flex justify-end space-x-2">
            <x-ui.button variant="ghost" size="sm" @click="clearFilters()">Clear</x-ui.button>
            <x-ui.button variant="primary" size="sm" @click="applyFilters()">Apply</x-ui.button>
        </div>
    </x-slot>
</x-ui.dropdown>

5. Icon-only trigger:
<x-ui.dropdown align="right" width="48">
    <x-slot name="trigger">
        <button class="p-2 rounded-lg hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
            </svg>
        </button>
    </x-slot>

    <x-slot name="content">
        <a href="#" class="flex items-center px-4 py-2 text-sm hover:bg-dropdown-hover">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            View
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-sm hover:bg-dropdown-hover">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-dropdown-hover">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete
        </a>
    </x-slot>
</x-ui.dropdown>
--}}
