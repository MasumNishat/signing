@props([
    'striped' => true,
    'hover' => true,
    'bordered' => false,
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-border-primary']) }}>
        {{ $slot }}
    </table>
</div>
