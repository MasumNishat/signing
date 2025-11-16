@props([
    'type' => 'text',
    'name' => '',
    'label' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'helpText' => null,
    'icon' => null, // 'left' or 'right'
    'iconSlot' => null,
])

@php
    $inputClasses = 'block w-full rounded-lg border focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors disabled:opacity-50 disabled:cursor-not-allowed';

    if ($error) {
        $inputClasses .= ' border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500';
    } else {
        $inputClasses .= ' border-input-border bg-input-bg text-input-text placeholder-input-placeholder';
    }

    if ($icon === 'left') {
        $inputClasses .= ' pl-10';
    }
    if ($icon === 'right') {
        $inputClasses .= ' pr-10';
    }
@endphp

<div {{ $attributes->only('class') }}>
    @if($label)
        <x-form.label :for="$name" :required="$required">
            {{ $label }}
        </x-form.label>
    @endif

    <div class="relative {{ $label ? 'mt-1' : '' }}">
        @if($icon === 'left' && $iconSlot)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">{{ $iconSlot }}</span>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->except(['class', 'type', 'name', 'value', 'placeholder'])->merge(['class' => $inputClasses]) }}
            @if($required) required @endif
            @if($disabled) disabled @endif
            aria-describedby="{{ $error ? $name . '-error' : ($helpText ? $name . '-help' : '') }}"
            @if($error) aria-invalid="true" @endif
        />

        @if($icon === 'right' && $iconSlot)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <span class="text-gray-500">{{ $iconSlot }}</span>
            </div>
        @endif
    </div>

    @if($error)
        <x-form.validation-error :message="$error" :for="$name" />
    @elseif($helpText)
        <x-form.help-text :for="$name">{{ $helpText }}</x-form.help-text>
    @endif
</div>

{{--
Usage Examples:

1. Basic input:
<x-form.input name="email" label="Email Address" type="email" placeholder="you@example.com" />

2. Required input:
<x-form.input name="subject" label="Email Subject" :required="true" />

3. With error (from validation):
<x-form.input
    name="email"
    label="Email"
    type="email"
    :error="$errors->first('email')"
/>

4. With help text:
<x-form.input
    name="username"
    label="Username"
    help-text="Choose a unique username (3-20 characters)"
/>

5. With left icon:
<x-form.input name="email" label="Email" type="email" icon="left">
    <x-slot name="iconSlot">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </x-slot>
</x-form.input>

6. Disabled input:
<x-form.input name="id" label="Envelope ID" value="ENV-12345" :disabled="true" />

7. Number input:
<x-form.input name="amount" label="Amount" type="number" min="0" step="0.01" />

8. Password input:
<x-form.input name="password" label="Password" type="password" :required="true" />

9. In Alpine.js form:
<div x-data="{ formData: { email: '', subject: '' } }">
    <form @submit.prevent="submitForm()">
        <x-form.input
            name="email"
            label="Email"
            type="email"
            x-model="formData.email"
        />

        <x-form.input
            name="subject"
            label="Subject"
            x-model="formData.subject"
            class="mt-4"
        />

        <x-ui.button type="submit" class="mt-6">Submit</x-ui.button>
    </form>
</div>

10. Search input with icon:
<x-form.input name="search" type="search" placeholder="Search envelopes..." icon="left">
    <x-slot name="iconSlot">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </x-slot>
</x-form.input>
--}}
