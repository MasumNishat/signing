@props([
    'name' => 'time',
    'label' => null,
    'value' => '',
    'required' => false,
    'error' => null,
])

<x-form.input
    type="time"
    :name="$name"
    :label="$label"
    :value="$value"
    :required="$required"
    :error="$error"
    {{ $attributes }}
/>
