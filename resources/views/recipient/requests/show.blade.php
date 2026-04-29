@php
    $requestCard = \App\Support\RecipientRequestStatusPresenter::card($request, (bool) session('request_submitted'));
    $requestPageTitle = session('request_submitted')
        ? __('Request submitted')
        : match ($request->status) {
            'FULFILLED' => __('recipient.request_fulfilled.page_title'),
            'APPROVED', 'REDEEMABLE' => __('recipient.request_redeem.page_title'),
            default => __('Request Details'),
        };
@endphp
<x-app-layout title="{{ $requestPageTitle }} #{{ $request->id }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('recipient.requests.index') }}"
                class="text-sm font-medium text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent-light/80">
                ← {{ __('Back to List') }}
            </a>
            @if($request->isCancellableByRecipient())
                <button type="button" @click="$dispatch('open-modal', 'cancel-request-{{ $request->id }}')"
                    class="btn bg-error font-medium text-white hover:bg-error-focus">
                    {{ __('Cancel Request') }}
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

        <div class="mx-auto w-full max-w-5xl space-y-6">
        @include('recipient.requests.partials.request-status-card', [
            'request' => $request,
            'card' => $requestCard,
        ])

        @if(in_array($request->status, ['APPROVED', 'REDEEMABLE'], true) && $request->redemption && $request->redemption->status === 'PENDING')
            @php
                $modalQrExpired = $request->redemption->redeem_expires_at && $request->redemption->redeem_expires_at->isPast();
            @endphp
            @if(! $modalQrExpired)
                @php
                    $rawTokenDecrypted = Illuminate\Support\Facades\Crypt::decryptString($request->redemption->token_ciphertext);
                    $shortTokenDecrypted = Illuminate\Support\Facades\Crypt::decryptString($request->redemption->short_code_ciphertext);
                @endphp
                <x-lineone-modal id="redeem-qr-{{ $request->id }}" :title="__('recipient.request_redeem.modal_title')"
                    size="md">
                    <p class="mb-4 text-center text-sm text-slate-600 dark:text-navy-300">
                        {{ __('Show this QR code to the provider to redeem your order') }}
                    </p>
                    <div class="flex flex-col items-center justify-center space-y-4">
                        <div
                            class="rounded-lg border-2 border-slate-200 bg-white p-4 dark:border-navy-600 dark:bg-navy-800">
                            {!! app('qrcode')->size(200)->generate($rawTokenDecrypted) !!}
                        </div>
                        <div
                            class="w-full max-w-[240px] rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-center dark:border-navy-600 dark:bg-navy-700">
                            <p class="mb-1 text-xs uppercase tracking-wider text-slate-500 dark:text-navy-300">
                                {{ __('Manual Code') }}
                            </p>
                            <p class="select-all font-mono text-lg font-bold tracking-widest text-slate-800 dark:text-navy-100">
                                {{ $shortTokenDecrypted }}
                            </p>
                        </div>
                        <p class="text-center text-sm font-bold text-error">
                            {{ __('Expires in') }}:
                            {{ $request->redemption->redeem_expires_at->timezone('Asia/Riyadh')->diffForHumans() }}
                            ({{ $request->redemption->redeem_expires_at->timezone('Asia/Riyadh')->format('h:i A') }})
                        </p>
                        <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('Request #') }}{{ $request->id }}</p>
                    </div>
                </x-lineone-modal>
            @endif
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
            {{-- Items List --}}
            <div class="md:col-span-2">
                <div class="card p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Order') }}
                    </h3>
                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                        <table class="is-hoverable w-full text-left">
                            <thead>
                                <tr>
                                    <th
                                        class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                        {{ __('Item') }}
                                    </th>
                                    <th
                                        class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                        {{ __('Quantity') }}
                                    </th>
                                    <th
                                        class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                        {{ __('Price') }}
                                    </th>
                                    <th
                                        class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                        {{ __('Total') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($request->items as $item)
                                    @php
                                        $menu = $item->menuItem;
                                        $imgUrl = $menu?->image_url;
                                    @endphp
                                    <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                        <td class="px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5">
                                            <div class="flex max-w-md items-center gap-3">
                                                <div
                                                    class="size-12 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 dark:border-navy-600 dark:bg-navy-700">
                                                    @if($imgUrl)
                                                        <img src="{{ $imgUrl }}"
                                                            alt="{{ $menu?->localized_name ?? __('Menu item') }}"
                                                            class="size-full object-cover">
                                                    @else
                                                        <div class="flex size-full items-center justify-center text-slate-400 dark:text-navy-500"
                                                            aria-hidden="true">
                                                            <svg class="size-6" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <span class="min-w-0">{{ $menu?->localized_name ?? __('Unknown Item') }}</span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $item->quantity }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                            <x-sar-amount :value="$item->price_snapshot" />
                                        </td>
                                        <td
                                            class="whitespace-nowrap px-4 py-3 font-bold text-slate-700 dark:text-navy-100 sm:px-5">
                                            <x-sar-amount :value="number_format($item->price_snapshot * $item->quantity, 2)" />
                                        </td>
                                    </tr>
                                @endforeach
                                <tr
                                    class="border-t border-slate-200 bg-slate-100 font-bold dark:border-navy-600 dark:bg-navy-700/50">
                                    <td colspan="3"
                                        class="px-4 py-3 text-right text-slate-700 dark:text-navy-100 sm:px-5">
                                        {{ __('Grand Total') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-primary dark:text-accent-light sm:px-5">
                                        <x-sar-amount :value="number_format($request->reserved_amount, 2)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Provider Info --}}
            <div class="h-fit">
                <div class="card p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Provider Info') }}
                    </h3>
                    @php
                        $providerCardProfile = $request->provider->providerProfile;
                        $providerCardTitle = \App\Support\ProviderDisplay::businessTitle($providerCardProfile, $request->provider->name);
                    @endphp
                    <div class="flex items-start gap-3">
                        <x-provider-profile-avatar :profile="$providerCardProfile" :title="$providerCardTitle" />
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-slate-800 dark:text-navy-100">
                                {{ $providerCardTitle }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-navy-400">
                                {{ $providerCardProfile->location ?? __('Location N/A') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-slate-200 pt-6 dark:border-navy-600">
                        <p class="text-xs text-slate-400 dark:text-navy-500">{{ __('Request ID') }}: {{ $request->id }}
                        </p>
                        <p class="text-xs text-slate-400 dark:text-navy-500">{{ __('Date') }}:
                            {{ $request->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</x-app-layout>
