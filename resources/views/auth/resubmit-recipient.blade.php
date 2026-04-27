{{-- Full recipient application edit — field styling matches resources/views/auth/register.blade.php (recipient) --}}
@php
    $p = $profile;
    $k = $kyc;
    $phoneDisplay = $user->phone_number;
    if (is_string($phoneDisplay) && str_starts_with($phoneDisplay, '966') && strlen($phoneDisplay) >= 12) {
        $phoneDisplay = '0'.substr($phoneDisplay, 3);
    }
    $sel = 'form-input form-select peer w-full rounded-lg border border-slate-300 bg-transparent bg-none px-3 py-2.5 pr-9 pl-3 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent';
    $inp = 'form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 pr-9 pl-3 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent';
    $dis = 'disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-slate-50 dark:disabled:bg-navy-800/80';
@endphp
<x-register-layout :title="__('Resubmit application documents')" :heading="__('Review and update your application')" :subheading="__('All fields show your current answers. Submit again to send for review.')" max-width="wide">
    <form id="resubmit-recipient-form" method="POST" action="{{ route('application.resubmit.update') }}" class="w-full space-y-8">
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

        {{-- Personal / account --}}
        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600 dark:text-navy-100">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-4">{{ __('Personal Information') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" :value="__('Full Name')" />
                    <label class="relative flex mt-1">
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name"
                            class="{{ $inp }}" />
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="email_readonly" :value="__('Email')" />
                    <label class="relative flex mt-1">
                        <input id="email_readonly" type="email" value="{{ $user->email }}" dir="ltr" disabled tabindex="-1" autocomplete="off"
                            class="{{ $inp }} {{ $dis }}" />
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 dark:text-navy-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('Email and phone cannot be changed.') }}</p>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="phone_readonly" :value="__('Phone (Saudi)')" />
                    <label class="relative flex mt-1">
                        <input id="phone_readonly" type="tel" value="{{ $phoneDisplay }}" dir="ltr" disabled tabindex="-1" autocomplete="off"
                            class="{{ $inp }} pl-9 pr-3 text-left {{ $dis }}" />
                        <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 dark:text-navy-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        {{-- Profile --}}
        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-4">{{ __('Identity & address') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="nationality" :value="__('Nationality')" />
                    <div class="nationality-choices-wrap mt-1">
                        <select id="nationality" name="nationality" required class="{{ $sel }}">
                            <option value="">— {{ __('Select') }} —</option>
                            @include('partials.nationality-select-options', ['selected' => old('nationality', $p->nationality)])
                        </select>
                    </div>
                    <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="id_type" :value="__('Identity Document Type')" />
                    <div class="relative flex mt-1">
                        <select id="id_type" name="id_type" required class="{{ $sel }}">
                            <option value="">— {{ __('Select') }} —</option>
                            <option value="national_id" @selected(old('id_type', $p->id_type) === 'national_id')>{{ __('National ID') }}</option>
                            <option value="iqama" @selected(old('id_type', $p->id_type) === 'iqama')>{{ __('Iqama') }}</option>
                            <option value="hudood_number" @selected(old('id_type', $p->id_type) === 'hudood_number')>{{ __('Hudood Number (رقم الحدود)') }}</option>
                        </select>
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                        </span>
                    </div>
                    <x-input-error :messages="$errors->get('id_type')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="id_number" :value="__('ID Number (رقم الهوية / الإقامة)')" />
                    <label class="relative flex mt-1">
                        <input id="id_number" type="text" name="id_number" value="{{ old('id_number', $p->id_number) }}" required
                            inputmode="numeric" maxlength="10" pattern="\d{10}"
                            placeholder="{{ __('10-digit ID number') }}"
                            class="{{ $inp }}" />
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('id_number')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="short_address" :value="__('Short Address')" />
                    <label class="relative flex mt-1">
                        <input id="short_address" type="text" name="short_address" value="{{ old('short_address', $p->short_address) }}" required
                            placeholder="{{ __('City - District - Street - House Number') }}"
                            class="{{ $inp }}" />
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('short_address')" class="mt-2" />
                </div>
            </div>
        </section>

        {{-- KYC --}}
        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-4">{{ __('KYC Details') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="income_band" :value="__('Income Band')" />
                    <div class="relative flex mt-1">
                        <select id="income_band" name="income_band" required class="{{ $sel }}">
                            <option value="">— {{ __('Select') }} —</option>
                            @foreach(\App\Models\RecipientKycDetails::INCOME_BANDS as $band)
                                <option value="{{ $band }}" @selected(old('income_band', $k->income_band) === $band)>{{ $band }} <x-sar-symbol />/{{ __('month') }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2 10h20M4 10v9a1 1 0 001 1h4a1 1 0 001-1v-6a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 001 1h4a1 1 0 001-1v-9M4 10V7a2 2 0 012-2h12a2 2 0 012 2v3" /></svg>
                        </span>
                    </div>
                    <x-input-error :messages="$errors->get('income_band')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="household_size" :value="__('Household Size (number of family members)')" />
                    <label class="relative flex mt-1">
                        <input id="household_size" type="number" name="household_size" value="{{ old('household_size', $k->household_size) }}" min="1" max="50" required
                            class="{{ $inp }}" />
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('household_size')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="marital_status" :value="__('Marital Status')" />
                    <div class="relative flex mt-1">
                        <select id="marital_status" name="marital_status" required class="{{ $sel }}">
                            <option value="">— {{ __('Select') }} —</option>
                            <option value="single" @selected(old('marital_status', $k->marital_status) === 'single')>{{ __('Single') }}</option>
                            <option value="married" @selected(old('marital_status', $k->marital_status) === 'married')>{{ __('Married') }}</option>
                            <option value="divorced" @selected(old('marital_status', $k->marital_status) === 'divorced')>{{ __('Divorced') }}</option>
                            <option value="widowed" @selected(old('marital_status', $k->marital_status) === 'widowed')>{{ __('Widowed') }}</option>
                        </select>
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </span>
                    </div>
                    <x-input-error :messages="$errors->get('marital_status')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employment_status" :value="__('Employment Status')" />
                    <div class="relative flex mt-1">
                        <select id="employment_status" name="employment_status" required class="{{ $sel }}">
                            <option value="">— {{ __('Select') }} —</option>
                            <option value="unemployed" @selected(old('employment_status', $k->employment_status) === 'unemployed')>{{ __('Unemployed') }}</option>
                            <option value="unable_to_work" @selected(old('employment_status', $k->employment_status) === 'unable_to_work')>{{ __('Unable to work') }}</option>
                            <option value="employed_insufficient_income" @selected(old('employment_status', $k->employment_status) === 'employed_insufficient_income')>{{ __('Employed but insufficient income') }}</option>
                        </select>
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </span>
                    </div>
                    <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
                </div>
                <div>
                    <fieldset class="relative rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 pr-9 pl-3 dark:border-navy-450 dark:bg-navy-900/50">
                        <span class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        </span>
                        <legend class="block font-medium text-sm text-slate-700 dark:text-navy-200 mb-2">
                            {{ __('Are you a student?') }} <span class="text-red-400" aria-hidden="true">*</span>
                        </legend>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="is_student" value="1" @checked(old('is_student', $k->is_student ? '1' : '0') === '1') required
                                    class="w-4 h-4 text-primary bg-slate-100 border-slate-300 focus:ring-primary focus:ring-2 dark:text-accent-light dark:focus:ring-accent">
                                <span class="ms-2 text-sm font-medium text-slate-800 dark:text-navy-100">{{ __('Yes, I am a student') }}</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="is_student" value="0" @checked(old('is_student', $k->is_student ? '1' : '0') === '0')
                                    class="w-4 h-4 text-primary bg-slate-100 border-slate-300 focus:ring-primary focus:ring-2 dark:text-accent-light dark:focus:ring-accent">
                                <span class="ms-2 text-sm font-medium text-slate-800 dark:text-navy-100">{{ __('No') }}</span>
                            </label>
                        </div>
                    </fieldset>
                    <x-input-error :messages="$errors->get('is_student')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="situation_description" :value="__('Situation Description')" />
                    <textarea id="situation_description" name="situation_description" rows="4" required
                        minlength="10" maxlength="1000"
                        placeholder="{{ __('Describe your current situation and why you need food assistance (10–1000 characters)') }}"
                        class="{{ $inp }} resize-none">{{ old('situation_description', $k->situation_description) }}</textarea>
                    <x-input-error :messages="$errors->get('situation_description')" class="mt-2" />
                </div>
            </div>
        </section>

        {{-- Documents: current + optional new --}}
        <section class="rounded-lg border border-slate-200 p-6 bg-white dark:bg-navy-800 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100 mb-2">{{ __('Documents') }}</h2>
            <p class="text-sm text-slate-600 dark:text-navy-300 mb-4">{{ __('Current uploads are shown below. Choose a new file only if you want to replace it.') }}</p>
            <div>
                <x-input-label :value="__('Identity Photo (current)')" />
                @if($p->id_photo_path)
                    <a href="{{ route('application.my-file', 'id_photo') }}" target="_blank" class="block mt-2">
                        <img src="{{ route('application.my-file', 'id_photo') }}" alt="" class="max-h-48 rounded-lg border border-slate-200 object-contain" />
                    </a>
                @endif
                <p class="text-xs text-slate-500 mt-2">{{ __('Replace identity photo') }}</p>
                <input type="file" id="id_file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm" />
                <input type="hidden" name="id_photo_base64" id="id_photo_base64" value="" />
            </div>
            <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                {{ __('Your GPS location was captured when you first registered and does not need to be re-submitted.') }}
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-lineone-button type="submit" id="resubmit-submit">{{ __('Submit for review') }}</x-lineone-button>
            <a href="{{ route('approval.pending') }}" class="inline-flex items-center px-4 py-2 text-sm text-slate-600 hover:text-slate-900">{{ __('Cancel') }}</a>
        </div>
    </form>

    <script>
        (function () {
            setTimeout(function () {
                var el = document.getElementById('nationality');
                if (el && window.nublMountNationalityChoices) {
                    window.nublMountNationalityChoices(el, @json(__('Search')));
                }
            }, 150);
            const form = document.getElementById('resubmit-recipient-form');
            if (!form) return;
            function readAsDataUrl(file) {
                return new Promise(function (resolve, reject) {
                    const r = new FileReader();
                    r.onload = function () { resolve(r.result); };
                    r.onerror = reject;
                    r.readAsDataURL(file);
                });
            }
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const idFile = document.getElementById('id_file').files[0];
                document.getElementById('id_photo_base64').value = '';
                const tasks = [];
                if (idFile) tasks.push(readAsDataUrl(idFile).then(function (u) { document.getElementById('id_photo_base64').value = u; }));
                Promise.all(tasks).then(function () { form.submit(); });
            });
        })();
    </script>
</x-register-layout>
