<x-app-layout title="{{ __('My Requests') }}" is-header-blur="true">
    <div class="pt-4">
        @php
            $hasFilters = request()->anyFilled(['search', 'status']);
            $statusFilter = request('status');
            $statusTabs = [
                ['key' => null,        'label' => __('All')],
                ['key' => 'pending',   'label' => __('Pending')],
                ['key' => 'redeemable','label' => __('Redeemable')],
                ['key' => 'fulfilled', 'label' => __('Fulfilled')],
                ['key' => 'cancelled', 'label' => __('Cancelled')],
            ];
        @endphp

        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold tracking-wide text-slate-800 line-clamp-1 dark:text-navy-50">
                    {{ __('My Requests') }}
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-navy-300">
                    {{ __('Track your orders, view details, and manage cancellations.') }}
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end sm:gap-4">
                <form method="GET" action="{{ route('recipient.requests.index') }}" class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Search...') }}"
                        class="form-input form-input-lineone w-full min-w-0 sm:w-56">

                    @if($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif

                    <button type="submit"
                        class="btn size-9 rounded-full p-0 text-primary hover:bg-primary/10 focus:bg-primary/10 dark:text-accent dark:hover:bg-accent/10 dark:focus:bg-accent/10"
                        title="{{ __('Search') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    @if($hasFilters)
                        <a href="{{ route('recipient.requests.index') }}"
                            class="btn size-9 rounded-full p-0 text-error hover:bg-error/10 focus:bg-error/10"
                            title="{{ __('Reset') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </form>

                <a href="{{ route('recipient.providers.index') }}"
                   class="btn flex items-center justify-center gap-2 bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                    <i class="fa-solid fa-plus text-xs"></i>
                    {{ __('New Request') }}
                </a>
            </div>
        </div>

        <div class="mt-4">
            <div class="inline-flex flex-wrap items-center gap-2 rounded-full bg-slate-100 p-1 dark:bg-navy-600">
                @foreach($statusTabs as $tab)
                    @php
                        $isActive = ($tab['key'] === null && !$statusFilter) || ($tab['key'] !== null && $statusFilter === $tab['key']);
                        $href = route('recipient.requests.index', array_filter([
                            'search' => request('search'),
                            'status' => $tab['key'],
                        ], fn ($v) => $v !== null && $v !== ''));
                    @endphp
                    <a href="{{ $href }}"
                       class="btn h-9 rounded-full px-5 text-sm font-medium transition-colors
                            {{ $isActive
                                ? 'bg-amber-400 text-slate-900 hover:bg-amber-400 focus:bg-amber-400'
                                : 'bg-transparent text-slate-700 hover:bg-white/70 focus:bg-white/70 dark:text-navy-100 dark:hover:bg-navy-500 dark:focus:bg-navy-500' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-4">
            @if($requests->isEmpty())
                <div class="card px-6 py-12 text-center text-slate-500 dark:text-navy-300">
                    @if(request()->anyFilled(['search', 'status']))
                        <p class="mb-4">{{ __('No requests match your filters.') }}</p>
                        <a href="{{ route('recipient.requests.index') }}"
                           class="btn border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-150 dark:border-navy-600 dark:text-navy-100 dark:hover:bg-navy-600">
                            {{ __('Clear Filters') }}
                        </a>
                    @else
                        <p class="mb-4">{{ __('You haven\'t made any requests yet.') }}</p>
                        <x-lineone-button :href="route('recipient.providers.index')" variant="primary" size="sm">
                            {{ __('Browse Providers') }}
                        </x-lineone-button>
                    @endif
                </div>
                    @else
                        {{-- Orders list (one per row like providers list) --}}
                        <div class="space-y-3 p-3 sm:space-y-4 sm:p-4">
                            @foreach($requests as $request)
                                @php
                                    $itemQty = $request->items->sum('quantity');
                                    $itemsForPreview = $request->items->take(3)->map(function ($it) {
                                        $name = $it->menuItem?->name ?? $it->menuItem?->name_en ?? $it->menuItem?->name_ar ?? null;
                                        if (!$name) {
                                            return null;
                                        }
                                        return trim($name).' ×'.(int) ($it->quantity ?? 1);
                                    })->filter()->values();
                                    $moreCount = max(0, $request->items->count() - $itemsForPreview->count());

                                    $statusConfig = [
                                        'REQUESTED' => ['class' => 'bg-warning/10 text-warning dark:bg-warning/15', 'label' => __('Requested')],
                                        'APPROVED' => ['class' => 'bg-info/10 text-info dark:bg-info/15', 'label' => __('Approved')],
                                        'ADMIN_PENDING' => ['class' => 'bg-warning/10 text-warning dark:bg-warning/15', 'label' => __('Admin Pending')],
                                        'ADMIN_APPROVED' => ['class' => 'bg-success/10 text-success dark:bg-success/15', 'label' => __('Admin Approved')],
                                        'REDEEMABLE' => ['class' => 'bg-success/10 text-success dark:bg-success/15', 'label' => __('Redeemable')],
                                        'FULFILLED' => ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => __('Fulfilled')],
                                        'REJECTED' => ['class' => 'bg-error/10 text-error dark:bg-error/15', 'label' => __('Rejected')],
                                        'ADMIN_REJECTED' => ['class' => 'bg-error/10 text-error dark:bg-error/15', 'label' => __('Rejected')],
                                        'CANCELLED' => ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => __('Cancelled')],
                                    ];
                                    $config = $statusConfig[$request->status] ?? ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => str_replace('_', ' ', $request->status)];
                                @endphp

                                <div class="card overflow-hidden p-4 transition-colors hover:border-primary/35 sm:p-5 dark:hover:border-accent/35">
                                    {{-- Top row (provider-style) --}}
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                                        <div class="flex min-w-0 flex-1 flex-row gap-3 sm:contents">
                                            <x-user-avatar :user="$request->provider" size-class="size-12" />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h5 class="truncate text-base font-bold tracking-tight text-slate-800 dark:text-navy-100 sm:text-lg">
                                                        {{ \App\Support\ProviderDisplay::businessTitle($request->provider->providerProfile, $request->provider->name) }}
                                                    </h5>
                                                </div>

                                                <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-300" dir="auto">
                                                    <span class="font-semibold text-primary dark:text-accent-light">#{{ $request->id }}</span>
                                                    <span class="mx-1">•</span>
                                                    {{ $request->created_at->locale(app()->getLocale())->translatedFormat('j F Y') }}
                                                    <span class="mx-1">•</span>
                                                    <span class="font-semibold text-slate-700 dark:text-navy-100">{{ $request->reserved_amount }} {{ __('SAR') }}</span>
                                                </p>

                                                <p class="mt-1 line-clamp-1 text-sm text-slate-600 dark:text-navy-300">
                                                    @if($itemsForPreview->isNotEmpty())
                                                        {{ $itemsForPreview->join('، ') }}@if($moreCount) {{ __('+ :count more', ['count' => $moreCount]) }}@endif
                                                    @else
                                                        {{ $itemQty }} {{ trans_choice('recipient.requests.items_count', $itemQty) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex shrink-0 items-center justify-end gap-2 sm:justify-center">
                                            @php
                                                $redemption = $request->redemption;
                                                $qrExpired = $redemption && (
                                                    $redemption->status === 'EXPIRED'
                                                    || ($redemption->redeem_expires_at && $redemption->redeem_expires_at->isPast())
                                                );
                                                $canShowQr = $redemption
                                                    && $redemption->status === 'PENDING'
                                                    && ! $qrExpired
                                                    && in_array($request->status, ['APPROVED', 'REDEEMABLE'], true);
                                            @endphp

                                            @if($canShowQr)
                                                @php
                                                    $rawTokenDecrypted = Illuminate\Support\Facades\Crypt::decryptString($redemption->token_ciphertext);
                                                    $shortTokenDecrypted = Illuminate\Support\Facades\Crypt::decryptString($redemption->short_code_ciphertext);
                                                @endphp
                                                <button type="button"
                                                    title="{{ __('View QR') }}"
                                                    @click="$dispatch('open-modal', 'redeem-qr-{{ $request->id }}')"
                                                    class="btn size-9 rounded-full bg-success/10 p-0 text-success hover:bg-success/20 focus:bg-success/20">
                                                    <i class="fa-solid fa-qrcode text-[1.05rem]" aria-hidden="true"></i>
                                                </button>

                                                <x-lineone-modal id="redeem-qr-{{ $request->id }}" :title="__('recipient.request_redeem.modal_title')" size="md">
                                                    <p class="mb-4 text-center text-sm text-slate-600 dark:text-navy-300">
                                                        {{ __('Show this QR code to the provider to redeem your order') }}
                                                    </p>
                                                    <div class="flex flex-col items-center justify-center space-y-4">
                                                        <div class="rounded-lg border-2 border-slate-200 bg-white p-4 dark:border-navy-600 dark:bg-navy-800">
                                                            {!! app('qrcode')->size(200)->generate($rawTokenDecrypted) !!}
                                                        </div>
                                                        <div class="w-full max-w-[240px] rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-center dark:border-navy-600 dark:bg-navy-700">
                                                            <p class="mb-1 text-xs uppercase tracking-wider text-slate-500 dark:text-navy-300">
                                                                {{ __('Manual Code') }}
                                                            </p>
                                                            <p class="select-all font-mono text-lg font-bold tracking-widest text-slate-800 dark:text-navy-100">
                                                                {{ $shortTokenDecrypted }}
                                                            </p>
                                                        </div>
                                                        <p class="text-center text-sm font-bold text-error">
                                                            {{ __('Expires in') }}:
                                                            {{ $redemption->redeem_expires_at->timezone('Asia/Riyadh')->diffForHumans() }}
                                                            ({{ $redemption->redeem_expires_at->timezone('Asia/Riyadh')->format('h:i A') }})
                                                        </p>
                                                        <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('Request #') }}{{ $request->id }}</p>
                                                    </div>
                                                </x-lineone-modal>
                                            @endif

                                            <a href="{{ route('recipient.requests.show', $request->id) }}"
                                               title="{{ __('Order Details') }}"
                                               class="btn size-9 rounded-full bg-primary/10 p-0 text-primary hover:bg-primary/20 focus:bg-primary/20 dark:bg-accent/10 dark:text-accent-light dark:hover:bg-accent/20">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                            </a>

                                            @if($request->isCancellableByRecipient())
                                                <button type="button"
                                                    @click="$dispatch('open-modal', 'cancel-request-{{ $request->id }}')"
                                                    title="{{ __('Cancel') }}"
                                                    class="btn size-9 rounded-full bg-error/10 p-0 text-error hover:bg-error/20 focus:bg-error/20">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>

                                                <x-lineone-modal id="cancel-request-{{ $request->id }}" :title="__('Cancel Request')" size="md">
                                                    <p class="text-slate-600 dark:text-navy-300">{{ __('Are you sure you want to cancel this request?') }}</p>
                                                    <form action="{{ route('recipient.requests.cancel', $request->id) }}" method="POST" class="mt-4 flex justify-end gap-2">
                                                        @csrf
                                                        <button type="button" @click="$dispatch('close-modal', 'cancel-request-{{ $request->id }}')"
                                                            class="btn border-slate-300 font-medium text-slate-700 hover:bg-slate-150 dark:border-navy-600 dark:text-navy-100 dark:hover:bg-navy-600">
                                                            {{ __('Cancel') }}
                                                        </button>
                                                        <button type="submit" class="btn bg-error font-medium text-white hover:bg-error-focus">
                                                            {{ __('Yes, Cancel Request') }}
                                                        </button>
                                                    </form>
                                                </x-lineone-modal>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Mini hero (always visible like screenshot) --}}
                                    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-navy-500 dark:bg-navy-700">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="flex size-11 items-center justify-center rounded-2xl bg-success/10 text-success dark:bg-success/15">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-slate-800 dark:text-navy-50">{{ __('Order no') }} #{{ $request->id }}</p>
                                                    <p class="mt-0.5 text-xs font-semibold text-slate-700 dark:text-navy-100">{{ $request->reserved_amount }} {{ __('SAR') }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-5">
                                            @php
                                                $miniCard = \App\Support\RecipientRequestStatusPresenter::card($request, false);
                                            @endphp
                                            @include('recipient.requests.partials.request-status-steps', [
                                                'request' => $request,
                                                'card' => $miniCard,
                                            ])

                                            <p class="mt-4 text-xs text-slate-500 dark:text-navy-300">
                                                {{ $itemQty }} {{ trans_choice('recipient.requests.items_count', $itemQty) }}
                                                <span class="mx-1">•</span>
                                                {{ __('Ordered') }} {{ $request->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 px-4 sm:px-5">
                            {{ $requests->withQueryString()->links() }}
                        </div>
                    @endif
        </div>
    </div>
</x-app-layout>
