<x-app-layout title="{{ __('Review Request') }} #{{ $request->id }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('provider.requests.index') }}"
                class="text-sm font-medium text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent-light/80">
                ← {{ __('Back to List') }}
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
            {{-- Left: Request Details --}}
            <div class="space-y-4 lg:col-span-2 lg:space-y-6">
                <div class="card p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Ordered Items') }}</h3>
                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                        <table class="is-hoverable w-full text-left">
                            <thead>
                                <tr>
                                    <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Item') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Qty') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Price') }}</th>
                                    <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($request->items as $item)
                                    <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                        <td class="px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5">{{ $item->menuItem->name ?? __('Unknown') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $item->quantity }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $item->price_snapshot }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-700 dark:text-navy-100 sm:px-5">{{ number_format($item->price_snapshot * $item->quantity, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-t border-slate-200 bg-slate-100 font-bold dark:border-navy-600 dark:bg-navy-700/50">
                                    <td colspan="3" class="px-4 py-3 text-right text-slate-700 dark:text-navy-100 sm:px-5">{{ __('Grand Total') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-lg text-primary dark:text-accent-light sm:px-5">{{ number_format($request->reserved_amount, 2) }} {{ __('SAR') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Recipient Information') }}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs uppercase text-slate-500 dark:text-navy-400">{{ __('Name') }}</span>
                            <span class="font-medium text-slate-700 dark:text-navy-100">{{ $request->recipient->name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs uppercase text-slate-500 dark:text-navy-400">{{ __('Request Date') }}</span>
                            <span class="text-slate-700 dark:text-navy-100">{{ $request->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Actions --}}
            <div class="lg:col-span-1">
                <div class="card sticky top-6 p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Actions') }}</h3>

                    @if($request->status === 'PENDING')
                        <div class="space-y-4">
                            <form action="{{ route('provider.requests.update', $request->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="adopt">
                                <button type="submit"
                                    class="btn w-full bg-success text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90 dark:bg-success dark:hover:bg-success-focus dark:focus:bg-success-focus">
                                    {{ __('Adopt Request (My Fund)') }}
                                </button>
                                <p class="mt-1 text-center text-xs text-slate-500 dark:text-navy-400">
                                    {{ __('You will cover the cost of this request.') }}
                                </p>
                            </form>

                            <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>

                            <form action="{{ route('provider.requests.update', $request->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="approve">
                                <button type="submit"
                                    class="btn w-full bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus">
                                    {{ __('Approve (City Fund)') }}
                                </button>
                                <p class="mt-1 text-center text-xs text-slate-500 dark:text-navy-400">
                                    {{ __('Request will be paid from the City Fund.') }}
                                </p>
                            </form>

                            <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>

                            <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                                class="btn w-full border-2 border-error bg-transparent text-error hover:bg-error/10 focus:bg-error/10 dark:hover:bg-error/15 dark:focus:bg-error/15">
                                {{ __('Reject Request') }}
                            </button>

                            <form id="reject-form" action="{{ route('provider.requests.update', $request->id) }}"
                                method="POST" class="mt-4 hidden rounded-lg border border-error/30 bg-error/10 p-4 dark:bg-error/15 dark:border-error/20">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="reject">

                                <label for="reason" class="mb-2 block text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('Reason') }}</label>
                                <select id="reason" name="rejection_reason_code" required
                                    class="form-select form-select-lineone">
                                    <option value="">{{ __('Select a reason...') }}</option>
                                    <option value="Item Unavailable">{{ __('Item Unavailable') }}</option>
                                    <option value="Capacity Full">{{ __('Capacity Full') }}</option>
                                    <option value="Closing Soon">{{ __('Closing Soon') }}</option>
                                    <option value="Other">{{ __('Other') }}</option>
                                </select>

                                <textarea name="rejection_reason_note" rows="2"
                                    class="form-textarea form-textarea-lineone mt-3"
                                    placeholder="{{ __('Additional notes (optional)') }}"></textarea>

                                <button type="submit"
                                    class="btn mt-3 w-full bg-error text-white hover:bg-error-focus dark:bg-error dark:hover:bg-error-focus">
                                    {{ __('Confirm Rejection') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="rounded-lg bg-slate-100 p-4 text-center dark:bg-navy-700/50">
                            <span class="block font-bold text-slate-700 dark:text-navy-100">{{ __('Request') }} {{ $request->status }}</span>
                            <span class="text-sm text-slate-500 dark:text-navy-400">{{ __('No further actions available.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
