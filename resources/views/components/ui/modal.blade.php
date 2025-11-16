@props([
    'name' => 'modal',
    'show' => false,
    'maxWidth' => 'md', // sm, md, lg, xl, 2xl, full
    'closeable' => true,
])

@php
    $maxWidthClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        'full' => 'max-w-full mx-4',
    ];
@endphp

<div x-data="{
        show: @js($show),
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-hidden');
            {{ $closeable ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-hidden');
        }
    })"
    @open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    @close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    @keydown.escape.window="show = false"
    @keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    @keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
    style="display: none;">

    <!-- Background Overlay -->
    <div x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 transform transition-all"
         @click="show = false">
        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
    </div>

    <!-- Modal Content -->
    <div x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="mb-6 bg-modal-bg rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:mx-auto {{ $maxWidthClasses[$maxWidth] }}"
         @click.stop>

        {{ $slot }}

    </div>
</div>

{{--
Usage Examples:

1. Basic modal:
<x-ui.modal name="confirm-delete" max-width="md">
    <div class="p-6">
        <h2 class="text-xl font-bold mb-4">Confirm Delete</h2>
        <p class="mb-6">Are you sure you want to delete this envelope?</p>

        <div class="flex justify-end space-x-3">
            <x-ui.button variant="ghost" @click="$dispatch('close-modal', 'confirm-delete')">
                Cancel
            </x-ui.button>
            <x-ui.button variant="danger" @click="deleteEnvelope()">
                Delete
            </x-ui.button>
        </div>
    </div>
</x-ui.modal>

2. Open modal with Alpine.js:
<x-ui.button @click="$dispatch('open-modal', 'confirm-delete')">
    Delete Envelope
</x-ui.button>

3. Close modal:
<x-ui.button @click="$dispatch('close-modal', 'confirm-delete')">
    Close
</x-ui.button>

4. Large modal for forms:
<x-ui.modal name="create-envelope" max-width="2xl">
    <div class="bg-modal-header px-6 py-4 border-b border-modal-border">
        <h2 class="text-xl font-bold text-text-primary">Create New Envelope</h2>
    </div>

    <div class="p-6">
        <form @submit.prevent="createEnvelope()">
            <!-- Form fields here -->

            <div class="flex justify-end space-x-3 mt-6">
                <x-ui.button variant="ghost" type="button" @click="$dispatch('close-modal', 'create-envelope')">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" type="submit">
                    Create Envelope
                </x-ui.button>
            </div>
        </form>
    </div>
</x-ui.modal>

5. Modal with header and footer:
<x-ui.modal name="view-details" max-width="lg">
    <!-- Header -->
    <div class="bg-modal-header px-6 py-4 border-b border-modal-border flex items-center justify-between">
        <h2 class="text-xl font-bold text-text-primary">Envelope Details</h2>
        <button @click="$dispatch('close-modal', 'view-details')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Body -->
    <div class="p-6 max-h-96 overflow-y-auto">
        <!-- Content here -->
    </div>

    <!-- Footer -->
    <div class="bg-modal-footer px-6 py-4 border-t border-modal-border flex justify-end space-x-3">
        <x-ui.button variant="ghost" @click="$dispatch('close-modal', 'view-details')">
            Close
        </x-ui.button>
        <x-ui.button variant="primary">
            Download PDF
        </x-ui.button>
    </div>
</x-ui.modal>

6. Confirmation modal:
<div x-data="{ showConfirm: false }">
    <x-ui.button @click="$dispatch('open-modal', 'confirm-action')">
        Void Envelope
    </x-ui.button>

    <x-ui.modal name="confirm-action">
        <div class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-red-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="text-lg font-medium mb-2">Are you sure?</h3>
            <p class="text-sm text-text-secondary mb-6">
                This action cannot be undone. This will permanently void the envelope.
            </p>
            <div class="flex justify-center space-x-3">
                <x-ui.button variant="ghost" @click="$dispatch('close-modal', 'confirm-action')">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="danger" @click="voidEnvelope(); $dispatch('close-modal', 'confirm-action')">
                    Void Envelope
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>

7. Non-closeable modal (for critical actions):
<x-ui.modal name="processing" :closeable="false" max-width="sm">
    <div class="p-6 text-center">
        <svg class="animate-spin h-12 w-12 mx-auto mb-4 text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-medium">Processing...</h3>
        <p class="text-sm text-text-secondary mt-2">Please wait while we process your request.</p>
    </div>
</x-ui.modal>
--}}
