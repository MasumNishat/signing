@props([])

<tbody {{ $attributes->merge(['class' => 'bg-primary divide-y divide-border-primary']) }}>
    {{ $slot }}
</tbody>
