<x-app-layout title="{{ __('Donor Dashboard') }}" is-header-blur="true">
    <div class="mt-4 space-y-6 sm:mt-5 sm:space-y-8 lg:mt-6 lg:space-y-10">
        {{-- Calm Header: ما يهم الداعم القلق --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800 dark:text-navy-100 sm:text-2xl">
                    {{ __('Welcome,') }} {{ auth()->user()->name ?? __('Donor') }}
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-navy-400">
                    {{ __('Your donations are tracked. Here is your impact.') }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('donor.donations.index') }}" class="btn border-slate-300 font-medium text-slate-700 hover:bg-slate-100 dark:border-navy-600 dark:text-navy-200 dark:hover:bg-navy-600">
                    {{ __('My Donations') }}
                </a>
                <a href="{{ route('donor.donations.new') }}" class="btn inline-flex items-center gap-2 bg-primary px-5 py-2.5 text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                    <i class="fa-solid fa-heart text-sm"></i>
                    <span>{{ __('Donate') }}</span>
                </a>
            </div>
        </div>

        {{-- Primary Metrics: الوصول السريع لما يهمك --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5">
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-navy-400">{{ __('Total Donated') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-navy-100">
                            {{ number_format($donorTotalDonated, 2) }} <span class="text-sm font-normal text-slate-500 dark:text-navy-400">{{ __('SAR') }}</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">
                            {{ $donorDonationCount }} {{ __('donations') }}
                            @if($donorLastContributionHuman)
                                <span class="text-slate-400 dark:text-navy-500">· {{ __('Last') }} {{ $donorLastContributionHuman }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                        <i class="fa-solid fa-coins text-lg text-primary dark:text-accent"></i>
                    </div>
                </div>
            </div>
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-navy-400">{{ __('Requests Delivered') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-navy-100">{{ $donorRequestsDelivered }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('people helped') }}</p>
                    </div>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success/10">
                        <i class="fa-solid fa-box-open text-lg text-success"></i>
                    </div>
                </div>
            </div>
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-navy-400">{{ __('Amount Allocated') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-navy-100">
                            {{ number_format($donorAmountAllocated, 2) }} <span class="text-sm font-normal text-slate-500 dark:text-navy-400">{{ __('SAR') }}</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ $donorRequestsFunded }} {{ __('requests funded') }}</p>
                    </div>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info/10">
                        <i class="fa-solid fa-clipboard-check text-lg text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Platform CTA (compact) --}}
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-navy-600 dark:bg-navy-800/50">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600 dark:text-navy-300">
                    <span class="font-medium text-slate-700 dark:text-navy-200">{{ $recipientsWaiting }} {{ __('people waiting') }}</span>
                    {{ __('·') }} {{ $pendingRequestsCount }} {{ __('requests') }} {{ __('·') }} {{ number_format($pendingAmount) }} {{ __('SAR needed') }}
                </p>
                <a href="{{ route('donor.donations.new') }}" class="text-sm font-medium text-primary hover:underline dark:text-accent-light">
                    {{ __('Help now') }} →
                </a>
            </div>
        </div>

        {{-- Chart --}}
        <div class="card p-5 sm:p-6">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Your Impact Over Time') }}</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('Donations by month') }}</p>
            </div>
            @if(empty($donorChartData['categories']))
                <div class="flex min-h-[220px] items-center justify-center rounded-lg bg-slate-50 dark:bg-navy-800/30">
                    <div class="text-center">
                        <i class="fa-solid fa-chart-line text-3xl text-slate-300 dark:text-navy-500"></i>
                        <p class="mt-2 text-sm font-medium text-slate-600 dark:text-navy-400">{{ __('No donations yet') }}</p>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-500">{{ __('Your impact will appear here after you donate.') }}</p>
                    </div>
                </div>
            @else
                <div id="donor-impact-chart" class="-mx-2 min-h-[220px]"></div>
                <script type="application/json" id="donor-chart-data">
                @json(['series' => $donorChartData['series'], 'categories' => $donorChartData['categories'], 'label' => __('Amount Donated')])
                </script>
                <script>
                (function() {
                    var retries = 0;
                    function initDonorChart() {
                        var el = document.getElementById('donor-impact-chart');
                        var dataEl = document.getElementById('donor-chart-data');
                        if (!el || !dataEl) return;
                        if (typeof ApexCharts === 'undefined' && retries++ < 20) {
                            setTimeout(initDonorChart, 50);
                            return;
                        }
                        try {
                            var data = JSON.parse(dataEl.textContent);
                            var config = {
                                series: [{ name: data.label, data: data.series }],
                                chart: { type: 'area', height: 260, parentHeightOffset: 0, toolbar: { show: false }, fontFamily: 'inherit' },
                                colors: ['#6366f1'],
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
                        document.addEventListener('DOMContentLoaded', initDonorChart);
                    } else {
                        initDonorChart();
                    }
                })();
                </script>
            @endif
        </div>

        {{-- Recent Impact: تتبع كل تسليم — للاطمئنان --}}
        <div class="card p-5 sm:p-6">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Recent Impact') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ __('When and how your donations helped — no personal data.') }}</p>
                </div>
                <a href="{{ route('donor.donations.index') }}" class="mt-2 text-sm font-medium text-primary hover:underline dark:text-accent-light sm:mt-0">
                    {{ __('View all receipts') }} →
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
                                <tr class="border-b border-slate-100 dark:border-navy-700/50 last:border-0">
                                    <td class="py-3 pr-4">
                                        <span class="font-mono text-xs text-slate-600 dark:text-navy-300">{{ $row['pseudonymous_id'] }}</span>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ $row['type'] }}</p>
                                    </td>
                                    <td class="py-3 pr-4 text-slate-700 dark:text-navy-200">{{ $row['date'] }} {{ $row['time'] }}</td>
                                    <td class="py-3 pr-4 font-medium text-slate-800 dark:text-navy-100">{{ number_format($row['amount'], 2) }} {{ __('SAR') }}</td>
                                    <td class="py-3">
                                        @php
                                            $badgeClass = match($row['status_key'] ?? '') {
                                                'FULFILLED' => 'bg-success/10 text-success',
                                                'REDEEMABLE' => 'bg-info/10 text-info',
                                                default => 'bg-slate-100 text-slate-600 dark:bg-navy-600 dark:text-navy-300',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">{{ $row['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 py-12 dark:border-navy-600">
                    <i class="fa-solid fa-inbox text-3xl text-slate-300 dark:text-navy-500"></i>
                    <p class="mt-2 text-sm text-slate-500 dark:text-navy-400">{{ __('No records yet') }}</p>
                    <p class="mt-0.5 text-xs text-slate-400 dark:text-navy-500">{{ __('Your impact will appear here after donations are delivered.') }}</p>
                </div>
            @endif
        </div>

        {{-- Minimal Footer --}}
        <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50/50 py-6 dark:border-navy-600 dark:bg-navy-800/30">
            <p class="text-center text-sm text-slate-600 dark:text-navy-400">
                {{ __('Every riyal goes to a specific request. Full transparency, privacy preserved.') }}
            </p>
            <button type="button" @click="$dispatch('open-modal', 'how-donation-helps')"
                class="text-sm font-medium text-primary hover:underline dark:text-accent-light">
                {{ __('How does donation help?') }} →
            </button>
        </div>
    </div>

    {{-- How Does Donation Help Modal --}}
    <x-lineone-modal id="how-donation-helps" :title="__('How does donation help?')" size="lg">
        <div class="space-y-5">
            <div class="flex items-start gap-4 rounded-lg bg-primary/5 p-4 dark:bg-accent/5">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                    <i class="fa-solid fa-heart text-primary dark:text-accent"></i>
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
                    <i class="fa-solid fa-shield-halved text-success"></i>
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
                    <i class="fa-solid fa-hand-holding-heart text-info"></i>
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
