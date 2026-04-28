<x-app-layout title="{{ __('New Donation') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="max-w-2xl">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    {{ __('Make a Donation') }}
                </h2>

                <div class="card mt-3 p-6">
                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-error/30 bg-error/10 p-4 dark:bg-error/15 dark:border-error/20">
                            <ul class="list-disc list-inside text-sm text-error dark:text-error">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('donor.payments.initiate') }}">
                        @csrf

                        <div
                            x-data="{
                                presets: [10, 50, 100],
                                amount: @js(old('amount', '')),
                                setAmount(v) {
                                    this.amount = v
                                    this.$nextTick(() => this.$refs.amountInput?.focus())
                                },
                            }"
                            class="space-y-4"
                        >
                            <div class="grid grid-cols-3 gap-3">
                                <template x-for="p in presets" :key="p">
                                    <button
                                        type="button"
                                        @click="setAmount(p)"
                                        class="h-12 w-full rounded-lg border text-base font-semibold transition-colors"
                                        :class="Number(amount) === p
                                            ? 'border-success bg-success/10 text-success dark:border-success/60 dark:bg-success/15'
                                            : 'border-slate-200 bg-white text-slate-800 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-50 dark:hover:bg-navy-650'"
                                    >
                                        <span class="inline-flex items-center gap-2">
                                            <span x-text="p"></span>
                                            <span class="text-slate-400 dark:text-navy-200">
                                                <x-sar-symbol />
                                            </span>
                                        </span>
                                    </button>
                                </template>
                            </div>

                            <div>
                                <label for="amount" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Amount') }} (<x-sar-symbol />) *</label>
                                <input
                                    x-ref="amountInput"
                                    x-model="amount"
                                    type="number"
                                    id="amount"
                                    name="amount"
                                    value="{{ old('amount') }}"
                                    step="0.01"
                                    min="1"
                                    max="999999.99"
                                    required
                                    class="form-input form-input-lineone" placeholder="100">
                            </div>

                            <div class="flex items-center gap-2 rounded-lg bg-slate-100 p-3 text-sm text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                                <img src="https://cdn.ehsan.sa/ehsan-ui/images/icons/info-black-icon.svg" class="h-4 w-4 dark:invert" alt="{{ __('Info') }}">
                                <p class="m-0 leading-snug">{{ __('Your donation will automatically go to the most needy cases') }}</p>
                            </div>

                            <div class="flex items-center justify-center gap-3" role="region" aria-label="خيارات الدفع المتاحة">
                                <div class="flex h-8 items-center justify-center rounded-md border-2 border-slate-200 bg-white p-1 dark:border-navy-600 dark:bg-navy-700">
                                    <img class="h-6 w-6" src="{{ asset('images/icons/visa-icon.svg') }}" alt="Visa">
                                </div>
                                <div class="flex h-8 items-center justify-center rounded-md border-2 border-slate-200 bg-white p-1 dark:border-navy-600 dark:bg-navy-700">
                                    <img class="h-6 w-6" src="{{ asset('images/icons/apple-icon.svg') }}" alt="Apple Pay">
                                </div>
                                <div class="flex h-8 items-center justify-center rounded-md border-2 border-slate-200 bg-white p-1 dark:border-navy-600 dark:bg-navy-700">
                                    <img class="h-6 w-6" src="{{ asset('images/icons/mastercard-icon.svg') }}" alt="Master Card">
                                </div>
                                <div class="flex h-8 items-center justify-center rounded-md border-2 border-slate-200 bg-white p-1 dark:border-navy-600 dark:bg-navy-700">
                                    <img class="h-6 w-6" src="{{ asset('images/icons/mada-icon.svg') }}" alt="بطاقة مدى">
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-lineone-button :href="route('donor.dashboard')" variant="slate" outline>{{ __('Cancel') }}</x-lineone-button>
                            <x-lineone-button type="submit" variant="primary">{{ __('Proceed to Payment') }}</x-lineone-button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
