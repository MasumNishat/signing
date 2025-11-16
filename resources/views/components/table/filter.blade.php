@props(['filters' => []])

<div x-data="{ openFilters: false }" class="relative inline-block">
    <x-ui.button variant="ghost" size="sm" @click="openFilters = !openFilters">
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
        </svg>
        Filters
    </x-ui.button>

    <div x-show="openFilters" x-transition @click.outside="openFilters = false" class="absolute left-0 mt-2 w-64 bg-dropdown-bg border border-dropdown-border rounded-lg shadow-lg p-4 z-10" style="display: none;">
        {{ $slot }}
    </div>
</div>
