<x-app-layout title="{{ __('finance.payments.detail_title', ['id' => $payment->id]) }}" is-header-blur="true">
    <div class="pt-4">
        @include('admin.finances._nav')

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                {{ __('finance.payments.detail_title', ['id' => $payment->id]) }}
            </h2>
            <a href="{{ route('admin.finances.payments.index') }}"
                class="text-sm text-primary hover:underline dark:text-accent-light">{{ __('finance.common.back_to_list') }}</a>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                    {{ __('finance.payments.gateway_record') }}</h3>
                <dl class="space-y-2 text-sm">
                    @php
                        $payStKey = 'finance.payment_status.'.$payment->status;
                        $payStLabel = __($payStKey);
                    @endphp
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.status') }}</dt>
                        <dd class="font-medium">{{ $payStLabel !== $payStKey ? $payStLabel : $payment->status }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.amount') }}</dt>
                        <dd><x-sar-amount :value="number_format($payment->amount, 2)" /></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.gateway') }}</dt>
                        <dd>{{ $payment->gateway }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.external_invoice') }}</dt>
                        <dd class="font-mono text-xs">{{ $payment->external_payment_id ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.idempotency_key') }}</dt>
                        <dd class="break-all font-mono text-xs">{{ $payment->idempotency_key ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.created') }}</dt>
                        <dd>{{ $payment->created_at?->format('Y-m-d H:i:s') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.updated') }}</dt>
                        <dd>{{ $payment->updated_at?->format('Y-m-d H:i:s') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                    {{ __('finance.payments.donor') }}</h3>
                @if ($payment->sponsor)
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.name') }}</dt>
                            <dd>{{ $payment->sponsor->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.email') }}</dt>
                            <dd>{{ $payment->sponsor->email }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.phone') }}</dt>
                            <dd>{{ $payment->sponsor->phone_number ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.user_id') }}</dt>
                            <dd>#{{ $payment->sponsor_id }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-slate-500">—</p>
                @endif
            </div>
        </div>

        <div class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                {{ __('finance.payments.notes_payload') }}</h3>
            @if (is_array($payment->notes) && count($payment->notes))
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-navy-600 dark:bg-navy-800/30">
                    <x-finance.structured-data :data="$payment->notes" />
                </div>
            @else
                <p class="text-sm text-slate-500 dark:text-navy-300">—</p>
            @endif
        </div>

        <div class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                {{ __('finance.payments.related_ledger') }}</h3>
            <p class="mb-3 text-xs leading-relaxed text-slate-500 dark:text-navy-300">{{ __('finance.payments.ledger_hint') }}</p>
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <table class="is-hoverable w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="bg-slate-200 px-3 py-2 dark:bg-navy-800">{{ __('finance.common.tx') }}</th>
                            <th class="bg-slate-200 px-3 py-2 dark:bg-navy-800">{{ __('finance.ledger.columns.wallet') }}</th>
                            <th class="bg-slate-200 px-3 py-2 dark:bg-navy-800">{{ __('finance.ledger.columns.direction') }}</th>
                            <th class="bg-slate-200 px-3 py-2 dark:bg-navy-800">{{ __('finance.ledger.columns.source') }}</th>
                            <th class="bg-slate-200 px-3 py-2 dark:bg-navy-800">{{ __('finance.ledger.columns.amount') }}</th>
                            <th class="bg-slate-200 px-3 py-2 dark:bg-navy-800"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payment->fundTransactions as $ft)
                            @php
                                $ftDirK = 'finance.direction_code.'.$ft->direction;
                                $ftDirL = __($ftDirK);
                                $ftSrcK = 'finance.source_code.'.$ft->source;
                                $ftSrcL = __($ftSrcK);
                                $ftWtK = $ft->wallet?->owner_type ? 'finance.wallet_type.'.$ft->wallet->owner_type : null;
                                $ftWtL = $ftWtK ? __($ftWtK) : null;
                            @endphp
                            <tr>
                                <td class="px-3 py-2 font-mono">#{{ $ft->id }}</td>
                                <td class="px-3 py-2">{{ $ft->wallet?->owner_type ? ($ftWtL !== $ftWtK ? $ftWtL : $ft->wallet->owner_type) : '—' }} #{{ $ft->wallet_id }}</td>
                                <td class="px-3 py-2">{{ $ftDirL !== $ftDirK ? $ftDirL : $ft->direction }}</td>
                                <td class="px-3 py-2">{{ $ftSrcL !== $ftSrcK ? $ftSrcL : $ft->source }}</td>
                                <td class="px-3 py-2"><x-sar-amount :value="number_format($ft->amount, 2)" /></td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.finances.fund-transactions.show', $ft) }}"
                                        class="text-primary dark:text-accent-light">{{ __('finance.common.open') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-slate-500 dark:text-navy-300">{{ __('finance.payments.no_ledger') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                {{ __('finance.payments.request_links') }}</h3>
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <table class="is-hoverable w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="bg-slate-200 px-3 py-2 dark:bg-navy-800">{{ __('finance.payments.request_col') }}</th>
                            <th class="bg-slate-200 px-3 py-2 dark:bg-navy-800">{{ __('finance.payments.allocated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payment->requestPaymentLinks as $link)
                            <tr>
                                <td class="px-3 py-2">
                                    @if ($link->request)
                                        #{{ $link->request_id }} — {{ $link->request->status ?? '' }}
                                    @else
                                        #{{ $link->request_id }}
                                    @endif
                                </td>
                                <td class="px-3 py-2"><x-sar-amount :value="number_format($link->amount, 2)" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-3 py-4 text-slate-500 dark:text-navy-300">{{ __('finance.payments.no_allocations') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                {{ __('finance.payments.audit_log') }}</h3>
            <x-finance.audit-timeline :entries="$auditEntries" :empty-message="__('finance.payments.no_audit')" />
        </div>
    </div>
</x-app-layout>
