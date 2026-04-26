<x-app-layout title="{{ __('Incoming Requests') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-8 space-y-4 lg:mb-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ __('Incoming Requests') }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-snug text-slate-600 dark:text-navy-300">
                        {{ __('Provider requests page subtitle') }}
                    </p>
                </div>
                @if ($pendingProofCount > 0)
                    <div
                        class="inline-flex shrink-0 items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm dark:border-navy-600 dark:bg-navy-800/60 dark:text-navy-100"
                        role="status">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                            <i class="fa-solid fa-clock text-[0.85em] text-primary dark:text-accent" aria-hidden="true"></i>
                        </span>
                        <span>{{ trans_choice('provider_requests_awaiting_proof_summary', $pendingProofCount, ['count' => $pendingProofCount]) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="card mt-0 overflow-hidden p-0 sm:p-0">
                @include('provider.requests.partials.request-filters')

                @if ($requests->isEmpty())
                    <div class="px-6 py-14 text-center text-slate-500 dark:text-navy-300">
                        @if ($hasActiveFilters)
                            <i class="fa-solid fa-filter-circle-xmark mb-3 text-4xl text-slate-300 dark:text-navy-500" aria-hidden="true"></i>
                            <p class="font-medium">{{ __('No requests match your filters.') }}</p>
                            <a href="{{ route('provider.requests.index') }}"
                                class="mt-4 inline-flex items-center justify-center rounded-lg border border-primary/35 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/10 dark:border-accent/35 dark:text-accent-light dark:hover:bg-accent/10">
                                {{ __('Clear filters') }}
                            </a>
                        @else
                            <i class="fa-solid fa-inbox mb-3 text-4xl text-slate-300 dark:text-navy-500" aria-hidden="true"></i>
                            <p class="font-medium">{{ __('No requests found.') }}</p>
                        @endif
                    </div>
                @else
                    {{-- Mobile / tablet: cards --}}
                    <div class="flex flex-col gap-4 p-4 sm:p-5 lg:hidden">
                        @foreach ($requests as $request)
                            @include('provider.requests.partials.incoming-request-card', ['request' => $request])
                        @endforeach
                    </div>

                    {{-- Desktop: table --}}
                    <div class="is-scrollbar-hidden hidden min-w-full overflow-x-auto rounded-lg lg:block">
                        <table class="is-hoverable w-full text-start">
                            <thead>
                                <tr>
                                    <th scope="col" class="whitespace-nowrap rounded-ss-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Request') }}</th>
                                    <th scope="col" class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Reference') }}</th>
                                    <th scope="col" class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Items') }}</th>
                                    <th scope="col" class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Total') }}</th>
                                    <th scope="col" class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Date') }}</th>
                                    <th scope="col" class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Status') }}</th>
                                    <th scope="col" class="whitespace-nowrap rounded-se-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    @php
                                        $qty = (int) $request->items->sum('quantity');
                                        $amount = number_format((float) $request->reserved_amount, 2);
                                        $refLabel = \App\Support\PseudonymousRequestId::make($request->id);
                                        $proofPending = $request->needsProviderFulfillmentProof();
                                    @endphp
                                    <tr @class([
                                        'border-b transition-colors duration-150 border-slate-200 dark:border-b-navy-600',
                                        'even:bg-slate-50/70 dark:even:bg-navy-800/25' => ! $proofPending,
                                        'hover:bg-slate-100/90 dark:hover:bg-navy-700/35' => ! $proofPending,
                                        'border-s-4 border-warning/50 bg-warning/[0.08] dark:bg-warning/15 dark:even:bg-warning/15 hover:bg-warning/15 dark:hover:bg-warning/25' => $proofPending,
                                    ])>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                            <p class="font-semibold text-primary dark:text-accent-light">#{{ $request->id }}</p>
                                        </td>
                                        <td class="max-w-[14rem] px-4 py-3 sm:px-5">
                                            <div class="flex w-full justify-start">
                                                <div class="min-w-0 max-w-full">
                                                    @include('provider.requests.partials.pseudonymous-ref-inline', ['ref' => $refLabel, 'variant' => 'table'])
                                                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-navy-400" title="{{ \App\Support\RequestTypeLabel::forRequest($request) }}">{{ \App\Support\RequestTypeLabel::forRequest($request) }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-slate-800 dark:text-navy-100">
                                            {{ trans_choice('provider_items_line', $qty, ['count' => $qty]) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                            <p class="text-sm-plus font-semibold text-slate-800 dark:text-navy-100"><x-sar-symbol /> {{ $amount }}</p>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5">
                                            <div class="font-medium text-slate-700 dark:text-navy-100">{{ $request->created_at->locale(app()->getLocale())->isoFormat('L') }}</div>
                                            <div class="text-xs text-slate-500 dark:text-navy-300">{{ $request->created_at->locale(app()->getLocale())->isoFormat('LT') }}</div>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5">
                                            @include('provider.requests.partials.incoming-status-badge', ['request' => $request])
                                        </td>
                                        <td class="w-[11rem] min-w-[11rem] px-4 py-3 sm:px-5">
                                            <div @class([
                                                'flex flex-col gap-2',
                                                'min-h-[5.5rem] justify-center' => $proofPending,
                                            ])>
                                                <a href="{{ route('provider.requests.show', $request) }}"
                                                    class="inline-flex min-h-[2.5rem] items-center justify-center rounded-lg border border-primary/35 bg-primary/5 px-3 py-2 text-center text-sm font-semibold text-primary transition hover:bg-primary/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 dark:border-accent/35 dark:text-accent-light dark:hover:bg-accent/10 dark:focus-visible:ring-accent/40">
                                                    {{ __('Review') }}
                                                </a>
                                                @if ($proofPending)
                                                    <a href="{{ route('provider.proof.index', $request->redemption->id) }}"
                                                        class="inline-flex min-h-[2.5rem] items-center justify-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 dark:bg-accent dark:hover:bg-accent-focus dark:focus-visible:ring-accent/50">
                                                        <i class="fa-solid fa-camera text-xs" aria-hidden="true"></i>
                                                        {{ __('Upload proof') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php
                        $currentPerPage = (int) request('per_page', $filters['per_page'] ?? 15);
                    @endphp
                    <div
                        class="flex flex-col gap-4 border-t border-slate-200/90 bg-slate-50/50 px-4 py-4 dark:border-navy-600 dark:bg-navy-900/20 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm font-semibold text-slate-600 dark:text-navy-300">{{ __('Per page') }}</span>
                            <div
                                class="inline-flex rounded-xl border border-primary/25 bg-white p-1 shadow-sm dark:border-accent/35 dark:bg-navy-800">
                                @foreach ([15, 25, 50] as $n)
                                    <a href="{{ route('provider.requests.index', array_merge(request()->except(['page', 'per_page']), ['per_page' => $n])) }}"
                                        @class([
                                            'inline-flex min-w-[2.75rem] items-center justify-center rounded-lg px-3 py-1.5 text-sm font-bold transition',
                                            'bg-primary text-white shadow-sm dark:bg-accent' => $currentPerPage === $n,
                                            'text-slate-600 hover:bg-primary/10 hover:text-primary dark:text-navy-200 dark:hover:bg-accent/15 dark:hover:text-accent-light' => $currentPerPage !== $n,
                                        ])>
                                        {{ $n }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="min-w-0 w-full sm:w-auto sm:flex-1 sm:flex sm:justify-end">
                            {{ $requests->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
