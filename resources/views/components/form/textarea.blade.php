@props([
    'name' => '',
    'label' => null,
    'value' => '',
    'placeholder' => '',
    'rows' => 4,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'helpText' => null,
    'maxlength' => null,
    'showCount' => false,
])

@php
    $textareaClasses = 'block w-full rounded-lg border focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors disabled:opacity-50 disabled:cursor-not-allowed resize-none';

    if ($error) {
        $textareaClasses .= ' border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500';
    } else {
        $textareaClasses .= ' border-input-border bg-input-bg text-input-text placeholder-input-placeholder';
    }
@endphp

<div {{ $attributes->only('class') }}
     @if($showCount && $maxlength) x-data="{ charCount: {{ strlen(old($name, $value)) }} }" @endif>
    @if($label)
        <x-form.label :for="$name" :required="$required">
            {{ $label }}
        </x-form.label>
    @endif

    <div class="{{ $label ? 'mt-1' : '' }}">
        <textarea
            name="{{ $name }}"
            id="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->except(['class', 'name', 'rows', 'placeholder'])->merge(['class' => $textareaClasses]) }}
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($showCount && $maxlength) x-on:input="charCount = $el.value.length" @endif
            aria-describedby="{{ $error ? $name . '-error' : ($helpText ? $name . '-help' : '') }}"
            @if($error) aria-invalid="true" @endif
        >{{ old($name, $value) }}</textarea>

        @if($showCount && $maxlength)
            <div class="mt-1 text-xs text-text-tertiary text-right">
                <span x-text="charCount"></span> / {{ $maxlength }} characters
            </div>
        @endif
    </div>

    @if($error)
        <x-form.validation-error :message="$error" :for="$name" />
    @elseif($helpText)
        <x-form.help-text :for="$name">{{ $helpText }}</x-form.help-text>
    @endif
</div>

{{--
Usage Examples:

1. Basic textarea:
<x-form.textarea name="description" label="Description" placeholder="Enter description..." />

2. With character count:
<x-form.textarea
    name="message"
    label="Message"
    :maxlength="500"
    :show-count="true"
/>

3. Custom rows:
<x-form.textarea name="notes" label="Notes" :rows="10" />

4. Required textarea:
<x-form.textarea
    name="reason"
    label="Void Reason"
    :required="true"
    help-text="Please explain why you are voiding this envelope"
/>

5. With error:
<x-form.textarea
    name="comments"
    label="Comments"
    :error="$errors->first('comments')"
/>

6. In Alpine.js form:
<div x-data="{ comment: '' }">
    <x-form.textarea
        name="comment"
        label="Your Comment"
        x-model="comment"
        :maxlength="1000"
        :show-count="true"
    />
    <p class="mt-2 text-sm" x-show="comment.length > 900">
        You're approaching the character limit!
    </p>
</div>
--}}
