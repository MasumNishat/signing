@props([
    'summary' => [],
    'loading' => false
])

<x-ui.card>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-text-primary">Billing Summary</h3>
        <a href="/billing" class="text-sm font-medium text-primary-600 hover:text-primary-500">View details</a>
    </div>

    <div x-show="{{ $loading ? 'true' : 'false' }}" class="space-y-4">
        <x-ui.skeleton type="text" class="h-6 w-full" />
        <x-ui.skeleton type="text" class="h-6 w-full" />
        <x-ui.skeleton type="text" class="h-6 w-full" />
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}" class="space-y-4">
        <!-- Current Plan -->
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-text-secondary">Current Plan</p>
                <p class="mt-1 text-lg font-semibold text-text-primary">{{ $summary['plan_name'] ?? 'Free' }}</p>
            </div>
            @if(isset($summary['plan_name']) && $summary['plan_name'] !== 'Free')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    Active
                </span>
            @endif
        </div>

        <div class="border-t border-card-border pt-4">
            <!-- Envelope Usage -->
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-text-secondary">Envelopes Used</p>
                <p class="text-sm font-medium text-text-primary">
                    {{ $summary['envelopes_used'] ?? 0 }} / {{ $summary['envelopes_limit'] ?? 'Unlimited' }}
                </p>
            </div>
            @if(isset($summary['envelopes_limit']) && $summary['envelopes_limit'] !== 'Unlimited')
                @php
                $percentage = ($summary['envelopes_used'] / $summary['envelopes_limit']) * 100;
                @endphp
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-500 {{ $percentage >= 90 ? 'bg-red-500' : ($percentage >= 70 ? 'bg-orange-500' : 'bg-primary-500') }}"
                         style="width: {{ min($percentage, 100) }}%"></div>
                </div>
            @endif
        </div>

        <div class="border-t border-card-border pt-4">
            <!-- Next Billing Date -->
            @if(isset($summary['next_billing_date']))
                <div class="flex items-center justify-between">
                    <p class="text-sm text-text-secondary">Next Billing</p>
                    <p class="text-sm font-medium text-text-primary">
                        {{ \Carbon\Carbon::parse($summary['next_billing_date'])->format('M d, Y') }}
                    </p>
                </div>
            @endif

            <!-- Current Balance -->
            @if(isset($summary['current_balance']))
                <div class="flex items-center justify-between mt-2">
                    <p class="text-sm text-text-secondary">Current Balance</p>
                    <p class="text-lg font-semibold {{ $summary['current_balance'] > 0 ? 'text-green-600' : 'text-text-primary' }}">
                        ${{ number_format($summary['current_balance'], 2) }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Upgrade CTA -->
        @if(isset($summary['plan_name']) && $summary['plan_name'] === 'Free')
            <div class="border-t border-card-border pt-4">
                <x-ui.button variant="primary" size="sm" onclick="window.location.href='/billing/plans'" class="w-full">
                    Upgrade Plan
                </x-ui.button>
            </div>
        @endif
    </div>
</x-ui.card>
