{{-- Toast container - Place this in your layout file (app.blade.php) --}}
<div class="fixed top-4 right-4 z-50 space-y-3"
     x-data="{ notifications: $store.toast.notifications }"
     aria-live="assertive"
     aria-atomic="true">

    <template x-for="notification in $store.toast.notifications" :key="notification.id">
        <div x-show="notification.show"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-full opacity-0"
             class="max-w-sm w-full bg-toast-bg shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
             role="alert">

            <div class="p-4">
                <div class="flex items-start">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        <!-- Success Icon -->
                        <template x-if="notification.type === 'success'">
                            <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>

                        <!-- Error Icon -->
                        <template x-if="notification.type === 'error'">
                            <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>

                        <!-- Warning Icon -->
                        <template x-if="notification.type === 'warning'">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </template>

                        <!-- Info Icon -->
                        <template x-if="notification.type === 'info'">
                            <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                    </div>

                    <!-- Content -->
                    <div class="ml-3 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-toast-text" x-text="notification.message"></p>

                        <!-- Action Button (optional) -->
                        <template x-if="notification.action">
                            <button @click="notification.action.callback(); $store.toast.remove(notification.id)"
                                    class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-500"
                                    x-text="notification.action.text">
                            </button>
                        </template>
                    </div>

                    <!-- Close Button -->
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="$store.toast.remove(notification.id)"
                                class="rounded-md inline-flex text-toast-text hover:text-toast-text-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Progress Bar (optional) -->
            <template x-if="notification.showProgress">
                <div class="h-1 bg-gray-200">
                    <div class="h-full bg-primary-600 transition-all duration-100"
                         :style="`width: ${notification.progress || 0}%`"></div>
                </div>
            </template>
        </div>
    </template>
</div>

{{--
Usage Examples:

1. Setup in layout (app.blade.php):
<body>
    <!-- Your content -->

    <!-- Toast Container (place near closing </body>) -->
    <x-ui.toast />
</body>

2. Show success toast:
<x-ui.button @click="$store.toast.success('Envelope sent successfully!')">
    Send Envelope
</x-ui.button>

3. Show error toast:
<x-ui.button @click="$store.toast.error('Failed to send envelope')">
    Trigger Error
</x-ui.button>

4. Show warning toast:
<x-ui.button @click="$store.toast.warning('This action cannot be undone')">
    Show Warning
</x-ui.button>

5. Show info toast:
<x-ui.button @click="$store.toast.info('New features available!')">
    Show Info
</x-ui.button>

6. Toast with custom duration:
<x-ui.button @click="$store.toast.add({
    type: 'success',
    message: 'This will stay for 10 seconds',
    duration: 10000
})">
    Long Toast
</x-ui.button>

7. Toast with action button:
<x-ui.button @click="$store.toast.add({
    type: 'info',
    message: 'Do you want to undo this action?',
    action: {
        text: 'Undo',
        callback: () => { console.log('Undo clicked'); }
    },
    duration: 10000
})">
    Toast with Action
</x-ui.button>

8. In Alpine.js component:
<div x-data="{
        async sendEnvelope() {
            try {
                await $api.post('/envelopes', this.formData);
                $store.toast.success('Envelope sent successfully!');
                window.location.href = '/envelopes';
            } catch (error) {
                $store.toast.error('Failed to send envelope: ' + error.message);
            }
        }
    }">
    <form @submit.prevent="sendEnvelope()">
        <!-- Form fields -->
        <x-ui.button type="submit">Send</x-ui.button>
    </form>
</div>

9. After form submission (from controller):
// Controller
public function store(Request $request)
{
    // ... save envelope

    return redirect()->route('envelopes.index')
        ->with('toast', [
            'type' => 'success',
            'message' => 'Envelope created successfully!'
        ]);
}

// Layout (app.blade.php) - auto-show on page load
@if(session('toast'))
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('toast').{{ session('toast.type') }}('{{ session('toast.message') }}');
        });
    </script>
@endif

10. Multiple toasts:
<x-ui.button @click="
    $store.toast.success('First notification');
    setTimeout(() => $store.toast.info('Second notification'), 500);
    setTimeout(() => $store.toast.warning('Third notification'), 1000);
">
    Show Multiple Toasts
</x-ui.button>

11. Persistent toast (no auto-dismiss):
<x-ui.button @click="$store.toast.add({
    type: 'error',
    message: 'Critical error - please contact support',
    duration: null
})">
    Persistent Toast
</x-ui.button>

Note: Toast store methods (defined in alpine-setup.js):
- $store.toast.success(message, duration?)
- $store.toast.error(message, duration?)
- $store.toast.warning(message, duration?)
- $store.toast.info(message, duration?)
- $store.toast.add({ type, message, duration, action, showProgress })
- $store.toast.remove(id)
--}}
