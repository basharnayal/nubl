{{--
    Registration: donor (instant) or recipient (pending approval).
    Recipient requires camera capture for ID photo + GPS location (no address photo).
    Design: Lineone sign-up-1 style.
--}}
<x-register-layout :title="__('Register')">
    @php
        $requestedMembershipType = request()->query('type');
        $initialMembershipType = in_array($requestedMembershipType, ['donor', 'recipient', 'provider'], true)
            ? $requestedMembershipType
            : '';
    @endphp

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" x-data="registerForm()" x-init="init()" x-on:submit="validateBeforeSubmit($event)">
        @csrf

        {{-- Membership type: radio cards --}}
        <div class="mb-6">
            <x-input-label :value="__('I am registering as')" class="mb-3 block text-sm font-medium text-slate-700" required />
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all duration-200 focus:outline-none"
                    :class="membershipType === 'donor' ? 'border-secondary ring-2 ring-secondary bg-secondary/10 dark:border-secondary dark:ring-secondary dark:bg-secondary/10' : 'border-slate-200 hover:border-slate-300 bg-white dark:border-navy-600 dark:hover:border-navy-500 dark:bg-navy-750'">
                    <input type="radio" name="membership_type" value="donor" required x-model="membershipType" x-on:change="onMembershipChange()" class="sr-only">
                    <span class="flex flex-1 items-center justify-center text-sm font-medium text-slate-700">{{ __('Donor') }}</span>
                </label>
                <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all duration-200 focus:outline-none"
                    :class="membershipType === 'recipient' ? 'border-primary ring-2 ring-primary bg-primary/10 dark:border-accent dark:ring-accent dark:bg-accent/10' : 'border-slate-200 hover:border-slate-300 bg-white dark:border-navy-600 dark:hover:border-navy-500 dark:bg-navy-750'">
                    <input type="radio" name="membership_type" value="recipient" required x-model="membershipType" x-on:change="onMembershipChange()" class="sr-only">
                    <span class="flex flex-1 items-center justify-center text-sm font-medium text-slate-700">{{ __('Recipient') }}</span>
                </label>
                <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all duration-200 focus:outline-none"
                    :class="membershipType === 'provider' ? 'border-primary ring-2 ring-primary bg-primary/10 dark:border-accent dark:ring-accent dark:bg-accent/10' : 'border-slate-200 hover:border-slate-300 bg-white dark:border-navy-600 dark:hover:border-navy-500 dark:bg-navy-750'">
                    <input type="radio" name="membership_type" value="provider" required x-model="membershipType" x-on:change="onMembershipChange()" class="sr-only">
                    <span class="flex flex-1 items-center justify-center text-sm font-medium text-slate-700">{{ __('Provider') }}</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('membership_type')" class="mt-2" />
        </div>

        {{-- Provider: separate multi-step form at /register/provider --}}
        <div x-show="membershipType === 'provider'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="p-4 rounded-lg border border-primary/20 bg-primary/10 dark:border-accent/20 dark:bg-accent/10" x-cloak>
            <p class="text-slate-600 dark:text-navy-300 text-sm mb-4">{{ __('Provider registration requires additional business information.') }}</p>
            <a href="{{ route('register.provider') }}"
                class="btn inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-focus focus:ring-4 focus:ring-primary/20 dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/20 transition">
                <span>{{ __('Continue') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
            </a>
        </div>

        {{-- Common: name, email, password (donor & recipient) --}}
        <div x-show="membershipType && membershipType !== 'provider'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="space-y-4" x-cloak>
            <div>
                <label class="relative flex">
                    <input id="name" name="name" type="text" value="{{ old('name') }}"
                        x-bind:required="!!membershipType"
                        x-bind:disabled="!membershipType"
                        autocomplete="name"
                        class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-9 pl-3 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent"
                        placeholder="{{ __('Full Name') }}" />
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label class="relative flex">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" dir="ltr"
                        x-bind:required="!!membershipType"
                        x-bind:disabled="!membershipType"
                        autocomplete="username"
                        class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-9 pl-3 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent [&:placeholder-shown]:text-right [&:not(:placeholder-shown)]:text-left"
                        placeholder="{{ __('Email') }}" />
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label class="relative flex">
                    <input id="password" name="password" type="password"
                        x-bind:required="!!membershipType"
                        x-bind:disabled="!membershipType"
                        autocomplete="new-password"
                        class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-9 pl-3 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent"
                        placeholder="{{ __('Password') }}" />
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

        </div>

        {{-- Donor only: phone --}}
        <div x-show="membershipType === 'donor'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-cloak class="space-y-4 mt-4 p-4 rounded-lg border border-secondary/20 bg-secondary/10 dark:border-secondary/20 dark:bg-secondary/10">
            <div>
                <label class="relative flex">
                    <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" dir="ltr"
                        maxlength="10"
                        x-bind:required="membershipType === 'donor'"
                        x-bind:disabled="membershipType !== 'donor'"
                        x-on:input="$event.target.value = $event.target.value.replace(/\D/g,'').slice(0, 10)"
                        autocomplete="tel"
                        class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 text-left placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent"
                        placeholder="{{ __('Phone placeholder') }}" />
                    <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
            </div>
        </div>

        {{-- Recipient: KYC + identity photo + GPS location --}}
        <div x-show="membershipType === 'recipient'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-cloak class="space-y-4 mt-4"
            x-bind:aria-hidden="membershipType !== 'recipient'">

            {{-- Recipient phone --}}
            <div class="p-4 rounded-lg border border-primary/20 bg-primary/10 dark:border-accent/20 dark:bg-accent/10">
                <label class="relative flex">
                    <input id="recipient_phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" dir="ltr"
                        maxlength="10"
                        x-bind:required="membershipType === 'recipient'"
                        x-bind:disabled="membershipType !== 'recipient'"
                        x-on:input="$event.target.value = $event.target.value.replace(/\D/g,'').slice(0, 10)"
                        autocomplete="tel"
                        class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 text-left placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent"
                        placeholder="{{ __('Phone placeholder') }}" />
                    <span class="pointer-events-none absolute left-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
            </div>

            {{-- Nationality + ID Type --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="nationality" :value="__('Nationality')" required />
                    {{-- Choices.js: searchable select; width locked to column via .nationality-choices-wrap --}}
                    <div class="nationality-choices-wrap mt-1">
                        <select id="nationality" name="nationality"
                            x-bind:required="membershipType === 'recipient'"
                            x-bind:disabled="membershipType !== 'recipient'"
                            class="form-input form-select w-full min-w-0 max-w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 text-sm dark:border-navy-450 dark:bg-navy-900/50">
                            <option value="">— {{ __('Select') }} —</option>
                            @include('partials.nationality-select-options', ['selected' => old('nationality')])
                        </select>
                    </div>
                    <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="id_type" :value="__('Identity Document Type')" required />
                    <div class="relative flex mt-1">
                        <select id="id_type" name="id_type"
                            x-bind:required="membershipType === 'recipient'"
                            x-bind:disabled="membershipType !== 'recipient'"
                            class="form-input form-select peer w-full rounded-lg border border-slate-300 bg-transparent bg-none px-3 py-2.5 pr-9 pl-3 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent">
                            <option value="">— {{ __('Select') }} —</option>
                            <option value="national_id"    {{ old('id_type') === 'national_id'    ? 'selected' : '' }}>{{ __('National ID') }}</option>
                            <option value="iqama"          {{ old('id_type') === 'iqama'          ? 'selected' : '' }}>{{ __('Iqama') }}</option>
                            <option value="hudood_number"  {{ old('id_type') === 'hudood_number'  ? 'selected' : '' }}>{{ __('Hudood Number') }}</option>
                        </select>
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                        </span>
                    </div>
                    <x-input-error :messages="$errors->get('id_type')" class="mt-2" />
                </div>
            </div>

            {{-- ID Number --}}
            <div>
                <x-input-label for="id_number" :value="__('ID / Iqama Number')" required />
                <label class="relative flex mt-1">
                    <input id="id_number" name="id_number" type="text" inputmode="numeric" dir="ltr"
                        value="{{ old('id_number') }}"
                        maxlength="10"
                        x-bind:required="membershipType === 'recipient'"
                        x-bind:disabled="membershipType !== 'recipient'"
                        x-on:input="$event.target.value = $event.target.value.replace(/\D/g,'').slice(0, 10)"
                        placeholder="{{ __('10-digit ID number') }}"
                        class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pr-9 pl-3 text-left placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent" />
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('id_number')" class="mt-2" />
            </div>

            {{-- Identity photo: camera capture --}}
            <div class="rounded-lg border border-primary/20 bg-primary/10 dark:border-accent/20 dark:bg-accent/10 p-4">
                <x-input-label :value="__('Identity Photo (Capture with camera)')" required />
                <p class="text-sm text-slate-600 mt-1 mb-3">{{ __('You must capture your identity document using your device camera. File upload is not allowed.') }}</p>

                <div x-show="!idPhotoCaptured" class="space-y-3">
                    <div x-show="!cameraActive" class="flex gap-2">
                        <button type="button" x-on:click="startCamera()"
                            class="text-white bg-primary hover:bg-primary-focus focus:ring-4 focus:ring-primary/20 dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/20 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                            {{ __('Start Camera') }}
                        </button>
                    </div>
                    <div x-show="cameraActive" class="space-y-3">
                        <video id="camera-preview" x-ref="video" autoplay playsinline class="w-full rounded-lg border border-slate-300 dark:border-navy-500"></video>
                        <div class="flex gap-2">
                            <button type="button" x-on:click="capturePhoto()"
                                class="text-white bg-primary hover:bg-primary-focus focus:ring-4 focus:ring-primary/20 dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/20 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                {{ __('Capture Photo') }}
                            </button>
                            <button type="button" x-on:click="stopCamera()"
                                class="text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 dark:text-navy-100 dark:bg-navy-700 dark:border-navy-500 dark:hover:bg-navy-600 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div x-show="idPhotoCaptured" class="space-y-2">
                    <img x-ref="previewImg" src="" alt="{{ __('Captured') }}" class="max-h-40 rounded-lg border border-slate-300 dark:border-navy-500" />
                    <button type="button" x-on:click="retakePhoto()"
                        class="text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent font-medium text-sm">
                        {{ __('Retake photo') }}
                    </button>
                </div>

                <input type="hidden" name="id_photo_base64" x-model="idPhotoBase64" x-bind:disabled="membershipType !== 'recipient'" />
                <x-input-error :messages="$errors->get('id_photo_base64')" class="mt-2" />
            </div>

            {{-- Short Address --}}
            <div>
                <x-input-label for="short_address" :value="__('Short Address')" required />
                <label class="relative flex mt-1">
                    <input id="short_address" type="text" name="short_address" value="{{ old('short_address') }}"
                        x-bind:required="membershipType === 'recipient'"
                        x-bind:disabled="membershipType !== 'recipient'"
                        placeholder="{{ __('City - District - Street - House Number') }}"
                        class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 pr-9 pl-3 placeholder:text-slate-400/70 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent" />
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('short_address')" class="mt-2" />
            </div>

            {{-- GPS Location (mandatory) --}}
            <div class="rounded-lg border border-primary/20 bg-primary/10 dark:border-accent/20 dark:bg-accent/10 p-4">
                <x-input-label :value="__('Location Verification')" required />
                <p class="text-sm text-slate-600 mt-1 mb-3">{{ __('We need to verify your location. Please allow location access when prompted by your browser.') }}</p>

                <div x-show="!locationCaptured" class="space-y-2">
                    <button type="button" x-on:click="requestLocation()"
                        :disabled="locationLoading"
                        class="text-white bg-primary hover:bg-primary-focus focus:ring-4 focus:ring-primary/20 dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/20 font-medium rounded-lg text-sm px-5 py-2.5 transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!locationLoading">{{ __('Allow Location Access') }}</span>
                        <span x-show="locationLoading">{{ __('Retrieving location…') }}</span>
                    </button>
                    <p x-show="locationError" x-text="locationError" class="text-sm text-red-600 dark:text-red-400 mt-1"></p>
                </div>

                <div x-show="locationCaptured" class="flex items-center gap-2 text-sm text-green-700 dark:text-green-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ __('Location captured successfully.') }}</span>
                    <button type="button" x-on:click="clearLocation()" class="text-xs text-slate-500 underline hover:text-slate-700 ms-2">{{ __('Reset') }}</button>
                </div>

                <input type="hidden" name="location_lat" x-model="locationLat" x-bind:disabled="membershipType !== 'recipient'" />
                <input type="hidden" name="location_lng" x-model="locationLng" x-bind:disabled="membershipType !== 'recipient'" />
                <x-input-error :messages="$errors->get('location_lat')" class="mt-2" />
                <x-input-error :messages="$errors->get('location_lng')" class="mt-2" />
            </div>

            {{-- KYC: Income + Household --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="income_band" :value="__('Income Band')" required />
                    <div class="relative flex mt-1">
                        <select id="income_band" name="income_band"
                            x-bind:required="membershipType === 'recipient'"
                            x-bind:disabled="membershipType !== 'recipient'"
                            class="form-input form-select peer w-full rounded-lg border border-slate-300 bg-transparent bg-none px-3 py-2.5 pr-9 pl-3 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent">
                            <option value="">— {{ __('Select') }} —</option>
                            <option value="0-500"     {{ old('income_band') === '0-500'     ? 'selected' : '' }}>0 - 500 <x-sar-symbol />/{{ __('month') }}</option>
                            <option value="500-1000"  {{ old('income_band') === '500-1000'  ? 'selected' : '' }}>500 - 1,000 <x-sar-symbol />/{{ __('month') }}</option>
                            <option value="1000-1500" {{ old('income_band') === '1000-1500' ? 'selected' : '' }}>1,000 - 1,500 <x-sar-symbol />/{{ __('month') }}</option>
                            <option value="1500-2000" {{ old('income_band') === '1500-2000' ? 'selected' : '' }}>1,500 - 2,000 <x-sar-symbol />/{{ __('month') }}</option>
                            <option value="2000-2500" {{ old('income_band') === '2000-2500' ? 'selected' : '' }}>2,000 - 2,500 <x-sar-symbol />/{{ __('month') }}</option>
                            <option value="2500-3000" {{ old('income_band') === '2500-3000' ? 'selected' : '' }}>2,500 - 3,000 <x-sar-symbol />/{{ __('month') }}</option>
                            <option value="3000-5000" {{ old('income_band') === '3000-5000' ? 'selected' : '' }}>3,000 - 5,000 <x-sar-symbol />/{{ __('month') }}</option>
                            <option value="5000+"     {{ old('income_band') === '5000+'     ? 'selected' : '' }}>5,000+ <x-sar-symbol />/{{ __('month') }}</option>
                        </select>
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2 10h20M4 10v9a1 1 0 001 1h4a1 1 0 001-1v-6a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 001 1h4a1 1 0 001-1v-9M4 10V7a2 2 0 012-2h12a2 2 0 012 2v3" /></svg>
                        </span>
                    </div>
                    <x-input-error :messages="$errors->get('income_band')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="household_size" :value="__('Household Size (number of family members)')" required />
                    <label class="relative flex mt-1">
                        <input id="household_size" type="number" name="household_size" value="{{ old('household_size', 1) }}" min="1" max="50"
                            x-bind:required="membershipType === 'recipient'"
                            x-bind:disabled="membershipType !== 'recipient'"
                            class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 pr-9 pl-3 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent" />
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('household_size')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="marital_status" :value="__('Marital Status')" required />
                    <div class="relative flex mt-1">
                        <select id="marital_status" name="marital_status"
                            x-bind:required="membershipType === 'recipient'"
                            x-bind:disabled="membershipType !== 'recipient'"
                            class="form-input form-select peer w-full rounded-lg border border-slate-300 bg-transparent bg-none px-3 py-2.5 pr-9 pl-3 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent">
                            <option value="">— {{ __('Select') }} —</option>
                            <option value="single"   {{ old('marital_status') === 'single'   ? 'selected' : '' }}>{{ __('Single') }}</option>
                            <option value="married"  {{ old('marital_status') === 'married'  ? 'selected' : '' }}>{{ __('Married') }}</option>
                            <option value="divorced" {{ old('marital_status') === 'divorced' ? 'selected' : '' }}>{{ __('Divorced') }}</option>
                            <option value="widowed"  {{ old('marital_status') === 'widowed'  ? 'selected' : '' }}>{{ __('Widowed') }}</option>
                        </select>
                        <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </span>
                    </div>
                    <x-input-error :messages="$errors->get('marital_status')" class="mt-2" />
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
                                <input type="radio" name="is_student" value="1" {{ old('is_student') === '1' || old('is_student') === true ? 'checked' : '' }}
                                    x-bind:required="membershipType === 'recipient'"
                                    x-bind:disabled="membershipType !== 'recipient'"
                                    class="w-4 h-4 text-primary bg-slate-100 border-slate-300 focus:ring-primary focus:ring-2 dark:text-accent-light dark:focus:ring-accent">
                                <span class="ms-2 text-sm font-medium text-slate-800 dark:text-navy-100">{{ __('Yes, I am a student') }}</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="is_student" value="0" {{ old('is_student') === '0' || old('is_student') === false ? 'checked' : '' }}
                                    x-bind:disabled="membershipType !== 'recipient'"
                                    class="w-4 h-4 text-primary bg-slate-100 border-slate-300 focus:ring-primary focus:ring-2 dark:text-accent-light dark:focus:ring-accent">
                                <span class="ms-2 text-sm font-medium text-slate-800 dark:text-navy-100">{{ __('No') }}</span>
                            </label>
                        </div>
                    </fieldset>
                    <x-input-error :messages="$errors->get('is_student')" class="mt-2" />
                </div>
            </div>

            {{-- Employment Status --}}
            <div>
                <x-input-label for="employment_status" :value="__('Employment Status')" required />
                <div class="relative flex mt-1">
                    <select id="employment_status" name="employment_status"
                        x-bind:required="membershipType === 'recipient'"
                        x-bind:disabled="membershipType !== 'recipient'"
                        class="form-input form-select peer w-full rounded-lg border border-slate-300 bg-transparent bg-none px-3 py-2.5 pr-9 pl-3 hover:z-10 hover:border-slate-400 focus:z-10 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent">
                        <option value="">— {{ __('Select') }} —</option>
                        <option value="unemployed"                 {{ old('employment_status') === 'unemployed'                 ? 'selected' : '' }}>{{ __('Unemployed') }}</option>
                        <option value="unable_to_work"             {{ old('employment_status') === 'unable_to_work'             ? 'selected' : '' }}>{{ __('Unable to work') }}</option>
                        <option value="employed_insufficient_income" {{ old('employment_status') === 'employed_insufficient_income' ? 'selected' : '' }}>{{ __('Employed but income is insufficient') }}</option>
                    </select>
                    <span class="pointer-events-none absolute right-0 flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </span>
                </div>
                <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
            </div>

            {{-- Situation Description --}}
            <div>
                <x-input-label for="situation_description" :value="__('Situation Description')" required />
                <p class="text-xs text-slate-500 dark:text-navy-400 mt-0.5 mb-1">{{ __('Briefly describe your situation and why you need support.') }}</p>
                <textarea id="situation_description" name="situation_description"
                    rows="4"
                    x-bind:required="membershipType === 'recipient'"
                    x-bind:disabled="membershipType !== 'recipient'"
                    maxlength="1000"
                    placeholder="{{ __('Write a brief description of your situation…') }}"
                    class="form-input w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2.5 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-900/50 dark:hover:border-navy-400 dark:focus:border-accent resize-y">{{ old('situation_description') }}</textarea>
                <x-input-error :messages="$errors->get('situation_description')" class="mt-2" />
            </div>

        </div>

        <div class="mt-8 flex flex-col gap-4 border-t border-slate-200 pt-6 dark:border-navy-500">
            <a class="text-sm font-medium text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent"
                href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>
            <p class="text-pretty text-xs leading-relaxed text-slate-600 dark:text-navy-300">
                {{ __('auth.legal.consent_register') }}
                <a class="font-semibold text-primary underline decoration-primary/30 underline-offset-2 hover:text-primary-focus hover:decoration-primary/50 dark:text-accent-light dark:decoration-accent/30 dark:hover:text-accent dark:hover:decoration-accent/50"
                    href="{{ route('legal.terms', ['return' => request()->getRequestUri()]) }}">
                    {{ __('auth.legal.terms') }}
                </a>
                {{ __('auth.legal.and') }}
                <a class="font-semibold text-primary underline decoration-primary/30 underline-offset-2 hover:text-primary-focus hover:decoration-primary/50 dark:text-accent-light dark:decoration-accent/30 dark:hover:text-accent dark:hover:decoration-accent/50"
                    href="{{ route('legal.privacy', ['return' => request()->getRequestUri()]) }}">
                    {{ __('auth.legal.privacy') }}
                </a>.
            </p>
            <!-- <button type="submit" x-show="membershipType && membershipType !== 'provider'"
                class="btn w-full bg-primary px-5 py-2.5 font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 sm:w-auto"> -->
            <button type="submit" x-show="membershipType && membershipType !== 'provider'" x-cloak
                class="btn w-full sm:w-auto bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 px-5 py-2.5 rounded-lg">
                {{ __('Register') }}
            </button>
        </div>
    </form>

    {{-- Alpine.js: membership switcher + camera + GPS --}}
    <script>
        function registerForm() {
            return {
                membershipType: @json(old('membership_type', $initialMembershipType)) || '',
                cameraActive: false,
                idPhotoCaptured: false,
                idPhotoBase64: '',
                stream: null,
                locationLat: '',
                locationLng: '',
                locationCaptured: false,
                locationLoading: false,
                locationError: '',
                nationalityChoices: null,
                _nationalityChoicesTimer: null,

                init() {
                    if (this.membershipType && document.querySelector('[name="membership_type"]').value) {
                        this.onMembershipChange();
                    }
                    this.$nextTick(() => {
                        document.querySelectorAll('input[name="phone_number"]').forEach(el => {
                            el.value = el.value.replace(/\D/g,'').slice(0, 10);
                        });
                    });
                },

                onMembershipChange() {
                    if (this.membershipType !== 'recipient') {
                        if (this._nationalityChoicesTimer) {
                            clearTimeout(this._nationalityChoicesTimer);
                            this._nationalityChoicesTimer = null;
                        }
                        this.destroyNationalityChoices();
                    }
                    if (this.membershipType === 'recipient') {
                        this.stopCamera();
                        this.idPhotoCaptured = false;
                        this.idPhotoBase64 = '';
                        this.locationLat = '';
                        this.locationLng = '';
                        this.locationCaptured = false;
                        this.locationError = '';
                    }
                    this.$nextTick(() => {
                        if (this.membershipType === 'recipient') {
                            this.scheduleNationalityChoices(320);
                        }
                    });
                },

                destroyNationalityChoices() {
                    if (this.nationalityChoices) {
                        try {
                            this.nationalityChoices.destroy();
                        } catch (e) {
                            /* noop */
                        }
                        this.nationalityChoices = null;
                    }
                    const el = document.getElementById('nationality');
                    if (el) {
                        el._nublChoicesInstance = null;
                    }
                },

                scheduleNationalityChoices(delayMs) {
                    if (this._nationalityChoicesTimer) {
                        clearTimeout(this._nationalityChoicesTimer);
                    }
                    this._nationalityChoicesTimer = setTimeout(() => {
                        this._nationalityChoicesTimer = null;
                        this.maybeInitNationalityChoices();
                    }, delayMs);
                },

                maybeInitNationalityChoices(retry = 0) {
                    if (this.membershipType !== 'recipient') {
                        return;
                    }
                    const el = document.getElementById('nationality');
                    if (!el || this.nationalityChoices || !window.nublMountNationalityChoices) {
                        return;
                    }
                    if (el.offsetWidth < 2 || el.getClientRects().length === 0) {
                        if (retry < 15) {
                            setTimeout(() => this.maybeInitNationalityChoices(retry + 1), 80);
                        }
                        return;
                    }
                    window.nublMountNationalityChoices(el, @json(__('Search'))).then((instance) => {
                        if (instance && this.membershipType === 'recipient') {
                            this.nationalityChoices = instance;
                        }
                    });
                },

                async startCamera() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                        this.$refs.video.srcObject = this.stream;
                        this.cameraActive = true;
                    } catch (err) {
                        alert('{{ __("Camera access is required to capture your identity photo. Please allow camera permission.") }}');
                        console.error(err);
                    }
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.cameraActive = false;
                },

                capturePhoto() {
                    const video = this.$refs.video;
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    this.idPhotoBase64 = canvas.toDataURL('image/jpeg', 0.85);
                    this.idPhotoCaptured = true;
                    this.$refs.previewImg.src = this.idPhotoBase64;
                    this.stopCamera();
                },

                retakePhoto() {
                    this.idPhotoCaptured = false;
                    this.idPhotoBase64 = '';
                    this.startCamera();
                },

                requestLocation() {
                    if (!navigator.geolocation) {
                        this.locationError = '{{ __("Geolocation is not supported by your browser.") }}';
                        return;
                    }
                    this.locationLoading = true;
                    this.locationError = '';
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.locationLat = pos.coords.latitude;
                            this.locationLng = pos.coords.longitude;
                            this.locationCaptured = true;
                            this.locationLoading = false;
                        },
                        (err) => {
                            this.locationLoading = false;
                            this.locationError = '{{ __("Location access was denied. You must allow location access to complete registration.") }}';
                            console.error(err);
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                },

                clearLocation() {
                    this.locationLat = '';
                    this.locationLng = '';
                    this.locationCaptured = false;
                    this.locationError = '';
                },

                validateBeforeSubmit(event) {
                    document.querySelectorAll('input[name="phone_number"]').forEach(el => {
                        if (el.value) el.value = el.value.replace(/^0+/, '');
                    });
                    if (this.membershipType === 'recipient') {
                        if (!this.idPhotoBase64) {
                            event.preventDefault();
                            alert('{{ __("Please capture your identity photo using the camera before submitting.") }}');
                            return false;
                        }
                        if (!this.locationCaptured) {
                            event.preventDefault();
                            alert('{{ __("Please allow location access before submitting.") }}');
                            return false;
                        }
                    }
                }
            };
        }
    </script>

</x-register-layout>
