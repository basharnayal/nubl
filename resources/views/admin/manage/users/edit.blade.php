<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit User') }}: {{ $user->name }}</h2>
            <a href="{{ route('admin.manage.users.index') }}" class="text-sm text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent font-medium">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.manage.users.update', $user) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="bg-white rounded-lg shadow p-6 space-y-6">
                    <div>
                        <x-input-label for="membership_type" :value="__('User Type')" />
                        <input type="text" value="{{ ucfirst($user->membership_type) }}" disabled class="block mt-1 w-full rounded-lg border-gray-200 bg-gray-50 shadow-sm" />
                        <input type="hidden" name="membership_type" value="{{ $user->membership_type }}" />
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Name')" required />
                        <x-text-input id="name" name="name" value="{{ old('name', $user->name) }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" required />
                        <x-text-input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone_number" :value="__('Phone (Saudi)')" required />
                        <x-text-input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number', ($user->phone_number ?? $user->providerProfile?->phone_number) ? \App\Helpers\PhoneHelper::formatForInput($user->phone_number ?? $user->providerProfile?->phone_number) : '') }}" placeholder="05XXXXXXXX" class="block mt-1 w-full" maxlength="10" required />
                        <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('New Password (leave blank to keep)')" />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" />
                    </div>

                    @if($user->membership_type === 'recipient' && $user->recipientProfile)
                        <div class="pt-4 border-t border-gray-200 space-y-4">
                            <h3 class="font-medium text-gray-900">{{ __('Recipient Details') }}</h3>
                            @php
                                $old = array_merge(old(), [
                                    'nationality' => old('nationality', $user->recipientProfile->nationality),
                                    'short_address' => old('short_address', $user->recipientProfile->short_address),
                                    'id_type' => old('id_type', $user->recipientProfile->id_type),
                                    'income_band' => old('income_band', $user->recipientKycDetails?->income_band),
                                    'household_size' => old('household_size', $user->recipientKycDetails?->household_size),
                                    'marital_status' => old('marital_status', $user->recipientKycDetails?->marital_status),
                                    'is_student' => old('is_student', $user->recipientKycDetails?->is_student ? '1' : '0'),
                                ]);
                            @endphp
                            @include('admin.manage.users._recipient-fields', ['old' => $old, 'nationalities' => $nationalities ?? config('nationalities', []), 'photosRequired' => false])
                        </div>
                    @endif

                    @if($user->membership_type === 'provider' && $user->providerProfile)
                        <div class="pt-4 border-t border-gray-200 space-y-4">
                            <h3 class="font-medium text-gray-900">{{ __('Provider Details') }}</h3>
                            @php
                                $old = array_merge(old(), [
                                    'full_name_ar' => old('full_name_ar', $user->providerProfile->full_name_ar),
                                    'full_name_en' => old('full_name_en', $user->providerProfile->full_name_en),
                                    'business_name_ar' => old('business_name_ar', $user->providerProfile->business_name_ar),
                                    'business_name_en' => old('business_name_en', $user->providerProfile->business_name_en),
                                    'unified_number' => old('unified_number', $user->providerProfile->unified_number),
                                    'business_category' => old('business_category', $user->providerProfile->business_category ?? []),
                                    'address_ar' => old('address_ar', $user->providerProfile->address_ar),
                                    'address_en' => old('address_en', $user->providerProfile->address_en),
                                    'city' => old('city', $user->providerProfile->city),
                                    'region' => old('region', $user->providerProfile->region),
                                    'location' => old('location', $user->providerProfile->location),
                                    'daily_capacity' => old('daily_capacity', $user->providerOperatingInfo?->daily_capacity ?? 50),
                                    'service_type' => old('service_type', $user->providerOperatingInfo?->service_type ?? []),
                                    'estimated_preparation_order_time' => old('estimated_preparation_order_time', $user->providerOperatingInfo?->estimated_preparation_order_time),
                                    'adoption_support' => old('adoption_support', $user->providerOperatingInfo?->adoption_support),
                                    'bank_name' => old('bank_name', $user->providerFinancialInfo?->bank_name),
                                    'iban' => old('iban', $user->providerFinancialInfo?->iban),
                                    'account_holder_name' => old('account_holder_name', $user->providerFinancialInfo?->account_holder_name),
                                ]);
                            @endphp
                            @include('admin.manage.users._provider-fields', ['old' => $old, 'businessCategories' => $businessCategories, 'serviceTypes' => $serviceTypes, 'docsRequired' => false])
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    <x-lineone-button type="submit" variant="primary">{{ __('Update User') }}</x-lineone-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
