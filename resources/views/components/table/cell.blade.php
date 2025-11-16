@props(['align' => 'left'])

@php
$alignClasses = [
    'left' => 'text-left',
    'center' => 'text-center',
    'right' => 'text-right',
];
@endphp

<td {{ $attributes->merge(['class' => 'px-4 py-3 text-sm text-text-primary whitespace-nowrap ' . $alignClasses[$align]]) }}>
    {{ $slot }}
</td>
