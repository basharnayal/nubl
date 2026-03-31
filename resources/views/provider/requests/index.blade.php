<x-app-layout title="{{ __('Incoming Requests') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('Incoming Requests') }}
                    </h2>
                </div>

                <div class="card mt-3">
                    @if($requests->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">
                            {{ __('No requests found.') }}
                        </div>
                    @else
                        <div class="is-scrollbar-hidden min-w-full overflow-x-auto rounded-lg">
                            <table class="is-hoverable w-full text-start">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap rounded-ss-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Request') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Reference') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Items') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Total') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Date') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Status') }}</th>
                                        <th class="whitespace-nowrap rounded-se-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                        <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <p class="font-medium text-primary dark:text-accent-light">#{{ $request->id }}</p>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-200/90 dark:bg-navy-600" title="{{ __('Anonymous reference') }}" aria-hidden="true">
                                                        <i class="fa-solid fa-fingerprint text-sm text-slate-600 dark:text-navy-200"></i>
                                                    </div>
                                                    <div class="min-w-0 text-start" dir="ltr">
                                                        <span class="font-mono text-xs font-medium text-slate-700 dark:text-navy-100">{{ \App\Support\PseudonymousRequestId::make($request->id) }}</span>
                                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-400">{{ \App\Support\RequestTypeLabel::forRequest($request) }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                {{ $request->items->sum('quantity') }} {{ __('items') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <p class="text-sm-plus font-medium text-slate-700 dark:text-navy-100"> {{ $request->reserved_amount }} {{ __('SAR') }}</p>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="font-medium text-slate-700 dark:text-navy-100">{{ $request->created_at->format('M d, Y') }}</div>
                                                <div class="text-xs text-slate-500 dark:text-navy-300">{{ $request->created_at->format('H:i') }}</div>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                @php
                                                    $statusConfig = [
                                                        'REQUESTED' => ['class' => 'bg-warning/10 text-warning dark:bg-warning/15', 'label' => __('Requested')],
                                                        'APPROVED' => ['class' => 'bg-info/10 text-info dark:bg-info/15', 'label' => __('Approved')],
                                                        'ADMIN_PENDING' => ['class' => 'bg-warning/10 text-warning dark:bg-warning/15', 'label' => __('Admin Pending')],
                                                        'ADMIN_APPROVED' => ['class' => 'bg-success/10 text-success dark:bg-success/15', 'label' => __('Admin Approved')],
                                                        'REDEEMABLE' => ['class' => 'bg-success/10 text-success dark:bg-success/15', 'label' => __('Redeemable')],
                                                        'FULFILLED' => ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => __('Fulfilled')],
                                                        'REJECTED' => ['class' => 'bg-error/10 text-error dark:bg-error/15', 'label' => __('Rejected')],
                                                        'ADMIN_REJECTED' => ['class' => 'bg-error/10 text-error dark:bg-error/15', 'label' => __('Rejected')],
                                                    ];
                                                    $config = $statusConfig[$request->status] ?? ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => $request->status];
                                                @endphp
                                                <span class="badge rounded-full {{ $config['class'] }}">{{ $config['label'] }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <div class="flex flex-col gap-1.5">
                                                    <a href="{{ route('provider.requests.show', $request) }}"
                                                        class="font-medium text-primary outline-hidden transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent-light/80">
                                                        {{ __('Review') }}
                                                    </a>
                                                    @if($request->needsProviderFulfillmentProof())
                                                        <a href="{{ route('provider.proof.index', $request->redemption->id) }}"
                                                            class="text-sm font-medium text-primary hover:underline dark:text-accent-light">
                                                            {{ __('Upload proof') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="flex flex-col justify-between space-y-4 px-4 py-4 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
                            {{ $requests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
