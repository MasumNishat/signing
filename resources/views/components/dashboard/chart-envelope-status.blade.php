@props([
    'data' => [],
    'loading' => false
])

<x-ui.card>
    <div class="px-6 py-4 border-b border-card-border">
        <h3 class="text-lg font-semibold text-text-primary">Envelope Status Distribution</h3>
        <p class="text-sm text-text-secondary">Overview of envelope statuses</p>
    </div>

    <div x-show="{{ $loading ? 'true' : 'false' }}" class="p-6">
        <x-ui.skeleton type="chart" class="h-64" />
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}" class="p-6">
        <div x-data="{
            chartData: {{ json_encode($data) }},
            init() {
                // Chart rendering using Chart.js (if available) or fallback to simple bars
                this.renderChart();
            },
            renderChart() {
                // Placeholder for chart rendering
                console.log('Chart data:', this.chartData);
            }
        }" class="space-y-4">
            <!-- Simple bar chart fallback (no Chart.js dependency) -->
            @php
            $total = array_sum($data);
            $statuses = [
                'draft' => ['label' => 'Draft', 'color' => 'bg-gray-500'],
                'sent' => ['label' => 'Sent', 'color' => 'bg-blue-500'],
                'delivered' => ['label' => 'Delivered', 'color' => 'bg-indigo-500'],
                'completed' => ['label' => 'Completed', 'color' => 'bg-green-500'],
                'voided' => ['label' => 'Voided', 'color' => 'bg-red-500'],
            ];
            @endphp

            @foreach($statuses as $key => $status)
                @php
                $count = $data[$key] ?? 0;
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                @endphp

                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-text-primary">{{ $status['label'] }}</span>
                        <span class="text-text-secondary">{{ $count }} ({{ $percentage }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="{{ $status['color'] }} h-2 rounded-full transition-all duration-500"
                             style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endforeach

            @if($total === 0)
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="mt-2 text-sm text-text-secondary">No envelope data available</p>
                </div>
            @endif
        </div>
    </div>
</x-ui.card>
