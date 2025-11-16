@props([
    'name' => '',
    'label' => null,
    'value' => '',
    'placeholder' => '',
    'suggestions' => [],
    'required' => false,
    'error' => null,
])

<div x-data="{
        query: '{{ old($name, $value) }}',
        suggestions: @js($suggestions),
        filteredSuggestions: [],
        showSuggestions: false,
        filter() {
            if (this.query.length > 0) {
                this.filteredSuggestions = this.suggestions.filter(s =>
                    s.toLowerCase().includes(this.query.toLowerCase())
                );
                this.showSuggestions = this.filteredSuggestions.length > 0;
            } else {
                this.showSuggestions = false;
            }
        },
        select(value) {
            this.query = value;
            this.showSuggestions = false;
        }
    }"
    @click.outside="showSuggestions = false"
    {{ $attributes }}>

    @if($label)
        <x-form.label :for="$name" :required="$required">{{ $label }}</x-form.label>
    @endif

    <div class="{{ $label ? 'mt-1' : '' }} relative">
        <input
            type="text"
            name="{{ $name }}"
            id="{{ $name }}"
            x-model="query"
            @input="filter()"
            @focus="filter()"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            class="block w-full rounded-lg border border-input-border bg-input-bg text-input-text px-3 py-2 focus:ring-2 focus:ring-primary-500"
        />

        <div x-show="showSuggestions"
             x-transition
             class="absolute z-10 w-full mt-1 bg-dropdown-bg border border-dropdown-border rounded-lg shadow-lg max-h-60 overflow-auto">
            <template x-for="suggestion in filteredSuggestions" :key="suggestion">
                <button type="button"
                        @click="select(suggestion)"
                        x-text="suggestion"
                        class="block w-full text-left px-4 py-2 text-sm hover:bg-dropdown-hover transition-colors">
                </button>
            </template>
        </div>
    </div>

    @if($error)
        <x-form.validation-error :message="$error" :for="$name" />
    @endif
</div>
