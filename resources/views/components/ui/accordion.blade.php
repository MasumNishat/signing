@props([
    'title' => '',
    'open' => false,
])

<div x-data="{ open: @js($open) }"
     {{ $attributes->merge(['class' => 'border border-border-primary rounded-lg overflow-hidden']) }}>

    <!-- Accordion Header -->
    <button @click="open = !open"
            type="button"
            class="flex items-center justify-between w-full px-4 py-3 text-left bg-bg-primary hover:bg-bg-hover transition-colors">
        <span class="font-medium text-text-primary">{{ $title }}</span>

        <!-- Toggle Icon -->
        <svg class="w-5 h-5 text-text-secondary transition-transform duration-200"
             :class="open ? 'rotate-180' : ''"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Accordion Content -->
    <div x-show="open"
         x-collapse
         class="px-4 py-3 bg-bg-secondary border-t border-border-primary">
        {{ $slot }}
    </div>
</div>

{{--
Usage Examples:

1. Single accordion:
<x-ui.accordion title="What is DocuSign Clone?">
    <p class="text-text-secondary">
        DocuSign Clone is a document signing platform that allows you to send, sign, and manage documents electronically.
    </p>
</x-ui.accordion>

2. Accordion group (FAQ):
<div class="space-y-2">
    <x-ui.accordion title="How do I send an envelope?">
        <p class="text-text-secondary">
            Click on "Send Envelope" button, upload your documents, add recipients, and click send.
        </p>
    </x-ui.accordion>

    <x-ui.accordion title="Can I void a sent envelope?">
        <p class="text-text-secondary">
            Yes, you can void any envelope that hasn't been completed yet.
        </p>
    </x-ui.accordion>

    <x-ui.accordion title="How do I create a template?" :open="true">
        <p class="text-text-secondary">
            Go to Templates section, click "Create Template", and follow the wizard.
        </p>
    </x-ui.accordion>
</div>

3. With rich content:
<x-ui.accordion title="Envelope Details">
    <dl class="space-y-2">
        <div>
            <dt class="text-sm font-medium text-text-secondary">Status</dt>
            <dd class="text-sm text-text-primary">Completed</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-text-secondary">Created</dt>
            <dd class="text-sm text-text-primary">Jan 15, 2025</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-text-secondary">Recipients</dt>
            <dd class="text-sm text-text-primary">3 signers</dd>
        </div>
    </dl>
</x-ui.accordion>

4. Custom styling:
<x-ui.accordion title="Advanced Options" class="bg-primary-50 border-primary-200">
    <div class="space-y-3">
        <label class="flex items-center">
            <input type="checkbox" class="rounded">
            <span class="ml-2 text-sm">Enable auto-reminders</span>
        </label>
        <label class="flex items-center">
            <input type="checkbox" class="rounded">
            <span class="ml-2 text-sm">Require ID verification</span>
        </label>
    </div>
</x-ui.accordion>

5. Settings panel:
<div class="space-y-2">
    <x-ui.accordion title="🔔 Notification Settings" :open="true">
        <div class="space-y-3">
            <label class="flex items-center justify-between">
                <span class="text-sm">Email notifications</span>
                <input type="checkbox" class="rounded" checked>
            </label>
            <label class="flex items-center justify-between">
                <span class="text-sm">Browser notifications</span>
                <input type="checkbox" class="rounded">
            </label>
        </div>
    </x-ui.accordion>

    <x-ui.accordion title="🔒 Privacy Settings">
        <p class="text-sm text-text-secondary">
            Manage your privacy and data sharing preferences.
        </p>
    </x-ui.accordion>

    <x-ui.accordion title="⚙️ Account Settings">
        <p class="text-sm text-text-secondary">
            Update your account information and preferences.
        </p>
    </x-ui.accordion>
</div>
--}}
