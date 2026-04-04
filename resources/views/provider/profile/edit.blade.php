@php
    $oh = old('operating_hours', $operatingInfo->operating_hours ?? []);
    $selectedServiceTypes = old('service_type', $operatingInfo->service_type ?? []);
@endphp
<x-app-layout title="{{ __('Operating profile') }}" is-header-blur="true">
    <div class="mx-auto max-w-3xl space-y-6 pb-24 pt-4 lg:pb-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('provider.dashboard') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-primary dark:text-navy-300 dark:hover:text-accent">
                <i class="fa-solid fa-arrow-left rtl:rotate-180" aria-hidden="true"></i>
                {{ __('Back to dashboard') }}
            </a>
        </div>

        {{-- Page intro --}}
        <div
            class="card overflow-hidden rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/[0.07] via-white to-white p-6 shadow-sm dark:border-accent/20 dark:from-navy-800/80 dark:via-navy-800/50 dark:to-navy-800/40 sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-6">
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light">
                    <i class="fa-solid fa-clock text-2xl" aria-hidden="true"></i>
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-navy-50">
                        {{ __('Hours, capacity & pickup notes') }}
                    </h1>
                    <p class="text-sm leading-relaxed text-slate-600 dark:text-navy-300">
                        {{ __('Recipients see this information on your menu page. Pausing the store is on the dashboard.') }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('provider.profile.save_hint') }}</p>
                </div>
            </div>
        </div>

        @if (session('success'))
            <x-lineone-alert type="success" dismissible>{{ session('success') }}</x-lineone-alert>
        @endif

        @if ($errors->any())
            <div
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('provider.profile.update') }}"
            class="space-y-8 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-navy-600 dark:bg-navy-800/40 sm:p-8">
            @csrf
            @method('PUT')

            {{-- Operating hours --}}
            <section class="space-y-4">
                <div class="border-b border-slate-100 pb-2 dark:border-navy-600">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100">{{ __('Operating hours') }}</h2>
                </div>
                <div class="space-y-3">
                    @foreach ($weekdayKeys as $dayKey)
                        @php
                            $dayData = $oh[$dayKey] ?? ['closed' => true];
                            $closed = ! empty($dayData['closed']);
                        @endphp
                        <div
                            class="provider-oh-row rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-navy-600 dark:bg-navy-900/30 sm:p-5"
                            data-day="{{ $dayKey }}">
                            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <span
                                    class="font-semibold text-slate-800 dark:text-navy-100">{{ __('provider.weekday.'.$dayKey) }}</span>
                                <label
                                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm dark:border-navy-500 dark:bg-navy-800">
                                    <input type="checkbox" name="operating_hours[{{ $dayKey }}][closed]" value="1"
                                        class="provider-oh-closed rounded border-slate-300 text-primary" @checked($closed) />
                                    <span class="text-slate-600 dark:text-navy-300">{{ __('Closed') }}</span>
                                </label>
                            </div>
                            {{-- LTR flex row + justify: EN packs left, AR (rtl:) packs right; time inputs stay dir=ltr --}}
                            <div
                                class="provider-oh-hours flex w-full flex-col gap-3 sm:flex-row sm:items-end sm:justify-start sm:gap-4 rtl:sm:flex-row-reverse rtl:sm:justify-end">
                                <div class="min-w-0 w-full shrink-0 sm:w-auto sm:max-w-[12rem]">
                                    <label
                                        class="mb-1 block text-start text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('Open') }}</label>
                                    <div dir="ltr" class="w-full">
                                        <input type="time" name="operating_hours[{{ $dayKey }}][open]"
                                            value="{{ old("operating_hours.$dayKey.open", $dayData['open'] ?? '09:00') }}"
                                            @disabled($closed)
                                            class="provider-oh-open w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-50" />
                                    </div>
                                </div>
                                <div class="min-w-0 w-full shrink-0 sm:w-auto sm:max-w-[12rem]">
                                    <label
                                        class="mb-1 block text-start text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('Close') }}</label>
                                    <div dir="ltr" class="w-full">
                                        <input type="time" name="operating_hours[{{ $dayKey }}][close]"
                                            value="{{ old("operating_hours.$dayKey.close", $dayData['close'] ?? '17:00') }}"
                                            @disabled($closed)
                                            class="provider-oh-close w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-50" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Capacity & services --}}
            <section class="space-y-5 border-t border-slate-100 pt-8 dark:border-navy-600">
                <div>
                    <label for="daily_capacity"
                        class="mb-2 block text-sm font-semibold text-slate-800 dark:text-navy-100">{{ __('Daily capacity') }}</label>
                    <input type="number" name="daily_capacity" id="daily_capacity" min="1" max="10000" required
                        value="{{ old('daily_capacity', $operatingInfo->daily_capacity) }}"
                        class="form-input form-input-lineone w-full max-w-xs rounded-xl border text-base" />
                </div>

                <div>
                    <span class="mb-2 block text-sm font-semibold text-slate-800 dark:text-navy-100">{{ __('Service type') }}</span>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($serviceTypes as $st)
                            <label
                                class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm transition hover:border-primary/30 dark:border-navy-600 dark:bg-navy-800/80 dark:hover:border-accent/30">
                                <input type="checkbox" name="service_type[]" value="{{ $st }}" @checked(in_array($st, $selectedServiceTypes, true))
                                    class="rounded border-slate-300 text-primary" />
                                <span>{{ __('provider.service_type.'.$st) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="estimated_preparation_order_time"
                        class="mb-2 block text-sm font-semibold">{{ __('Estimated preparation time') }}</label>
                    <input type="text" name="estimated_preparation_order_time" id="estimated_preparation_order_time"
                        required maxlength="100"
                        value="{{ old('estimated_preparation_order_time', $operatingInfo->estimated_preparation_order_time) }}"
                        class="form-input form-input-lineone w-full max-w-md rounded-xl" />
                </div>

                <div>
                    <label for="adoption_support" class="mb-2 block text-sm font-semibold">{{ __('Adoption support') }}</label>
                    <select name="adoption_support" id="adoption_support" required
                        class="form-input form-input-lineone w-full max-w-md rounded-xl">
                        @foreach ($adoptionSupportOptions as $key => $label)
                            <option value="{{ $key }}" @selected(old('adoption_support', $operatingInfo->adoption_support) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            {{-- Pickup notes --}}
            <section class="space-y-2 border-t border-slate-100 pt-8 dark:border-navy-600">
                <label for="pickup_notes" class="block text-sm font-semibold">{{ __('Pickup / delivery notes for beneficiaries') }}</label>
                <textarea name="pickup_notes" id="pickup_notes" rows="5" maxlength="2000"
                    placeholder="{{ __('e.g. Entrance from side street, call on arrival…') }}"
                    class="form-input form-input-lineone min-h-[7rem] w-full rounded-xl">{{ old('pickup_notes', $operatingInfo->pickup_notes) }}</textarea>
                <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('Shown on your public menu page.') }}</p>
            </section>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-8 dark:border-navy-600 sm:flex-row sm:justify-end">
                <button type="submit"
                    class="btn inline-flex min-h-[2.75rem] w-full items-center justify-center gap-2 rounded-xl bg-primary px-8 py-3 font-semibold text-white shadow-sm hover:bg-primary-focus sm:w-auto dark:bg-accent dark:hover:bg-accent-focus">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
    <script>
        document.querySelectorAll('.provider-oh-row').forEach(function(row) {
            var cb = row.querySelector('.provider-oh-closed');
            var opens = row.querySelectorAll('.provider-oh-open, .provider-oh-close');
            function sync() {
                var off = cb && cb.checked;
                opens.forEach(function(el) {
                    el.disabled = off;
                });
            }
            if (cb) {
                cb.addEventListener('change', sync);
                sync();
            }
        });
    </script>
</x-app-layout>
