{{--
    Provider registration: 4 steps (Personal → Operating → Financial → Documents+Password).
    If providerData exists → prefill + read-only. Otherwise → editable form.
--}}
<x-guest-layout max-width="wide" :title="__('Provider Registration')">
    @php
        $readonly = $providerData !== null;
        $profile = ($providerData ?? [])['profile'] ?? null;
        $operating = ($providerData ?? [])['operating'] ?? null;
        $financial = ($providerData ?? [])['financial'] ?? null;
        $stepErrorKeys = [
            1 => ['full_name_ar', 'full_name_en', 'phone_number', 'email', 'business_name_ar', 'business_name_en', 'unified_number', 'business_category', 'address_ar', 'address_en', 'city', 'region', 'location', 'profile_logo'],
            2 => array_merge(['daily_capacity', 'service_type', 'estimated_preparation_order_time', 'adoption_support'], array_map(fn($d) => "operating_hours.{$d}", array_keys(config('provider.weekdays')))),
            3 => ['bank_name', 'iban', 'account_holder_name'],
            4 => ['business_license', 'id_or_iqama', 'password'],
        ];
        $initialStep = 1;
        if (!$readonly && $errors->any()) {
            foreach ($stepErrorKeys as $s => $keys) {
                if ($errors->hasAny($keys)) { $initialStep = $s; break; }
            }
        }
    @endphp

    <div x-data='providerForm({{ $readonly ? 1 : $initialStep }}, @json(array_keys(config("provider.weekdays"))))'
         x-init="init()"
         x-cloak
         data-module="provider-registration">
        @if($readonly)
            {{-- Read-only: submitted, awaiting admin approval --}}
            <div class="space-y-6">
                <p class="text-sm text-slate-600 p-3 bg-primary/10 rounded-lg border border-primary/20 dark:bg-accent/10 dark:border-accent/20">{{ __('Your application (view only). Awaiting admin approval.') }}</p>

                {{-- Step 1: Personal --}}
                <div x-show="step === 1" x-transition class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Personal & Business Information') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><x-input-label :value="__('Full Name (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->full_name_ar }}</p></div>
                        <div><x-input-label :value="__('Full Name (English)')" /><p class="mt-1 text-gray-900">{{ $profile->full_name_en }}</p></div>
                    </div>
                    <div><x-input-label :value="__('Phone')" /><p class="mt-1 text-gray-900">{{ $profile->phone_number }}</p></div>
                    <div><x-input-label :value="__('Email')" /><p class="mt-1 text-gray-900">{{ $profile->user->email }}</p></div>
                    @if($profile->logo_url)
                        <div>
                            <x-input-label :value="__('Business / profile photo')" />
                            <img src="{{ $profile->logo_url }}" alt="" class="mt-2 h-20 w-20 rounded-xl border border-slate-200 object-cover dark:border-navy-600" />
                        </div>
                    @endif
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
                        <button type="button" x-show="step < 4" x-on:click="step++" class="text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent font-medium text-sm">{{ __('Next') }}</button>
                        <a href="{{ route('approval.pending') }}" class="text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent text-sm font-medium">{{ __('Back to status') }}</a>
                    </div>
                </div>
            </div>
        @else
            {{-- Editable form --}}
            <form method="POST" action="{{ route('register.provider') }}" enctype="multipart/form-data" onsubmit="this.querySelectorAll('input[name=phone_number]').forEach(el => { if(el.value) el.value = el.value.replace(/^0+/, ''); });">
                @csrf

                <div id="provider-validation-error" class="hidden mb-6 p-4 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm" role="alert"></div>

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

                {{-- Step indicator (Lineone/Alpine) --}}
                <ol class="flex items-center w-full mb-8 text-sm font-medium text-center text-slate-500 sm:flex-nowrap">
                    @foreach([1 => __('Personal'), 2 => __('Operating'), 3 => __('Financial'), 4 => __('Documents')] as $s => $label)
                    <li class="flex items-center {{ $s < 4 ? 'flex-1' : '' }}">
                        <span class="flex items-center justify-center w-8 h-8 mr-2 rounded-full shrink-0"
                            :class="step >= {{ $s }} ? (step === {{ $s }} ? 'bg-primary text-white dark:bg-accent' : 'bg-primary/80 text-white dark:bg-accent/80') : 'bg-slate-200 text-slate-500 dark:bg-navy-600 dark:text-navy-300'">{{ $s }}</span>
                        <span class="hidden sm:inline" :class="step >= {{ $s }} ? (step === {{ $s }} ? 'text-primary dark:text-accent-light' : 'text-primary/80 dark:text-accent-light/80') : ''">{{ $label }}</span>
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
                        <x-text-input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" placeholder="{{ __('Phone placeholder') }}" class="block mt-1 w-full" maxlength="10"
                            x-on:input="$event.target.value = $event.target.value.replace(/\D/g,'').slice(0, 10)" required />
                        <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" required />
                        <x-text-input id="email" name="email" type="email" value="{{ old('email') }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label :value="__('Business / profile photo (optional)')" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('PNG, JPG, or WebP — max 2 MB. Shown on your storefront and account.') }}</p>
                        <input type="file" name="profile_logo" accept="image/png,image/jpeg,image/webp"
                            class="mt-2 block w-full text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary dark:text-navy-300 dark:file:bg-accent/15 dark:file:text-accent-light" />
                        <x-input-error :messages="$errors->get('profile_logo')" class="mt-2" />
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
                                    class="rounded border-gray-300 text-primary focus:ring-primary dark:text-accent-light dark:focus:ring-accent">
                                <span class="ms-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $cat)) }}</span>
                            </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('business_category')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="address_ar" :value="__('Address (Arabic)')" required />
                            <textarea id="address_ar" name="address_ar" rows="2" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring-primary dark:focus:border-accent dark:focus:ring-accent">{{ old('address_ar') }}</textarea>
                            <x-input-error :messages="$errors->get('address_ar')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="address_en" :value="__('Address (English)')" required />
                            <textarea id="address_en" name="address_en" rows="2" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring-primary dark:focus:border-accent dark:focus:ring-accent">{{ old('address_en') }}</textarea>
                            <x-input-error :messages="$errors->get('address_en')" class="mt-2" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="city" :value="__('City')" required />
                            <select id="city" name="city" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary dark:focus:ring-accent dark:focus:border-accent">
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
                            <select id="region" name="region" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary dark:focus:ring-accent dark:focus:border-accent">
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
                                            class="rounded border-gray-300 text-primary focus:ring-primary dark:text-accent-light dark:focus:ring-accent">
                                        <span class="ms-2 text-sm text-gray-600">{{ __('Closed') }}</span>
                                    </label>
                                </div>
                                <div class="flex gap-4 items-center" x-show="!closed">
                                    <div class="flex-1">
                                        <x-input-label :value="__('Open')" class="text-xs" />
                                        <input type="time" name="operating_hours[{{ $dayKey }}][open]" value="{{ old("operating_hours.{$dayKey}.open", '09:00') }}"
                                            x-bind:disabled="closed" class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary dark:focus:ring-accent dark:focus:border-accent">
                                    </div>
                                    <div class="flex-1">
                                        <x-input-label :value="__('Close')" class="text-xs" />
                                        <input type="time" name="operating_hours[{{ $dayKey }}][close]" value="{{ old("operating_hours.{$dayKey}.close", '17:00') }}"
                                            x-bind:disabled="closed" class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary dark:focus:ring-accent dark:focus:border-accent">
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
                                    class="rounded border-gray-300 text-primary focus:ring-primary dark:text-accent-light dark:focus:ring-accent">
                                <span class="ms-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $st)) }}</span>
                            </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('service_type')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="estimated_preparation_order_time" :value="__('Estimated Preparation Time')" required />
                        <select id="estimated_preparation_order_time" name="estimated_preparation_order_time" required
                            class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary dark:focus:ring-accent dark:focus:border-accent">
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
                        <select id="adoption_support" name="adoption_support" required class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary dark:focus:ring-accent dark:focus:border-accent">
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
                            class="block mt-1 w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20 dark:file:bg-accent/10 dark:file:text-accent-light dark:hover:file:bg-accent/20 cursor-pointer">
                        <x-input-error :messages="$errors->get('business_license')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="id_or_iqama" :value="__('ID or Iqama')" required />
                        <input id="id_or_iqama" name="id_or_iqama" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                            class="block mt-1 w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20 dark:file:bg-accent/10 dark:file:text-accent-light dark:hover:file:bg-accent/20 cursor-pointer">
                        <x-input-error :messages="$errors->get('id_or_iqama')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password" :value="__('Password')" required />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-6 border-t border-slate-200">
                    <button type="button" x-show="step > 1" x-on:click="hideError(); step--" class="text-slate-600 hover:text-slate-900 font-medium text-sm py-2">{{ __('Previous') }}</button>
                    <div class="flex gap-3 ml-auto">
                        <button type="button" x-show="step < 4" x-on:click="validateAndNext()" class="btn bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus font-medium rounded-lg text-sm px-5 py-2.5 transition">{{ __('Next') }}</button>
                        <x-primary-button type="submit" x-show="step === 4">{{ __('Submit Application') }}</x-primary-button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <script>
        window.__providerFormMessages = {
            fill_required: @json(__('Please fill all required fields in this step.')),
            business_category: @json(__('Please select at least one business category.')),
            service_type: @json(__('Please select at least one service type.')),
            phone_invalid: @json(__('Phone must be a valid Saudi number (9 digits, e.g. 512345678).')),
            region: @json(__('Please select region.')),
            operating_hours: @json(__('Please set opening and closing time for each open day, or mark as closed.')),
        };
    </script>
    <style>[x-cloak]{display:none!important}</style>
</x-guest-layout>
