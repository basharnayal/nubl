@props([
    'filters',
    'filterStatuses',
    'statusFilterLabels',
])

@php
    $queryExceptPage = request()->except('page');
    $perPageValue = (int) old('per_page', $filters['per_page'] ?? 15);
    // Quick filters: drop incompatible params so pills don’t stack contradictory filters.
    $filterQuickBase = array_merge(
        request()->except(['page', 'status', 'needs_proof', 'funding_source']),
        ['per_page' => $perPageValue]
    );
    $needsProofParams = array_merge($filterQuickBase, ['needs_proof' => '1']);
    $statusQuickBase = $filterQuickBase;
    $requestedParams = array_merge($statusQuickBase, ['status' => 'REQUESTED']);
    $approvedParams = array_merge($statusQuickBase, ['status' => 'APPROVED']);
    $redeemableParams = array_merge($statusQuickBase, ['status' => 'REDEEMABLE']);
    $fulfilledParams = array_merge($statusQuickBase, ['status' => 'FULFILLED']);
    $adoptedParams = array_merge($statusQuickBase, ['funding_source' => 'PROVIDER_ADOPTION']);
    $quickPill =
        'inline-flex shrink-0 min-h-[2.5rem] items-center gap-1.5 whitespace-nowrap rounded-full border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-primary/30 hover:bg-primary/5 hover:text-primary dark:border-navy-600 dark:bg-navy-800/60 dark:text-navy-200 dark:hover:border-accent/35 dark:hover:bg-accent/10 dark:hover:text-accent-light';
@endphp

<div class="border-b border-slate-200/90 bg-slate-50/50 px-4 py-5 dark:border-navy-600 dark:bg-navy-900/20 sm:px-6">
    <form method="get" action="{{ route('provider.requests.index') }}" class="space-y-5">
        <input type="hidden" name="per_page" value="{{ $perPageValue }}">
        @if (filled($filters['funding_source'] ?? null))
            <input type="hidden" name="funding_source" value="{{ $filters['funding_source'] }}">
        @endif

        <div class="grid gap-4 lg:grid-cols-12 lg:items-stretch lg:gap-4">
            {{-- Date range — neutral cards (donor dashboard style) --}}
            <div @class([
                'flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-navy-600 dark:bg-navy-800/40 lg:col-span-4',
                'border-error/50 bg-error/[0.04] dark:border-error/40 dark:bg-error/10' => $errors->has('to'),
            ])>
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-navy-100">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                        <i class="fa-regular fa-calendar-days text-sm text-primary dark:text-accent" aria-hidden="true"></i>
                    </span>
                    {{ __('Date range') }}
                </p>
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="filter-from"
                            class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('From date') }}</label>
                        <input id="filter-from" type="date" name="from" value="{{ old('from', $filters['from']) }}"
                            aria-invalid="{{ $errors->has('to') ? 'true' : 'false' }}"
                            @class([
                                'form-input w-full rounded-lg bg-white text-sm dark:bg-navy-800',
                                'border-slate-300 dark:border-navy-500 focus:border-primary focus:ring-primary/20 dark:focus:border-accent dark:focus:ring-accent/25' => ! $errors->has('to'),
                                'border-error/60 focus:border-error focus:ring-error/25 dark:border-error/50' => $errors->has('to'),
                            ])>
                    </div>
                    <div>
                        <label for="filter-to"
                            class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('To date') }}</label>
                        <input id="filter-to" type="date" name="to" value="{{ old('to', $filters['to']) }}"
                            aria-invalid="{{ $errors->has('to') ? 'true' : 'false' }}"
                            @class([
                                'form-input w-full rounded-lg bg-white text-sm dark:bg-navy-800',
                                'border-slate-300 dark:border-navy-500 focus:border-primary focus:ring-primary/20 dark:focus:border-accent dark:focus:ring-accent/25' => ! $errors->has('to'),
                                'border-2 border-error focus:border-error focus:ring-error/30 dark:border-error' => $errors->has('to'),
                            ])>
                    </div>
                </div>
                @error('to')
                    <div class="mt-3 flex gap-2 rounded-lg border border-error/40 bg-error/10 px-3 py-2 text-sm font-semibold text-error dark:border-error/50 dark:bg-error/15 dark:text-red-200"
                        role="alert">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            {{-- Status + proof --}}
            <div
                class="flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-navy-600 dark:bg-navy-800/40 lg:col-span-4">
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-navy-100">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                        <i class="fa-solid fa-sliders text-sm text-primary dark:text-accent" aria-hidden="true"></i>
                    </span>
                    {{ __('Status and proof') }}
                </p>
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="filter-status"
                            class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('Status') }}</label>
                        <select id="filter-status" name="status"
                            class="form-select w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-primary focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-800 dark:focus:border-accent dark:focus:ring-accent/25">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach ($filterStatuses as $st)
                                <option value="{{ $st }}" @selected(old('status', $filters['status']) === $st)>
                                    {{ $statusFilterLabels[$st] ?? $st }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter-needs-proof"
                            class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('Proof') }}</label>
                        <select id="filter-needs-proof" name="needs_proof"
                            class="form-select w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-primary focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-800 dark:focus:border-accent dark:focus:ring-accent/25">
                            <option value="">{{ __('All orders') }}</option>
                            <option value="1" @selected(old('needs_proof', $filters['needs_proof']) === '1')>{{ __('Proof pending only') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Request lookup --}}
            <div
                class="flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-navy-600 dark:bg-navy-800/40 lg:col-span-4">
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-navy-100">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                        <i class="fa-solid fa-hashtag text-sm text-primary dark:text-accent" aria-hidden="true"></i>
                    </span>
                    {{ __('Request lookup') }}
                </p>
                <div class="flex flex-1 flex-col justify-end">
                    <label for="filter-q"
                        class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('Request number') }}</label>
                    <input id="filter-q" type="search" name="q" value="{{ old('q', $filters['q']) }}" inputmode="numeric"
                        autocomplete="off" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" placeholder="{{ __('e.g. 17 or #17') }}"
                        class="form-input w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm placeholder:text-slate-400 focus:border-primary focus:ring-primary/20 dark:border-navy-500 dark:bg-navy-800 dark:placeholder:text-navy-500 dark:focus:border-accent dark:focus:ring-accent/25">
                </div>
            </div>
        </div>

        {{-- Quick filters + actions: single row (flex-nowrap); scroll horizontally when wider than viewport --}}
        <div class="border-t border-slate-200/90 pt-5 dark:border-navy-600">
            <p class="mb-3 text-sm font-semibold text-slate-700 dark:text-navy-100">{{ __('Quick filters') }}</p>
            <div
                class="is-scrollbar-hidden min-w-0 overflow-x-auto pb-1"
                role="region"
                aria-label="{{ __('Quick filters') }}">
                <div class="flex w-max flex-nowrap items-center gap-2 sm:gap-2.5">
                    <div class="flex flex-nowrap gap-2 sm:gap-2.5">
                        <a href="{{ route('provider.requests.index', $requestedParams) }}" class="{{ $quickPill }}">
                            <i class="fa-solid fa-inbox text-[0.75rem] opacity-90" aria-hidden="true"></i>
                            {{ __('Requested') }}
                        </a>
                        <a href="{{ route('provider.requests.index', $approvedParams) }}" class="{{ $quickPill }}">
                            <i class="fa-solid fa-circle-check text-[0.75rem] opacity-90" aria-hidden="true"></i>
                            {{ __('Approved') }}
                        </a>
                        <a href="{{ route('provider.requests.index', $redeemableParams) }}" class="{{ $quickPill }}">
                            <i class="fa-solid fa-qrcode text-[0.75rem] opacity-90" aria-hidden="true"></i>
                            {{ __('Redeemable') }}
                        </a>
                        <a href="{{ route('provider.requests.index', $needsProofParams) }}" class="{{ $quickPill }}">
                            <i class="fa-solid fa-clock text-[0.75rem] opacity-90" aria-hidden="true"></i>
                            {{ __('Needs proof') }}
                        </a>
                        <a href="{{ route('provider.requests.index', $fulfilledParams) }}" class="{{ $quickPill }}">
                            <i class="fa-solid fa-flag-checkered text-[0.75rem] opacity-90" aria-hidden="true"></i>
                            {{ __('Fulfilled') }}
                        </a>
                        <a href="{{ route('provider.requests.index', $adoptedParams) }}" class="{{ $quickPill }}">
                            <i class="fa-solid fa-hand-holding-heart text-[0.75rem] opacity-90" aria-hidden="true"></i>
                            {{ __('provider.requests.quick_filter_adopted') }}
                        </a>
                    </div>
                    <div class="flex shrink-0 flex-nowrap gap-2 sm:gap-3">
                        <a href="{{ route('provider.requests.index') }}"
                            class="inline-flex min-h-[2.75rem] shrink-0 items-center justify-center whitespace-nowrap rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-100 dark:hover:border-navy-400 dark:hover:bg-navy-700 sm:min-w-[10rem]">
                            {{ __('Clear filters') }}
                        </a>
                        <button type="submit"
                            class="inline-flex min-h-[2.75rem] shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:bg-accent dark:hover:bg-accent-focus dark:focus-visible:ring-accent dark:focus-visible:ring-offset-navy-900 sm:min-w-[12rem]">
                            <i class="fa-solid fa-magnifying-glass text-sm opacity-95" aria-hidden="true"></i>
                            {{ __('Apply filters') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
