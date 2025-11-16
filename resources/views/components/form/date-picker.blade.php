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
    :name="$name"
    :label="$label"
    :value="$value"
    :min="$min"
    :max="$max"
    :required="$required"
    :error="$error"
    {{ $attributes }}
/>
