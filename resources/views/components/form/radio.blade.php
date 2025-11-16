@props([
    'name' => '',
    'label' => null,
    'value' => '',
    'checked' => false,
    'disabled' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <input
        type="radio"
        name="{{ $name }}"
        id="{{ $name }}_{{ $value }}"
        value="{{ $value }}"
        {{ $checked ? 'checked' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        class="w-4 h-4 text-primary-600 bg-input-bg border-input-border focus:ring-primary-500 focus:ring-2 transition-colors disabled:opacity-50"
    />

    @if($label)
        <label for="{{ $name }}_{{ $value }}" class="ml-3 text-sm font-medium text-text-primary cursor-pointer">
            {{ $label }}
        </label>
    @endif
</div>

{{--
Usage:
<x-form.radio name="role" value="admin" label="Administrator" />
<x-form.radio name="role" value="user" label="User" :checked="true" />
--}}
