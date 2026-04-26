<x-app-layout title="{{ __('finance.provider_payouts.detail_title', ['id' => $payout->id]) }}" is-header-blur="true">
    <div class="pt-4">
        @include('admin.finances._nav')

        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('admin.finances.provider-payouts.index') }}" class="text-sm text-primary hover:underline dark:text-accent-light">← {{ __('finance.provider_payouts.back') }}</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-100">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-100">{{ session('error') }}</div>
        @endif

        <div class="card p-4">
            <h2 class="text-base font-medium text-slate-800 dark:text-navy-50">{{ __('finance.provider_payouts.detail_heading') }} #{{ $payout->id }}</h2>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                <div><dt class="text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.col_provider') }}</dt>
                    <dd>{{ $payout->provider?->providerProfile?->business_name_en ?? $payout->provider?->email }} ({{ $payout->provider_id }})</dd></div>
                <div><dt class="text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.col_amount') }}</dt>
                    <dd class="font-mono">
                        <x-sar-amount :value="number_format((float) $payout->amount, 2)" />
                    </dd></div>
                <div><dt class="text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.col_status') }}</dt>
                    <dd>{{ __('finance.provider_payouts.status.'.$payout->status) }}</dd></div>
                <div><dt class="text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.col_week') }}</dt>
                    <dd>{{ $payout->week_start_at?->format('Y-m-d H:i') }} – {{ $payout->week_end_at?->format('Y-m-d H:i') }}</dd></div>
                <div><dt class="text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.snapshot_balance') }}</dt>
                    <dd class="font-mono">{{ $payout->snapshot_wallet_balance !== null ? number_format((float) $payout->snapshot_wallet_balance, 2) : '—' }}</dd></div>
                <div><dt class="text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.snapshot_available') }}</dt>
                    <dd class="font-mono">{{ $payout->snapshot_available_amount !== null ? number_format((float) $payout->snapshot_available_amount, 2) : '—' }}</dd></div>
                @if($payout->reference_number)
                    <div><dt class="text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.reference') }}</dt>
                        <dd>{{ $payout->reference_number }}</dd></div>
                @endif
                @if($payout->fundTransactionOut)
                    <div><dt class="text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.ledger_out') }}</dt>
                        <dd><a href="{{ route('admin.finances.fund-transactions.show', $payout->fundTransactionOut) }}" class="text-primary hover:underline">#{{ $payout->fundTransactionOut->id }}</a></dd></div>
                @endif
            </dl>
            @if($payout->admin_note)
                <p class="mt-4 text-sm"><span class="text-slate-500">{{ __('finance.provider_payouts.admin_note') }}:</span> {{ $payout->admin_note }}</p>
            @endif
        </div>

        <div class="card mt-4 p-4">
            <h3 class="text-sm font-medium text-slate-800 dark:text-navy-50">{{ __('finance.provider_payouts.linked_ledger') }}</h3>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-slate-200 dark:border-navy-500">
                        <tr>
                            <th class="p-2">{{ __('finance.provider_payouts.ft_id') }}</th>
                            <th class="p-2">{{ __('finance.provider_payouts.ft_amount') }}</th>
                            <th class="p-2">{{ __('finance.provider_payouts.request_id') }}</th>
                            <th class="p-2">{{ __('finance.provider_payouts.redemption_id') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payout->items as $item)
                            <tr class="border-b border-slate-100 dark:border-navy-600">
                                <td class="p-2">
                                    @if($item->fundTransaction)
                                        <a href="{{ route('admin.finances.fund-transactions.show', $item->fundTransaction) }}" class="text-primary hover:underline">#{{ $item->fund_transaction_id }}</a>
                                    @else
                                        #{{ $item->fund_transaction_id }}
                                    @endif
                                </td>
                                <td class="p-2 font-mono">{{ number_format((float) $item->amount, 2) }}</td>
                                <td class="p-2">{{ $item->fundTransaction?->request_id ?? '—' }}</td>
                                <td class="p-2">{{ $item->fundTransaction?->order_redemption_id ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($payout->receipt_path)
            <div class="card mt-4 p-4">
                <h3 class="text-sm font-medium">{{ __('finance.provider_payouts.receipt') }}</h3>
                <a href="{{ route('admin.finances.provider-payouts.receipt.file', $payout) }}" target="_blank" class="mt-2 inline-block text-primary hover:underline">{{ __('finance.provider_payouts.open_receipt') }}</a>
            </div>
        @endif

        @if($payout->isConfirmable())
            <div class="card mt-4 space-y-4 p-4">
                <h3 class="text-sm font-medium">{{ __('finance.provider_payouts.upload_receipt') }}</h3>
                <form action="{{ route('admin.finances.provider-payouts.receipt.store', $payout) }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <input type="file" name="receipt" required class="form-input form-input-lineone max-w-md">
                    <button type="submit" class="btn bg-slate-600 text-white dark:bg-navy-500">{{ __('finance.provider_payouts.save_receipt') }}</button>
                </form>

                <h3 class="text-sm font-medium pt-2">{{ __('finance.provider_payouts.confirm_section') }}</h3>
                <form action="{{ route('admin.finances.provider-payouts.confirm', $payout) }}" method="post" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">{{ __('finance.provider_payouts.reference') }}</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-input form-input-lineone w-full">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">{{ __('finance.provider_payouts.admin_note') }}</label>
                        <textarea name="admin_note" rows="2" class="form-textarea form-textarea-lineone w-full">{{ old('admin_note') }}</textarea>
                    </div>
                    @if(!$payout->receipt_path)
                        <div class="md:col-span-2">
                            <label class="text-xs text-slate-500">{{ __('finance.provider_payouts.receipt') }} *</label>
                            <input type="file" name="receipt" class="form-input form-input-lineone w-full" required>
                        </div>
                    @else
                        <div class="md:col-span-2 text-sm text-slate-500">{{ __('finance.provider_payouts.receipt_optional_replace') }}</div>
                        <div class="md:col-span-2">
                            <input type="file" name="receipt" class="form-input form-input-lineone w-full">
                        </div>
                    @endif
                    <div class="md:col-span-2">
                        <button type="submit" class="btn bg-primary text-white dark:bg-accent">{{ __('finance.provider_payouts.confirm_transfer') }}</button>
                    </div>
                </form>

                <div class="border-t border-slate-200 pt-4 dark:border-navy-500">
                    <form action="{{ route('admin.finances.provider-payouts.reject', $payout) }}" method="post" class="mb-4 max-w-lg" onsubmit="return confirm(@json(__('finance.provider_payouts.confirm_reject')))">
                        @csrf
                        <label class="text-xs text-slate-500">{{ __('finance.provider_payouts.admin_note') }}</label>
                        <textarea name="admin_note" rows="2" class="form-textarea form-textarea-lineone mb-2 w-full">{{ old('admin_note') }}</textarea>
                        <button type="submit" class="btn border border-amber-500 text-amber-700 dark:text-amber-300">{{ __('finance.provider_payouts.reject') }}</button>
                    </form>
                    <form action="{{ route('admin.finances.provider-payouts.cancel', $payout) }}" method="post" class="max-w-lg" onsubmit="return confirm(@json(__('finance.provider_payouts.confirm_cancel')))">
                        @csrf
                        <label class="text-xs text-slate-500">{{ __('finance.provider_payouts.admin_note') }}</label>
                        <textarea name="admin_note" rows="2" class="form-textarea form-textarea-lineone mb-2 w-full">{{ old('admin_note') }}</textarea>
                        <button type="submit" class="btn border border-slate-400 text-slate-700 dark:text-navy-200">{{ __('finance.provider_payouts.cancel') }}</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
