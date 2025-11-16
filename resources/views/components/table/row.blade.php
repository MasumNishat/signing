@props(['clickable' => false])

<tr {{ $attributes->merge(['class' => ($clickable ? 'cursor-pointer hover:bg-bg-hover' : '') . ' transition-colors']) }}>
    {{ $slot }}
</tr>
