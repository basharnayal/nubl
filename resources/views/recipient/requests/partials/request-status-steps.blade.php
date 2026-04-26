@props(['request', 'card'])

@php
    $accent = $card['accent'] ?? 'slate';
    $isRedeemed = (bool) ($request->redemption && $request->redemption->status === 'REDEEMED');
    $redeemCompleted = $isRedeemed || $request->status === 'FULFILLED';
    $accentMap = [
        'primary' => [
            'done' => 'bg-primary text-white shadow-sm dark:bg-accent',
            'currentRing' => 'ring-2 ring-primary/40 dark:ring-accent/50',
            'currentBg' => 'bg-primary/20 text-primary dark:bg-accent/25 dark:text-accent-light',
            'line' => 'bg-slate-200 dark:bg-navy-500',
        ],
        'success' => [
            'done' => 'bg-success text-white shadow-sm',
            'currentRing' => 'ring-2 ring-success/40',
            'currentBg' => 'bg-success/20 text-success',
            'line' => 'bg-slate-200 dark:bg-navy-500',
        ],
        'warning' => [
            'done' => 'bg-warning text-white shadow-sm',
            'currentRing' => 'ring-2 ring-warning/50',
            'currentBg' => 'bg-warning/20 text-warning',
            'line' => 'bg-slate-200 dark:bg-navy-500',
        ],
        'error' => [
            'done' => 'bg-error text-white shadow-sm',
            'currentRing' => 'ring-2 ring-error/40',
            'currentBg' => 'bg-error/15 text-error',
            'line' => 'bg-slate-200 dark:bg-navy-500',
        ],
        'slate' => [
            'done' => 'bg-slate-600 text-white shadow-sm dark:bg-navy-500',
            'currentRing' => 'ring-2 ring-slate-300 dark:ring-navy-500',
            'currentBg' => 'bg-slate-200 text-slate-700 dark:bg-navy-600 dark:text-navy-100',
            'line' => 'bg-slate-200 dark:bg-navy-500',
        ],
    ];
    $c = $accentMap[$accent] ?? $accentMap['slate'];

    // Force the same 4-step structure as the main hero (step1..step4)
    $stepsRaw = $card['steps'] ?? [];
    $steps = array_slice($stepsRaw, 0, 4);
    foreach ($steps as $i => $st) {
        $steps[$i]['labelKey'] = \App\Support\RecipientRequestStatusPresenter::STEP_LABELS[$i]['label'] ?? ($steps[$i]['labelKey'] ?? '');
    }

    $stepTitleCurrent = match ($accent) {
        'primary' => 'text-primary dark:text-accent-light',
        'success' => 'text-success dark:text-success',
        'warning' => 'text-warning',
        'error' => 'text-error',
        default => 'text-slate-800 dark:text-navy-100',
    };
@endphp

@php
    // Same grid-template-columns logic as the main hero
    $cols = '';
    foreach($steps as $idx => $step) {
        $cols .= 'minmax(0,1fr)';
        if ($idx < count($steps) - 1) {
            $cols .= ' minmax(0.5rem,1.25rem) ';
        }
    }
@endphp

<div class="w-full">
    <div class="grid w-full items-start gap-x-0" style="grid-template-columns: {{ $cols }};">
        @foreach($steps as $idx => $step)
            @if($idx > 0)
                @php
                    $left = $steps[$idx - 1]['state'];
                    $right = $step['state'];
                    $solid = $left === 'done' && in_array($right, ['done', 'current'], true);
                @endphp
                <div class="flex h-11 items-center justify-center self-start" aria-hidden="true">
                    @if($solid)
                        <div class="relative h-1 w-full overflow-hidden rounded-full shadow-[inset_0_1px_0_rgba(255,255,255,0.35)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] {{ $c['line'] }}">
                            <span class="pointer-events-none absolute inset-y-0 left-0 w-[min(72%,11rem)] -translate-x-full bg-gradient-to-r from-transparent via-white/95 to-transparent motion-reduce:animate-none dark:via-white/50 dark:from-transparent dark:to-transparent animate-[recipient-step-line-shimmer_1.65s_linear_infinite] [mask-image:linear-gradient(90deg,transparent_0%,black_18%,black_82%,transparent_100%)]"></span>
                        </div>
                    @else
                        <div class="h-1 w-full rounded-full bg-[repeating-linear-gradient(90deg,rgb(148_163_184)_0px,rgb(148_163_184)_6px,transparent_6px,transparent_14px)] bg-[length:24px_100%] shadow-[inset_0_1px_0_rgba(255,255,255,0.25)] motion-reduce:animate-none animate-[recipient-step-dash-flow_0.95s_linear_infinite] dark:bg-[repeating-linear-gradient(90deg,rgb(173_181_189)_0px,rgb(173_181_189)_6px,transparent_6px,transparent_14px)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]"></div>
                    @endif
                </div>
            @endif

            @php
                $st = $step['state'];
                $labelKey = $step['labelKey'] ?? '';
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
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full sm:h-11 sm:w-11 {{ $c['done'] }}" aria-hidden="true">
                            @if($idx === 2)
                                {{-- Redeem step: QR while redeemable, check once redeemed --}}
                                @if($redeemCompleted)
                                    <svg class="size-4 text-white sm:size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                @else
                                    <i class="fa-solid fa-qrcode text-[0.95rem] text-white sm:text-[1.05rem]" aria-hidden="true"></i>
                                @endif
                            @else
                                <svg class="size-4 text-white sm:size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            @endif
                        </div>
                    @elseif($st === 'current')
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $c['currentRing'] }} {{ $c['currentBg'] }} sm:h-11 sm:w-11" aria-hidden="true">
                            @if($idx === 2)
                                {{-- Redeem step: show QR until redeemed --}}
                                @if($redeemCompleted)
                                    <svg class="size-4 sm:size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                @else
                                    <i class="fa-solid fa-qrcode animate-pulse text-[0.95rem] sm:text-[1.05rem]" aria-hidden="true"></i>
                                @endif
                            @else
                                <svg class="size-4 animate-pulse sm:size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0Z" />
                                </svg>
                            @endif
                        </div>
                    @else
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-slate-300 bg-slate-50 text-slate-400 dark:border-navy-500 dark:bg-navy-700/40 dark:text-navy-500 sm:h-11 sm:w-11" aria-hidden="true">
                            @if($idx === 2)
                                @if($redeemCompleted)
                                    <svg class="size-4 opacity-70 sm:size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                @else
                                    <i class="fa-solid fa-qrcode text-[0.95rem] opacity-60 sm:text-[1.05rem]" aria-hidden="true"></i>
                                @endif
                            @else
                                <span class="text-sm font-bold">…</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="mt-3 w-full px-0.5 text-center">
                    <p class="text-[0.7rem] font-semibold leading-tight sm:text-sm {{ $titleClass }}">{{ __($labelKey) }}</p>
                    {{-- hint removed for compact list view --}}
                </div>
            </div>
        @endforeach
    </div>
</div>

