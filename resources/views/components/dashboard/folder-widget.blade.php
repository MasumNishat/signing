@props([
    'folders' => [],
    'loading' => false
])

<x-ui.card>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-text-primary">Folders</h3>
        <a href="/folders" class="text-sm font-medium text-primary-600 hover:text-primary-500">Manage</a>
    </div>

    <div x-show="{{ $loading ? 'true' : 'false' }}" class="space-y-2">
        @for($i = 0; $i < 5; $i++)
            <x-ui.skeleton type="text" class="h-10 w-full" />
        @endfor
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}">
        @if(count($folders) === 0)
            <div class="text-center py-8">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <p class="mt-2 text-sm text-text-secondary">No custom folders</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($folders as $folder)
                    <a href="/folders/{{ $folder['id'] }}"
                       class="flex items-center justify-between p-3 rounded-lg hover:bg-bg-hover transition-colors group">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-{{ $folder['color'] ?? 'gray' }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-text-primary">{{ $folder['name'] }}</p>
                                @if(isset($folder['item_count']))
                                    <p class="text-xs text-text-secondary">{{ $folder['item_count'] }} items</p>
                                @endif
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-ui.card>
