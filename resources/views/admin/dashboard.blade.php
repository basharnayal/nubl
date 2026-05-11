<x-app-layout :title="__('dashboard.title')" is-header-blur="true">
    <div class="space-y-6 pt-4 pb-8">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-navy-50">
                        {{ __('dashboard.title') }}
                    </h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-navy-300">
                        {{ __('dashboard.subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-navy-600 dark:bg-navy-800">
                <i class="fa-regular fa-clock text-xs text-slate-400 dark:text-navy-400" aria-hidden="true"></i>
                <span class="text-xs font-medium text-slate-500 dark:text-navy-300">
                    {{ now()->format('D, d M Y') }}
                </span>
                <span class="text-xs text-slate-300 dark:text-navy-600" aria-hidden="true">·</span>
                <span class="tabular-nums text-xs font-semibold text-slate-700 dark:text-navy-100">
                    {{ now()->format('H:i') }}
                </span>
            </div>
        </div>

        {{-- 1. SYSTEM STATUS STRIP --}}
        @include('admin.dashboard._status-strip', ['statuses' => $overview['system_status']])

        {{-- 2. NEEDS YOUR ATTENTION + KPI CARDS (2/3 + 1/3) --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            <div class="xl:col-span-2">
                @include('admin.dashboard._attention-queue', ['items' => $overview['attention_items']])
            </div>

            <div>
                @include('admin.dashboard._kpi-grid', ['kpis' => $overview['kpis']])
            </div>
        </div>

        {{-- 3. FINANCIAL SNAPSHOT --}}
        @include('admin.dashboard._financial-snapshot', ['financial' => $overview['financial']])

        {{-- 4. PLATFORM SNAPSHOT + RECENT AUDIT (1/2 + 1/2) --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @include('admin.dashboard._platform-snapshot', ['platform' => $overview['platform']])
            @include('admin.dashboard._recent-audit',      ['activities' => $overview['recent_activity']])
        </div>

    </div>
</x-app-layout>
