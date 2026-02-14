<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('User') }}: {{ $user->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.manage.users.edit', $user) }}" class="inline-flex items-center px-4 py-2 bg-nubl-teal-600 text-white rounded-lg hover:bg-nubl-teal-700 font-medium text-sm">{{ __('Edit') }}</a>
                <a href="{{ route('admin.manage.users.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium text-sm">{{ __('Back') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Basic Info --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Basic Info') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><dt class="text-sm text-gray-500">ID</dt><dd class="text-gray-900">{{ $user->id }}</dd></div>
                    <div><dt class="text-sm text-gray-500">{{ __('Name') }}</dt><dd class="text-gray-900">{{ $user->name }}</dd></div>
                    <div><dt class="text-sm text-gray-500">{{ __('Email') }}</dt><dd class="text-gray-900">{{ $user->email }}</dd></div>
                    <div><dt class="text-sm text-gray-500">{{ __('Phone') }}</dt><dd class="text-gray-900">{{ $user->phone_number ?? $user->providerProfile?->phone_number ?? '-' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">{{ __('Role') }}</dt><dd class="text-gray-900">{{ $user->roles->pluck('name')->implode(', ') ?: '-' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">{{ __('Membership') }}</dt><dd class="text-gray-900">{{ ucfirst($user->membership_type ?? '-') }}</dd></div>
                    <div><dt class="text-sm text-gray-500">{{ __('Status') }}</dt>
                        <dd>
                            @if($user->is_active)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('Active') }}</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">{{ __('Inactive') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-sm text-gray-500">{{ __('Registration Status') }}</dt><dd class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $user->status ?? 'active')) }}</dd></div>
                    <div><dt class="text-sm text-gray-500">{{ __('Created') }}</dt><dd class="text-gray-900">{{ $user->created_at->format('Y-m-d H:i') }}</dd></div>
                </dl>
            </div>

            {{-- Recipient: Full Info --}}
            @if($user->recipientProfile)
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Personal Information') }}</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><dt class="text-sm text-gray-500">{{ __('Name') }}</dt><dd class="text-gray-900">{{ $user->name }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Email') }}</dt><dd class="text-gray-900">{{ $user->email }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Phone') }}</dt><dd class="text-gray-900">{{ $user->phone_number ?? '-' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Nationality') }}</dt><dd class="text-gray-900">{{ $user->recipientProfile->nationality ?? '-' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('ID Type') }}</dt><dd class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $user->recipientProfile->id_type ?? '-')) }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Address') }}</dt><dd class="text-gray-900">{{ $user->recipientProfile->short_address ?? '-' }}</dd></div>
                    </dl>
                </div>
                @if($user->recipientKycDetails)
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('KYC Details') }}</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><dt class="text-sm text-gray-500">{{ __('Income Band') }}</dt><dd class="text-gray-900">{{ $user->recipientKycDetails->income_band ?? '-' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Household Size') }}</dt><dd class="text-gray-900">{{ $user->recipientKycDetails->household_size ?? '-' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Marital Status') }}</dt><dd class="text-gray-900">{{ ucfirst($user->recipientKycDetails->marital_status ?? '-') }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Student') }}</dt><dd class="text-gray-900">{{ $user->recipientKycDetails->is_student ? __('Yes') : __('No') }}</dd></div>
                    </dl>
                </div>
                @endif
                @if(($user->recipientProfile->id_photo_path ?? null) || ($user->recipientKycDetails?->address_confirmation ?? null))
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Documents') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        @if($user->recipientProfile->id_photo_path ?? null)
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('ID Photo') }}</dt>
                            <dd>
                                <a href="{{ route('admin.users.file', [$user, 'id_photo']) }}" target="_blank" class="block mt-2">
                                    @php $ext = strtolower(pathinfo($user->recipientProfile->id_photo_path, PATHINFO_EXTENSION)); @endphp
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                        <img src="{{ route('admin.users.file', [$user, 'id_photo']) }}" alt="ID Photo" class="max-w-full max-h-64 rounded-lg border object-contain" />
                                    @else
                                        <span class="text-nubl-teal-600 hover:underline">{{ __('ID Photo') }}</span>
                                    @endif
                                </a>
                            </dd>
                        </div>
                        @endif
                        @if($user->recipientKycDetails?->address_confirmation ?? null)
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('Address Confirmation') }}</dt>
                            <dd>
                                <a href="{{ route('admin.users.file', [$user, 'address_confirmation']) }}" target="_blank" class="block mt-2">
                                    @php $ext = strtolower(pathinfo($user->recipientKycDetails->address_confirmation, PATHINFO_EXTENSION)); @endphp
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                        <img src="{{ route('admin.users.file', [$user, 'address_confirmation']) }}" alt="Address Confirmation" class="max-w-full max-h-64 rounded-lg border object-contain" />
                                    @else
                                        <span class="text-nubl-teal-600 hover:underline">{{ __('Address Confirmation') }}</span>
                                    @endif
                                </a>
                            </dd>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            @endif

            {{-- Provider: Full Info (Personal & Business, Operating, Financial, Documents) --}}
            @if($user->providerProfile)
                @php
                    $profile = $user->providerProfile;
                    $operating = $user->providerOperatingInfo;
                    $financial = $user->providerFinancialInfo;
                    $documents = $user->providerDocuments;
                    $weekdays = config('provider.weekdays');
                    $adoptionLabels = config('provider.adoption_support_options');
                @endphp

                <section class="rounded-lg shadow p-6 bg-white">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Personal & Business Information') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><x-input-label :value="__('Full Name (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->full_name_ar ?? '-' }}</p></div>
                        <div><x-input-label :value="__('Full Name (English)')" /><p class="mt-1 text-gray-900">{{ $profile->full_name_en ?? '-' }}</p></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><x-input-label :value="__('Phone')" /><p class="mt-1 text-gray-900">{{ $profile->phone_number ?? $user->phone_number ?? '-' }}</p></div>
                        <div><x-input-label :value="__('Email')" /><p class="mt-1 text-gray-900">{{ $profile->email ?? $user->email ?? '-' }}</p></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><x-input-label :value="__('Business Name (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->business_name_ar ?? '-' }}</p></div>
                        <div><x-input-label :value="__('Business Name (English)')" /><p class="mt-1 text-gray-900">{{ $profile->business_name_en ?? '-' }}</p></div>
                    </div>
                    <div class="mt-4"><x-input-label :value="__('Unified Number')" /><p class="mt-1 text-gray-900">{{ $profile->unified_number ?? '-' }}</p></div>
                    <div class="mt-4"><x-input-label :value="__('Business Category')" /><p class="mt-1 text-gray-900">{{ implode(', ', array_map(fn($c) => ucfirst(str_replace('_', ' ', $c)), $profile->business_category ?? [])) ?: '-' }}</p></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><x-input-label :value="__('Address (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->address_ar ?? '-' }}</p></div>
                        <div><x-input-label :value="__('Address (English)')" /><p class="mt-1 text-gray-900">{{ $profile->address_en ?? '-' }}</p></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><x-input-label :value="__('City')" /><p class="mt-1 text-gray-900">{{ config('provider.cities')[$profile->city] ?? $profile->city ?? '-' }}</p></div>
                        <div><x-input-label :value="__('Region')" /><p class="mt-1 text-gray-900">{{ config('provider.regions')[$profile->region] ?? $profile->region ?? '-' }}</p></div>
                    </div>
                    @if($profile->location)<div class="mt-4"><x-input-label :value="__('Location')" /><p class="mt-1 text-gray-900">{{ $profile->location }}</p></div>@endif
                </section>

                @if($operating)
                <section class="rounded-lg shadow p-6 bg-white">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Operating Information') }}</h2>
                    <div class="mb-4">
                        <x-input-label :value="__('Operating Hours')" />
                        <div class="mt-2 space-y-1">
                            @foreach($operating->operating_hours ?? [] as $day => $data)
                                <p class="text-sm text-gray-700">
                                    {{ __($weekdays[$day] ?? $day) }}:
                                    @if($data['closed'] ?? true)
                                        {{ __('Closed') }}
                                    @else
                                        {{ $data['open'] ?? '' }} - {{ $data['close'] ?? '' }}
                                    @endif
                                </p>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><x-input-label :value="__('Daily Capacity')" /><p class="mt-1 text-gray-900">{{ $operating->daily_capacity ?? '-' }}</p></div>
                        <div><x-input-label :value="__('Estimated Preparation Time')" /><p class="mt-1 text-gray-900">{{ $operating->estimated_preparation_order_time ?? '-' }}</p></div>
                    </div>
                    <div class="mt-4"><x-input-label :value="__('Service Type')" /><p class="mt-1 text-gray-900">{{ implode(', ', array_map(fn($s) => ucfirst(str_replace('_', ' ', $s)), $operating->service_type ?? [])) ?: '-' }}</p></div>
                    @if($operating->adoption_support ?? null)
                    <div class="mt-4"><x-input-label :value="__('Adopt orders as community support')" /><p class="mt-1 text-gray-900">{{ $adoptionLabels[$operating->adoption_support] ?? $operating->adoption_support }}</p></div>
                    @endif
                </section>
                @endif

                @if($financial)
                <section class="rounded-lg shadow p-6 bg-white">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Financial Information') }}</h2>
                    <div class="space-y-4">
                        <div><x-input-label :value="__('Bank Name')" /><p class="mt-1 text-gray-900">{{ $financial->bank_name ?? '-' }}</p></div>
                        <div><x-input-label :value="__('IBAN')" /><p class="mt-1 text-gray-900">{{ $financial->iban ?? '-' }}</p></div>
                        <div><x-input-label :value="__('Account Holder')" /><p class="mt-1 text-gray-900">{{ $financial->account_holder_name ?? '-' }}</p></div>
                    </div>
                </section>
                @endif

                @if($documents)
                <section class="rounded-lg shadow p-6 bg-white">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Documents') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        @if($documents->business_license_path ?? null)
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('Business License') }}</dt>
                            <dd>
                                @php $ext = strtolower(pathinfo($documents->business_license_path, PATHINFO_EXTENSION)); @endphp
                                <a href="{{ route('admin.users.file', [$user, 'business_license']) }}" target="_blank" class="block mt-2">
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                        <img src="{{ route('admin.users.file', [$user, 'business_license']) }}" alt="Business License" class="max-w-full max-h-64 rounded-lg border object-contain" />
                                    @else
                                        <span class="inline-flex items-center px-3 py-2 bg-nubl-teal-100 text-nubl-teal-700 rounded-lg text-sm font-medium hover:bg-nubl-teal-200">{{ __('Business License') }}</span>
                                    @endif
                                </a>
                            </dd>
                        </div>
                        @endif
                        @if($documents->id_or_iqama_path ?? null)
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('ID / Iqama') }}</dt>
                            <dd>
                                @php $ext = strtolower(pathinfo($documents->id_or_iqama_path, PATHINFO_EXTENSION)); @endphp
                                <a href="{{ route('admin.users.file', [$user, 'id_or_iqama']) }}" target="_blank" class="block mt-2">
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                        <img src="{{ route('admin.users.file', [$user, 'id_or_iqama']) }}" alt="ID / Iqama" class="max-w-full max-h-64 rounded-lg border object-contain" />
                                    @else
                                        <span class="inline-flex items-center px-3 py-2 bg-nubl-teal-100 text-nubl-teal-700 rounded-lg text-sm font-medium hover:bg-nubl-teal-200">{{ __('ID / Iqama') }}</span>
                                    @endif
                                </a>
                            </dd>
                        </div>
                        @endif
                    </div>
                </section>
                @endif
            @endif

            {{-- Actions --}}
            <div class="flex gap-3">
                @if($user->is_active)
                    <form method="POST" action="{{ route('admin.manage.users.deactivate', $user) }}" onsubmit="return confirm('{{ __('Deactivate this user?') }}')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium">{{ __('Deactivate') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.manage.users.reactivate', $user) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">{{ __('Reactivate') }}</button>
                    </form>
                @endif
                @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.manage.users.destroy', $user) }}" onsubmit="return confirm('{{ __('Permanently delete this user?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">{{ __('Delete') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
