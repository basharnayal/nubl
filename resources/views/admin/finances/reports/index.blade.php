<x-app-layout title="{{ __('finance.reports.title') }}" is-header-blur="true">
    <div class="pt-4">
        @include('admin.finances._nav')

        <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">{{ __('finance.reports.heading') }}</h2>
        <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-navy-300">{{ __('finance.reports.hint') }}</p>

        <form method="GET" action="{{ route('admin.finances.reports.index') }}" class="card mt-4 flex flex-wrap items-end gap-3 p-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-navy-300">{{ __('finance.reports.period') }}</label>
                <select name="period" class="form-select form-select-lineone mt-1">
                    <option value="custom" {{ ($period ?? 'custom') === 'custom' ? 'selected' : '' }}>{{ __('finance.reports.custom_range') }}</option>
                    <option value="daily" {{ ($period ?? '') === 'daily' ? 'selected' : '' }}>{{ __('finance.reports.today') }}</option>
                    <option value="monthly" {{ ($period ?? '') === 'monthly' ? 'selected' : '' }}>{{ __('finance.reports.this_month') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-navy-300">{{ __('finance.reports.from') }}</label>
                <input type="date" name="date_from" value="{{ $date_from }}" class="form-input form-input-lineone mt-1">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-navy-300">{{ __('finance.reports.to') }}</label>
                <input type="date" name="date_to" value="{{ $date_to }}" class="form-input form-input-lineone mt-1">
            </div>
            <button type="submit" class="btn bg-primary text-white dark:bg-accent">{{ __('finance.reports.apply') }}</button>
        </form>

        <div class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">{{ __('finance.reports.gateway_payments') }}</h3>
            <p class="mb-2 text-xs text-slate-500 dark:text-navy-300">{{ __('finance.reports.range') }}:
                {{ $summary['from']->format('Y-m-d H:i') }} — {{ $summary['to']->format('Y-m-d H:i') }}</p>
            <dl class="grid gap-2 text-sm md:grid-cols-2">
                <div class="flex justify-between gap-4">
                    <dt>{{ __('finance.reports.total_rows') }}</dt>
                    <dd>{{ $summary['payments_total_count'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>{{ __('finance.reports.succeeded_amount') }}</dt>
                    <dd><x-sar-amount :value="number_format($summary['payments_succeeded_amount'], 2)" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>{{ __('finance.reports.failed_amount') }}</dt>
                    <dd><x-sar-amount :value="number_format($summary['payments_failed_amount'], 2)" /></dd>
                </div>
            </dl>
            <h4 class="mt-4 text-xs font-semibold uppercase text-slate-500 dark:text-navy-400">{{ __('finance.reports.by_status') }}</h4>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($summary['payments_by_status'] as $row)
                    @php
                        $stKey = 'finance.payment_status.'.$row->status;
                        $stLabel = __($stKey);
                    @endphp
                    <li class="flex justify-between gap-4 border-b border-slate-100 py-1 dark:border-navy-600">
                        <span>{{ $stLabel !== $stKey ? $stLabel : $row->status }}</span>
                        <span>{{ $row->cnt }} × <x-sar-amount :value="number_format($row->total, 2)" class="gap-1.5" /></span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">{{ __('finance.reports.fund_ledger') }}</h3>
            <dl class="grid gap-2 text-sm md:grid-cols-2">
                <div class="flex justify-between gap-4">
                    <dt>{{ __('finance.reports.entries') }}</dt>
                    <dd>{{ $summary['ledger_entries_count'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>{{ __('finance.reports.total_in') }}</dt>
                    <dd><x-sar-amount :value="number_format($summary['ledger_in_amount'], 2)" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>{{ __('finance.reports.total_out') }}</dt>
                    <dd><x-sar-amount :value="number_format($summary['ledger_out_amount'], 2)" /></dd>
                </div>
            </dl>
        </div>
    </div>
</x-app-layout>
