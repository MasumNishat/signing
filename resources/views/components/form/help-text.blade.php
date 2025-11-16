@props([
    'for' => '',
])

<p id="{{ $for }}-help"
   {{ $attributes->merge(['class' => 'mt-1 text-sm text-text-secondary']) }}>
    {{ $slot }}
</p>

{{--
Usage Examples:

1. Basic help text:
<x-form.help-text for="password">
    Password must be at least 8 characters long
</x-form.help-text>

2. With form input:
<x-form.input name="username" label="Username" />
<x-form.help-text for="username">
    Choose a unique username (3-20 characters, alphanumeric only)
</x-form.help-text>

3. Custom styling:
<x-form.help-text for="email" class="text-blue-600">
    We'll never share your email with anyone else
</x-form.help-text>
--}}
