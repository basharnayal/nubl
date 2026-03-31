@props([
    'data' => [],
    'depth' => 0,
])

@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use App\Support\FinanceUi;
@endphp

@if(empty($data))
    <p class="text-sm text-slate-500 dark:text-navy-300">—</p>
@else
    <div @class([
        'space-y-1',
        'ms-0 sm:ms-3 sm:border-s-2 sm:border-primary/25 sm:ps-3' => $depth > 0,
    ])>
        @foreach($data as $key => $value)
            @if(is_array($value))
                @if(Arr::isList($value))
                    <div
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-navy-600 dark:bg-navy-800/40">
                        <div
                            class="border-b border-slate-100 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:border-navy-600 dark:bg-navy-800 dark:text-navy-200">
                            {{ FinanceUi::fieldLabel((string) $key) }}
                        </div>
                        <ul class="divide-y divide-slate-100 dark:divide-navy-600">
                            @foreach($value as $i => $item)
                                <li class="flex items-start gap-3 px-3 py-2.5">
                                    <span
                                        class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary dark:bg-accent/15 dark:text-accent-light">{{ $i + 1 }}</span>
                                    <div class="min-w-0 flex-1">
                                        @if (is_array($item))
                                            <x-finance.structured-data :data="$item" :depth="$depth + 1" />
                                        @else
                                            <span class="text-sm text-slate-800 dark:text-navy-100">
                                                @if (is_bool($item))
                                                    {{ $item ? __('finance.common.yes') : __('finance.common.no') }}
                                                @else
                                                    {{ $item }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 dark:border-navy-600 dark:bg-navy-800/30">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-navy-200">
                            {{ FinanceUi::fieldLabel((string) $key) }}</p>
                        <x-finance.structured-data :data="$value" :depth="$depth + 1" />
                    </div>
                @endif
            @else
                <div
                    class="flex flex-col gap-1 border-b border-slate-100 py-2.5 text-sm last:border-0 dark:border-navy-600 sm:flex-row sm:items-start sm:gap-4">
                    <dt class="min-w-0 shrink-0 text-slate-500 dark:text-navy-300 sm:w-44">
                        {{ FinanceUi::fieldLabel((string) $key) }}</dt>
                    <dd class="min-w-0 flex-1 break-words text-slate-800 dark:text-navy-50">
                        @if (is_bool($value))
                            <span
                                class="badge rounded-full bg-success/10 text-success dark:bg-success/15">{{ $value ? __('finance.common.yes') : __('finance.common.no') }}</span>
                        @elseif (Str::isUrl((string) $value))
                            <a href="{{ $value }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-primary underline decoration-primary/30 underline-offset-2 hover:text-primary-focus dark:text-accent-light">
                                {{ __('finance.common.open_link') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        @else
                            <span class="font-medium">{{ $value }}</span>
                        @endif
                    </dd>
                </div>
            @endif
        @endforeach
    </div>
@endif
