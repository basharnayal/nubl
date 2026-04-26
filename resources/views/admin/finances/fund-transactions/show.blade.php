<x-app-layout title="{{ __('finance.ledger.detail_title', ['id' => $fundTransaction->id]) }}" is-header-blur="true">
    <div class="pt-4">
        @include('admin.finances._nav')

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                {{ __('finance.ledger.detail_title', ['id' => $fundTransaction->id]) }}
            </h2>
            <a href="{{ route('admin.finances.fund-transactions.index') }}"
                class="text-sm text-primary hover:underline dark:text-accent-light">{{ __('finance.common.back_to_list') }}</a>
        </div>

        @php
            $dirK = 'finance.direction_code.'.$fundTransaction->direction;
            $dirL = __($dirK);
            $srcK = 'finance.source_code.'.$fundTransaction->source;
            $srcL = __($srcK);
            $wtK = $fundTransaction->wallet?->owner_type ? 'finance.wallet_type.'.$fundTransaction->wallet->owner_type : null;
            $wtL = $wtK ? __($wtK) : null;
        @endphp

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                    {{ __('finance.ledger.wallet_section') }}</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.wallet_id_label') }}</dt>
                        <dd class="font-mono">#{{ $fundTransaction->wallet_id }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.owner_type') }}</dt>
                        <dd>{{ $fundTransaction->wallet?->owner_type ? ($wtL !== $wtK ? $wtL : $fundTransaction->wallet->owner_type) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.balance_cached') }}</dt>
                        <dd>
                            @if($fundTransaction->wallet)
                                <x-sar-amount :value="number_format($fundTransaction->wallet->balance, 2)" />
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    @if ($fundTransaction->wallet?->owner_type === 'PROVIDER' && $fundTransaction->wallet?->provider)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.provider_user') }}</dt>
                            <dd>#{{ $fundTransaction->wallet->provider->user_id }}
                                {{ $fundTransaction->wallet->provider->user?->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                    {{ __('finance.ledger.movement') }}</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.columns.direction') }}</dt>
                        <dd>{{ $dirL !== $dirK ? $dirL : $fundTransaction->direction }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.columns.source') }}</dt>
                        <dd>{{ $srcL !== $srcK ? $srcL : $fundTransaction->source }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.amount') }}</dt>
                        <dd><x-sar-amount :value="number_format($fundTransaction->amount, 2)" /></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.donor_sponsor') }}</dt>
                        <dd>{{ $fundTransaction->sponsor_id ? '#'.$fundTransaction->sponsor_id.' '.$fundTransaction->sponsor?->name : '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.common.created') }}</dt>
                        <dd>{{ $fundTransaction->created_at?->format('Y-m-d H:i:s') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                {{ __('finance.ledger.related_records') }}</h3>
            <dl class="grid gap-3 text-sm md:grid-cols-2">
                <div>
                    <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.columns.payment') }}</dt>
                    <dd>
                        @if ($fundTransaction->payment)
                            <a href="{{ route('admin.finances.payments.show', $fundTransaction->payment) }}"
                                class="text-primary dark:text-accent-light">#{{ $fundTransaction->payment_id }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.columns.request') }}</dt>
                    <dd class="font-mono">{{ $fundTransaction->request_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-navy-300">{{ __('finance.ledger.order_redemption') }}</dt>
                    <dd class="font-mono">{{ $fundTransaction->order_redemption_id ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase text-slate-600 dark:text-navy-200">
                {{ __('finance.ledger.audit_log') }}</h3>
            <x-finance.audit-timeline :entries="$auditEntries" :empty-message="__('finance.ledger.no_audit')" />
        </div>
    </div>
</x-app-layout>
