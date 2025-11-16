@props([
    'data' => null, // Laravel paginator object
    'simple' => false, // Simple pagination (prev/next only)
    'showText' => true, // Show "Showing X to Y of Z results"
])

@if($data && $data->hasPages())
    <div {{ $attributes->merge(['class' => 'flex items-center justify-between']) }}>
        @if($showText && !$simple)
            <!-- Results Text -->
            <div class="text-sm text-text-secondary">
                Showing
                <span class="font-medium text-text-primary">{{ $data->firstItem() }}</span>
                to
                <span class="font-medium text-text-primary">{{ $data->lastItem() }}</span>
                of
                <span class="font-medium text-text-primary">{{ $data->total() }}</span>
                results
            </div>
        @endif

        <!-- Pagination Links -->
        <nav class="@if($showText && !$simple) ml-auto @endif">
            <ul class="inline-flex items-center -space-x-px rounded-lg shadow-sm">
                {{-- Previous Page Link --}}
                @if($data->onFirstPage())
                    <li>
                        <span class="px-3 py-2 ml-0 leading-tight text-text-tertiary bg-bg-secondary border border-border-primary rounded-l-lg cursor-not-allowed">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </li>
                @else
                    <li>
                        <a href="{{ $data->previousPageUrl() }}"
                           class="px-3 py-2 ml-0 leading-tight text-text-secondary bg-bg-primary border border-border-primary rounded-l-lg hover:bg-bg-hover hover:text-text-primary transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                @endif

                @if(!$simple)
                    {{-- Page Number Links --}}
                    @foreach($data->getUrlRange(1, $data->lastPage()) as $page => $url)
                        @if($page == $data->currentPage())
                            <li>
                                <span class="px-3 py-2 leading-tight text-primary-600 bg-primary-50 border border-primary-300 font-medium">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}"
                                   class="px-3 py-2 leading-tight text-text-secondary bg-bg-primary border border-border-primary hover:bg-bg-hover hover:text-text-primary transition-colors">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif

                {{-- Next Page Link --}}
                @if($data->hasMorePages())
                    <li>
                        <a href="{{ $data->nextPageUrl() }}"
                           class="px-3 py-2 leading-tight text-text-secondary bg-bg-primary border border-border-primary rounded-r-lg hover:bg-bg-hover hover:text-text-primary transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                @else
                    <li>
                        <span class="px-3 py-2 leading-tight text-text-tertiary bg-bg-secondary border border-border-primary rounded-r-lg cursor-not-allowed">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif

{{--
Usage Examples:

1. Basic pagination (from Laravel controller):
// Controller
public function index()
{
    $envelopes = Envelope::paginate(15);
    return view('envelopes.index', compact('envelopes'));
}

// View
<x-ui.pagination :data="$envelopes" />

2. Simple pagination (prev/next only):
<x-ui.pagination :data="$envelopes" :simple="true" />

3. Without results text:
<x-ui.pagination :data="$envelopes" :show-text="false" />

4. With Alpine.js AJAX pagination:
<div x-data="{
        currentPage: 1,
        perPage: 15,
        total: 0,
        envelopes: [],
        async loadEnvelopes() {
            const response = await $api.get(`/envelopes?page=${this.currentPage}&per_page=${this.perPage}`);
            this.envelopes = response.data.data;
            this.total = response.data.meta.total;
        }
    }"
    x-init="loadEnvelopes()">

    <!-- Content -->
    <div class="space-y-4">
        <template x-for="envelope in envelopes" :key="envelope.id">
            <x-ui.card>
                <p x-text="envelope.email_subject"></p>
            </x-ui.card>
        </template>
    </div>

    <!-- Custom Pagination -->
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-text-secondary">
            Showing
            <span class="font-medium text-text-primary" x-text="((currentPage - 1) * perPage) + 1"></span>
            to
            <span class="font-medium text-text-primary" x-text="Math.min(currentPage * perPage, total)"></span>
            of
            <span class="font-medium text-text-primary" x-text="total"></span>
            results
        </div>

        <nav>
            <ul class="inline-flex -space-x-px">
                <li>
                    <button @click="currentPage--; loadEnvelopes()"
                            :disabled="currentPage === 1"
                            :class="currentPage === 1 ? 'cursor-not-allowed opacity-50' : ''"
                            class="px-3 py-2 rounded-l-lg border">
                        Previous
                    </button>
                </li>
                <li>
                    <button @click="currentPage++; loadEnvelopes()"
                            :disabled="currentPage * perPage >= total"
                            :class="currentPage * perPage >= total ? 'cursor-not-allowed opacity-50' : ''"
                            class="px-3 py-2 rounded-r-lg border">
                        Next
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</div>

5. Centered pagination:
<div class="flex justify-center">
    <x-ui.pagination :data="$envelopes" :show-text="false" />
</div>

6. With per-page selector:
<div class="flex items-center justify-between">
    <div class="flex items-center space-x-2">
        <span class="text-sm text-text-secondary">Show</span>
        <select class="border rounded px-2 py-1 text-sm" onchange="window.location.href = this.value">
            <option value="?per_page=10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
            <option value="?per_page=25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
            <option value="?per_page=50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
            <option value="?per_page=100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
        </select>
        <span class="text-sm text-text-secondary">entries</span>
    </div>

    <x-ui.pagination :data="$envelopes" :show-text="false" />
</div>

7. Mobile responsive:
<div class="flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0">
    <div class="text-sm text-text-secondary hidden sm:block">
        Showing {{ $envelopes->firstItem() }} to {{ $envelopes->lastItem() }} of {{ $envelopes->total() }} results
    </div>

    <x-ui.pagination :data="$envelopes" :show-text="false" />
</div>
--}}
