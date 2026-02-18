{{--
    Registration: donor (instant) or recipient (pending approval).
    Recipient requires camera capture for ID + address proof (no file upload).
--}}
<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" x-data="registerForm()" x-init="init()" x-on:submit="validateBeforeSubmit($event)">
        @csrf

        {{-- Membership type: radio cards --}}
        <div class="mb-6">
            <x-input-label :value="__('I am registering as')" class="mb-3 block text-sm font-medium text-slate-700" required />
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {{-- Donor: gold (giving) | Recipient: teal (hope) | Provider: blue (trust) --}}
                <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all duration-200 focus:outline-none"
                    :class="membershipType === 'donor' ? 'border-nubl-gold-500 ring-2 ring-nubl-gold-500 bg-nubl-gold-50/50' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <input type="radio" name="membership_type" value="donor" required x-model="membershipType" x-on:change="onMembershipChange()" class="sr-only">
                    <span class="flex flex-1 items-center justify-center text-sm font-medium text-slate-700">{{ __('Donor') }}</span>
                </label>
                <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all duration-200 focus:outline-none"
                    :class="membershipType === 'recipient' ? 'border-nubl-teal-500 ring-2 ring-nubl-teal-500 bg-nubl-teal-50/50' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <input type="radio" name="membership_type" value="recipient" required x-model="membershipType" x-on:change="onMembershipChange()" class="sr-only">
                    <span class="flex flex-1 items-center justify-center text-sm font-medium text-slate-700">{{ __('Recipient') }}</span>
                </label>
                <label class="relative flex cursor-pointer rounded-lg border p-4 transition-all duration-200 focus:outline-none"
                    :class="membershipType === 'provider' ? 'border-nubl-blue-500 ring-2 ring-nubl-blue-500 bg-nubl-blue-50/50' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <input type="radio" name="membership_type" value="provider" required x-model="membershipType" x-on:change="onMembershipChange()" class="sr-only">
                    <span class="flex flex-1 items-center justify-center text-sm font-medium text-slate-700">{{ __('Provider') }}</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('membership_type')" class="mt-2" />
        </div>

        {{-- Provider: separate multi-step form at /register/provider --}}
        <div x-show="membershipType === 'provider'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="p-4 rounded-lg border border-nubl-teal-200 bg-nubl-teal-50" x-cloak>
            <p class="text-slate-600 text-sm mb-4">{{ __('Provider registration requires additional business information.') }}</p>
            <a href="{{ route('register.provider') }}"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-nubl-teal-600 text-white text-sm font-medium rounded-lg hover:bg-nubl-teal-700 focus:ring-4 focus:ring-nubl-teal-200 transition">
                {{ __('Continue to Provider Registration') }}
            </a>
        </div>

        {{-- Common: name, email, password (donor & recipient) --}}
        <div x-show="membershipType && membershipType !== 'provider'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="space-y-4" x-cloak>
            <div>
                <x-input-label for="name" :value="__('Full Name')" required />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')"
                    :required="true"
                    x-bind:disabled="!membershipType"
                    autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" required />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                    :required="true"
                    x-bind:disabled="!membershipType"
                    autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" required />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                    :required="true"
                    x-bind:disabled="!membershipType"
                    autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

        </div>

        {{-- Donor only: phone — gold accent (giving) --}}
        <div x-show="membershipType === 'donor'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-cloak class="space-y-4 mt-4 p-4 rounded-lg border border-nubl-gold-200 bg-nubl-gold-50/50">
            <div>
                <x-input-label for="phone_number" :value="__('Phone Number (Saudi format)')" required />
                <x-text-input id="phone_number" class="block mt-1 w-full" type="tel" name="phone_number" :value="old('phone_number')" placeholder="05XXXXXXXX"
                    maxlength="10"
                    x-bind:required="membershipType === 'donor'"
                    x-bind:disabled="membershipType !== 'donor'"
                    x-on:input="$event.target.value = $event.target.value.replace(/\D/g,'').slice(0, 10)"
                    autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
            </div>
        </div>

        {{-- Recipient: KYC + identity/address photos (camera only) --}}
        <div x-show="membershipType === 'recipient'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-cloak class="space-y-4 mt-4"
            x-bind:aria-hidden="membershipType !== 'recipient'">
            {{-- Recipient phone — teal accent (same style as donor) --}}
            <div class="p-4 rounded-lg border border-nubl-teal-200 bg-nubl-teal-50/50">
                <x-input-label for="recipient_phone_number" :value="__('Phone Number (Saudi format)')" required />
                <x-text-input id="recipient_phone_number" class="block mt-1 w-full" type="tel" name="phone_number" :value="old('phone_number')" placeholder="05XXXXXXXX"
                    maxlength="10"
                    x-bind:required="membershipType === 'recipient'"
                    x-bind:disabled="membershipType !== 'recipient'"
                    x-on:input="$event.target.value = $event.target.value.replace(/\D/g,'').slice(0, 10)"
                    autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
            </div>
            {{-- Basic info --}}
            <div>
                <x-input-label for="nationality" :value="__('Nationality')" required />
                <select id="nationality" name="nationality"
                    x-bind:required="membershipType === 'recipient'"
                    x-bind:disabled="membershipType !== 'recipient'"
                    class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                    <option value="">— {{ __('Select') }} —</option>
                    @foreach(config('nationalities') as $country)
                        <option value="{{ $country }}" {{ old('nationality') === $country ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="short_address" :value="__('Short Address')" required />
                <input id="short_address" type="text" name="short_address" value="{{ old('short_address') }}"
                    x-bind:required="membershipType === 'recipient'"
                    x-bind:disabled="membershipType !== 'recipient'"
                    placeholder="{{ __('City, district, or area sufficient to identify where you live') }}"
                    class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500 placeholder:text-slate-400" />
                <x-input-error :messages="$errors->get('short_address')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="id_type" :value="__('Identity Document Type')" required />
                <select id="id_type" name="id_type"
                    x-bind:required="membershipType === 'recipient'"
                    x-bind:disabled="membershipType !== 'recipient'"
                    class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                    <option value="">— {{ __('Select') }} —</option>
                    <option value="national_id" {{ old('id_type') === 'national_id' ? 'selected' : '' }}>{{ __('National ID') }}</option>
                    <option value="iqama" {{ old('id_type') === 'iqama' ? 'selected' : '' }}>{{ __('Iqama') }}</option>
                </select>
                <x-input-error :messages="$errors->get('id_type')" class="mt-2" />
            </div>

            {{-- Identity photo: camera capture required — teal accent --}}
            <div class="rounded-lg border border-nubl-teal-200 bg-nubl-teal-50 p-4">
                <x-input-label :value="__('Identity Photo (Capture with camera)')" required />
                <p class="text-sm text-slate-600 mt-1 mb-3">{{ __('You must capture your identity document using your device camera. File upload is not allowed.') }}</p>

                <div x-show="!idPhotoCaptured" class="space-y-3">
                    <div x-show="!cameraActive" class="flex gap-2">
                        <button type="button" x-on:click="startCamera()"
                            class="text-white bg-nubl-teal-600 hover:bg-nubl-teal-700 focus:ring-4 focus:ring-nubl-teal-200 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                            {{ __('Start Camera') }}
                        </button>
                    </div>
                    <div x-show="cameraActive" class="space-y-3">
                        <video id="camera-preview" x-ref="video" autoplay playsinline class="w-full rounded-lg border border-gray-300"></video>
                        <div class="flex gap-2">
                            <button type="button" x-on:click="capturePhoto()"
                                class="text-white bg-nubl-teal-600 hover:bg-nubl-teal-700 focus:ring-4 focus:ring-nubl-teal-200 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                {{ __('Capture Photo') }}
                            </button>
                            <button type="button" x-on:click="stopCamera()"
                                class="text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div x-show="idPhotoCaptured" class="space-y-2">
                    <img x-ref="previewImg" src="" alt="Captured" class="max-h-40 rounded-lg border border-gray-300" />
                    <button type="button" x-on:click="retakePhoto()"
                        class="text-nubl-teal-600 hover:text-nubl-teal-700 font-medium text-sm">
                        {{ __('Retake photo') }}
                    </button>
                </div>

                <input type="hidden" name="id_photo_base64" x-model="idPhotoBase64" x-bind:disabled="membershipType !== 'recipient'" />
                <x-input-error :messages="$errors->get('id_photo_base64')" class="mt-2" />
            </div>

            {{-- KYC: income, household, marital status, student --}}
            <div>
                <x-input-label for="income_band" :value="__('Income Band')" required />
                <select id="income_band" name="income_band"
                    x-bind:required="membershipType === 'recipient'"
                    x-bind:disabled="membershipType !== 'recipient'"
                    class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                    <option value="">— {{ __('Select') }} —</option>
                    <option value="0-500" {{ old('income_band') === '0-500' ? 'selected' : '' }}>0 - 500 {{ __('SAR/month') }}</option>
                    <option value="500-1000" {{ old('income_band') === '500-1000' ? 'selected' : '' }}>500 - 1,000 {{ __('SAR/month') }}</option>
                    <option value="1000-1500" {{ old('income_band') === '1000-1500' ? 'selected' : '' }}>1,000 - 1,500 {{ __('SAR/month') }}</option>
                    <option value="1500-2000" {{ old('income_band') === '1500-2000' ? 'selected' : '' }}>1,500 - 2,000 {{ __('SAR/month') }}</option>
                    <option value="2000-2500" {{ old('income_band') === '2000-2500' ? 'selected' : '' }}>2,000 - 2,500 {{ __('SAR/month') }}</option>
                    <option value="2500-3000" {{ old('income_band') === '2500-3000' ? 'selected' : '' }}>2,500 - 3,000 {{ __('SAR/month') }}</option>
                    <option value="3000-5000" {{ old('income_band') === '3000-5000' ? 'selected' : '' }}>3,000 - 5,000 {{ __('SAR/month') }}</option>
                    <option value="5000+" {{ old('income_band') === '5000+' ? 'selected' : '' }}>5,000+ {{ __('SAR/month') }}</option>
                </select>
                <x-input-error :messages="$errors->get('income_band')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="household_size" :value="__('Household Size (number of family members)')" required />
                <x-text-input id="household_size" class="block mt-1 w-full" type="number" name="household_size" :value="old('household_size', 1)" min="1" max="50"
                    x-bind:required="membershipType === 'recipient'"
                    x-bind:disabled="membershipType !== 'recipient'" />
                <x-input-error :messages="$errors->get('household_size')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="marital_status" :value="__('Marital Status')" required />
                <select id="marital_status" name="marital_status"
                    x-bind:required="membershipType === 'recipient'"
                    x-bind:disabled="membershipType !== 'recipient'"
                    class="block mt-1 w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-nubl-teal-500 focus:border-nubl-teal-500">
                    <option value="">— {{ __('Select') }} —</option>
                    <option value="single" {{ old('marital_status') === 'single' ? 'selected' : '' }}>{{ __('Single') }}</option>
                    <option value="married" {{ old('marital_status') === 'married' ? 'selected' : '' }}>{{ __('Married') }}</option>
                    <option value="divorced" {{ old('marital_status') === 'divorced' ? 'selected' : '' }}>{{ __('Divorced') }}</option>
                    <option value="widowed" {{ old('marital_status') === 'widowed' ? 'selected' : '' }}>{{ __('Widowed') }}</option>
                </select>
                <x-input-error :messages="$errors->get('marital_status')" class="mt-2" />
            </div>

            <div>
                <fieldset class="flex flex-wrap gap-4">
                    <legend class="block font-medium text-sm text-gray-700 mb-2">{{ __('Are you a student?') }} <span class="text-red-400" aria-hidden="true">*</span></legend>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="is_student" value="1" {{ old('is_student') === '1' || old('is_student') === true ? 'checked' : '' }}
                            x-bind:required="membershipType === 'recipient'"
                            x-bind:disabled="membershipType !== 'recipient'"
                            class="w-4 h-4 text-nubl-teal-600 bg-slate-100 border-slate-300 focus:ring-nubl-teal-500 focus:ring-2">
                        <span class="ms-2 text-sm font-medium text-gray-900">{{ __('Yes, I am a student') }}</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="is_student" value="0" {{ old('is_student') === '0' || old('is_student') === false ? 'checked' : '' }}
                            x-bind:disabled="membershipType !== 'recipient'"
                            class="w-4 h-4 text-nubl-teal-600 bg-slate-100 border-slate-300 focus:ring-nubl-teal-500 focus:ring-2">
                        <span class="ms-2 text-sm font-medium text-gray-900">{{ __('No') }}</span>
                    </label>
                </fieldset>
                <x-input-error :messages="$errors->get('is_student')" class="mt-2" />
            </div>

            {{-- Address proof: camera capture required — gold accent --}}
            <div class="rounded-lg border border-nubl-gold-200 bg-nubl-gold-50/70 p-4">
                <x-input-label :value="__('Address confirmation photo')" required />
                <p class="text-sm text-slate-600 mt-1 mb-3">{{ __('Capture a photo of your address proof (e.g. utility bill, lease) using your device camera.') }}</p>

                <div x-show="!addressPhotoCaptured" class="space-y-3">
                    <div x-show="!addressCameraActive" class="flex gap-2">
                        <button type="button" x-on:click="startAddressCamera()"
                            class="text-white bg-nubl-gold-500 hover:bg-nubl-gold-600 focus:ring-4 focus:ring-nubl-gold-200 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                            {{ __('Start Camera') }}
                        </button>
                    </div>
                    <div x-show="addressCameraActive" class="space-y-3">
                        <video id="address-camera-preview" x-ref="addressVideo" autoplay playsinline class="w-full rounded-lg border border-gray-300"></video>
                        <div class="flex gap-2">
                            <button type="button" x-on:click="captureAddressPhoto()"
                                class="text-white bg-nubl-gold-500 hover:bg-nubl-gold-600 focus:ring-4 focus:ring-nubl-gold-200 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                {{ __('Capture Photo') }}
                            </button>
                            <button type="button" x-on:click="stopAddressCamera()"
                                class="text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 font-medium rounded-lg text-sm px-5 py-2.5 transition">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div x-show="addressPhotoCaptured" class="space-y-2">
                    <img x-ref="addressPreviewImg" src="" alt="Address captured" class="max-h-40 rounded-lg border border-gray-300" />
                    <button type="button" x-on:click="retakeAddressPhoto()"
                        class="text-nubl-gold-600 hover:text-nubl-gold-700 font-medium text-sm">
                        {{ __('Retake photo') }}
                    </button>
                </div>

                <input type="hidden" name="address_confirmation_base64" x-model="addressPhotoBase64" x-bind:disabled="membershipType !== 'recipient'" />
                <x-input-error :messages="$errors->get('address_confirmation_base64')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4 mt-8 pt-6 border-t border-slate-200">
            <a class="text-sm text-nubl-teal-600 hover:text-nubl-teal-700 font-medium" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>
            <div class="flex gap-3">
                <a x-show="membershipType === 'provider'" x-cloak href="{{ route('register.provider') }}"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-nubl-teal-600 text-white text-sm font-medium rounded-lg hover:bg-nubl-teal-700 focus:ring-4 focus:ring-nubl-teal-200 transition">
                    {{ __('Continue to Provider Registration') }}
                </a>
                <x-primary-button type="submit" class="!bg-nubl-blue-600 hover:!bg-nubl-blue-700 focus:!ring-nubl-blue-200" x-show="membershipType && membershipType !== 'provider'" x-cloak>
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </div>
    </form>

    {{-- Alpine.js: membership switcher + camera capture for recipient --}}
    <script>
        function registerForm() {
            return {
                membershipType: '{{ old('membership_type', '') }}' || '',
                cameraActive: false,
                idPhotoCaptured: false,
                idPhotoBase64: '',
                stream: null,
                addressCameraActive: false,
                addressPhotoCaptured: false,
                addressPhotoBase64: '',
                addressStream: null,

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

                // Reset photos when switching away from recipient
                onMembershipChange() {
                    if (this.membershipType === 'recipient') {
                        this.stopCamera();
                        this.stopAddressCamera();
                        this.idPhotoCaptured = false;
                        this.idPhotoBase64 = '';
                        this.addressPhotoCaptured = false;
                        this.addressPhotoBase64 = '';
                    }
                },

                async startCamera() {
                    this.stopAddressCamera();
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

                async startAddressCamera() {
                    this.stopCamera();
                    try {
                        this.addressStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                        this.$refs.addressVideo.srcObject = this.addressStream;
                        this.addressCameraActive = true;
                    } catch (err) {
                        alert('{{ __("Camera access is required. Please allow camera permission.") }}');
                        console.error(err);
                    }
                },

                stopAddressCamera() {
                    if (this.addressStream) {
                        this.addressStream.getTracks().forEach(track => track.stop());
                        this.addressStream = null;
                    }
                    this.addressCameraActive = false;
                },

                captureAddressPhoto() {
                    const video = this.$refs.addressVideo;
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    this.addressPhotoBase64 = canvas.toDataURL('image/jpeg', 0.85);
                    this.addressPhotoCaptured = true;
                    this.$refs.addressPreviewImg.src = this.addressPhotoBase64;
                    this.stopAddressCamera();
                },

                retakeAddressPhoto() {
                    this.addressPhotoCaptured = false;
                    this.addressPhotoBase64 = '';
                    this.startAddressCamera();
                },

                // Client-side: ensure photos captured before submit + strip leading zeros from phone (display only, not sent)
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
                        if (!this.addressPhotoBase64) {
                            event.preventDefault();
                            alert('{{ __("Please capture your address confirmation photo using the camera before submitting.") }}');
                            return false;
                        }
                    }
                }
            };
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-guest-layout>
