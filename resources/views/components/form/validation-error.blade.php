@props([
    'message' => '',
    'for' => '',
])

@if($message)
    <p id="{{ $for }}-error"
       {{ $attributes->merge(['class' => 'mt-1 text-sm text-red-600']) }}
       role="alert">
        {{ $message }}
    </p>
@endif

{{--
Usage Examples:

1. Basic error:
<x-form.validation-error :message="$errors->first('email')" for="email" />

2. Custom error message:
<x-form.validation-error message="This field is required" for="subject" />

3. With Alpine.js:
<div x-data="{ error: '' }">
    <input type="email" x-model="email" @blur="validateEmail()">
    <x-form.validation-error :message="error" for="email" />
</div>

4. Multiple errors:
@foreach($errors->get('email') as $error)
    <x-form.validation-error :message="$error" for="email" />
@endforeach
--}}
