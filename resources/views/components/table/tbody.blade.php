@props([])

<tbody {{ $attributes->merge(['class' => 'bg-bg-primary divide-y divide-border-primary']) }}>
    {{ $slot }}
</tbody>
