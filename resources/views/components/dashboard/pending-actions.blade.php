@props([
    'actions' => [],
    'loading' => false
])

<x-ui.card>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-text-primary">Pending Actions</h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
            {{ count($actions) }} pending
        </span>
    </div>

    <div x-show="{{ $loading ? 'true' : 'false' }}" class="space-y-3">
        @for($i = 0; $i < 3; $i++)
            <x-ui.skeleton type="text" class="h-16 w-full" />
        @endfor
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}">
        @if(count($actions) === 0)
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-text-primary">All caught up!</h3>
                <p class="mt-1 text-sm text-text-secondary">No pending actions at this time.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($actions as $action)
                    <div class="p-4 bg-bg-secondary rounded-lg hover:bg-bg-hover transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full
                                        @if($action['priority'] === 'high') bg-red-100 text-red-600
                                        @elseif($action['priority'] === 'medium') bg-orange-100 text-orange-600
                                        @else bg-blue-100 text-blue-600
                                        @endif">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($action['type'] === 'sign')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            @elseif($action['type'] === 'approve')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            @endif
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-text-primary">{{ $action['title'] }}</p>
                                    <p class="text-xs text-text-secondary mt-1">{{ $action['description'] }}</p>
                                    <div class="flex items-center space-x-4 mt-2">
                                        <span class="inline-flex items-center text-xs text-text-secondary">
                                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ $action['due_date'] ?? 'No due date' }}
                                        </span>
                                        @if(isset($action['from']))
                                            <span class="inline-flex items-center text-xs text-text-secondary">
                                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                {{ $action['from'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                <a href="{{ $action['url'] }}"
                                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    {{ $action['action_label'] ?? 'View' }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-ui.card>
