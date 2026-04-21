<x-app-layout title="{{ __('Edit User') }}: {{ $user->name }}" is-header-blur="true">
    <div class="pt-4">
        <div class="tabs mb-4 flex border-b border-slate-200 dark:border-navy-500">
            <a href="{{ route('admin.manage.users.index') }}"
                class="btn shrink-0 rounded-none border-b-2 px-4 py-2.5 font-medium {{ request()->routeIs('admin.manage.users.index') ? 'border-primary text-primary dark:border-accent dark:text-accent-light' : 'border-transparent text-slate-600 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100' }}">
                {{ __('Users') }}
            </a>
            <a href="{{ route('admin.users.pending') }}"
                class="btn shrink-0 rounded-none border-b-2 px-4 py-2.5 font-medium {{ request()->routeIs('admin.users.pending') ? 'border-primary text-primary dark:border-accent dark:text-accent-light' : 'border-transparent text-slate-600 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100' }}">
                {{ __('Pending users') }}
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('Edit User') }}: {{ $user->name }}
                    </h2>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('admin.users.application', $user) }}" class="text-sm font-medium text-slate-600 hover:text-primary dark:text-navy-300 dark:hover:text-accent-light">
                            {{ __('View application') }}
                        </a>
                        <a href="{{ route('admin.manage.users.index') }}" class="text-sm font-medium text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent">
                            {{ __('Back to List') }}
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.manage.users.update', $user) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card mt-3 px-4 py-5 sm:px-5">
                        <div class="space-y-6">
                            {{-- Account Section --}}
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-navy-300">{{ __('Account Information') }}</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="membership_type" :value="__('User Type')" />
                                        <input type="text" value="{{ ucfirst($user->membership_type) }}" disabled class="form-input form-input-lineone mt-1.5 w-full bg-slate-100 dark:bg-navy-600" />
                                        <input type="hidden" name="membership_type" value="{{ $user->membership_type }}" />
                                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('membership_type_hint') }}</p>
                                    </div>
                                    <div>
                                        <x-input-label for="name" :value="__('Name')" required />
                                        <x-text-input id="name" name="name" value="{{ old('name', $user->name) }}" class="mt-1.5 w-full" required />
                                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="email" :value="__('Email')" required />
                                        <x-text-input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-1.5 w-full" required />
                                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="phone_number" :value="__('Phone (Saudi)')" required />
                                        <x-text-input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number', ($user->phone_number ?? $user->providerProfile?->phone_number) ? \App\Support\PhoneHelper::formatForInput($user->phone_number ?? $user->providerProfile?->phone_number) : '') }}" placeholder="{{ __('Phone placeholder') }}" class="mt-1.5 w-full" maxlength="10" required />
                                        <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="password" :value="__('New Password (leave blank to keep)')" />
                                        <x-text-input id="password" name="password" type="password" class="mt-1.5 w-full" />
                                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1.5 w-full" />
                                    </div>
                                </div>

                                @include('admin.manage.users._roles-fields', [
                                    'allRoles' => $allRoles,
                                    'selected' => old('roles', $user->roles->pluck('name')->all()),
                                ])
                            </div>

                            @if($user->membership_type === 'recipient' && $user->recipientProfile)
                                <div class="space-y-4 pt-6 border-t border-slate-200 dark:border-navy-500">
                                    <h3 class="font-medium text-slate-800 dark:text-navy-100">{{ __('Recipient Details') }}</h3>
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
                                    @include('admin.manage.users._recipient-fields', ['user' => $user, 'old' => $old, 'nationalities' => $nationalities ?? config('nationalities', []), 'photosRequired' => false])
                                </div>
                            @endif

                            @if($user->membership_type === 'provider' && $user->providerProfile)
                                <div class="space-y-4 pt-6 border-t border-slate-200 dark:border-navy-500">
                                    <h3 class="font-medium text-slate-800 dark:text-navy-100">{{ __('Provider Details') }}</h3>
                                    @php
                                        $defaultHours = [];
                                        foreach (array_keys(config('provider.weekdays')) as $d) {
                                            $defaultHours[$d] = ['closed' => true];
                                        }
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
                                            'operating_hours' => old('operating_hours', $user->providerOperatingInfo?->operating_hours ?? $defaultHours),
                                            'daily_capacity' => old('daily_capacity', $user->providerOperatingInfo?->daily_capacity ?? 50),
                                            'service_type' => old('service_type', $user->providerOperatingInfo?->service_type ?? []),
                                            'estimated_preparation_order_time' => old('estimated_preparation_order_time', $user->providerOperatingInfo?->estimated_preparation_order_time),
                                            'adoption_support' => old('adoption_support', $user->providerOperatingInfo?->adoption_support),
                                            'bank_name' => old('bank_name', $user->providerFinancialInfo?->bank_name),
                                            'iban' => old('iban', $user->providerFinancialInfo?->iban),
                                            'account_holder_name' => old('account_holder_name', $user->providerFinancialInfo?->account_holder_name),
                                        ]);
                                    @endphp
                                    @include('admin.manage.users._provider-fields', ['user' => $user, 'old' => $old, 'businessCategories' => $businessCategories, 'serviceTypes' => $serviceTypes, 'docsRequired' => false, 'weekdayKeys' => $weekdayKeys ?? array_keys(config('provider.weekdays'))])
                                </div>
                            @endif
                        </div>

                        <div class="mt-8 flex justify-end border-t border-slate-200 pt-6 dark:border-navy-500">
                            <x-lineone-button type="submit" variant="primary">{{ __('Update User') }}</x-lineone-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
