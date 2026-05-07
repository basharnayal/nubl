@php
    use App\Models\FundTransaction;
    use App\Models\ProviderPayout;
@endphp
<x-app-layout title="{{ __('provider.wallet.title') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-800 dark:text-navy-50">{{ __('provider.wallet.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-navy-300">{{ __('provider.wallet.subtitle') }}</p>
        </div>

        @if (!$wallet)
            <div class="card p-6 text-sm text-slate-600 dark:text-navy-200">{{ __('provider.wallet.no_wallet') }}</div>
        @else
            <div class="card p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('provider.wallet.balance') }}</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums text-slate-900 dark:text-navy-50">
                            <x-sar-amount class="text-base font-normal text-slate-500" :value="number_format((float) $wallet->balance, 2)" />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-navy-400">{{ __('provider.wallet.wallet_id') }}</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-800 dark:text-navy-100">#{{ $wallet->id }}</dd>
                    </div>
                </dl>
                <!-- <p class="mt-4 text-xs text-slate-500 dark:text-navy-400">{{ __('provider.wallet.balance_hint') }}</p> -->
            </div>

            <div class="card mt-6 overflow-x-auto">
                <h3 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 dark:border-navy-500 dark:text-navy-50">{{ __('provider.wallet.ledger') }}</h3>
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-navy-500 dark:bg-navy-700/50">
                        <tr>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_id') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_direction') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_source') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_amount') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr class="border-b border-slate-100 dark:border-navy-600">
                                <td class="p-3 font-mono text-xs">#{{ $tx->id }}</td>
                                <td class="p-3">{{ $tx->direction === FundTransaction::DIRECTION_IN ? __('finance.direction_code.IN') : __('finance.direction_code.OUT') }}</td>
                                <td class="p-3">{{ __('finance.source_code.'.$tx->source) }}</td>
                                <td class="p-3 font-mono">{{ number_format((float) $tx->amount, 2) }}</td>
                                <td class="p-3 text-xs text-slate-500">{{ $tx->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-500">{{ __('provider.wallet.no_transactions') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="border-t border-slate-200 px-4 py-3 dark:border-navy-500">
                    {{ $transactions->links() }}
                </div>
            </div>

            <div class="card mt-6 overflow-x-auto">
                <h3 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 dark:border-navy-500 dark:text-navy-50">{{ __('provider.wallet.payouts') }}</h3>
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-navy-500 dark:bg-navy-700/50">
                        <tr>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_payout_id') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_status') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_amount') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_week') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_admin_note') }}</th>
                            <th class="p-3 font-medium">{{ __('provider.wallet.col_receipt') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $p)
                            <tr class="border-b border-slate-100 dark:border-navy-600 align-top">
                                <td class="p-3 font-mono text-xs">#{{ $p->id }}</td>
                                <td class="p-3">{{ __('finance.provider_payouts.status.'.$p->status) }}</td>
                                <td class="p-3 font-mono">{{ number_format((float) $p->amount, 2) }}</td>
                                <td class="p-3 text-xs">{{ $p->week_start_at?->format('Y-m-d') }} – {{ $p->week_end_at?->format('Y-m-d') }}</td>
                                <td class="p-3 max-w-xs text-xs text-slate-600 dark:text-navy-200">{{ $p->admin_note ?: '—' }}</td>
                                <td class="p-3">
                                    @if ($p->status === ProviderPayout::STATUS_TRANSFERRED && $p->receipt_path)
                                        <a href="{{ route('provider.wallet.payout-receipt', $p) }}" class="text-primary hover:underline dark:text-accent-light">{{ __('provider.wallet.download_receipt') }}</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-slate-500">{{ __('provider.wallet.no_payouts') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="border-t border-slate-200 px-4 py-3 dark:border-navy-500">
                    {{ $payouts->links() }}
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
