@props([
    'placeholder' => null,
    'disabled' => false,
    'required' => false,
])

@php
    $selectClasses = 'block w-full rounded-lg border border-input-border bg-input-bg text-input-text focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors disabled:opacity-50 disabled:cursor-not-allowed px-4 py-2';
@endphp

<select
    {{ $attributes->merge(['class' => $selectClasses]) }}
    @if($required) required @endif
    @if($disabled) disabled @endif
>
    @if($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif

    {{ $slot }}
</select>

{{--
Usage Examples:

1. Basic select:
<x-ui.select>
    <option value="all">All</option>
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
</x-ui.select>

2. With x-model (Alpine.js):
<x-ui.select x-model="filters.status">
    <option value="all">All Statuses</option>
    <option value="sent">Sent</option>
    <option value="completed">Completed</option>
</x-ui.select>

3. With change event:
<x-ui.select x-model="filters.type" @change="loadData()">
    <option value="all">All Types</option>
    <option value="pdf">PDF</option>
    <option value="word">Word</option>
</x-ui.select>

4. With placeholder:
<x-ui.select placeholder="Select an option...">
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
</x-ui.select>

5. Required select:
<x-ui.select x-bind:required="true">
    <option value="">Choose...</option>
    <option value="yes">Yes</option>
    <option value="no">No</option>
</x-ui.select>

6. Disabled select:
<x-ui.select x-bind:disabled="true">
    <option>Read-only</option>
</x-ui.select>

7. With optgroups:
<x-ui.select x-model="selectedUser">
    <optgroup label="Administrators">
        <option value="1">Admin User</option>
    </optgroup>
    <optgroup label="Regular Users">
        <option value="2">Regular User</option>
    </optgroup>
</x-ui.select>

8. Sort order select:
<x-ui.select x-model="filters.sortBy">
    <option value="created_at">Upload Date</option>
    <option value="name">Name</option>
    <option value="size">Size</option>
</x-ui.select>
--}}
