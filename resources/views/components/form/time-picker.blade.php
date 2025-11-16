@props([
    'name' => 'time',
    'label' => null,
    'value' => '',
    'required' => false,
    'error' => null,
])

<x-form.input
    type="time"
    x-bind:name="$name"
    x-bind:label="$label"
    x-bind:value="$value"
    x-bind:required="$required"
    x-bind:error="$error"
    {{ $attributes }}
/>
