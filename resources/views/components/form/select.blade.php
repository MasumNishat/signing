@props([
    'name' => '',
    'label' => null,
    'options' => [],
    'value' => '',
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'helpText' => null,
])

@php
    $selectClasses = 'block w-full rounded-lg border focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors disabled:opacity-50 disabled:cursor-not-allowed';

    if ($error) {
        $selectClasses .= ' border-red-300 text-red-900 focus:ring-red-500';
    } else {
        $selectClasses .= ' border-input-border bg-input-bg text-input-text';
    }
@endphp

<div {{ $attributes->only('class') }}>
    @if($label)
        <x-form.label :for="$name" :required="$required">
            {{ $label }}
        </x-form.label>
    @endif

    <div class="{{ $label ? 'mt-1' : '' }}">
        <select
            name="{{ $name }}"
            id="{{ $name }}"
            {{ $attributes->except(['class', 'name'])->merge(['class' => $selectClasses]) }}
            @if($required) required @endif
            @if($disabled) disabled @endif
            aria-describedby="{{ $error ? $name . '-error' : ($helpText ? $name . '-help' : '') }}"
            @if($error) aria-invalid="true" @endif
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}"
                        @if(old($name, $value) == $optionValue) selected @endif>
                    {{ $optionLabel }}
                </option>
            @endforeach

            {{ $slot }}
        </select>
    </div>

    @if($error)
        <x-form.validation-error :message="$error" :for="$name" />
    @elseif($helpText)
        <x-form.help-text :for="$name">{{ $helpText }}</x-form.help-text>
    @endif
</div>

{{--
Usage Examples:

1. Basic select:
<x-form.select
    name="status"
    label="Status"
    :options="['sent' => 'Sent', 'completed' => 'Completed', 'voided' => 'Voided']"
/>

2. With placeholder:
<x-form.select
    name="country"
    label="Country"
    placeholder="Select a country..."
    :options="$countries"
/>

3. With error:
<x-form.select
    name="role"
    label="Role"
    :options="['admin' => 'Administrator', 'user' => 'User']"
    :error="$errors->first('role')"
/>

4. Required select:
<x-form.select
    name="type"
    label="Document Type"
    :required="true"
    :options="['contract' => 'Contract', 'nda' => 'NDA', 'agreement' => 'Agreement']"
/>

5. With custom options (using slot):
<x-form.select name="recipient_type" label="Recipient Type">
    <option value="">Select type...</option>
    <optgroup label="Active">
        <option value="signer">Signer</option>
        <option value="approver">Approver</option>
    </optgroup>
    <optgroup label="Passive">
        <option value="viewer">Viewer</option>
        <option value="cc">Carbon Copy</option>
    </optgroup>
</x-form.select>

6. In Alpine.js:
<div x-data="{ selectedStatus: '' }">
    <x-form.select
        name="status"
        label="Filter by Status"
        x-model="selectedStatus"
        @change="filterEnvelopes()"
        :options="['all' => 'All', 'sent' => 'Sent', 'completed' => 'Completed']"
    />
</div>
--}}
