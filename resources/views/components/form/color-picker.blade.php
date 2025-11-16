@props([
    'name' => 'color',
    'label' => null,
    'value' => '#000000',
    'required' => false,
    'error' => null,
])

<div {{ $attributes->only('class') }}>
    @if($label)
        <x-form.label :for="$name" :required="$required">{{ $label }}</x-form.label>
    @endif

    <div class="{{ $label ? 'mt-1' : '' }} flex items-center space-x-3">
        <input
            type="color"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            {{ $required ? 'required' : '' }}
            class="w-12 h-10 rounded border border-input-border cursor-pointer"
        />
        <input
            type="text"
            value="{{ old($name, $value) }}"
            @input="$el.previousElementSibling.value = $event.target.value"
            placeholder="#000000"
            pattern="^#[0-9A-Fa-f]{6}$"
            class="flex-1 rounded-lg border border-input-border bg-input-bg px-3 py-2 text-sm"
        />
    </div>

    @if($error)
        <x-form.validation-error :message="$error" :for="$name" />
    @endif
</div>
