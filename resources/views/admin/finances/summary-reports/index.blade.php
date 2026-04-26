<x-app-layout title="{{ __('finance.summary_reports.title') }}" is-header-blur="true">
    <div class="pt-4">
        @include('admin.finances._nav')

        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    {{ __('finance.summary_reports.heading') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-navy-300">
                    {{ __('finance.summary_reports.hint') }}
                </p>
            </div>
        </div>

        @if ($reports->isEmpty())
            <div class="card p-6 text-center text-sm text-slate-500 dark:text-navy-300">
                {{ __('finance.summary_reports.no_reports') }}
            </div>
        @else
            <div class="card overflow-x-auto">
                <table class="is-hoverable w-full text-start">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-navy-500">
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-navy-300 text-start">
                                {{ __('finance.summary_reports.col_type') }}
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-navy-300 text-start">
                                {{ __('finance.summary_reports.col_period') }}
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-navy-300 text-start">
                                {{ __('finance.summary_reports.col_payments_succeeded') }}
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-navy-300 text-start">
                                {{ __('finance.summary_reports.col_ledger_out') }}
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-navy-300 text-start">
                                {{ __('finance.summary_reports.col_generated') }}
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-navy-300"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr class="border-b border-slate-200 dark:border-navy-500">
                                <td class="px-4 py-3">
                                    <span class="badge rounded-full px-2 py-0.5 text-xs font-semibold
                                        {{ $report->type === 'monthly'
                                            ? 'bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light'
                                            : 'bg-info/10 text-info dark:bg-info/15 dark:text-info' }}">
                                        {{ __('finance.summary_reports.type_' . $report->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-navy-100">
                                    <span dir="ltr" class="tabular-nums">
                                        {{ $report->period_from->toDateString() }}
                                        &rarr;
                                        {{ $report->period_to->toDateString() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700 dark:text-navy-100">
                                    <x-sar-amount :value="number_format($report->payload['payments_succeeded_amount'] ?? 0, 2)" />
                                </td>
                                <td class="px-4 py-3 text-sm tabular-nums text-slate-700 dark:text-navy-100">
                                    <x-sar-amount :value="number_format($report->payload['ledger_out_amount'] ?? 0, 2)" />
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-navy-300">
                                    {{ $report->generated_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.finances.summary-reports.download', $report->id) }}"
                                        class="btn flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50">
                                        <i class="fa-solid fa-file-excel text-[0.8em]" aria-hidden="true"></i>
                                        {{ __('finance.summary_reports.download_excel') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
