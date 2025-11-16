@props([
    'items' => [],
    'separator' => 'chevron', // 'chevron', 'slash', 'arrow'
])

@if(count($items) > 0)
    <nav aria-label="Breadcrumb" class="mb-6">
        <ol class="flex items-center space-x-2 text-sm">
            <!-- Home Link -->
            <li>
                <a href="/dashboard"
                   class="text-text-secondary hover:text-text-primary transition-colors flex items-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="sr-only">Home</span>
                </a>
            </li>

            @foreach($items as $index => $item)
                <!-- Separator -->
                <li>
                    @if($separator === 'chevron')
                        <svg class="w-4 h-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    @elseif($separator === 'slash')
                        <span class="text-text-tertiary">/</span>
                    @elseif($separator === 'arrow')
                        <svg class="w-4 h-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    @endif
                </li>

                <!-- Breadcrumb Item -->
                <li>
                    @if($index === count($items) - 1)
                        <!-- Current Page (not a link) -->
                        <span class="font-medium text-text-primary" aria-current="page">
                            {{ $item['label'] }}
                        </span>
                    @else
                        <!-- Link to parent pages -->
                        <a href="{{ $item['url'] }}"
                           class="text-text-secondary hover:text-text-primary transition-colors">
                            {{ $item['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif

{{--
Usage Examples:

1. Simple breadcrumb:
<x-layout.breadcrumbs :items="[
    ['label' => 'Envelopes', 'url' => '/envelopes'],
    ['label' => 'Create New', 'url' => '']
]" />

2. With custom separator:
<x-layout.breadcrumbs separator="slash" :items="[
    ['label' => 'Templates', 'url' => '/templates'],
    ['label' => 'Marketing', 'url' => '/templates/marketing'],
    ['label' => 'Edit Template', 'url' => '']
]" />

3. Deep navigation:
<x-layout.breadcrumbs separator="arrow" :items="[
    ['label' => 'Settings', 'url' => '/settings'],
    ['label' => 'Account', 'url' => '/settings/account'],
    ['label' => 'Billing', 'url' => '/settings/account/billing'],
    ['label' => 'Payment Methods', 'url' => '']
]" />

4. From controller (pass to view):
public function edit($id)
{
    $envelope = Envelope::findOrFail($id);

    return view('envelopes.edit', [
        'envelope' => $envelope,
        'breadcrumbs' => [
            ['label' => 'Envelopes', 'url' => route('envelopes.index')],
            ['label' => $envelope->email_subject, 'url' => route('envelopes.show', $id)],
            ['label' => 'Edit', 'url' => '']
        ]
    ]);
}

Then in view:
<x-layout.breadcrumbs :items="$breadcrumbs" />
--}}
