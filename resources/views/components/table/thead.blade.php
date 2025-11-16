@props(['sticky' => false])

<thead {{ $attributes->merge(['class' => 'bg-bg-secondary ' . ($sticky ? 'sticky top-0 z-10' : '')]) }}>
    {{ $slot }}
</thead>
