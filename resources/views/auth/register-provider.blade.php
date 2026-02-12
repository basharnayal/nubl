{{--
    Provider registration: 4 steps (Personal → Operating → Financial → Documents+Password).
    If providerData exists → prefill + read-only. Otherwise → editable form.
--}}
<x-guest-layout max-width="wide">
    @php
        $readonly = $providerData !== null;
        $profile = ($providerData ?? [])['profile'] ?? null;
        $operating = ($providerData ?? [])['operating'] ?? null;
        $financial = ($providerData ?? [])['financial'] ?? null;
        $initialStep = 1;
        if (!$readonly && $errors->any()) {
            $step1Keys = ['full_name_ar', 'full_name_en', 'phone_number', 'email', 'business_name_ar', 'business_name_en', 'unified_number', 'business_category', 'address_ar', 'address_en', 'city', 'region', 'location'];
            $step2Keys = array_merge(['daily_capacity', 'service_type', 'estimated_preparation_order_time', 'adoption_support'], array_map(fn($d) => "operating_hours.{$d}", array_keys(config('provider.weekdays'))));
            $step3Keys = ['bank_name', 'iban', 'account_holder_name'];
            $step4Keys = ['business_license', 'id_or_iqama', 'password', 'password_confirmation'];
            if ($errors->hasAny($step1Keys)) {
                $initialStep = 1;
            } elseif ($errors->hasAny($step2Keys)) {
                $initialStep = 2;
            } elseif ($errors->hasAny($step3Keys)) {
                $initialStep = 3;
            } elseif ($errors->hasAny($step4Keys)) {
                $initialStep = 4;
            } else {
                $initialStep = 1;
            }
        }
    @endphp

    <div x-data="{ step: {{ $readonly ? 1 : $initialStep }} }" x-init="$nextTick(() => { const el = document.getElementById('phone_number'); if (el) el.value = el.value.replace(/\D/g,'').slice(0, 10); })" x-cloak>
        @if($readonly)
            {{-- Read-only: submitted, awaiting admin approval --}}
            <div class="space-y-6">
                <p class="text-sm text-slate-600 p-3 bg-nubl-teal-50 rounded-lg border border-nubl-teal-100">{{ __('Your application (view only). Awaiting admin approval.') }}</p>

                {{-- Step 1: Personal --}}
                <div x-show="step === 1" x-transition class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Personal & Business Information') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><x-input-label :value="__('Full Name (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->full_name_ar }}</p></div>
                        <div><x-input-label :value="__('Full Name (English)')" /><p class="mt-1 text-gray-900">{{ $profile->full_name_en }}</p></div>
                    </div>
                    <div><x-input-label :value="__('Phone')" /><p class="mt-1 text-gray-900">{{ $profile->phone_number }}</p></div>
                    <div><x-input-label :value="__('Email')" /><p class="mt-1 text-gray-900">{{ $profile->user->email }}</p></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><x-input-label :value="__('Business Name (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->business_name_ar }}</p></div>
                        <div><x-input-label :value="__('Business Name (English)')" /><p class="mt-1 text-gray-900">{{ $profile->business_name_en }}</p></div>
                    </div>
                    <div><x-input-label :value="__('Unified Number')" /><p class="mt-1 text-gray-900">{{ $profile->unified_number }}</p></div>
                    <div><x-input-label :value="__('Business Category')" /><p class="mt-1 text-gray-900">{{ implode(', ', array_map(fn($c) => ucfirst(str_replace('_', ' ', $c)), $profile->business_category)) }}</p></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><x-input-label :value="__('Address (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->address_ar }}</p></div>
                        <div><x-input-label :value="__('Address (English)')" /><p class="mt-1 text-gray-900">{{ $profile->address_en }}</p></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><x-input-label :value="__('City')" /><p class="mt-1 text-gray-900">{{ $profile->city }}</p></div>
                        <div><x-input-label :value="__('Region')" /><p class="mt-1 text-gray-900">{{ $profile->region }}</p></div>
                    </div>
                    @if($profile->location)<div><x-input-label :value="__('Location')" /><p class="mt-1 text-gray-900">{{ $profile->location }}</p></div>@endif
                </div>

                {{-- Step 2: Operating --}}
                <div x-show="step === 2" x-transition class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Operating Information') }}</h2>
                    <div><x-input-label :value="__('Operating Hours')" />
                        @foreach($operating->operating_hours ?? [] as $day => $data)
                            <p class="mt-1 text-sm text-gray-700">
                                {{ __($weekdays[$day] ?? $day) }}:
                                @if($data['closed'] ?? true)
                                    {{ __('Closed') }}
                                @else
                                    {{ $data['open'] ?? '' }} - {{ $data['close'] ?? '' }}
                                @endif
                            </p>
                        @endforeach
                    </div>
                    <div><x-input-label :value="__('Daily Capacity')" /><p class="mt-1 text-gray-900">{{ $operating->daily_capacity }}</p></div>
                    <div><x-input-label :value="__('Service Type')" /><p class="mt-1 text-gray-900">{{ implode(', ', array_map(fn($s) => ucfirst(str_replace('_', ' ', $s)), $operating->service_type ?? [])) }}</p></div>
                    <div><x-input-label :value="__('Estimated Preparation Time')" /><p class="mt-1 text-gray-900">{{ $operating->estimated_preparation_order_time }}</p></div>
                </div>

                {{-- Step 3: Financial --}}
                <div x-show="step === 3" x-transition class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Financial Information') }}</h2>
                    <div><x-input-label :value="__('Bank Name')" /><p class="mt-1 text-gray-900">{{ $financial->bank_name }}</p></div>
                    <div><x-input-label :value="__('IBAN')" /><p class="mt-1 text-gray-900">{{ $financial->iban }}</p></div>
                    <div><x-input-label :value="__('Account Holder')" /><p class="mt-1 text-gray-900">{{ $financial->account_holder_name }}</p></div>
                </div>

                {{-- Step 4: Documents --}}
                <div x-show="step === 4" x-transition>
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Documents') }}</h2>
                    <p class="text-sm text-gray-600 mt-1">{{ __('Business license and ID/Iqama submitted.') }}</p>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <button type="button" x-show="step > 1" x-on:click="step--" class="text-gray-600 hover:text-gray-900 font-medium text-sm">{{ __('Previous') }}</button>
                    <div class="flex gap-2">
                        <button type="button" x-show="step < 4" x-on:click="step++" class="text-nubl-teal-600 hover:text-nubl-teal-700 font-medium text-sm">{{ __('Next') }}</button>
                        <a href="{{ route('approval.pending') }}" class="text-nubl-teal-600 hover:text-nubl-teal-700 text-sm font-medium">{{ __('Back to status') }}</a>
                    </div>
                </div>
            </div>
        @else
            {{-- Editable form --}}
            <form method="POST" action="{{ route('register.provider') }}" enctype="multipart/form-data" onsubmit="this.querySelectorAll('input[name=phone_number]').forEach(el => { if(el.value) el.value = el.value.replace(/^0+/, ''); });">
                @csrf

                @if($errors->any())
                <div class="mb-6 p-4 rounded-lg border border-red-200 bg-red-50">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-red-800">{{ __('Please correct the following errors:') }}</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Flowbite-style steps --}}
                <ol class="flex items-center w-full mb-8 text-sm font-medium text-center text-slate-500 sm:flex-nowrap">
                    @foreach([1 => __('Personal'), 2 => __('Operating'), 3 => __('Financial'), 4 => __('Documents')] as $s => $label)
                    <li class="flex items-center {{ $s < 4 ? 'flex-1' : '' }}">
                        <span class="flex items-center justify-center w-8 h-8 mr-2 rounded-full shrink-0"
                            :class="step >= {{ $s }} ? (step === {{ $s }} ? 'bg-nubl-gold-500 text-white' : 'bg-nubl-teal-500 text-white') : 'bg-slate-200 text-slate-500'">{{ $s }}</span>
                        <span class="hidden sm:inline" :class="step >= {{ $s }} ? (step === {{ $s }} ? 'text-nubl-gold-600' : 'text-nubl-teal-600') : ''">{{ $label }}</span>
                        @if($s < 4)<span class="flex-1 h-px mx-2 bg-slate-200 shrink-0"></span>@endif
                    </li>
                    @endforeach
                </ol>

                {{-- Step 1: Personal & Business --}}
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Personal & Business Information') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="full_name_ar" :value="__('Full Name (Arabic)')" required />
                            <x-text-input id="full_name_ar" name="full_name_ar" value="{{ old('full_name_ar') }}" class="block mt-1 w-full" required />
                            <x-input-error :messages="$errors->get('full_name_ar')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="full_name_en" :value="__('Full Name (English)')" required />
                            <x-text-input id="full_name_en" name="full_name_en" value="{{ old('full_name_en') }}" class="block mt-1 w-full" required />
                            <x-input-error :messages="$errors->get('full_name_en')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="phone_number" :value="__('Phone (Saudi +966)')" required />
                        <x-text-input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" placeholder="05XXXXXXXX" class="block mt-1 w-full" maxlength="10"
                            x-on:input="$event.target.value = $event.target.value.replace(/\D/g,'').slice(0, 10)" required />
                        <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" required />
                        <x-text-input id="email" name="email" type="email" value="{{ old('email') }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="business_name_ar" :value="__('Business Name (Arabic)')" required />
                            <x-text-input id="business_name_ar" name="business_name_ar" value="{{ old('business_name_ar') }}" class="block mt-1 w-full" required />
                            <x-input-error :messages="$errors->get('business_name_ar')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="business_name_en" :value="__('Business Name (English)')" required />
                            <x-text-input id="business_name_en" name="business_name_en" value="{{ old('business_name_en') }}" class="block mt-1 w-full" required />
                            <x-input-error :messages="$errors->get('business_name_en')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="unified_number" :value="__('Unified Number')" required />
                        <x-text-input id="unified_number" name="unified_number" value="{{ old('unified_number') }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('unified_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label :value="__('Business Category')" required />
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($businessCategories as $cat)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="business_category[]" value="{{ $cat }}" {{ in_array($cat, old('business_category', [])) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-nubl-teal-600 focus:ring-nubl-teal-500">
                                <span class="ms-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $cat)) }}</span>
                            </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('business_category')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="address_ar" :value="__('Address (Arabic)')" required />
                            <textarea id="address_ar" name="address_ar" rows="2" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:border-nubl-teal-500 focus:ring-nubl-teal-500">{{ old('address_ar') }}</textarea>
                            <x-input-error :messages="$errors->get('address_ar')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="address_en" :value="__('Address (English)')" required />
                            <textarea id="address_en" name="address_en" rows="2" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:border-nubl-teal-500 focus:ring-nubl-teal-500">{{ old('address_en') }}</textarea>
                            <x-input-error :messages="$errors->get('address_en')" class="mt-2" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="city" :value="__('City')" required />
                            <select id="city" name="city" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                                <option value="">— {{ __('Select') }} —</option>
                                @foreach(config('provider.cities', []) as $key => $label)
                                <option value="{{ $key }}" {{ old('city') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('city')" class="mt-2" />
                        </div>
                        <div>
                            @php $regions = config('provider.regions', []); @endphp
                            <x-input-label for="region" :value="__('Region')" :required="count($regions) > 1" />
                            @if(count($regions) === 1)
                            <input type="hidden" name="region" value="{{ array_key_first($regions) }}">
                            <p class="mt-1 text-gray-700">{{ reset($regions) }}</p>
                            @else
                            <select id="region" name="region" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                                @foreach($regions as $key => $label)
                                <option value="{{ $key }}" {{ old('region', 'western') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @endif
                            <x-input-error :messages="$errors->get('region')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="location" :value="__('Geographical Location')" />
                        <x-text-input id="location" name="location" value="{{ old('location') }}" placeholder="{{ __('Map or textual location') }}" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>
                </div>

                {{-- Step 2: Operating (per-day hours: open, close, or closed) --}}
                <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Operating Information') }}</h2>
                    <div>
                        <x-input-label :value="__('Operating Hours (per day)')" />
                        <p class="text-sm text-gray-600 mb-3">{{ __('Set opening and closing time for each day, or mark as closed.') }}</p>
                        <div class="space-y-4 divide-y divide-gray-200">
                            @foreach($weekdays as $dayKey => $dayLabel)
                            <div class="pt-4 first:pt-0" x-data="{ closed: {{ old("operating_hours.{$dayKey}.closed") ? 'true' : 'false' }} }">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-gray-700">{{ __($dayLabel) }}</span>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="operating_hours[{{ $dayKey }}][closed]" value="1"
                                            x-model="closed"
                                            class="rounded border-gray-300 text-nubl-teal-600 focus:ring-nubl-teal-500">
                                        <span class="ms-2 text-sm text-gray-600">{{ __('Closed') }}</span>
                                    </label>
                                </div>
                                <div class="flex gap-4 items-center" x-show="!closed">
                                    <div class="flex-1">
                                        <x-input-label :value="__('Open')" class="text-xs" />
                                        <input type="time" name="operating_hours[{{ $dayKey }}][open]" value="{{ old("operating_hours.{$dayKey}.open", '09:00') }}"
                                            x-bind:disabled="closed" class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                                    </div>
                                    <div class="flex-1">
                                        <x-input-label :value="__('Close')" class="text-xs" />
                                        <input type="time" name="operating_hours[{{ $dayKey }}][close]" value="{{ old("operating_hours.{$dayKey}.close", '17:00') }}"
                                            x-bind:disabled="closed" class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('operating_hours.'.$dayKey)" class="mt-2" />
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <x-input-label for="daily_capacity" :value="__('Daily Capacity (max orders)')" required />
                        <x-text-input id="daily_capacity" name="daily_capacity" type="number" value="{{ old('daily_capacity', 50) }}" min="1" max="10000" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('daily_capacity')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label :value="__('Service Type')" required />
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($serviceTypes as $st)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="service_type[]" value="{{ $st }}" {{ in_array($st, old('service_type', [])) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-nubl-teal-600 focus:ring-nubl-teal-500">
                                <span class="ms-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $st)) }}</span>
                            </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('service_type')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="estimated_preparation_order_time" :value="__('Estimated Preparation Time')" required />
                        <select id="estimated_preparation_order_time" name="estimated_preparation_order_time" required
                            class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                            <option value="15 minutes" {{ old('estimated_preparation_order_time') == '15 minutes' ? 'selected' : '' }}>15 {{ __('minutes') }}</option>
                            <option value="30 minutes" {{ old('estimated_preparation_order_time', '30 minutes') == '30 minutes' ? 'selected' : '' }}>30 {{ __('minutes') }}</option>
                            <option value="45 minutes" {{ old('estimated_preparation_order_time') == '45 minutes' ? 'selected' : '' }}>45 {{ __('minutes') }}</option>
                            <option value="1 hour" {{ old('estimated_preparation_order_time') == '1 hour' ? 'selected' : '' }}>1 {{ __('hour') }}</option>
                            <option value="1.5 hours" {{ old('estimated_preparation_order_time') == '1.5 hours' ? 'selected' : '' }}>1.5 {{ __('hours') }}</option>
                            <option value="2 hours" {{ old('estimated_preparation_order_time') == '2 hours' ? 'selected' : '' }}>2 {{ __('hours') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('estimated_preparation_order_time')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="adoption_support" :value="__('Do you wish to adopt orders as community support and service?')" required />
                        <select id="adoption_support" name="adoption_support" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                            <option value="">— {{ __('Select') }} —</option>
                            @foreach(config('provider.adoption_support_options', []) as $key => $label)
                            <option value="{{ $key }}" {{ old('adoption_support') === $key ? 'selected' : '' }}>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('adoption_support')" class="mt-2" />
                    </div>
                </div>

                {{-- Step 3: Financial --}}
                <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('Financial Information') }}</h2>
                    <div>
                        <x-input-label for="bank_name" :value="__('Bank Name')" required />
                        <x-text-input id="bank_name" name="bank_name" value="{{ old('bank_name') }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="iban" :value="__('IBAN')" required />
                        <x-text-input id="iban" name="iban" value="{{ old('iban') }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('iban')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="account_holder_name" :value="__('Account Holder Name')" required />
                        <x-text-input id="account_holder_name" name="account_holder_name" value="{{ old('account_holder_name') }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('account_holder_name')" class="mt-2" />
                    </div>
                </div>

                {{-- Step 4: Documents + Password --}}
                <div x-show="step === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('Documents & Password') }}</h2>
                    <div class="flex items-center gap-2 p-3 rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-600">
                        <svg class="w-5 h-5 shrink-0 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <span>{{ __('Max file size:') }} <strong>{{ $documentMaxSizeMb }} MB</strong>. {{ __('Accepted formats:') }} PDF, JPG, PNG.</span>
                    </div>
                    <div>
                        <x-input-label for="business_license" :value="__('Business License')" required />
                        <input id="business_license" name="business_license" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                            class="block mt-1 w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-nubl-teal-50 file:text-nubl-teal-700 hover:file:bg-nubl-teal-100 cursor-pointer">
                        <x-input-error :messages="$errors->get('business_license')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="id_or_iqama" :value="__('ID or Iqama')" required />
                        <input id="id_or_iqama" name="id_or_iqama" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                            class="block mt-1 w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-nubl-teal-50 file:text-nubl-teal-700 hover:file:bg-nubl-teal-100 cursor-pointer">
                        <x-input-error :messages="$errors->get('id_or_iqama')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password" :value="__('Password')" required />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" required />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-6 border-t border-slate-200">
                    <button type="button" x-show="step > 1" x-on:click="step--" class="text-slate-600 hover:text-slate-900 font-medium text-sm py-2">{{ __('Previous') }}</button>
                    <div class="flex gap-3 ml-auto">
                        <button type="button" x-show="step < 4" x-on:click="step++" class="text-white bg-nubl-teal-600 hover:bg-nubl-teal-700 font-medium rounded-lg text-sm px-5 py-2.5 transition">{{ __('Next') }}</button>
                        <x-primary-button type="submit" class="!bg-nubl-blue-600 hover:!bg-nubl-blue-700 focus:!ring-nubl-blue-200" x-show="step === 4">{{ __('Submit Application') }}</x-primary-button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <style>[x-cloak]{display:none!important}</style>
</x-guest-layout>
