@props([
    'name' => '',
    'label' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'error' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-start']) }}>
    <div class="flex items-center h-5">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $value }}"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            class="w-4 h-4 text-primary-600 bg-input-bg border-input-border rounded focus:ring-primary-500 focus:ring-2 transition-colors disabled:opacity-50"
            @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        />
    </div>

    @if($label)
        <div class="ml-3">
            <label for="{{ $name }}" class="text-sm font-medium text-text-primary cursor-pointer">
                {{ $label }}
            </label>
            @if($error)
                <x-form.validation-error :message="$error" :for="$name" />
            @endif
        </div>
    @endif
</div>

{{--
Usage: <x-form.checkbox name="agree" label="I agree to terms" :checked="true" />
--}}
