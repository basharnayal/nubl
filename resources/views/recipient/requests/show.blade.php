<x-app-layout title="{{ __('Request Details') }} #{{ $request->id }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('recipient.requests.index') }}"
                class="text-sm font-medium text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent-light/80">
                ← {{ __('Back to List') }}
            </a>
        </div>

        {{-- Status Card --}}
        <div class="card mb-6">
            <div class="border-b border-slate-200 p-6 dark:border-navy-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-navy-400">{{ __('Status') }}</p>
                        @php
                            $statusConfig = [
                                'PENDING' => ['class' => 'bg-warning/10 text-warning dark:bg-warning/15', 'label' => __('Pending')],
                                'ADOPTED' => ['class' => 'bg-primary/10 text-primary dark:bg-accent-light/15 dark:text-accent-light', 'label' => __('Adopted')],
                                'PROVIDER_APPROVED' => ['class' => 'bg-info/10 text-info dark:bg-info/15', 'label' => __('Provider Approved')],
                                'ADMIN_PENDING' => ['class' => 'bg-warning/10 text-warning dark:bg-warning/15', 'label' => __('Admin Pending')],
                                'ADMIN_APPROVED' => ['class' => 'bg-success/10 text-success dark:bg-success/15', 'label' => __('Admin Approved')],
                                'REDEEMABLE' => ['class' => 'bg-success/10 text-success dark:bg-success/15', 'label' => __('Redeemable')],
                                'FULFILLED' => ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => __('Fulfilled')],
                                'PROVIDER_REJECTED' => ['class' => 'bg-error/10 text-error dark:bg-error/15', 'label' => __('Rejected')],
                                'ADMIN_REJECTED' => ['class' => 'bg-error/10 text-error dark:bg-error/15', 'label' => __('Rejected')],
                            ];
                            $config = $statusConfig[$request->status] ?? ['class' => 'bg-slate-200/80 text-slate-600 dark:bg-navy-500 dark:text-navy-200', 'label' => str_replace('_', ' ', $request->status)];
                        @endphp
                        <span class="badge mt-1 inline-block rounded-full px-3 py-1 text-lg font-bold {{ $config['class'] }}">
                            {{ $config['label'] }}
                        </span>
                    </div>

                    <div class="text-right">
                        @if(in_array($request->status, ['ADOPTED', 'PROVIDER_APPROVED', 'ADMIN_APPROVED', 'REDEEMABLE']))
                            <button type="button" onclick="alert('{{ __('QR Code generation coming soon!') }}')"
                                class="btn inline-flex items-center bg-success text-white hover:bg-success-focus focus:bg-success-focus dark:bg-success dark:hover:bg-success-focus">
                                <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM6 6h6v6H6V6zm2 2v2h2V8H8zm8-2h6v6h-6V6zm2 2v2h2V8h-2zM6 18h6v6H6v-6zm2 2v2h2v-2H8z"></path>
                                </svg>
                                {{ __('View QR Code') }}
                            </button>
                        @endif
                    </div>
                </div>

                @if($request->rejection_reason_code)
                    <div class="mt-4 rounded-lg border border-error/30 bg-error/10 p-4 dark:bg-error/15 dark:border-error/20">
                        <p class="font-bold text-slate-800 dark:text-navy-100">{{ __('Reason for Rejection') }}:</p>
                        <p class="text-slate-700 dark:text-navy-200">{{ $request->rejection_reason_code }}</p>
                        @if($request->rejection_reason_note)
                            <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">{{ $request->rejection_reason_note }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
            {{-- Items List --}}
            <div class="md:col-span-2">
                <div class="card p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Request Items') }}</h3>
                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                        <table class="is-hoverable w-full text-left">
                            <thead>
                                <tr>
                                    <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Item') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Quantity') }}</th>
                                    <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Price') }}</th>
                                    <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($request->items as $item)
                                    <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                        <td class="px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5">{{ $item->menuItem->name ?? __('Unknown Item') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $item->quantity }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $item->price_snapshot }} {{ __('SAR') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-700 dark:text-navy-100 sm:px-5">{{ number_format($item->price_snapshot * $item->quantity, 2) }} {{ __('SAR') }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-t border-slate-200 bg-slate-100 font-bold dark:border-navy-600 dark:bg-navy-700/50">
                                    <td colspan="3" class="px-4 py-3 text-right text-slate-700 dark:text-navy-100 sm:px-5">{{ __('Grand Total') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-primary dark:text-accent-light sm:px-5">{{ number_format($request->reserved_amount, 2) }} {{ __('SAR') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Provider Info --}}
            <div class="h-fit">
                <div class="card p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-navy-100">{{ __('Provider Info') }}</h3>
                    <p class="font-medium text-slate-800 dark:text-navy-100">{{ $request->provider->name }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-navy-400">
                        {{ $request->provider->providerProfile->location ?? __('Location N/A') }}
                    </p>

                    <div class="mt-6 border-t border-slate-200 pt-6 dark:border-navy-600">
                        <p class="text-xs text-slate-400 dark:text-navy-500">{{ __('Request ID') }}: {{ $request->id }}</p>
                        <p class="text-xs text-slate-400 dark:text-navy-500">{{ __('Date') }}: {{ $request->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
