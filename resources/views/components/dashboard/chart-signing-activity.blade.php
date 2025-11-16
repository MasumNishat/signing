@props([
    'data' => [],
    'period' => 'week', // week, month, year
    'loading' => false
])

<x-ui.card>
    <div class="px-6 py-4 border-b border-card-border flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-text-primary">Signing Activity</h3>
            <p class="text-sm text-text-secondary">Documents signed over time</p>
        </div>
        <select class="text-sm border-border-primary rounded-md bg-bg-primary text-text-primary" x-model="period">
            <option value="week">Last 7 Days</option>
            <option value="month">Last 30 Days</option>
            <option value="year">Last 12 Months</option>
        </select>
    </div>

    <div x-show="{{ $loading ? 'true' : 'false' }}" class="p-6">
        <x-ui.skeleton type="chart" class="h-64" />
    </div>

    <div x-show="{{ $loading ? 'false' : 'true' }}" class="p-6">
        <div x-data="{
            period: '{{ $period }}',
            chartData: {{ json_encode($data) }},
            init() {
                this.renderChart();
            },
            renderChart() {
                // Placeholder for chart rendering
                console.log('Activity chart:', this.chartData, this.period);
            }
        }">
            @if(count($data) > 0)
                <!-- Simple line chart visualization -->
                @php
                $max = max(array_column($data, 'count'));
                @endphp

                <div class="space-y-4">
                    <div class="flex items-end justify-between space-x-2 h-48">
                        @foreach($data as $point)
                            @php
                            $height = $max > 0 ? ($point['count'] / $max) * 100 : 0;
                            @endphp
                            <div class="flex-1 flex flex-col items-center justify-end space-y-2 group">
                                <div class="relative">
                                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        {{ $point['count'] }} signed
                                    </div>
                                    <div class="w-full bg-primary-500 hover:bg-primary-600 rounded-t transition-all duration-300"
                                         style="height: {{ $height }}%; min-height: 4px;"></div>
                                </div>
                                <span class="text-xs text-text-secondary">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Summary stats -->
                    <div class="pt-4 border-t border-card-border">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-2xl font-bold text-text-primary">{{ array_sum(array_column($data, 'count')) }}</p>
                                <p class="text-xs text-text-secondary">Total Signed</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-text-primary">{{ count($data) > 0 ? round(array_sum(array_column($data, 'count')) / count($data), 1) : 0 }}</p>
                                <p class="text-xs text-text-secondary">Daily Average</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-text-primary">{{ max(array_column($data, 'count')) }}</p>
                                <p class="text-xs text-text-secondary">Peak Day</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <p class="mt-2 text-sm text-text-secondary">No activity data available</p>
                </div>
            @endif
        </div>
    </div>
</x-ui.card>
