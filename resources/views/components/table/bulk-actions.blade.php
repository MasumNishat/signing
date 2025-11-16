@props(['selectedCount' => 0])

<div x-data="{ selectAll: false, selected: [] }" class="flex items-center justify-between p-4 bg-bg-secondary border-b border-border-primary">
    <div class="flex items-center space-x-4">
        <label class="flex items-center">
            <input type="checkbox" x-model="selectAll" @change="selected = selectAll ? items.map(i => i.id) : []" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <span class="ml-2 text-sm text-text-primary">Select All</span>
        </label>
        <span x-show="selected.length > 0" x-text="selected.length + ' selected'" class="text-sm text-text-secondary"></span>
    </div>

    <div x-show="selected.length > 0" class="flex items-center space-x-2">
        {{ $slot }}
    </div>
</div>
