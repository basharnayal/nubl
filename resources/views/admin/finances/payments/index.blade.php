@php
    use App\Models\Payment;
@endphp
<x-app-layout title="{{ __('finance.payments.title') }}" is-header-blur="true">
    <div class="pt-4">
        @include('admin.finances._nav')

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">{{ __('finance.payments.title') }}</h2>
            <div class="flex flex-wrap gap-2">
                <x-lineone-button :href="route('admin.finances.payments.export', request()->query())" variant="slate" size="sm">
                    {{ __('finance.payments.export_csv') }}
                </x-lineone-button>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.finances.payments.index') }}" class="card mt-4 space-y-3 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('finance.payments.search_placeholder') }}"
                    class="form-input form-input-lineone w-full min-w-0">
                <select name="status" class="form-select form-select-lineone w-full">
                    <option value="">{{ __('finance.payments.all_statuses') }}</option>
                    <option value="PROBLEM_GROUP" {{ request('status') === 'PROBLEM_GROUP' ? 'selected' : '' }}>{{ __('finance.payments.problem_statuses') }}</option>
                    @foreach([Payment::STATUS_INITIATED, Payment::STATUS_PENDING, Payment::STATUS_PROCESSING, Payment::STATUS_SUCCEEDED, Payment::STATUS_FAILED] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ __('finance.payment_status.'.$st) }}</option>
                    @endforeach
                </select>
                <select name="gateway" class="form-select form-select-lineone w-full">
                    <option value="">{{ __('finance.payments.all_gateways') }}</option>
                    <option value="{{ Payment::GATEWAY_MYFATOORAH }}" {{ request('gateway') === Payment::GATEWAY_MYFATOORAH ? 'selected' : '' }}>{{ Payment::GATEWAY_MYFATOORAH }}</option>
                </select>
                <input type="number" name="donor_id" value="{{ request('donor_id') }}" placeholder="{{ __('finance.payments.donor_user_id') }}"
                    class="form-input form-input-lineone w-full min-w-0">
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input form-input-lineone w-full">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input form-input-lineone w-full">
                <input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}" placeholder="{{ __('finance.payments.min_amount') }}"
                    class="form-input form-input-lineone w-full min-w-0">
                <input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}" placeholder="{{ __('finance.payments.max_amount') }}"
                    class="form-input form-input-lineone w-full min-w-0">
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn bg-primary text-white dark:bg-accent">{{ __('finance.payments.filter') }}</button>
                <a href="{{ route('admin.finances.payments.index') }}" class="btn border border-slate-300 dark:border-navy-500">{{ __('finance.payments.reset') }}</a>
            </div>
        </form>

        <div class="card mt-4">
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <table class="is-hoverable w-full text-left">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('finance.payments.columns.id') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('finance.payments.columns.donor') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('finance.payments.columns.gateway') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('finance.payments.columns.external_id') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('finance.payments.columns.amount') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('finance.payments.columns.status') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('finance.payments.columns.created') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('finance.payments.columns.updated') }}</th>
                            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            @php
                                $notes = $payment->notes ?? [];
                                $failHint = $notes['callback_status'] ?? ($notes['error_url'] ?? null);
                                $stKey = 'finance.payment_status.'.$payment->status;
                                $stLabel = __($stKey);
                            @endphp
                            <tr class="border-b border-slate-200 dark:border-navy-500">
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-medium text-primary dark:text-accent-light">#{{ $payment->id }}</td>
                                <td class="px-4 py-3 sm:px-5">
                                    <div class="font-medium text-slate-700 dark:text-navy-100">{{ $payment->sponsor?->name ?? '—' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-navy-300">{{ $payment->sponsor?->email }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $payment->gateway }}</td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5 font-mono text-xs">{{ $payment->external_payment_id ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ number_format($payment->amount, 2) }} {{ __('finance.common.sar') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    <span @class([
                                        'badge rounded-full px-2 py-0.5 text-xs',
                                        'bg-success/10 text-success' => $payment->status === Payment::STATUS_SUCCEEDED,
                                        'bg-error/10 text-error' => $payment->status === Payment::STATUS_FAILED,
                                        'bg-warning/10 text-warning' => in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING, Payment::STATUS_INITIATED], true),
                                    ])>{{ $stLabel !== $stKey ? $stLabel : $payment->status }}</span>
                                    @if($payment->status === Payment::STATUS_FAILED && $failHint)
                                        <div class="mt-1 max-w-xs text-xs text-error" title="{{ is_scalar($failHint) ? $failHint : json_encode($failHint) }}">
                                            {{ is_scalar($failHint) ? $failHint : __('finance.payments.see_details') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-xs text-slate-600 dark:text-navy-200">{{ $payment->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-xs text-slate-600 dark:text-navy-200">{{ $payment->updated_at?->format('Y-m-d H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    <a href="{{ route('admin.finances.payments.show', $payment) }}" class="text-primary hover:underline dark:text-accent-light">{{ __('finance.common.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">{{ __('finance.payments.no_results') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="border-t border-slate-200 px-4 py-4 dark:border-navy-500 sm:px-5">
                    {{ $payments->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
