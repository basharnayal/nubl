@php
    $linkClass = function (string $routeName) {
        $active = request()->routeIs($routeName);

        return $active
            ? 'border-primary text-primary dark:border-accent dark:text-accent-light'
            : 'border-transparent text-slate-600 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100';
    };
@endphp
<div class="tabs mb-4 flex flex-wrap gap-1 border-b border-slate-200 dark:border-navy-500">
    <a href="{{ route('admin.finances.overview') }}"
        class="btn shrink-0 rounded-none border-b-2 px-3 py-2.5 text-sm font-medium sm:px-4 {{ $linkClass('admin.finances.overview') }}">
        {{ __('finance.nav.overview') }}
    </a>
    <a href="{{ route('admin.finances.payments.index') }}"
        class="btn shrink-0 rounded-none border-b-2 px-3 py-2.5 text-sm font-medium sm:px-4 {{ $linkClass('admin.finances.payments.*') }}">
        {{ __('finance.nav.payments') }}
    </a>
    <a href="{{ route('admin.finances.fund-transactions.index') }}"
        class="btn shrink-0 rounded-none border-b-2 px-3 py-2.5 text-sm font-medium sm:px-4 {{ $linkClass('admin.finances.fund-transactions.*') }}">
        {{ __('finance.nav.fund_ledger') }}
    </a>
    <a href="{{ route('admin.finances.reports.index') }}"
        class="btn shrink-0 rounded-none border-b-2 px-3 py-2.5 text-sm font-medium sm:px-4 {{ $linkClass('admin.finances.reports.*') }}">
        {{ __('finance.nav.reports') }}
    </a>
</div>
