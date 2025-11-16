@props([
    'name' => '',
    'label' => null,
    'options' => [],
    'selected' => [],
    'error' => null,
])

<div x-data="{
        selectedOptions: @js($selected),
        toggle(value) {
            if (this.selectedOptions.includes(value)) {
                this.selectedOptions = this.selectedOptions.filter(v => v !== value);
            } else {
                this.selectedOptions.push(value);
            }
        }
    }"
    {{ $attributes }}>

    @if($label)
        <x-form.label :for="$name">{{ $label }}</x-form.label>
    @endif

    <div class="{{ $label ? 'mt-1' : '' }} border border-input-border rounded-lg p-2 bg-input-bg max-h-48 overflow-y-auto">
        @foreach($options as $value => $label)
            <label class="flex items-center p-2 hover:bg-bg-hover rounded cursor-pointer">
                <input
                    type="checkbox"
                    :checked="selectedOptions.includes('{{ $value }}')"
                    @change="toggle('{{ $value }}')"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="ml-2 text-sm text-text-primary">{{ $label }}</span>
            </label>
        @endforeach

        <template x-for="option in selectedOptions" :key="option">
            <input type="hidden" name="{{ $name }}[]" :value="option" />
        </template>
    </div>

    @if($error)
        <x-form.validation-error :message="$error" :for="$name" />
    @endif
</div>
