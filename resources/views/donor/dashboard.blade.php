<x-app-layout title="{{ __('Donor Dashboard') }}" is-header-blur="true">
    <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">
        {{-- Hero Welcome --}}
        <div class="col-span-12">
            <div class="card relative overflow-hidden bg-linear-to-br from-primary via-primary-focus to-secondary px-6 py-8 dark:from-accent dark:via-accent-focus dark:to-accent">
                <div class="relative z-10 flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                    <div>
                        <h1 class="text-2xl font-bold tracking-wide text-white sm:text-3xl">
                            {{ __('Welcome,') }} {{ auth()->user()->name ?? __('Donor') }} 💝
                        </h1>
                        <p class="mt-2 max-w-xl text-base text-white/90">
                            {{ __('This is your impact — yours alone. See how your support has helped, with full transparency and complete privacy for those in need.') }}
                        </p>
                    </div>
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                        <i class="fa-solid fa-heart-pulse text-3xl text-white"></i>
                    </div>
                </div>
                <div class="absolute bottom-0 right-0 overflow-hidden opacity-20">
                    <i class="fa-solid fa-hands-holding-heart text-9xl translate-x-1/4 translate-y-1/4 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Your Impact Cards --}}
        <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
            <div class="card flex-row justify-between p-4">
                <div>
                    <p class="text-xs-plus uppercase tracking-wide text-slate-500 dark:text-navy-300">{{ __('Total Donated') }}</p>
                    <div class="mt-4 flex items-baseline space-x-1">
                        <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">
                            {{ number_format($donorTotalDonated) }}
                        </p>
                        <p class="text-sm font-medium text-slate-500 dark:text-navy-400">{{ __('SAR') }}</p>
                    </div>
                </div>
                <div class="mask is-squircle flex size-12 shrink-0 items-center justify-center bg-primary/10 dark:bg-accent/10">
                    <i class="fa-solid fa-coins text-xl text-primary dark:text-accent-light"></i>
                </div>
            </div>
            <div class="card flex-row justify-between p-4">
                <div>
                    <p class="text-xs-plus uppercase tracking-wide text-slate-500 dark:text-navy-300">{{ __('Requests Funded') }}</p>
                    <div class="mt-4 flex items-baseline space-x-1">
                        <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">{{ $donorRequestsFunded }}</p>
                    </div>
                </div>
                <div class="mask is-squircle flex size-12 shrink-0 items-center justify-center bg-success/10">
                    <i class="fa-solid fa-clipboard-check text-xl text-success"></i>
                </div>
            </div>
            <div class="card flex-row justify-between p-4">
                <div>
                    <p class="text-xs-plus uppercase tracking-wide text-slate-500 dark:text-navy-300">{{ __('Beneficiaries Helped') }}</p>
                    <div class="mt-4 flex items-baseline space-x-1">
                        <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100">{{ $donorBeneficiariesHelped }}</p>
                    </div>
                </div>
                <div class="mask is-squircle flex size-12 shrink-0 items-center justify-center bg-info/10">
                    <i class="fa-solid fa-users text-xl text-info"></i>
                </div>
            </div>
            <div class="card flex-row justify-between p-4">
                <div>
                    <p class="text-xs-plus uppercase tracking-wide text-slate-500 dark:text-navy-300">{{ __('Last Contribution') }}</p>
                    <div class="mt-4 flex items-baseline space-x-1">
                        <p class="text-lg font-semibold text-slate-700 dark:text-navy-100">
                            {{ $donorLastContribution?->diffForHumans() ?? __('—') }}
                        </p>
                    </div>
                </div>
                <div class="mask is-squircle flex size-12 shrink-0 items-center justify-center bg-warning/10">
                    <i class="fa-solid fa-clock-rotate-left text-xl text-warning"></i>
                </div>
            </div>
        </div>

        {{-- Platform Needs You Now --}}
        <div class="col-span-12">
            <div class="card overflow-hidden border-l-4 border-warning bg-warning/5 px-5 py-5 dark:bg-warning/10 dark:border-warning">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-warning/20">
                            <i class="fa-solid fa-hand-holding-heart text-2xl text-warning"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-navy-100">
                                {{ __('The Platform Needs You Now') }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">
                                {{ $recipientsWaiting }} {{ __('people waiting for support') }} •
                                {{ $pendingRequestsCount }} {{ __('requests awaiting funding') }} •
                                {{ number_format($pendingAmount) }} {{ __('SAR needed') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col gap-2 sm:w-48">
                        <div class="flex justify-between text-xs font-medium">
                            <span>{{ __('Funded') }}</span>
                            <span>{{ $fundedPercent }}%</span>
                        </div>
                        <div class="progress h-2.5 bg-slate-200 dark:bg-navy-600">
                            <div class="relative overflow-hidden rounded-full bg-warning transition-all duration-500" style="width: {{ $fundedPercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart + Transparency Sidebar --}}
        <div class="col-span-12 lg:col-span-8">
            <div class="card px-4 pb-4 sm:px-5">
                <div class="my-4 flex h-8 items-center justify-between">
                    <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                        {{ __('Your Impact Over the Last 2 Years') }}
                    </h2>
                </div>
                <div class="ax-transparent-gridline -mx-2 h-64 sm:h-72" x-data="{
                    init() {
                        $nextTick(() => {
                            if (this.$el._x_chart) return;
                            this.$el._x_chart = new ApexCharts(this.$el, {
                                series: [{ name: '{{ __('Requests Funded') }}', data: @json($donorChartData['series']) }],
                                chart: { type: 'area', height: '100%', toolbar: { show: false } },
                                colors: ['#6366f1'],
                                dataLabels: { enabled: false },
                                stroke: { curve: 'smooth', width: 2 },
                                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
                                xaxis: { categories: @json($donorChartData['categories']) },
                                tooltip: { x: { format: 'MMM yyyy' } },
                                legend: { show: false },
                                grid: { padding: { left: 0, right: 0 } }
                            });
                            this.$el._x_chart.render();
                        });
                    }
                }" x-init="init()"></div>
            </div>
        </div>
        <div class="col-span-12 lg:col-span-4">
            <div class="card px-4 pb-4 sm:px-5">
                <div class="my-4 flex h-8 items-center justify-between">
                    <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                        {{ __('Transparency of Your Contribution') }}
                    </h2>
                </div>
                <p class="text-xs-plus text-slate-500 dark:text-navy-400">
                    {{ __('How your donated amounts have helped beneficiaries:') }}
                </p>
                <div class="mt-4 space-y-4">
                    <div class="flex items-center justify-between rounded-lg bg-slate-100 px-4 py-3 dark:bg-navy-600">
                        <span class="text-sm text-slate-600 dark:text-navy-300">{{ __('Requests from your funds') }}</span>
                        <span class="font-semibold text-slate-800 dark:text-navy-100">{{ $donorTransparency['requests_from_your_funds'] }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-100 px-4 py-3 dark:bg-navy-600">
                        <span class="text-sm text-slate-600 dark:text-navy-300">{{ __('Meals / items delivered') }}</span>
                        <span class="font-semibold text-slate-800 dark:text-navy-100">{{ $donorTransparency['meals_items_delivered'] }}</span>
                    </div>
                    <div class="space-y-2">
                        <p class="text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('Breakdown') }}</p>
                        <div class="flex gap-2">
                            <div class="flex-1 rounded-lg bg-primary/10 py-2 text-center text-xs font-medium text-primary dark:bg-accent/10 dark:text-accent-light">
                                {{ $donorTransparency['meals_percent'] }}% {{ __('Meals') }}
                            </div>
                            <div class="flex-1 rounded-lg bg-success/10 py-2 text-center text-xs font-medium text-success">
                                {{ $donorTransparency['baskets_percent'] }}% {{ __('Baskets') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- How Beneficiaries Benefited (No PII) --}}
        <div class="col-span-12">
            <div class="card px-4 pb-4 sm:px-5">
                <div class="my-4 flex h-8 items-center justify-between">
                    <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                        {{ __('How Beneficiaries Benefited From Your Donations') }}
                    </h2>
                </div>
                <p class="mb-4 text-xs-plus text-slate-500 dark:text-navy-400">
                    {{ __('Date and time of delivery — no names or personal data. Full transparency with privacy preserved.') }}
                </p>
                @if(count($donorImpactTimeline) > 0)
                    <div class="scrollbar-sm overflow-x-auto">
                        <table class="is-hoverable w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-navy-500">
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-medium uppercase text-slate-500 dark:text-navy-400">{{ __('Date') }}</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-medium uppercase text-slate-500 dark:text-navy-400">{{ __('Time') }}</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-medium uppercase text-slate-500 dark:text-navy-400">{{ __('Amount') }}</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-medium uppercase text-slate-500 dark:text-navy-400">{{ __('Type') }}</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-xs font-medium uppercase text-slate-500 dark:text-navy-400">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($donorImpactTimeline as $row)
                                    <tr class="border-b border-slate-150 dark:border-navy-500">
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100">{{ $row['date'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-navy-300">{{ $row['time'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">{{ $row['amount'] }} {{ __('SAR') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">{{ $row['type'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3"><span class="badge bg-success/10 text-success">{{ $row['status'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 py-12 dark:border-navy-600">
                        <i class="fa-solid fa-inbox text-4xl text-slate-300 dark:text-navy-500"></i>
                        <p class="mt-3 text-sm font-medium text-slate-500 dark:text-navy-400">{{ __('No records yet') }}</p>
                        <p class="mt-1 text-xs text-slate-400 dark:text-navy-500">{{ __('When you donate, your impact will appear here with full transparency.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- How Your Donation Helps (Emotional) --}}
        <div class="col-span-12">
            <div class="card overflow-hidden bg-linear-to-br from-slate-50 to-primary/5 dark:from-navy-800 dark:to-accent/5">
                <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:justify-between lg:p-8">
                    <div class="max-w-2xl">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-navy-100">
                            {{ __('How Does Your Donation Help?') }}
                        </h3>
                        <p class="mt-4 text-slate-600 dark:text-navy-300">
                            {{ __('Every riyal you give does not go to anonymous accounts. It goes to a specific request: a family meal, a food basket, or daily support for someone in need.') }}
                        </p>
                        <p class="mt-3 text-slate-600 dark:text-navy-300">
                            {{ __('Here you see exactly when and how your contribution was used — without revealing anyone\'s identity. We protect the dignity of beneficiaries while giving you the transparency you deserve.') }}
                        </p>
                        <p class="mt-4 font-semibold text-primary dark:text-accent-light">
                            {{ __('You are not a number in a statistic. You are a partner in real change.') }}
                        </p>
                    </div>
                    <div class="shrink-0">
                        <a href="#" class="btn flex items-center gap-2 bg-primary px-6 py-3 text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                            <i class="fa-solid fa-heart"></i>
                            <span>{{ __('Donate Now') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
