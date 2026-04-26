@props(['request'])
@php
    $qty = (int) $request->items->sum('quantity');
    $amount = number_format((float) $request->reserved_amount, 2);
    $refLabel = \App\Support\PseudonymousRequestId::make($request->id);
    $typeLabel = \App\Support\RequestTypeLabel::forRequest($request);
    $proofPending = $request->needsProviderFulfillmentProof();
@endphp
<div @class([
    'card border p-4 shadow-sm dark:bg-navy-700/30',
    'border-slate-200/90 dark:border-navy-500' => ! $proofPending,
    'border-warning/40 bg-warning/[0.06] ring-2 ring-warning/20 dark:border-warning/50 dark:bg-warning/10 dark:ring-warning/25' => $proofPending,
])>
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-navy-600">
        <div>
            <p class="text-lg font-semibold text-primary dark:text-accent-light">#{{ $request->id }}</p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ $request->created_at->locale(app()->getLocale())->isoFormat('L LT') }}</p>
        </div>
        @include('provider.requests.partials.incoming-status-badge', ['request' => $request])
    </div>

    <div class="mt-3 flex w-full justify-start">
        <div class="min-w-0 max-w-full">
            @include('provider.requests.partials.pseudonymous-ref-inline', ['ref' => $refLabel, 'variant' => 'card'])
            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-navy-400" title="{{ $typeLabel }}">{{ $typeLabel }}</p>
        </div>
    </div>

    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('Items') }}</dt>
            <dd class="mt-0.5 font-medium text-slate-800 dark:text-navy-100">{{ trans_choice('provider_items_line', $qty, ['count' => $qty]) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('Total') }}</dt>
            <dd class="mt-0.5 font-semibold text-slate-800 dark:text-navy-100"><x-sar-symbol /> {{ $amount }}</dd>
        </div>
    </dl>

    <div class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 dark:border-navy-600">
        <a href="{{ route('provider.requests.show', $request) }}"
            class="inline-flex min-h-[2.75rem] items-center justify-center rounded-lg border-2 border-primary/40 bg-white px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5 dark:border-accent/40 dark:bg-navy-700 dark:text-accent-light dark:hover:bg-navy-600">
            {{ __('Review') }}
        </a>
        @if ($request->needsProviderFulfillmentProof())
            <a href="{{ route('provider.proof.index', $request->redemption->id) }}"
                class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-primary-focus focus:outline-none focus:ring-2 focus:ring-primary/40 dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/40">
                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                {{ __('Upload proof') }}
            </a>
        @endif
    </div>
</div>
