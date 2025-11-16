@props(['placeholder' => 'Search...'])

<div class="relative">
    <input
        type="search"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'block w-full pl-10 pr-4 py-2 rounded-lg border border-input-border bg-input-bg text-input-text placeholder-input-placeholder focus:ring-2 focus:ring-primary-500 focus:border-transparent']) }}
    />
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
</div>
