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
                <div class="card border-slate-150 p-5 shadow-sm dark:border-navy-600 sm:p-6">
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-800 dark:text-navy-100">
                            {{ __('Requested Items') }}
                        </h3>
                    </div>
                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                        <table class="w-full text-start">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-navy-500">
                                    <th class="whitespace-nowrap px-4 py-3 text-start font-semibold uppercase text-slate-500 dark:text-navy-300 lg:px-5">
                                        {{ __('Item') }}
                                    </th>
                                    <th class="whitespace-nowrap px-4 py-3 text-center font-semibold uppercase text-slate-500 dark:text-navy-300 lg:px-5">
                                        {{ __('Qty') }}
                                    </th>
                                    <th class="whitespace-nowrap px-4 py-3 text-center font-semibold uppercase text-slate-500 dark:text-navy-300 lg:px-5">
                                        {{ __('Price') }}
                                    </th>
                                    <th class="whitespace-nowrap px-4 py-3 text-end font-semibold uppercase text-slate-500 dark:text-navy-300 lg:px-5">
                                        {{ __('Total') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-150 dark:divide-navy-500">
                                @foreach($request->items as $item)
                                    @php
                                        $menuItem = $item->menuItem;
                                        $itemName = $menuItem?->localized_name ?: __('Unknown');
                                        $itemImageUrl = $menuItem?->image_url;
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-4 sm:px-5">
                                            <div class="flex items-center gap-3">
                                                @if($itemImageUrl)
                                                    <img
                                                        src="{{ $itemImageUrl }}"
                                                        loading="lazy"
                                                        alt="{{ $itemName }}"
                                                        class="size-12 shrink-0 rounded-lg border border-slate-200/70 object-cover dark:border-navy-600"
                                                    />
                                                @else
                                                    <div
                                                        class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-navy-600 dark:text-navy-300"
                                                        title="{{ __('Item image not available') }}"
                                                    >
                                                        <span class="sr-only">{{ __('Item image not available') }}</span>
                                                        <i class="fa-solid fa-utensils text-lg"></i>
                                                    </div>
                                                @endif

                                                <div class="flex flex-col min-w-0">
                                                    <span class="font-bold text-slate-700 dark:text-navy-100 truncate">
                                                        {{ $itemName }}
                                                    </span>
                                                    @if(filled($menuItem?->menuItemCategory?->name))
                                                        <span class="text-xs text-slate-500 dark:text-navy-400 truncate mt-0.5">
                                                            {{ $menuItem->menuItemCategory->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-center text-slate-600 dark:text-navy-300 sm:px-5">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-center text-slate-600 dark:text-navy-300 sm:px-5">
                                            {{ number_format($item->price_snapshot, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-end font-bold text-slate-700 dark:text-navy-100 sm:px-5">
                                            {{ number_format($item->price_snapshot * $item->quantity, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 dark:bg-navy-700/50">
                                    <td colspan="3" class="px-4 py-4 text-end font-semibold text-slate-700 dark:text-navy-100 sm:px-5">
                                        {{ __('Grand Total') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-end text-xl font-bold text-warning sm:px-5">
                                        <x-sar-symbol /> {{ number_format($request->reserved_amount, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="card border-slate-150 p-5 shadow-sm dark:border-navy-600 sm:p-6">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-slate-800 dark:text-navy-100">
                            {{ __('Request Details') }}
                        </h3>
                    </div>

                    <div class="mb-6 flex items-center gap-3 rounded-lg bg-slate-100 p-3 dark:bg-navy-700/50">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white text-primary shadow-sm dark:bg-navy-600 dark:text-accent-light">
                            <i class="fa-solid fa-user-shield text-base"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-700 dark:text-navy-100">
                                {{ __('Anonymous requester') }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-navy-400">
                                {{ __('No personal data is shown.') }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="rounded-lg border border-slate-150 p-3 dark:border-navy-600">
                            <div class="flex items-center gap-2 mb-1.5">
                                <i class="fa-solid fa-receipt text-xs text-primary dark:text-accent-light"></i>
                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400 dark:text-navy-300">
                                    {{ __('Reference') }}
                                </span>
                            </div>
                            <span class="font-mono text-sm font-medium text-slate-700 dark:text-navy-100">
                                {{ \App\Support\PseudonymousRequestId::make($request->id) }}
                            </span>
                        </div>

                        <div class="rounded-lg border border-slate-150 p-3 dark:border-navy-600">
                            <div class="flex items-center gap-2 mb-1.5">
                                <i class="fa-solid fa-calendar-days text-xs text-primary dark:text-accent-light"></i>
                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400 dark:text-navy-300">
                                    {{ __('Request Date') }}
                                </span>
                            </div>
                            <span class="text-sm font-medium text-slate-700 dark:text-navy-100">
                                {{ $request->created_at->format('M d, Y H:i') }}
                            </span>
                        </div>

                        <div class="rounded-lg border border-slate-150 p-3 dark:border-navy-600">
                            <div class="flex items-center gap-2 mb-1.5">
                                <i class="fa-solid fa-tag text-xs text-primary dark:text-accent-light"></i>
                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400 dark:text-navy-300">
                                    {{ __('Category') }}
                                </span>
                            </div>
                            <span class="text-sm font-medium text-slate-700 dark:text-navy-100">
                                {{ \App\Support\RequestTypeLabel::forRequest($request) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Actions --}}
            <div class="lg:col-span-1">
                <div class="card sticky top-6 p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Actions') }}</h3>

                    <div class="space-y-4">
                        <a href="{{ route('provider.qr.scan', ['request_id' => $request->id]) }}"
                            class="group flex w-full items-start gap-3 rounded-xl border border-primary/15 bg-primary/[0.04] p-4 transition hover:border-primary/25 hover:bg-primary/[0.07] dark:border-accent/20 dark:bg-accent/10 dark:hover:border-accent/35 dark:hover:bg-accent/15">
                            <span
                                class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary dark:bg-accent/20 dark:text-accent-light">
                                <i class="fa-solid fa-qrcode text-lg" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-bold text-slate-800 dark:text-navy-50">
                                        {{ __('Scan QR Code') }}
                                    </span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-xs text-slate-400 transition group-hover:text-slate-600 dark:text-navy-400 dark:group-hover:text-navy-200" aria-hidden="true"></i>
                                </div>
                                <p class="mt-1 text-xs leading-snug text-slate-600 dark:text-navy-300" dir="auto">
                                    {{ __('Open the QR scanner to verify and complete this request.') }}
                                </p>
                            </div>
                        </a>

                        @if(in_array($request->status, ['REQUESTED', 'ADMIN_APPROVED']))
                            <form action="{{ route('provider.requests.update', $request->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="adopt">
                                <button type="submit"
                                    class="btn w-full bg-success text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90 dark:bg-success dark:hover:bg-success-focus dark:focus:bg-success-focus">
                                    <i class="fa-solid fa-hand-holding-heart me-2" aria-hidden="true"></i>
                                    {{ __('Adopt Request') }}
                                </button>
                                <p class="mt-1 text-center text-xs text-slate-500 dark:text-navy-400">
                                    {{ __('You will personally cover this request.') }}
                                </p>
                            </form>

                            <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>

                            <form action="{{ route('provider.requests.update', $request->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="approve">
                                <button type="submit"
                                    class="btn w-full bg-warning text-white hover:bg-warning-focus focus:bg-warning-focus active:bg-warning-focus/90 dark:bg-warning dark:hover:bg-warning-focus dark:focus:bg-warning-focus">
                                    <i class="fa-solid fa-landmark me-2" aria-hidden="true"></i>
                                    {{ __('Accept (City Fund)') }}
                                </button>
                                <p class="mt-1 text-center text-xs text-slate-500 dark:text-navy-400">
                                    {{ __('Approve this request under City Fund. Redemption completes it.') }}
                                </p>
                            </form>

                            <div class="my-4 h-px bg-slate-200 dark:bg-navy-500"></div>

                            <button type="button"
                                onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                                class="btn w-full border-2 border-error bg-transparent text-error hover:bg-error/10 focus:bg-error/10 dark:hover:bg-error/15 dark:focus:bg-error/15">
                                <i class="fa-solid fa-ban me-2" aria-hidden="true"></i>
                                {{ __('Reject Request') }}
                            </button>

                            <form id="reject-form" action="{{ route('provider.requests.update', $request->id) }}"
                                method="POST"
                                class="mt-4 hidden rounded-lg border border-error/30 bg-error/10 p-4 dark:bg-error/15 dark:border-error/20">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="reject">

                                <label for="reason"
                                    class="mb-2 block text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('Reason') }}</label>
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
                        @elseif($request->needsProviderFulfillmentProof())
                            <p class="text-sm text-slate-600 dark:text-navy-300">
                                {{ __('The order was scanned. Upload fulfillment proof to complete this request.') }}
                            </p>
                            <a href="{{ route('provider.proof.index', $request->redemption->id) }}"
                                class="btn flex w-full items-center justify-center bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus">
                                <i class="fa-solid fa-camera me-2"></i>
                                {{ __('Upload fulfillment proof') }}
                            </a>
                        @else
                        <div class="rounded-lg bg-slate-100 p-4 text-center dark:bg-navy-700/50">
                            <span class="block font-bold text-slate-700 dark:text-navy-100">{{ __('Request') }}
                                {{ $request->status }}</span>
                            <span
                                class="text-sm text-slate-500 dark:text-navy-400">{{ __('No further actions available.') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>