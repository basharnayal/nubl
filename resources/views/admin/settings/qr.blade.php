<x-app-layout title="{{ __('QR code validity') }}" is-header-blur="true">
    <div class="mx-auto max-w-3xl space-y-6 pb-24 pt-4 lg:pb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <a href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-primary dark:text-navy-300 dark:hover:text-accent">
                <i class="fa-solid fa-arrow-left rtl:rotate-180" aria-hidden="true"></i>
                {{ __('Back to admin dashboard') }}
            </a>
        </div>

        <div class="mb-2 flex items-start gap-4">
            <div
                class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent">
                <i class="fa-solid fa-qrcode text-xl" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1 space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-navy-50">
                    {{ __('QR code validity') }}
                </h1>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-navy-300" dir="auto">
                    {{ __('Set how long recipients can redeem orders using a QR code after it is issued.') }}
                </p>
            </div>
        </div>

        <div
            class="card rounded-xl border border-primary/20 bg-gradient-to-b from-primary/[0.06] to-white p-6 shadow-sm dark:border-navy-600 dark:from-navy-800/50 dark:to-navy-800/40 sm:p-8">
            <form method="POST" action="{{ route('admin.settings.qr.update') }}" class="space-y-6"
                x-data="{
                    minutes: {{ (int) old('ttl_minutes', $ttlMinutes) }},
                    min: {{ $min }},
                    max: {{ $max }},
                    approxHours() {
                        const m = parseInt(this.minutes, 10) || 0;
                        return (m / 60).toFixed(m % 60 === 0 ? 0 : 1);
                    }
                }">
                @csrf
                @method('PUT')

                <div>
                    <label for="ttl_minutes" class="mb-2 block text-sm font-semibold text-slate-800 dark:text-navy-100">
                        {{ __('Redemption validity (minutes)') }}
                    </label>
                    {{-- LTR shell + no spinners: fixes RTL layout / odd selection on number inputs --}}
                    <div dir="ltr" class="max-w-md text-left">
                        <input type="number" name="ttl_minutes" id="ttl_minutes" x-model.number="minutes"
                            min="{{ $min }}" max="{{ $max }}" required inputmode="numeric" autocomplete="off"
                            class="form-input form-input-lineone w-full min-h-[2.75rem] text-base tabular-nums [appearance:textfield] [&::-webkit-inner-spin-button]:m-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:m-0 [&::-webkit-outer-spin-button]:appearance-none">
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-navy-400">
                        <span class="tabular-nums" dir="ltr" x-text="approxHours()"></span>
                        {{ __('hours (approx.)') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-navy-400" dir="auto">
                        {{ __('Allowed range: :min–:max minutes. The system default is :default minutes (3 hours) if you have not saved a custom value.', ['min' => $min, 'max' => $max, 'default' => $default]) }}
                    </p>
                    <p class="mt-3 text-xs leading-relaxed text-slate-500 dark:text-navy-400" dir="auto">
                        {{ __('Applies to new QR codes when a request becomes redeemable. Codes already issued keep their original expiry.') }}
                    </p>
                    @error('ttl_minutes')
                        <p class="mt-2 text-sm font-medium text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-navy-400">
                        {{ __('Quick presets') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([60 => '1h', 120 => '2h', 180 => '3h', 360 => '6h'] as $mins => $label)
                            <button type="button" @click="minutes = {{ $mins }}"
                                class="inline-flex rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-primary/30 hover:bg-primary/5 hover:text-primary dark:border-navy-600 dark:bg-navy-800/60 dark:text-navy-200 dark:hover:border-accent/30 dark:hover:bg-accent/10 dark:hover:text-accent-light"
                                :class="{ 'ring-2 ring-primary/40 dark:ring-accent/40': minutes === {{ $mins }} }">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 dark:border-navy-600 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500 dark:text-navy-400">
                        {{ __('Changes take effect for newly generated QR codes.') }}
                    </p>
                    <button type="submit"
                        class="btn inline-flex min-h-[2.75rem] w-full items-center justify-center gap-2 rounded-xl bg-primary px-8 py-2.5 font-semibold text-white shadow-sm hover:bg-primary-focus focus:ring-2 focus:ring-primary/30 sm:w-auto sm:min-w-[10rem] dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/30">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
