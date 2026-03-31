<x-app-layout title="{{ __('Incoming Requests') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-800 dark:text-navy-50">
                {{ __('Incoming Requests') }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-navy-300">
                {{ __('Provider requests page subtitle') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="card mt-0 p-0 sm:p-0">
                @if ($requests->isEmpty())
                    <div class="px-6 py-14 text-center text-slate-500 dark:text-navy-300">
                        <i class="fa-solid fa-inbox mb-3 text-4xl text-slate-300 dark:text-navy-500" aria-hidden="true"></i>
                        <p class="font-medium">{{ __('No requests found.') }}</p>
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
                                    <th class="whitespace-nowrap rounded-ss-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Request') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Reference') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Items') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Total') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Date') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Status') }}</th>
                                    <th class="whitespace-nowrap rounded-se-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    @php
                                        $qty = (int) $request->items->sum('quantity');
                                        $amount = number_format((float) $request->reserved_amount, 2);
                                    @endphp
                                    <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                            <p class="font-semibold text-primary dark:text-accent-light">#{{ $request->id }}</p>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5">
                                            <div class="flex items-center gap-3">
                                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-200/90 dark:bg-navy-600" title="{{ __('Anonymous reference') }}" aria-hidden="true">
                                                    <i class="fa-solid fa-fingerprint text-sm text-slate-600 dark:text-navy-200"></i>
                                                </div>
                                                <div class="min-w-0 text-start" dir="ltr">
                                                    <span class="font-mono text-xs font-medium text-slate-700 dark:text-navy-100">{{ \App\Support\PseudonymousRequestId::make($request->id) }}</span>
                                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ \App\Support\RequestTypeLabel::forRequest($request) }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-slate-800 dark:text-navy-100">
                                            {{ trans_choice('provider_items_line', $qty, ['count' => $qty]) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                            <p class="text-sm-plus font-semibold text-slate-800 dark:text-navy-100">{{ $amount }} {{ __('SAR') }}</p>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5">
                                            <div class="font-medium text-slate-700 dark:text-navy-100">{{ $request->created_at->locale(app()->getLocale())->isoFormat('L') }}</div>
                                            <div class="text-xs text-slate-500 dark:text-navy-300">{{ $request->created_at->locale(app()->getLocale())->isoFormat('LT') }}</div>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5">
                                            @include('provider.requests.partials.incoming-status-badge', ['request' => $request])
                                        </td>
                                        <td class="min-w-[10rem] px-4 py-3 sm:px-5">
                                            <div class="flex flex-col gap-2">
                                                <a href="{{ route('provider.requests.show', $request) }}"
                                                    class="inline-flex items-center justify-center rounded-lg border border-primary/35 bg-primary/5 px-3 py-2 text-center text-sm font-semibold text-primary hover:bg-primary/10 dark:border-accent/35 dark:text-accent-light dark:hover:bg-accent/10">
                                                    {{ __('Review') }}
                                                </a>
                                                @if ($request->needsProviderFulfillmentProof())
                                                    <a href="{{ route('provider.proof.index', $request->redemption->id) }}"
                                                        class="inline-flex min-h-[2.5rem] items-center justify-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
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

                    <div class="flex flex-col justify-between space-y-4 border-t border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5 dark:border-navy-600">
                        {{ $requests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
