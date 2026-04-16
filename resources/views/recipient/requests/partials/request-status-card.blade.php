@props(['request', 'card'])

@php
    $providerTitle = \App\Support\ProviderDisplay::businessTitle(
        $request->provider->providerProfile,
        $request->provider->name,
    );
    $accent = $card['accent'];
    $accentMap = [
        'primary' => [
            'tint' => 'bg-primary/[0.06] dark:bg-accent/[0.08]',
            'done' => 'bg-primary text-white shadow-sm dark:bg-accent',
            'currentRing' => 'ring-2 ring-primary/40 dark:ring-accent/50',
            'currentBg' => 'bg-primary/20 text-primary dark:bg-accent/25 dark:text-accent-light',
            'line' => 'bg-slate-200 dark:bg-navy-500',
            'lineDashed' => 'border-slate-300 dark:border-navy-600',
        ],
        'success' => [
            'tint' => 'bg-success/[0.06] dark:bg-success/[0.08]',
            'done' => 'bg-success text-white shadow-sm',
            'currentRing' => 'ring-2 ring-success/40',
            'currentBg' => 'bg-success/20 text-success',
            'line' => 'bg-slate-200 dark:bg-navy-500',
            'lineDashed' => 'border-slate-300 dark:border-navy-600',
        ],
        'warning' => [
            'tint' => 'bg-warning/[0.08] dark:bg-warning/[0.1]',
            'done' => 'bg-warning text-white shadow-sm',
            'currentRing' => 'ring-2 ring-warning/50',
            'currentBg' => 'bg-warning/20 text-warning',
            'line' => 'bg-slate-200 dark:bg-navy-500',
            'lineDashed' => 'border-slate-300 dark:border-navy-600',
        ],
        'error' => [
            'tint' => 'bg-error/[0.06] dark:bg-error/[0.08]',
            'done' => 'bg-error text-white shadow-sm',
            'currentRing' => 'ring-2 ring-error/40',
            'currentBg' => 'bg-error/15 text-error',
            'line' => 'bg-slate-200 dark:bg-navy-500',
            'lineDashed' => 'border-slate-300 dark:border-navy-600',
        ],
        'slate' => [
            'tint' => 'bg-slate-100/90 dark:bg-navy-700/40',
            'done' => 'bg-slate-600 text-white shadow-sm dark:bg-navy-500',
            'currentRing' => 'ring-2 ring-slate-300 dark:ring-navy-500',
            'currentBg' => 'bg-slate-200 text-slate-700 dark:bg-navy-600 dark:text-navy-100',
            'line' => 'bg-slate-200 dark:bg-navy-500',
            'lineDashed' => 'border-slate-300 dark:border-navy-600',
        ],
    ];
    $c = $accentMap[$accent] ?? $accentMap['slate'];
    $steps = $card['steps'];
    $redemption = $request->redemption;
    $qrExpired = $redemption && (
        $redemption->status === 'EXPIRED'
        || ($redemption->redeem_expires_at && $redemption->redeem_expires_at->isPast())
    );
    $canShowQr = $redemption
        && $redemption->status === 'PENDING'
        && ! $qrExpired
        && in_array($request->status, ['APPROVED', 'REDEEMABLE'], true);

    $heroWrap = match ($card['heroIcon']) {
        'clock' => 'flex size-16 items-center justify-center rounded-full shadow-md ring-4 '.$c['currentRing'].' '.$c['currentBg'],
        'x' => 'flex size-16 items-center justify-center rounded-full bg-error text-white shadow-md ring-4 ring-error/25',
        'ban' => 'flex size-16 items-center justify-center rounded-full bg-slate-600 text-white shadow-md ring-4 ring-slate-300 dark:bg-navy-500',
        'check' => match ($accent) {
            'primary' => 'flex size-16 items-center justify-center rounded-full bg-primary text-white shadow-md ring-4 ring-primary/25 dark:bg-accent dark:ring-accent/30',
            'success' => 'flex size-16 items-center justify-center rounded-full bg-success text-white shadow-md ring-4 ring-success/20 dark:ring-success/30',
            'warning' => 'flex size-16 items-center justify-center rounded-full bg-warning text-white shadow-md ring-4 ring-warning/25',
            'error' => 'flex size-16 items-center justify-center rounded-full bg-error text-white shadow-md ring-4 ring-error/25',
            default => 'flex size-16 items-center justify-center rounded-full bg-slate-600 text-white shadow-md ring-4 ring-slate-300 dark:bg-navy-500',
        },
        default => 'flex size-16 items-center justify-center rounded-full bg-slate-600 text-white shadow-md ring-4 ring-slate-300 dark:bg-navy-500',
    };

    $stepTitleCurrent = match ($accent) {
        'primary' => 'text-primary dark:text-accent-light',
        'success' => 'text-success dark:text-success',
        'warning' => 'text-warning',
        'error' => 'text-error',
        default => 'text-slate-800 dark:text-navy-100',
    };
@endphp

<div class="w-full">
    <div
        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xl shadow-slate-200/30 dark:border-navy-600 dark:bg-navy-800 dark:shadow-none">
        <div class="{{ $c['tint'] }} px-4 py-8 sm:px-8 sm:py-10">
            <div class="flex flex-col items-center text-center">
                {{-- Hero icon (accent + icon type only) --}}
                <div class="{{ $heroWrap }}">
                    @if($card['heroIcon'] === 'check')
                        <svg class="size-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @elseif($card['heroIcon'] === 'x')
                        <svg class="size-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    @elseif($card['heroIcon'] === 'ban')
                        <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M12 12h.01" />
                        </svg>
                    @else
                        <svg class="size-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0Z" />
                        </svg>
                    @endif
                </div>

                <h1 class="mt-5 text-xl font-bold tracking-tight text-slate-800 dark:text-navy-50 sm:text-2xl">
                    {{ __($card['titleKey']) }}
                </h1>
                <p class="mt-3 max-w-lg text-sm leading-relaxed text-slate-600 dark:text-navy-300">
                    {{ __($card['subtitleKey']) }}
                </p>
                <p class="mt-4 text-base">
                    <span class="font-bold text-slate-800 dark:text-navy-100">{{ $providerTitle }}</span>
                    <span class="text-slate-400 dark:text-navy-500"> · </span>
                    <span class="tabular-nums font-bold text-primary dark:text-accent-light">
                        {{ number_format((float) $request->reserved_amount, 2) }} {{ __('SAR') }}
                    </span>
                </p>
                <div class="mt-5 flex flex-col items-center">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-slate-500 dark:text-navy-400">
                        {{ __('Status') }}</p>
                    <span
                        class="mt-1.5 inline-flex rounded-full px-4 py-1.5 text-sm font-semibold {{ $card['badgeClass'] }}">
                        @if(! empty($card['badgeStatusRaw']))
                            {{ $card['badgeStatusRaw'] }}
                        @elseif(! empty($card['badgeLabelKey']))
                            {{ __($card['badgeLabelKey']) }}
                        @endif
                    </span>
                </div>
            </div>

            @if($request->rejection_reason_code)
                <div
                    class="mx-auto mt-6 max-w-lg rounded-lg border border-error/30 bg-error/10 p-4 text-start text-sm dark:border-error/20 dark:bg-error/15">
                    <p class="font-bold text-slate-800 dark:text-navy-100">{{ __('Reason for Rejection') }}:</p>
                    <p class="text-slate-700 dark:text-navy-200">{{ __($request->rejection_reason_code) }}</p>
                    @if($request->rejection_reason_note)
                        <p class="mt-1 text-slate-600 dark:text-navy-300">{{ $request->rejection_reason_note }}</p>
                    @endif
                </div>
            @endif

            {{-- Four steps: each column = circle + labels (centered); connectors flex between columns --}}
            <div class="mt-10 border-t border-slate-200/80 pt-10 dark:border-navy-600">
                <div class="w-full">
                    @php
                        $cols = '';
                        foreach($steps as $idx => $step) {
                            $cols .= 'minmax(0,1fr)';
                            if ($idx < count($steps) - 1) {
                                $cols .= ' minmax(0.5rem,1.25rem) ';
                            }
                        }
                    @endphp
                    <div
                        class="grid w-full items-start gap-x-0" style="grid-template-columns: {{ $cols }};">
                        @foreach($steps as $idx => $step)
                            @if($idx > 0)
                                @php
                                    $left = $steps[$idx - 1]['state'];
                                    $right = $step['state'];
                                    $solid = $left === 'done' && in_array($right, ['done', 'current'], true);
                                @endphp
                                <div class="flex h-11 items-center justify-center self-start" aria-hidden="true">
                                    @if($solid)
                                        {{-- Solid: bright sweep along completed segment --}}
                                        <div
                                            class="relative h-1 w-full overflow-hidden rounded-full shadow-[inset_0_1px_0_rgba(255,255,255,0.35)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] {{ $c['line'] }}">
                                            <span
                                                class="pointer-events-none absolute inset-y-0 left-0 w-[min(72%,11rem)] -translate-x-full bg-gradient-to-r from-transparent via-white/95 to-transparent motion-reduce:animate-none dark:via-white/50 dark:from-transparent dark:to-transparent animate-[recipient-step-line-shimmer_1.65s_linear_infinite] [mask-image:linear-gradient(90deg,transparent_0%,black_18%,black_82%,transparent_100%)]"></span>
                                        </div>
                                    @else
                                        {{-- Dashed: higher-contrast marching ticks --}}
                                        <div
                                            class="h-1 w-full rounded-full bg-[repeating-linear-gradient(90deg,rgb(148_163_184)_0px,rgb(148_163_184)_6px,transparent_6px,transparent_14px)] bg-[length:24px_100%] shadow-[inset_0_1px_0_rgba(255,255,255,0.25)] motion-reduce:animate-none animate-[recipient-step-dash-flow_0.95s_linear_infinite] dark:bg-[repeating-linear-gradient(90deg,rgb(173_181_189)_0px,rgb(173_181_189)_6px,transparent_6px,transparent_14px)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]"></div>
                                    @endif
                                </div>
                            @endif

                            @php
                                $st = $step['state'];
                                $labelKey = $step['labelKey'] ?? (\App\Support\RecipientRequestStatusPresenter::STEP_LABELS[$idx]['label'] ?? '');
                                $titleClass = $st === 'pending'
                                    ? 'text-slate-500 dark:text-navy-400'
                                    : ($st === 'current'
                                        ? $stepTitleCurrent
                                        : 'text-slate-800 dark:text-navy-100');
                                $hintClass = $st === 'pending' ? 'text-slate-400 dark:text-navy-500' : 'text-slate-500 dark:text-navy-400';
                            @endphp
                            <div class="flex min-w-0 flex-col items-center">
                                <div class="flex h-11 w-full items-center justify-center">
                                    @if($st === 'done')
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full sm:h-11 sm:w-11 {{ $c['done'] }}"
                                            aria-hidden="true">
                                            <svg class="size-4 text-white sm:size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </div>
                                    @elseif($st === 'current')
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $c['currentRing'] }} {{ $c['currentBg'] }} sm:h-11 sm:w-11"
                                            aria-hidden="true">
                                            <svg class="size-4 animate-pulse sm:size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0Z" />
                                            </svg>
                                        </div>
                                    @else
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-slate-300 bg-slate-50 text-slate-400 dark:border-navy-500 dark:bg-navy-700/40 dark:text-navy-500 sm:h-11 sm:w-11"
                                            aria-hidden="true">
                                            <span class="text-sm font-bold">…</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-3 w-full px-0.5 text-center">
                                    <p class="text-[0.7rem] font-semibold leading-tight sm:text-sm {{ $titleClass }}">
                                        {{ __($labelKey) }}</p>
                                    <p class="mt-0.5 text-[0.6rem] leading-tight sm:text-xs {{ $hintClass }}">
                                        {{ __($step['hintKey']) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Footer actions: same card shell; content by status --}}
            <div class="mt-10 flex flex-col items-center gap-4 border-t border-slate-200/80 pt-10 dark:border-navy-600">
                @if($card['footer'] === 'redeem')
                    @if($redemption && $redemption->status === 'REDEEMED')
                        <div
                            class="w-full max-w-md rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-center text-sm font-semibold text-success dark:border-success/25 dark:bg-success/15">
                            {{ __('Order already redeemed.') }}</div>
                    @elseif($redemption && $qrExpired)
                        <div
                            class="w-full max-w-md rounded-xl border border-error/30 bg-error/10 px-4 py-3 text-center text-sm font-semibold text-error dark:border-error/25 dark:bg-error/15">
                            {{ __('QR Expired.') }}</div>
                    @elseif($canShowQr)
                        <button type="button" @click="$dispatch('open-modal', 'redeem-qr-{{ $request->id }}')"
                            class="inline-flex w-full max-w-md items-center justify-center gap-2.5 rounded-full bg-success px-6 py-3.5 text-base font-semibold text-white shadow-md transition hover:bg-emerald-600 focus:outline-hidden focus:ring-2 focus:ring-success/50 dark:bg-emerald-600 dark:hover:bg-emerald-500">
                            <svg class="size-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM13.5 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z" />
                            </svg>
                            {{ __('recipient.request_redeem.view_qr') }}
                        </button>
                    @else
                        <p class="text-sm font-medium text-slate-500 dark:text-navy-400">{{ __('QR Code is being generated...') }}
                        </p>
                    @endif
                @elseif($card['footer'] === 'fulfilled')
                    <p class="max-w-md text-center text-sm text-slate-600 dark:text-navy-300">
                        {{ __('recipient.request_fulfilled.footer_note') }}</p>
                @endif

                <div class="flex w-full max-w-md flex-col gap-3 sm:flex-row sm:justify-center sm:gap-4">
                    @if($card['footer'] === 'requested')
                        <a href="{{ route('recipient.dashboard') }}"
                            class="btn flex-1 rounded-xl border border-slate-200 bg-white py-2.5 font-medium text-slate-800 shadow-sm hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
                            {{ __('recipient.request_submitted.back_home') }}</a>
                        <a href="{{ route('recipient.providers.index') }}"
                            class="btn flex-1 rounded-xl bg-primary py-2.5 font-medium text-white shadow-sm hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                            {{ __('recipient.request_submitted.browse_providers') }}</a>
                    @else
                        <a href="{{ route('recipient.dashboard') }}"
                            class="btn flex-1 rounded-xl border border-slate-200 bg-white py-2.5 font-medium text-slate-800 shadow-sm hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
                            {{ __('recipient.request_submitted.back_home') }}</a>
                        <a href="{{ route('recipient.requests.index') }}"
                            class="btn flex-1 rounded-xl border border-slate-200 bg-white py-2.5 font-medium text-slate-800 shadow-sm hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
                            {{ __('recipient.request_redeem.go_to_history') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
