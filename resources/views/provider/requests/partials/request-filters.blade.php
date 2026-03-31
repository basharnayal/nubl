@props([
    'filters',
    'filterStatuses',
    'statusFilterLabels',
    'thisWeekFrom',
    'thisWeekTo',
])

@php
    $queryExceptPage = request()->except('page');
    $thisWeekParams = array_merge($queryExceptPage, ['from' => $thisWeekFrom, 'to' => $thisWeekTo]);
    $needsProofParams = array_merge($queryExceptPage, ['needs_proof' => '1']);
    $perPageValue = (int) old('per_page', $filters['per_page'] ?? 15);
    // Status quick filters: drop status + needs_proof so we don’t combine incompatible filters.
    $statusQuickBase = array_merge(request()->except(['page', 'status', 'needs_proof']), ['per_page' => $perPageValue]);
    $requestedParams = array_merge($statusQuickBase, ['status' => 'REQUESTED']);
    $approvedParams = array_merge($statusQuickBase, ['status' => 'APPROVED']);
    $redeemableParams = array_merge($statusQuickBase, ['status' => 'REDEEMABLE']);
    $fulfilledParams = array_merge($statusQuickBase, ['status' => 'FULFILLED']);
@endphp

<div class="border-b border-slate-200/90 bg-gradient-to-b from-slate-50 to-white px-4 py-5 dark:border-navy-600 dark:from-navy-900/40 dark:to-navy-900/20 sm:px-6">
    <form method="get" action="{{ route('provider.requests.index') }}" class="space-y-5">
        <input type="hidden" name="per_page" value="{{ $perPageValue }}">

        <div class="grid gap-4 lg:grid-cols-12 lg:items-stretch lg:gap-4">
            {{-- Date range: info tint --}}
            <div @class([
                'flex flex-col rounded-xl border p-4 shadow-sm lg:col-span-4',
                'border-error/50 bg-error/[0.06] dark:border-error/40 dark:bg-error/10' => $errors->has('to'),
                'border-info/30 bg-info/[0.07] dark:border-info/35 dark:bg-info/10' => ! $errors->has('to'),
            ])>
                <p
                    class="mb-3 flex items-center gap-2 text-sm font-bold text-info dark:text-sky-300 [&_i]:text-info [&_i]:opacity-90">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-info/15 text-info dark:bg-info/25 dark:text-sky-200">
                        <i class="fa-regular fa-calendar-days text-sm" aria-hidden="true"></i>
                    </span>
                    {{ __('Date range') }}
                </p>
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="filter-from"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-navy-200">{{ __('From date') }}</label>
                        <input id="filter-from" type="date" name="from" value="{{ old('from', $filters['from']) }}"
                            aria-invalid="{{ $errors->has('to') ? 'true' : 'false' }}"
                            @class([
                                'form-input w-full rounded-lg bg-white text-sm shadow-sm dark:bg-navy-800',
                                'border border-error/60 focus:border-error focus:ring-2 focus:ring-error/30 dark:border-error/50' => $errors->has('to'),
                                'border border-info/25 focus:border-info focus:ring-2 focus:ring-info/30 dark:border-info/40 dark:focus:border-info dark:focus:ring-info/25' => ! $errors->has('to'),
                            ])>
                    </div>
                    <div>
                        <label for="filter-to"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-navy-200">{{ __('To date') }}</label>
                        <input id="filter-to" type="date" name="to" value="{{ old('to', $filters['to']) }}"
                            aria-invalid="{{ $errors->has('to') ? 'true' : 'false' }}"
                            @class([
                                'form-input w-full rounded-lg bg-white text-sm shadow-sm dark:bg-navy-800',
                                'border-2 border-error focus:border-error focus:ring-2 focus:ring-error/35 dark:border-error dark:focus:border-error' => $errors->has('to'),
                                'border border-info/25 focus:border-info focus:ring-2 focus:ring-info/30 dark:border-info/40 dark:focus:border-info dark:focus:ring-info/25' => ! $errors->has('to'),
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

            {{-- Status + proof: primary tint --}}
            <div
                class="flex flex-col rounded-xl border border-primary/25 bg-primary/[0.06] p-4 shadow-sm dark:border-accent/35 dark:bg-accent/10 lg:col-span-4">
                <p
                    class="mb-3 flex items-center gap-2 text-sm font-bold text-primary dark:text-accent-light [&_i]:opacity-90">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/15 text-primary dark:bg-accent/20 dark:text-accent-light">
                        <i class="fa-solid fa-sliders text-sm" aria-hidden="true"></i>
                    </span>
                    {{ __('Status and proof') }}
                </p>
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="filter-status"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-navy-200">{{ __('Status') }}</label>
                        <select id="filter-status" name="status"
                            class="form-select w-full rounded-lg border border-primary/25 bg-white text-sm shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/25 dark:border-accent/40 dark:bg-navy-800 dark:focus:border-accent dark:focus:ring-accent/30">
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
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-navy-200">{{ __('Proof') }}</label>
                        <select id="filter-needs-proof" name="needs_proof"
                            class="form-select w-full rounded-lg border border-primary/25 bg-white text-sm shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/25 dark:border-accent/40 dark:bg-navy-800 dark:focus:border-accent dark:focus:ring-accent/30">
                            <option value="">{{ __('All orders') }}</option>
                            <option value="1" @selected(old('needs_proof', $filters['needs_proof']) === '1')>{{ __('Proof pending only') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Search: warning tint (action-oriented lookup) --}}
            <div
                class="flex flex-col rounded-xl border border-amber-200/90 bg-amber-50/80 p-4 shadow-sm dark:border-amber-500/30 dark:bg-amber-950/25 lg:col-span-4">
                <p
                    class="mb-3 flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200 [&_i]:opacity-90">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-200/80 text-amber-900 dark:bg-amber-500/20 dark:text-amber-100">
                        <i class="fa-solid fa-hashtag text-sm" aria-hidden="true"></i>
                    </span>
                    {{ __('Request lookup') }}
                </p>
                <div class="flex flex-1 flex-col justify-end">
                    <label for="filter-q"
                        class="mb-1.5 block text-xs font-semibold text-amber-950/90 dark:text-amber-100/90">{{ __('Request number') }}</label>
                    <input id="filter-q" type="search" name="q" value="{{ old('q', $filters['q']) }}" inputmode="numeric"
                        autocomplete="off" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" placeholder="{{ __('e.g. 17 or #17') }}"
                        class="form-input w-full rounded-lg border border-amber-300/80 bg-white text-sm shadow-sm placeholder:text-amber-800/40 focus:border-amber-500 focus:ring-2 focus:ring-amber-400/40 dark:border-amber-500/40 dark:bg-navy-800 dark:placeholder:text-navy-400 dark:focus:border-amber-400 dark:focus:ring-amber-500/30">
                </div>
            </div>
        </div>

        {{-- Actions + quick filters --}}
        <div
            class="flex flex-col gap-4 border-t border-slate-200/90 pt-4 dark:border-navy-600 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-slate-600 dark:text-navy-300">{{ __('Quick filters') }}</span>
                <a href="{{ route('provider.requests.index', $requestedParams) }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-success/40 bg-success/10 px-3 py-1.5 text-xs font-bold text-success shadow-sm transition hover:bg-success/20 dark:border-emerald-500/45 dark:bg-emerald-500/15 dark:text-emerald-200 dark:hover:bg-emerald-500/25">
                    <i class="fa-solid fa-inbox text-[0.7rem]" aria-hidden="true"></i>
                    {{ __('Requested') }}
                </a>
                <a href="{{ route('provider.requests.index', $approvedParams) }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-primary/40 bg-primary/10 px-3 py-1.5 text-xs font-bold text-primary shadow-sm transition hover:bg-primary/20 dark:border-accent/45 dark:bg-accent/15 dark:text-accent-light dark:hover:bg-accent/25">
                    <i class="fa-solid fa-circle-check text-[0.7rem]" aria-hidden="true"></i>
                    {{ __('Approved') }}
                </a>
                <a href="{{ route('provider.requests.index', $redeemableParams) }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-violet-500/40 bg-violet-500/10 px-3 py-1.5 text-xs font-bold text-violet-700 shadow-sm transition hover:bg-violet-500/20 dark:border-violet-400/45 dark:bg-violet-500/15 dark:text-violet-200 dark:hover:bg-violet-500/25">
                    <i class="fa-solid fa-qrcode text-[0.7rem]" aria-hidden="true"></i>
                    {{ __('Redeemable') }}
                </a>
                <a href="{{ route('provider.requests.index', $needsProofParams) }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-warning/40 bg-warning/15 px-3 py-1.5 text-xs font-bold text-warning shadow-sm transition hover:bg-warning/25 dark:border-warning/45 dark:bg-warning/20 dark:text-amber-200 dark:hover:bg-warning/30">
                    <i class="fa-solid fa-clock text-[0.7rem]" aria-hidden="true"></i>
                    {{ __('Needs proof') }}
                </a>
                <a href="{{ route('provider.requests.index', $fulfilledParams) }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-400/50 bg-slate-200/60 px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-300/70 dark:border-navy-500 dark:bg-navy-700/80 dark:text-navy-100 dark:hover:bg-navy-600">
                    <i class="fa-solid fa-flag-checkered text-[0.7rem]" aria-hidden="true"></i>
                    {{ __('Fulfilled') }}
                </a>
                <a href="{{ route('provider.requests.index', $thisWeekParams) }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-info/35 bg-info/10 px-3 py-1.5 text-xs font-bold text-info shadow-sm transition hover:bg-info/20 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-200 dark:hover:bg-sky-500/25">
                    <i class="fa-regular fa-calendar text-[0.7rem]" aria-hidden="true"></i>
                    {{ __('This week') }}
                </a>
            </div>
            <div class="flex w-full flex-wrap gap-2 sm:w-auto sm:justify-end">
                <a href="{{ route('provider.requests.index') }}"
                    class="inline-flex min-h-[2.75rem] flex-1 items-center justify-center rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-100 dark:hover:border-navy-400 dark:hover:bg-navy-700 sm:flex-none">
                    {{ __('Clear filters') }}
                </a>
                <button type="submit"
                    class="inline-flex min-h-[2.75rem] flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:bg-accent dark:hover:bg-accent-focus dark:focus-visible:ring-accent dark:focus-visible:ring-offset-navy-900 sm:flex-none">
                    <i class="fa-solid fa-magnifying-glass text-sm opacity-95" aria-hidden="true"></i>
                    {{ __('Apply filters') }}
                </button>
            </div>
        </div>
    </form>
</div>
