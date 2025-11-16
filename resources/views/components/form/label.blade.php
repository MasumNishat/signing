@props([
    'for' => '',
    'required' => false,
])

<label for="{{ $for }}"
       {{ $attributes->merge(['class' => 'block text-sm font-medium text-text-primary']) }}>
    {{ $slot }}
    @if($required)
        <span class="text-red-500 ml-1">*</span>
    @endif
</label>

{{--
Usage Examples:

1. Basic label:
<x-form.label for="email">Email Address</x-form.label>

2. Required field:
<x-form.label for="subject" :required="true">Subject</x-form.label>

3. With custom styling:
<x-form.label for="name" class="text-lg font-bold">Your Name</x-form.label>

4. Standalone usage:
<x-form.label for="description">
    Description
</x-form.label>
<textarea id="description" name="description" rows="4" class="mt-1 w-full"></textarea>
--}}
