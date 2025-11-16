@props(['column' => '', 'sortBy' => '', 'sortDirection' => 'asc'])

<th {{ $attributes->merge(['class' => 'px-4 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider cursor-pointer hover:bg-bg-hover transition-colors']) }}
    @click="sortBy = '{{ $column }}'; sortDirection = sortBy === '{{ $column }}' && sortDirection === 'asc' ? 'desc' : 'asc'">
    <div class="flex items-center space-x-1">
        <span>{{ $slot }}</span>
        <svg class="w-4 h-4" :class="sortBy === '{{ $column }}' ? 'text-primary-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path x-show="sortBy !== '{{ $column }}' || sortDirection === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            <path x-show="sortBy === '{{ $column }}' && sortDirection === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </div>
</th>
