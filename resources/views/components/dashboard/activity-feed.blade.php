@props([
    'activities' => [],
    'limit' => 10,
    'loading' => false
])

<x-ui.card :padding="false">
    <div class="px-6 py-4 border-b border-card-border">
        <h3 class="text-lg font-semibold text-text-primary">Recent Activity</h3>
        <p class="text-sm text-text-secondary">Latest actions and events</p>
    </div>

    <div x-show="{{ $loading ? 'true' : 'false' }}" class="p-6 space-y-4">
        @for($i = 0; $i < 5; $i++)
            <x-ui.skeleton type="text" class="h-16 w-full" />
        @endfor
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}">
        @if(count($activities) === 0)
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-text-primary">No recent activity</h3>
                <p class="mt-1 text-sm text-text-secondary">Activity will appear here as you work.</p>
            </div>
        @else
            <div class="px-6 py-4 max-h-96 overflow-y-auto">
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach(array_slice($activities, 0, $limit) as $index => $activity)
                            <li>
                                <div class="relative pb-8">
                                    @if($index < min($limit, count($activities)) - 1)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-border-primary" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-bg-primary
                                                @if($activity['type'] === 'envelope_sent') bg-blue-500
                                                @elseif($activity['type'] === 'envelope_completed') bg-green-500
                                                @elseif($activity['type'] === 'envelope_voided') bg-red-500
                                                @elseif($activity['type'] === 'template_created') bg-purple-500
                                                @else bg-gray-500
                                                @endif">
                                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    @if($activity['type'] === 'envelope_sent')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    @elseif($activity['type'] === 'envelope_completed')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    @elseif($activity['type'] === 'envelope_voided')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    @endif
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm text-text-primary">
                                                    {{ $activity['description'] }}
                                                    @if(isset($activity['envelope_id']))
                                                        <a href="/envelopes/{{ $activity['envelope_id'] }}" class="font-medium text-primary-600 hover:text-primary-500">
                                                            View
                                                        </a>
                                                    @endif
                                                </p>
                                                <p class="mt-0.5 text-xs text-text-secondary">{{ $activity['user'] ?? 'System' }}</p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-xs text-text-secondary">
                                                <time datetime="{{ $activity['created_at'] }}">
                                                    {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}
                                                </time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
</x-ui.card>
