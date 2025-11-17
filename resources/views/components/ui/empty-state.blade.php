@props([
    'icon' => 'document',
    'title' => 'No items found',
    'description' => null,
    'actionText' => null,
    'actionUrl' => null,
])

@php
    $icons = [
        'document' => '<svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'envelope' => '<svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
        'user' => '<svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        'template' => '<svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'search' => '<svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
        'folder' => '<svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>',
    ];
@endphp

<div class="text-center py-12">
    {!! $icons[$icon] ?? $icons['document'] !!}

    <h3 class="mt-4 text-lg font-medium text-text-primary">
        {{ $title }}
    </h3>

    @if($description)
        <p class="mt-2 text-sm text-text-secondary max-w-sm mx-auto">
            {{ $description }}
        </p>
    @endif

    @if($actionText && $actionUrl)
        <div class="mt-6">
            <x-ui.button
                variant="primary"
                onclick="window.location.href='{{ $actionUrl }}'"
            >
                {{ $actionText }}
            </x-ui.button>
        </div>
    @endif
</div>

{{--
Usage Examples:

1. Basic empty state:
<x-ui.empty-state
    title="No documents found"
    description="Upload your first document to get started"
/>

2. With action button:
<x-ui.empty-state
    icon="document"
    title="No documents found"
    description="Upload your first document to get started"
    action-text="Upload Document"
    action-url="/documents/upload"
/>

3. Different icons:
<x-ui.empty-state
    icon="envelope"
    title="No envelopes"
    description="Create your first envelope"
    action-text="Create Envelope"
    action-url="/envelopes/create"
/>

4. Search results:
<x-ui.empty-state
    icon="search"
    title="No results found"
    description="Try adjusting your search criteria"
/>

5. Users:
<x-ui.empty-state
    icon="user"
    title="No users found"
    action-text="Add User"
    action-url="/users/create"
/>

6. Templates:
<x-ui.empty-state
    icon="template"
    title="No templates"
    description="Create a template for reusable documents"
    action-text="Create Template"
    action-url="/templates/create"
/>
--}}
