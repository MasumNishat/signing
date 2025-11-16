@props(['item' => null])

<td class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap">
    <x-ui.dropdown align="right" width="48">
        <x-slot name="trigger">
            <x-ui.icon-button variant="ghost" size="sm">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
            </x-ui.icon-button>
        </x-slot>

        <x-slot name="content">
            {{ $slot }}
        </x-slot>
    </x-ui.dropdown>
</td>
