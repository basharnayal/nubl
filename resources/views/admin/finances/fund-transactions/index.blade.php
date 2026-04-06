@php
    use App\Models\FundTransaction;
@endphp
<x-app-layout title="{{ __('finance.ledger.title') }}" is-header-blur="true">
    <div class="pt-4">
        @include('admin.finances._nav')

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">{{ __('finance.ledger.title') }}</h2>
                <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-navy-300">{{ __('finance.ledger.subtitle') }}</p>
            </div>
            <x-lineone-button :href="route('admin.finances.fund-transactions.export', request()->query())" variant="slate" size="sm">
                {{ __('finance.ledger.export_csv') }}
            </x-lineone-button>
        </div>

        <form method="GET" action="{{ route('admin.finances.fund-transactions.index') }}" class="card mt-4 space-y-3 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('finance.ledger.search_placeholder') }}"
                    class="form-input form-input-lineone w-full min-w-0">
                <select name="wallet_type" class="form-select form-select-lineone w-full">
                    <option value="">{{ __('finance.ledger.all_wallets') }}</option>
                    <option value="SYSTEM" {{ request('wallet_type') === 'SYSTEM' ? 'selected' : '' }}>{{ __('finance.wallet_type.SYSTEM') }}</option>
                    <option value="PROVIDER" {{ request('wallet_type') === 'PROVIDER' ? 'selected' : '' }}>{{ __('finance.wallet_type.PROVIDER') }}</option>
                </select>
                <select name="direction" class="form-select form-select-lineone w-full">
                    <option value="">{{ __('finance.ledger.direction') }}</option>
                    <option value="{{ FundTransaction::DIRECTION_IN }}" {{ request('direction') === FundTransaction::DIRECTION_IN ? 'selected' : '' }}>{{ __('finance.direction_code.IN') }}</option>
                    <option value="{{ FundTransaction::DIRECTION_OUT }}" {{ request('direction') === FundTransaction::DIRECTION_OUT ? 'selected' : '' }}>{{ __('finance.direction_code.OUT') }}</option>
                </select>
                <select name="source" class="form-select form-select-lineone w-full">
                    <option value="">{{ __('finance.ledger.source') }}</option>
                    @foreach([FundTransaction::SOURCE_DONATION, FundTransaction::SOURCE_PAYOUT, FundTransaction::SOURCE_REDEMPTION, FundTransaction::SOURCE_REFUND, FundTransaction::SOURCE_EXPIRY_ROLLBACK, FundTransaction::SOURCE_CANCELLED, FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT] as $src)
                        <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ __('finance.source_code.'.$src) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <input type="number" name="donor_id" value="{{ request('donor_id') }}" placeholder="{{ __('finance.payments.donor_user_id') }}"
                    class="form-input form-input-lineone w-full min-w-0">
                <input type="number" name="provider_user_id" value="{{ request('provider_user_id') }}" placeholder="{{ __('finance.ledger.provider_user_id') }}"
                    class="form-input form-input-lineone w-full min-w-0">
                <input type="number" name="request_id" value="{{ request('request_id') }}" placeholder="{{ __('finance.ledger.request_id') }}"
                    class="form-input form-input-lineone w-full min-w-0">
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input form-input-lineone w-full">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input form-input-lineone w-full">
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn bg-primary text-white dark:bg-accent">{{ __('finance.payments.filter') }}</button>
                <a href="{{ route('admin.finances.fund-transactions.index') }}" class="btn border border-slate-300 dark:border-navy-500">{{ __('finance.payments.reset') }}</a>
            </div>
        </form>

        <div class="card mt-4">
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <table class="is-hoverable w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.id') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.wallet') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.direction') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.source') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.amount') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.payment') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.request') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.redemption') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4">{{ __('finance.ledger.columns.created') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fundTransactions as $tx)
                            @php
                                $dirKey = 'finance.direction_code.'.$tx->direction;
                                $dirLabel = __($dirKey);
                                $srcKey = 'finance.source_code.'.$tx->source;
                                $srcLabel = __($srcKey);
                                $walletTypeKey = $tx->wallet?->owner_type ? 'finance.wallet_type.'.$tx->wallet->owner_type : null;
                                $walletTypeLabel = $walletTypeKey ? __($walletTypeKey) : null;
                                $walletTypeDisplay = $tx->wallet?->owner_type
                                    ? ($walletTypeLabel !== $walletTypeKey ? $walletTypeLabel : $tx->wallet->owner_type)
                                    : '—';
                            @endphp
                            <tr class="border-b border-slate-200 dark:border-navy-500">
                                <td class="whitespace-nowrap px-3 py-2 font-mono font-medium text-primary dark:text-accent-light lg:px-4">#{{ $tx->id }}</td>
                                <td class="px-3 py-2 lg:px-4">
                                    <div>{{ $walletTypeDisplay }}</div>
                                    <div class="text-xs text-slate-500 dark:text-navy-300">#{{ $tx->wallet_id }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 lg:px-4">{{ $dirLabel !== $dirKey ? $dirLabel : $tx->direction }}</td>
                                <td class="whitespace-nowrap px-3 py-2 lg:px-4">{{ $srcLabel !== $srcKey ? $srcLabel : $tx->source }}</td>
                                <td class="whitespace-nowrap px-3 py-2 lg:px-4">{{ number_format($tx->amount, 2) }} {{ __('finance.common.sar') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 font-mono lg:px-4">{{ $tx->payment_id ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 font-mono lg:px-4">{{ $tx->request_id ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 font-mono lg:px-4">{{ $tx->order_redemption_id ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-xs lg:px-4">{{ $tx->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 lg:px-4">
                                    <a href="{{ route('admin.finances.fund-transactions.show', $tx) }}" class="text-primary dark:text-accent-light">{{ __('finance.common.view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">{{ __('finance.ledger.no_results') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($fundTransactions->hasPages())
                <div class="border-t border-slate-200 px-4 py-4 dark:border-navy-500 sm:px-5">
                    {{ $fundTransactions->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
