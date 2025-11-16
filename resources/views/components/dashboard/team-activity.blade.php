@props([
    'activities' => [],
    'loading' => false
])

<x-ui.card>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-text-primary">Team Activity</h3>
        <a href="/users" class="text-sm font-medium text-primary-600 hover:text-primary-500">View team</a>
    </div>

    <div x-show="{{ $loading ? 'true' : 'false' }}" class="space-y-3">
        @for($i = 0; $i < 5; $i++)
            <x-ui.skeleton type="text" class="h-14 w-full" />
        @endfor
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}">
        @if(count($activities) === 0)
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="mt-2 text-sm text-text-secondary">No team activity yet</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($activities as $activity)
                    <div class="flex items-center space-x-3 p-3 rounded-lg hover:bg-bg-hover transition-colors">
                        <div class="flex-shrink-0">
                            @if(isset($activity['user_avatar']))
                                <img src="{{ $activity['user_avatar'] }}" alt="{{ $activity['user_name'] }}" class="h-10 w-10 rounded-full">
                            @else
                                <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                    <span class="text-sm font-medium text-primary-600">
                                        {{ strtoupper(substr($activity['user_name'], 0, 2)) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-text-primary">
                                <span class="font-medium">{{ $activity['user_name'] }}</span>
                                <span class="text-text-secondary">{{ $activity['action'] }}</span>
                            </p>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-xs text-text-secondary">
                                    {{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans() }}
                                </span>
                                @if(isset($activity['envelope_subject']))
                                    <span class="text-xs text-text-secondary">•</span>
                                    <span class="text-xs text-primary-600 truncate">
                                        {{ $activity['envelope_subject'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                @if($activity['status'] === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($activity['status'] === 'pending') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                @elseif($activity['status'] === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                @endif">
                                {{ ucfirst($activity['status']) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(count($activities) > 5)
                <div class="mt-4 text-center">
                    <a href="/activity" class="text-sm text-primary-600 hover:text-primary-500 font-medium">
                        View all activity
                    </a>
                </div>
            @endif
        @endif
    </div>
</x-ui.card>
