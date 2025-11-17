@props([
    'type' => 'text',
    'placeholder' => '',
    'disabled' => false,
    'required' => false,
])

@php
    $inputClasses = 'block w-full rounded-lg border border-input-border bg-input-bg text-input-text placeholder-input-placeholder focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors disabled:opacity-50 disabled:cursor-not-allowed px-4 py-2';
@endphp

<input
    type="{{ $type }}"
    placeholder="{{ $placeholder }}"
    {{ $attributes->merge(['class' => $inputClasses]) }}
    @if($required) required @endif
    @if($disabled) disabled @endif
/>

{{--
Usage Examples:

1. Basic input:
<x-ui.input type="text" placeholder="Search..." />

2. With x-model (Alpine.js):
<x-ui.input type="search" placeholder="Search documents..." x-model="filters.search" />

3. With event listener:
<x-ui.input
    type="search"
    placeholder="Search..."
    x-model="search"
    @input.debounce.300ms="performSearch()"
/>

4. Email input:
<x-ui.input type="email" placeholder="you@example.com" />

5. Number input:
<x-ui.input type="number" placeholder="0" min="0" step="1" />

6. Required input:
<x-ui.input type="text" placeholder="Required field" x-bind:required="true" />

7. Disabled input:
<x-ui.input type="text" value="Read-only" x-bind:disabled="true" />

8. Password input:
<x-ui.input type="password" placeholder="Enter password" />

9. With custom classes:
<x-ui.input type="text" class="max-w-md" placeholder="Custom width" />

10. Date input:
<x-ui.input type="date" />
--}}
