@props([
    'name' => 'file',
    'label' => null,
    'accept' => null,
    'multiple' => false,
    'maxSize' => '10MB',
    'error' => null,
])

<div x-data="{
        files: [],
        isDragging: false,
        handleFiles(fileList) {
            this.files = Array.from(fileList);
        }
    }"
    @drop.prevent="isDragging = false; handleFiles($event.dataTransfer.files)"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    {{ $attributes }}>

    @if($label)
        <x-form.label :for="$name">{{ $label }}</x-form.label>
    @endif

    <div class="{{ $label ? 'mt-1' : '' }}">
        <div :class="isDragging ? 'border-primary-500 bg-primary-50' : 'border-input-border'"
             class="border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer hover:border-primary-500">
            <input
                type="file"
                name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                id="{{ $name }}"
                @if($accept) accept="{{ $accept }}" @endif
                @if($multiple) multiple @endif
                @change="handleFiles($el.files)"
                class="hidden"
            />

            <label for="{{ $name }}" class="cursor-pointer">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="mt-2 text-sm text-text-primary">
                    <span class="font-medium text-primary-600">Click to upload</span> or drag and drop
                </p>
                <p class="mt-1 text-xs text-text-secondary">{{ $accept ?? 'Any file type' }} (Max {{ $maxSize }})</p>
            </label>
        </div>

        <div x-show="files.length > 0" class="mt-4 space-y-2">
            <template x-for="(file, index) in files" :key="index">
                <div class="flex items-center justify-between p-2 bg-bg-secondary rounded-lg">
                    <span class="text-sm text-text-primary" x-text="file.name"></span>
                    <button @click="files.splice(index, 1)" class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    @if($error)
        <x-form.validation-error :message="$error" :for="$name" />
    @endif
</div>
