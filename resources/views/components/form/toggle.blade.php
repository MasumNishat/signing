@props([
    'name' => '',
    'label' => null,
    'checked' => false,
    'disabled' => false,
])

<div x-data="{ enabled: @js($checked) }" {{ $attributes->merge(['class' => 'flex items-center justify-between']) }}>
    @if($label)
        <label for="{{ $name }}" class="text-sm font-medium text-text-primary mr-4">{{ $label }}</label>
    @endif

    <button
        type="button"
        @click="enabled = !enabled"
        :class="enabled ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        role="switch"
        :aria-checked="enabled"
        {{ $disabled ? 'disabled' : '' }}>
        <span class="sr-only">{{ $label }}</span>
        <span
            :class="enabled ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
    </button>

    <input type="hidden" name="{{ $name }}" :value="enabled ? '1' : '0'" />
</div>

{{--
Usage:
<x-form.toggle name="notifications" label="Enable Email Notifications" :checked="true" />
--}}
