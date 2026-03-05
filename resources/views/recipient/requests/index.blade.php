<x-app-layout title="{{ __('My Requests') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('My Requests') }}
                    </h2>
                </div>

                <div class="card mt-3">
                    @if($requests->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">
                            <p class="mb-4">{{ __('You haven\'t made any requests yet.') }}</p>
                            <x-lineone-button :href="route('recipient.providers.index')" variant="primary" size="sm">
                                {{ __('Browse Providers') }}
                            </x-lineone-button>
                        </div>
                    @else
                        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                            <table class="is-hoverable w-full text-left">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('ID') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Provider') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Date') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Items') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Total Amount') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Status') }}</th>
                                        <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                        <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <p class="font-medium text-primary dark:text-accent-light">#{{ $request->id }}</p>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <span class="font-medium text-slate-700 dark:text-navy-100">{{ $request->provider->name }}</span>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="font-medium text-slate-700 dark:text-navy-100">{{ $request->created_at->format('M d, Y') }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                {{ $request->items->sum('quantity') }} {{ __('items') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <p class="text-sm-plus font-medium text-slate-700 dark:text-navy-100">{{ $request->reserved_amount }} {{ __('SAR') }}</p>
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
                                                        'CANCELLED' => ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => __('Cancelled')],
                                                    ];
                                                    $config = $statusConfig[$request->status] ?? ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => str_replace('_', ' ', $request->status)];
                                                @endphp
                                                <span class="badge rounded-full {{ $config['class'] }}">{{ $config['label'] }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('recipient.requests.show', $request->id) }}"
                                                        class="font-medium text-primary outline-hidden transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent-light/80">
                                                        {{ __('View') }}
                                                    </a>
                                                    @if($request->isCancellableByRecipient())
                                                        <button type="button"
                                                            @click="$dispatch('open-modal', 'cancel-request-{{ $request->id }}')"
                                                            class="font-medium text-error outline-hidden transition-colors hover:text-error-focus">
                                                            {{ __('Cancel') }}
                                                        </button>
                                                        <x-lineone-modal id="cancel-request-{{ $request->id }}" :title="__('Cancel Request')" size="md">
                                                            <p class="text-slate-600 dark:text-navy-300">{{ __('Are you sure you want to cancel this request?') }}</p>
                                                            <form action="{{ route('recipient.requests.cancel', $request->id) }}" method="POST" class="mt-4 flex justify-end gap-2">
                                                                @csrf
                                                                <button type="button" @click="$dispatch('close-modal', 'cancel-request-{{ $request->id }}')"
                                                                    class="btn border-slate-300 font-medium text-slate-700 hover:bg-slate-150 dark:border-navy-600 dark:text-navy-100 dark:hover:bg-navy-600">
                                                                    {{ __('Cancel') }}
                                                                </button>
                                                                <button type="submit" class="btn bg-error font-medium text-white hover:bg-error-focus">
                                                                    {{ __('Yes, Cancel Request') }}
                                                                </button>
                                                            </form>
                                                        </x-lineone-modal>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 px-4 sm:px-5">
                            {{ $requests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
