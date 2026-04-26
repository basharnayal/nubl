<x-app-layout title="{{ __('Donor Dashboard') }}" is-header-blur="true">
    <style>
        @keyframes donor-live-dot-blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.35; transform: scale(0.88); }
        }
        .donor-live-dot {
            animation: donor-live-dot-blink 1.2s ease-in-out infinite;
        }
    </style>
    <div class="space-y-6 pt-4 pb-8">

        {{-- SECTION 1 - WELCOME HEADER + SECTION 2 - TOP ACTIONS --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-navy-50">
                    @if(app()->getLocale() === 'ar')
                        <span>{{ __('Welcome,') }}</span>
                        <span class="mx-1 inline-block" dir="ltr">{{ auth()->user()->name ?? __('Donor') }}</span>
                    @else
                        <span>{{ __('Welcome,') }}</span>
                        <span class="mx-1 inline-block">{{ auth()->user()->name ?? __('Donor') }}</span>
                    @endif
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-navy-300">
                    {{ __('Your donations are tracked. Here is your impact.') }}
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-1 dark:border-navy-600 dark:bg-navy-800/40">
                <a href="{{ route('donor.donations.index') }}"
                   class="btn inline-flex h-10 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 font-medium text-slate-700 shadow-sm transition-colors hover:border-slate-400 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600">
                    <i class="fa-solid fa-receipt text-sm me-1.5" aria-hidden="true"></i>
                    {{ __('My Donations') }}
                </a>
                <a href="{{ route('donor.donations.new') }}"
                   class="btn inline-flex h-10 items-center gap-2 rounded-xl bg-primary px-5 font-medium text-white shadow-sm shadow-primary/20 transition-colors hover:bg-primary-focus dark:bg-accent dark:shadow-accent/20 dark:hover:bg-accent-focus">
                    <i class="fa-solid fa-heart text-sm" aria-hidden="true"></i>
                    <span>{{ __('Donate') }}</span>
                </a>
            </div>
        </div>

        {{-- SECTION 3 - IMPACT STAT CARDS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5">

            {{-- Card 1: Total Donated --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-navy-600 dark:bg-navy-800">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-2xl font-bold tabular-nums tracking-tight text-emerald-700 dark:text-emerald-300"
                           aria-label="{{ __('Total Donated') }}: {{ number_format($donorTotalDonated, 2) }}, {{ $donorDonationCount }} {{ __('donations') }}">
                            {{ number_format($donorTotalDonated, 2) }}
                            <span class="text-sm font-medium text-slate-400 dark:text-navy-400"><x-sar-symbol /></span>
                        </p>
                        <p class="mt-0.5 text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('Total Donated') }}</p>
                        <p class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-400 dark:text-navy-400">
                            {{ $donorDonationCount }} {{ __('donations') }}
                            @if($donorLastContributionHuman)
                                &middot; {{ __('Last') }} {{ $donorLastContributionHuman }}
                            @endif
                        </p>
                    </div>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <i class="fa-solid fa-coins text-sm" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            {{-- Card 2: Requests Delivered --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-navy-600 dark:bg-navy-800">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-2xl font-bold tabular-nums tracking-tight text-info"
                           aria-label="{{ __('Requests Delivered') }}: {{ $donorRequestsDelivered }} {{ __('people helped') }}">
                            {{ $donorRequestsDelivered }}
                        </p>
                        <p class="mt-0.5 text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('Requests Delivered') }}</p>
                        <p class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-400 dark:text-navy-400">{{ __('people helped') }}</p>
                    </div>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info/10 text-info">
                        <i class="fa-solid fa-box-open text-sm" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            {{-- Card 3: Amount Allocated --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-navy-600 dark:bg-navy-800">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-2xl font-bold tabular-nums tracking-tight text-amber-700 dark:text-amber-300"
                           aria-label="{{ __('Amount Allocated') }}: {{ number_format($donorAmountAllocated, 2) }}, {{ $donorRequestsFunded }} {{ __('requests funded') }}">
                            {{ number_format($donorAmountAllocated, 2) }}
                            <span class="text-sm font-medium text-slate-400 dark:text-navy-400"><x-sar-symbol /></span>
                        </p>
                        <p class="mt-0.5 text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('Amount Allocated') }}</p>
                        <p class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-400 dark:text-navy-400">{{ $donorRequestsFunded }} {{ __('requests funded') }}</p>
                    </div>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                        <i class="fa-solid fa-basket-shopping text-sm" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

        </div>

        {{-- SECTION 4 - COMMUNITY NEED --}}
        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-navy-600 dark:bg-navy-800/20">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600 dark:text-navy-300 lg:min-w-0 lg:flex-1">
                    <span class="inline-flex items-center gap-2">
                        <span class="donor-live-dot inline-block h-2.5 w-2.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                        <span class="font-semibold text-slate-800 dark:text-navy-100">{{ $recipientsWaiting }} {{ __('people waiting') }}</span>
                    </span>
                    <span class="text-slate-300 dark:text-navy-500">&middot;</span>
                    <span class="font-semibold text-slate-800 dark:text-navy-100">{{ $pendingRequestsCount }} {{ __('requests') }}</span>
                    <span class="text-slate-300 dark:text-navy-500">&middot;</span>
                    <span class="font-medium text-slate-700 dark:text-navy-200">{{ __('Needed') }}:</span>
                    <span class="font-semibold text-amber-700 dark:text-amber-300"><x-sar-symbol /> {{ number_format($pendingAmount) }}</span>
                </div>
                <a href="{{ route('donor.donations.new') }}"
                   class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-100 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-100 dark:hover:bg-navy-600 lg:ms-auto">
                    {{ __('Help now') }} <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>

        {{-- SECTION 5 - YOUR IMPACT OVER TIME --}}
        <div class="card p-5 sm:p-6">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Your Impact Over Time') }}</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('Donations by month') }}</p>
            </div>

            @if(empty($donorChartData['categories']))
                <div class="flex min-h-[200px] flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/60 px-4 text-center dark:border-navy-600 dark:bg-navy-800/30">
                    <i class="fa-solid fa-chart-line text-2xl text-slate-300 dark:text-navy-500" aria-hidden="true"></i>
                    <p class="mt-2 text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('No donations yet') }}</p>
                    <p class="mt-0.5 max-w-xs text-xs text-slate-500 dark:text-navy-400">{{ __('Your impact will appear here after you donate.') }}</p>
                    <a href="{{ route('donor.donations.new') }}"
                       class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                        <i class="fa-solid fa-heart text-[10px]" aria-hidden="true"></i>
                        {{ __('Make your first donation') }}
                    </a>
                </div>
            @else
                <div id="donor-impact-chart" class="-mx-2 min-h-[220px]"></div>
                <script type="application/json" id="donor-chart-data">
                @json(['series' => $donorChartData['series'], 'categories' => $donorChartData['categories'], 'label' => __('Amount Donated')])
                </script>
                <script>
                (async function() {
                    async function initDonorChart() {
                        var el = document.getElementById('donor-impact-chart');
                        var dataEl = document.getElementById('donor-chart-data');
                        if (!el || !dataEl) return;
                        try {
                            var ApexCharts = await window.loadApexCharts();
                            var data = JSON.parse(dataEl.textContent);
                            var config = {
                                series: [{ name: data.label, data: data.series }],
                                chart: { type: 'area', height: 260, parentHeightOffset: 0, toolbar: { show: false }, fontFamily: 'inherit' },
                                colors: ['#10b981'],
                                dataLabels: { enabled: false },
                                stroke: { curve: 'smooth', width: 2 },
                                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
                                xaxis: { categories: data.categories, labels: { style: { fontSize: '11px' } } },
                                yaxis: { labels: { style: { fontSize: '11px' } } },
                                tooltip: { x: { format: 'MMM yyyy' } },
                                legend: { show: false },
                                grid: { padding: { left: 0, right: 0 }, borderColor: 'rgba(148, 163, 184, 0.1)' }
                            };
                            if (data.series.length === 0 || data.series.every(function(v) { return v === 0; })) {
                                config.yaxis = { min: 0, max: 5, forceNiceScale: true };
                            }
                            el._chart = new ApexCharts(el, config);
                            el._chart.render();
                        } catch (e) { console.warn('Donor chart init:', e); }
                    }
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() { initDonorChart(); });
                    } else {
                        initDonorChart();
                    }
                })();
                </script>
            @endif
        </div>

        {{-- SECTION 6 - RECENT IMPACT --}}
        <div class="card p-5 sm:p-6">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Recent Impact') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('When and how your donations helped — no personal data.') }}</p>
                </div>
                <a href="{{ route('donor.donations.index') }}"
                   class="mt-2 text-sm font-medium text-primary hover:underline dark:text-accent-light sm:mt-0">
                    {{ __('View all receipts') }} <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            @if(count($donorImpactTimeline) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-navy-600">
                                <th class="pb-3 pr-4 font-medium text-slate-500 dark:text-navy-400">{{ __('Request') }}</th>
                                <th class="pb-3 pr-4 font-medium text-slate-500 dark:text-navy-400">{{ __('When') }}</th>
                                <th class="pb-3 pr-4 font-medium text-slate-500 dark:text-navy-400">{{ __('Amount') }}</th>
                                <th class="pb-3 font-medium text-slate-500 dark:text-navy-400">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($donorImpactTimeline as $row)
                                <tr class="border-b border-slate-100 transition-colors last:border-0 hover:bg-slate-50/70 dark:border-navy-700/50 dark:hover:bg-navy-750/30">
                                    <td class="py-3 pr-4">
                                        <span class="font-mono text-xs text-slate-600 dark:text-navy-300">{{ $row['pseudonymous_id'] }}</span>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ $row['type'] }}</p>
                                    </td>
                                    <td class="py-3 pr-4 text-slate-700 dark:text-navy-200">{{ $row['date'] }} {{ $row['time'] }}</td>
                                    <td class="py-3 pr-4 font-medium text-slate-800 dark:text-navy-100"><x-sar-symbol /> {{ number_format($row['amount'], 2) }}</td>
                                    <td class="py-3">
                                        @php
                                            $badgeClass = match($row['status_key'] ?? '') {
                                                'FULFILLED'  => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                                'REDEEMABLE' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                                default      => 'bg-slate-100 text-slate-600 dark:bg-navy-600 dark:text-navy-300',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold {{ $badgeClass }}">{{ $row['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex min-h-[180px] flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/40 px-4 text-center dark:border-navy-600 dark:bg-navy-800/20">
                    <i class="fa-solid fa-inbox text-2xl text-slate-300 dark:text-navy-500" aria-hidden="true"></i>
                    <p class="mt-2 text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('No records yet') }}</p>
                    <p class="mt-0.5 max-w-xs text-xs text-slate-500 dark:text-navy-400">{{ __('Your impact will appear here after donations are delivered.') }}</p>
                </div>
            @endif
        </div>

        {{-- SECTION 7 - PRIVACY / TRANSPARENCY NOTE --}}
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 py-5 px-4 dark:border-navy-600 dark:bg-navy-800/30">
            <div class="flex flex-col items-center justify-center gap-2">
                <p class="text-center text-sm text-slate-600 dark:text-navy-300">
                    <i class="fa-solid fa-shield-halved me-1.5 text-slate-400 dark:text-navy-400" aria-hidden="true"></i>
                    {{ __('Every riyal goes to a specific request. Full transparency, privacy preserved.') }}
                </p>
                <button type="button" @click="$dispatch('open-modal', 'how-donation-helps')"
                    class="text-sm font-medium text-primary hover:underline dark:text-accent-light">
                    {{ __('How does donation help?') }} <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </div>

    </div>

    {{-- How Does Donation Help Modal --}}
    <x-lineone-modal id="how-donation-helps" :title="__('How does donation help?')" size="lg">
        <div class="space-y-5">
            <div class="flex items-start gap-4 rounded-lg bg-slate-50 p-4 dark:bg-navy-600/30">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                    <i class="fa-solid fa-heart text-primary dark:text-accent" aria-hidden="true"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-800 dark:text-navy-100">{{ __('Direct impact') }}</h4>
                    <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">
                        {{ __('Every riyal goes to a specific request: a family meal, a food basket, or daily support. No anonymous transfers — you see exactly where your contribution goes.') }}
                    </p>
                </div>
            </div>
            <div class="flex items-start gap-4 rounded-lg bg-slate-50 p-4 dark:bg-navy-600/30">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success/10">
                    <i class="fa-solid fa-shield-halved text-success" aria-hidden="true"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-800 dark:text-navy-100">{{ __('Full transparency') }}</h4>
                    <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">
                        {{ __('You see when and how your donation was used — date, amount, and delivery status. We protect the dignity of beneficiaries by never sharing personal data.') }}
                    </p>
                </div>
            </div>
            <div class="flex items-start gap-4 rounded-lg bg-slate-50 p-4 dark:bg-navy-600/30">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info/10">
                    <i class="fa-solid fa-hand-holding-heart text-info" aria-hidden="true"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-800 dark:text-navy-100">{{ __('You make the difference') }}</h4>
                    <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">
                        {{ __('You are not a number in a statistic. You are a partner in real change for people in need.') }}
                    </p>
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="button" @click="$dispatch('close-modal', 'how-donation-helps')"
                    class="btn bg-primary px-4 py-2 text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                    {{ __('Got it') }}
                </button>
            </div>
        </div>
    </x-lineone-modal>
</x-app-layout>
