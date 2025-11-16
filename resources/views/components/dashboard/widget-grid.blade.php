@props([
    'columns' => 2,
    'gap' => 6
])

<div class="grid grid-cols-1 md:grid-cols-{{ $columns }} gap-{{ $gap }}">
    {{ $slot }}
</div>
