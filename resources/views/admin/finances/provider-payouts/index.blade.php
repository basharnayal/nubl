@php
    use App\Models\ProviderPayout;
@endphp
<x-app-layout title="{{ __('finance.provider_payouts.title') }}" is-header-blur="true">
    <div class="pt-4">
        @include('admin.finances._nav')

        <div class="flex flex-col gap-2">
            <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">{{ __('finance.provider_payouts.title') }}</h2>
            <p class="text-sm text-slate-500 dark:text-navy-300">{{ __('finance.provider_payouts.subtitle') }}</p>
        </div>

        <form method="GET" action="{{ route('admin.finances.provider-payouts.index') }}" class="card mt-4 space-y-3 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <select name="status" class="form-select form-select-lineone w-full">
                    <option value="">{{ __('finance.provider_payouts.all_statuses') }}</option>
                    @foreach([
                        ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
                        ProviderPayout::STATUS_PROCESSING,
                        ProviderPayout::STATUS_TRANSFERRED,
                        ProviderPayout::STATUS_REJECTED,
                        ProviderPayout::STATUS_CANCELLED,
                    ] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ __('finance.provider_payouts.status.'.$st) }}</option>
                    @endforeach
                </select>
                <input type="number" name="provider_id" value="{{ request('provider_id') }}" placeholder="{{ __('finance.provider_payouts.filter_provider_id') }}"
                    class="form-input form-input-lineone w-full min-w-0">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input form-input-lineone w-full">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input form-input-lineone w-full">
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn bg-primary text-white dark:bg-accent">{{ __('finance.payments.filter') }}</button>
                <a href="{{ route('admin.finances.provider-payouts.index') }}" class="btn border border-slate-300 dark:border-navy-500">{{ __('finance.payments.reset') }}</a>
            </div>
        </form>

        <div class="card mt-4 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-slate-200 dark:border-navy-500">
                    <tr>
                        <th class="p-3 font-medium">{{ __('finance.provider_payouts.col_id') }}</th>
                        <th class="p-3 font-medium">{{ __('finance.provider_payouts.col_provider') }}</th>
                        <th class="p-3 font-medium">{{ __('finance.provider_payouts.col_amount') }}</th>
                        <th class="p-3 font-medium">{{ __('finance.provider_payouts.col_status') }}</th>
                        <th class="p-3 font-medium">{{ __('finance.provider_payouts.col_week') }}</th>
                        <th class="p-3 font-medium">{{ __('finance.provider_payouts.col_scheduled') }}</th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payouts as $p)
                        <tr class="border-b border-slate-100 dark:border-navy-600">
                            <td class="p-3">#{{ $p->id }}</td>
                            <td class="p-3">
                                {{ $p->provider?->providerProfile?->business_name_en ?? $p->provider?->email ?? '—' }}
                                <span class="block text-xs text-slate-500">ID {{ $p->provider_id }}</span>
                            </td>
                            <td class="p-3 font-mono">{{ number_format((float) $p->amount, 2) }}</td>
                            <td class="p-3">{{ __('finance.provider_payouts.status.'.$p->status) }}</td>
                            <td class="p-3 text-xs">{{ $p->week_start_at?->format('Y-m-d') }} – {{ $p->week_end_at?->format('Y-m-d') }}</td>
                            <td class="p-3 text-xs">{{ $p->scheduled_at?->format('Y-m-d H:i') }}</td>
                            <td class="p-3">
                                <a href="{{ route('admin.finances.provider-payouts.show', $p) }}" class="text-primary hover:underline dark:text-accent-light">{{ __('finance.provider_payouts.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-500">{{ __('finance.provider_payouts.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $payouts->links() }}
        </div>
    </div>
</x-app-layout>
