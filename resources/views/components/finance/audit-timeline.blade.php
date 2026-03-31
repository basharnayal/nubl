@props([
    'entries',
    'emptyMessage' => null,
])

@php
    use App\Support\FinanceUi;
@endphp

<div class="space-y-4">
    @forelse($entries as $entry)
        @php
            $props = $entry->properties?->toArray() ?? [];
        @endphp
        <article
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800/40">
            <header
                class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-4 py-3 dark:border-navy-600 dark:bg-navy-800/90">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary dark:bg-accent/15 dark:text-accent-light">
                        {{ FinanceUi::auditTitle($entry->description) }}
                    </span>
                </div>
                <time class="text-xs tabular-nums text-slate-500 dark:text-navy-300"
                    datetime="{{ $entry->created_at?->toIso8601String() }}">{{ $entry->created_at?->format('Y-m-d H:i:s') }}</time>
            </header>
            <div class="p-4">
                @if (count($props))
                    <x-finance.structured-data :data="$props" />
                @else
                    <p class="text-sm text-slate-500 dark:text-navy-300">—</p>
                @endif
            </div>
        </article>
    @empty
        <p class="text-sm text-slate-500 dark:text-navy-300">{{ $emptyMessage ?? '—' }}</p>
    @endforelse
</div>
