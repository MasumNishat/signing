@props([
    'envelopes' => [],
    'limit' => 5,
    'loading' => false
])

<x-ui.card :padding="false">
    <div class="px-6 py-4 border-b border-card-border flex items-center justify-between">
        <h3 class="text-lg font-semibold text-text-primary">Recent Envelopes</h3>
        <a href="/envelopes" class="text-sm font-medium text-primary-600 hover:text-primary-500">View all</a>
    </div>

    <div x-show="{{ $loading ? 'true' : 'false' }}" class="p-6 space-y-4">
        @for($i = 0; $i < 3; $i++)
            <x-ui.skeleton type="text" class="h-12 w-full" />
        @endfor
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}">
        @if(count($envelopes) === 0)
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-text-primary">No envelopes yet</h3>
                <p class="mt-1 text-sm text-text-secondary">Get started by sending your first envelope.</p>
                <div class="mt-6">
                    <x-ui.button variant="primary" onclick="window.location.href='/envelopes/create'">
                        Send Envelope
                    </x-ui.button>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-primary">
                    <thead class="bg-bg-secondary">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Subject</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Recipients</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Created</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-bg-primary divide-y divide-border-primary">
                        @foreach(array_slice($envelopes, 0, $limit) as $envelope)
                            <tr class="hover:bg-bg-hover cursor-pointer transition-colors" onclick="window.location.href='/envelopes/{{ $envelope['id'] }}'">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-medium text-text-primary">{{ $envelope['email_subject'] ?? 'Untitled' }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                        @if($envelope['status'] === 'sent') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($envelope['status'] === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($envelope['status'] === 'voided') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                        @endif">
                                        {{ ucfirst($envelope['status']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                    {{ $envelope['recipients_count'] ?? 0 }} recipients
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                    {{ \Carbon\Carbon::parse($envelope['created_at'])->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/envelopes/{{ $envelope['id'] }}" class="text-primary-600 hover:text-primary-500" onclick="event.stopPropagation()">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-ui.card>
