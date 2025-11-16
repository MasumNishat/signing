@props([
    'name' => 'date',
    'label' => null,
    'value' => '',
    'min' => null,
    'max' => null,
    'required' => false,
    'error' => null,
])

<x-form.input
    type="date"
    x-bind:name="$name"
    x-bind:label="$label"
    x-bind:value="$value"
    x-bind:min="$min"
    x-bind:max="$max"
    x-bind:required="$required"
    x-bind:error="$error"
    {{ $attributes }}
/>
