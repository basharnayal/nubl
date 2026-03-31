@php
    $phoneDisplay = $user->phone_number;
    if (is_string($phoneDisplay) && str_starts_with($phoneDisplay, '966') && strlen($phoneDisplay) >= 12) {
        $phoneDisplay = '0'.substr($phoneDisplay, 3);
    }
    $sel = 'form-input form-select peer w-full rounded-lg border border-slate-300 bg-transparent bg-none px-3 py-2.5 pr-9 pl-3 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent';
    $inpPeer = 'form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 pr-9 pl-3 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent';
    $dis = 'disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-slate-50 dark:disabled:bg-navy-800/80';
    $regions = config('provider.regions', []);
    $oh = old('operating_hours', $operating->operating_hours ?? []);
    $selectedCategories = old('business_category', $profile->business_category ?? []);
    $selectedServiceTypes = old('service_type', $operating->service_type ?? []);
@endphp
<x-register-layout :title="__('Resubmit application documents')" :heading="__('Review and update your application')" :subheading="__('All fields show your current answers. Leave document fields empty to keep existing files.')" max-width="wide">
    <form method="POST" action="{{ route('application.resubmit.update') }}" enctype="multipart/form-data" class="w-full space-y-8">
        @csrf

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-4">{{ __('Personal & Business Information') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="full_name_ar" :value="__('Full Name (Arabic)')" />
                    <x-text-input id="full_name_ar" name="full_name_ar" class="mt-1 block w-full" value="{{ old('full_name_ar', $profile->full_name_ar) }}" required />
                </div>
                <div>
                    <x-input-label for="full_name_en" :value="__('Full Name (English)')" />
                    <x-text-input id="full_name_en" name="full_name_en" class="mt-1 block w-full" value="{{ old('full_name_en', $profile->full_name_en) }}" required />
                </div>
            </div>
            <div class="mt-4">
                <x-input-label for="email_readonly" :value="__('Email')" />
                <label class="relative flex mt-1">
                    <input id="email_readonly" type="email" value="{{ $user->email }}" dir="ltr" disabled tabindex="-1" autocomplete="off"
                        class="{{ $inpPeer }} {{ $dis }}" />
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 dark:text-navy-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </span>
                </label>
                <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('Email and phone cannot be changed.') }}</p>
            </div>
            <div class="mt-4">
                <x-input-label for="phone_readonly" :value="__('Phone (Saudi)')" />
                <label class="relative flex mt-1">
                    <input id="phone_readonly" type="tel" value="{{ $phoneDisplay }}" dir="ltr" disabled tabindex="-1" autocomplete="off"
                        class="{{ $inpPeer }} pl-9 pr-3 text-left {{ $dis }}" />
                    <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 dark:text-navy-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    </span>
                </label>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="business_name_ar" :value="__('Business Name (Arabic)')" />
                    <x-text-input id="business_name_ar" name="business_name_ar" class="mt-1 block w-full" value="{{ old('business_name_ar', $profile->business_name_ar) }}" required />
                </div>
                <div>
                    <x-input-label for="business_name_en" :value="__('Business Name (English)')" />
                    <x-text-input id="business_name_en" name="business_name_en" class="mt-1 block w-full" value="{{ old('business_name_en', $profile->business_name_en) }}" required />
                </div>
            </div>
            <div class="mt-4">
                <x-input-label for="unified_number" :value="__('Unified Number')" />
                <x-text-input id="unified_number" name="unified_number" class="mt-1 block w-full" value="{{ old('unified_number', $profile->unified_number) }}" required />
            </div>
            <div class="mt-4">
                <x-input-label :value="__('Business Category')" />
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($businessCategories as $cat)
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="business_category[]" value="{{ $cat }}" @checked(in_array($cat, $selectedCategories, true))
                                class="rounded border-slate-300 text-primary focus:ring-primary dark:border-navy-500" />
                            <span class="text-sm">{{ ucfirst(str_replace('_', ' ', $cat)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="address_ar" :value="__('Address (Arabic)')" />
                    <textarea id="address_ar" name="address_ar" rows="3" required class="mt-1 block w-full rounded-lg border-slate-300 dark:bg-navy-900/50 dark:border-navy-500">{{ old('address_ar', $profile->address_ar) }}</textarea>
                </div>
                <div>
                    <x-input-label for="address_en" :value="__('Address (English)')" />
                    <textarea id="address_en" name="address_en" rows="3" required class="mt-1 block w-full rounded-lg border-slate-300 dark:bg-navy-900/50 dark:border-navy-500">{{ old('address_en', $profile->address_en) }}</textarea>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="city" :value="__('City')" />
                    <div class="relative flex mt-1">
                        <select id="city" name="city" required class="{{ $sel }}">
                            <option value="">— {{ __('Select') }} —</option>
                            @foreach(config('provider.cities', []) as $key => $label)
                                <option value="{{ $key }}" @selected(old('city', $profile->city) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </span>
                    </div>
                </div>
                <div>
                    <x-input-label for="region" :value="__('Region')" />
                    @if(count($regions) === 1)
                        <input type="hidden" name="region" value="{{ array_key_first($regions) }}">
                        <div class="mt-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-slate-800 dark:border-navy-450 dark:bg-navy-900/50 dark:text-navy-100">{{ reset($regions) }}</div>
                    @else
                        <div class="relative flex mt-1">
                            <select id="region" name="region" required class="{{ $sel }}">
                                @foreach($regions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('region', $profile->region) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" /></svg>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="mt-4">
                <x-input-label for="location" :value="__('Geographical Location')" />
                <x-text-input id="location" name="location" class="mt-1 block w-full" value="{{ old('location', $profile->location) }}" />
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-4">{{ __('Operating Information') }}</h2>
            <div class="space-y-4 divide-y divide-slate-200 dark:divide-navy-600">
                @foreach($weekdays as $dayKey => $dayLabel)
                    @php
                        $dayData = $oh[$dayKey] ?? ['closed' => true];
                        $closed = ! empty($dayData['closed']);
                    @endphp
                    <div class="resubmit-day-row pt-4 first:pt-0" data-day="{{ $dayKey }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-slate-800 dark:text-navy-100">{{ __($dayLabel) }}</span>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="operating_hours[{{ $dayKey }}][closed]" value="1"
                                    class="resubmit-closed-cb rounded border-slate-300 text-primary" @checked($closed) />
                                <span class="text-sm text-slate-600 dark:text-navy-300">{{ __('Closed') }}</span>
                            </label>
                        </div>
                        <div class="flex gap-4 items-center resubmit-hours-fields">
                            <div class="flex-1">
                                <x-input-label :value="__('Open')" class="text-xs" />
                                <input type="time" name="operating_hours[{{ $dayKey }}][open]"
                                    value="{{ old("operating_hours.$dayKey.open", $dayData['open'] ?? '09:00') }}"
                                    @disabled($closed)
                                    class="resubmit-hour-open mt-1 block w-full rounded-lg border-slate-300 dark:bg-navy-900/50" />
                            </div>
                            <div class="flex-1">
                                <x-input-label :value="__('Close')" class="text-xs" />
                                <input type="time" name="operating_hours[{{ $dayKey }}][close]"
                                    value="{{ old("operating_hours.$dayKey.close", $dayData['close'] ?? '17:00') }}"
                                    @disabled($closed)
                                    class="resubmit-hour-close mt-1 block w-full rounded-lg border-slate-300 dark:bg-navy-900/50" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <script>
                document.querySelectorAll('.resubmit-day-row').forEach(function (row) {
                    var cb = row.querySelector('.resubmit-closed-cb');
                    var opens = row.querySelectorAll('.resubmit-hour-open, .resubmit-hour-close');
                    function sync() {
                        var off = cb && cb.checked;
                        opens.forEach(function (el) { el.disabled = off; });
                    }
                    if (cb) {
                        cb.addEventListener('change', sync);
                        sync();
                    }
                });
            </script>
            <div class="mt-6">
                <x-input-label for="daily_capacity" :value="__('Daily Capacity')" />
                <x-text-input id="daily_capacity" name="daily_capacity" type="number" min="1" max="10000" class="mt-1 block w-full" value="{{ old('daily_capacity', $operating->daily_capacity) }}" required />
            </div>
            <div class="mt-4">
                <x-input-label :value="__('Service Type')" />
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($serviceTypes as $st)
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="service_type[]" value="{{ $st }}" @checked(in_array($st, $selectedServiceTypes, true))
                                class="rounded border-slate-300 text-primary" />
                            <span class="text-sm">{{ ucfirst(str_replace('_', ' ', $st)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="mt-4">
                <x-input-label for="estimated_preparation_order_time" :value="__('Estimated Preparation Time')" />
                <div class="relative flex mt-1">
                    <select id="estimated_preparation_order_time" name="estimated_preparation_order_time" required class="{{ $sel }}">
                        @foreach(['15 minutes', '30 minutes', '45 minutes', '1 hour', '1.5 hours', '2 hours'] as $opt)
                            <option value="{{ $opt }}" @selected(old('estimated_preparation_order_time', $operating->estimated_preparation_order_time) === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
            </div>
            <div class="mt-4">
                <x-input-label for="adoption_support" :value="__('Adopt orders as community support')" />
                <div class="relative flex mt-1">
                    <select id="adoption_support" name="adoption_support" required class="{{ $sel }}">
                        <option value="">— {{ __('Select') }} —</option>
                        @foreach(config('provider.adoption_support_options', []) as $key => $label)
                            <option value="{{ $key }}" @selected(old('adoption_support', $operating->adoption_support) === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    </span>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-4">{{ __('Financial Information') }}</h2>
            <div class="space-y-4">
                <div>
                    <x-input-label for="bank_name" :value="__('Bank Name')" />
                    <x-text-input id="bank_name" name="bank_name" class="mt-1 block w-full" value="{{ old('bank_name', $financial->bank_name) }}" required />
                </div>
                <div>
                    <x-input-label for="iban" :value="__('IBAN')" />
                    <x-text-input id="iban" name="iban" class="mt-1 block w-full" value="{{ old('iban', $financial->iban) }}" required />
                </div>
                <div>
                    <x-input-label for="account_holder_name" :value="__('Account Holder Name')" />
                    <x-text-input id="account_holder_name" name="account_holder_name" class="mt-1 block w-full" value="{{ old('account_holder_name', $financial->account_holder_name) }}" required />
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-4">{{ __('Documents') }}</h2>
            <p class="text-sm text-slate-600 dark:text-navy-300 mb-4">{{ __('Max size: :mb MB. PDF, JPG, PNG.', ['mb' => $documentMaxSizeMb]) }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                <div>
                    <x-input-label :value="__('Business License (current)')" />
                    @if($documents->business_license_path)
                        @php $ext = strtolower(pathinfo($documents->business_license_path, PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                            <img src="{{ route('application.my-file', 'business_license') }}" alt="" class="mt-2 max-h-48 rounded-lg border object-contain" />
                        @else
                            <a href="{{ route('application.my-file', 'business_license') }}" target="_blank" class="mt-2 inline-flex text-sm text-primary">{{ __('View / Download') }}</a>
                        @endif
                    @endif
                    <x-input-label for="business_license" class="mt-4" :value="__('Replace file (optional)')" />
                    <input id="business_license" name="business_license" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm" />
                </div>
                <div>
                    <x-input-label :value="__('ID / Iqama (current)')" />
                    @if($documents->id_or_iqama_path)
                        @php $ext2 = strtolower(pathinfo($documents->id_or_iqama_path, PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext2, ['jpg','jpeg','png','gif','webp']))
                            <img src="{{ route('application.my-file', 'id_or_iqama') }}" alt="" class="mt-2 max-h-48 rounded-lg border object-contain" />
                        @else
                            <a href="{{ route('application.my-file', 'id_or_iqama') }}" target="_blank" class="mt-2 inline-flex text-sm text-primary">{{ __('View / Download') }}</a>
                        @endif
                    @endif
                    <x-input-label for="id_or_iqama" class="mt-4" :value="__('Replace file (optional)')" />
                    <input id="id_or_iqama" name="id_or_iqama" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm" />
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-4">{{ __('Change password (optional)') }}</h2>
            <p class="text-sm text-slate-600 dark:text-navy-300 mb-4">{{ __('Leave blank to keep your current password.') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="password" :value="__('New password')" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-lineone-button type="submit">{{ __('Submit for review') }}</x-lineone-button>
            <a href="{{ route('approval.pending') }}" class="inline-flex items-center px-4 py-2 text-sm text-slate-600 hover:text-slate-900">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-register-layout>
