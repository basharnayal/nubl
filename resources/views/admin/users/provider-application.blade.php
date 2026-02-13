{{-- Admin view: Provider application with documents/images --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Provider Application') }} — {{ $user->name }}
            </h2>
            <a href="{{ route('admin.users.pending') }}" class="text-sm text-nubl-teal-600 hover:text-nubl-teal-700 font-medium">{{ __('Back to pending') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @php
                $profile = $providerData['profile'] ?? null;
                $operating = $providerData['operating'] ?? null;
                $financial = $providerData['financial'] ?? null;
                $documents = $providerData['documents'] ?? null;
                $weekdays = config('provider.weekdays');
                $adoptionLabels = config('provider.adoption_support_options');
            @endphp

            {{-- Personal & Business --}}
            <section class="rounded-lg border border-slate-200 p-6 bg-white">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Personal & Business Information') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label :value="__('Full Name (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->full_name_ar }}</p></div>
                    <div><x-input-label :value="__('Full Name (English)')" /><p class="mt-1 text-gray-900">{{ $profile->full_name_en }}</p></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div><x-input-label :value="__('Phone')" /><p class="mt-1 text-gray-900">{{ $profile->phone_number }}</p></div>
                    <div><x-input-label :value="__('Email')" /><p class="mt-1 text-gray-900">{{ $user->email }}</p></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div><x-input-label :value="__('Business Name (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->business_name_ar }}</p></div>
                    <div><x-input-label :value="__('Business Name (English)')" /><p class="mt-1 text-gray-900">{{ $profile->business_name_en }}</p></div>
                </div>
                <div class="mt-4"><x-input-label :value="__('Unified Number')" /><p class="mt-1 text-gray-900">{{ $profile->unified_number }}</p></div>
                <div class="mt-4"><x-input-label :value="__('Business Category')" /><p class="mt-1 text-gray-900">{{ implode(', ', array_map(fn($c) => ucfirst(str_replace('_', ' ', $c)), $profile->business_category ?? [])) }}</p></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div><x-input-label :value="__('Address (Arabic)')" /><p class="mt-1 text-gray-900">{{ $profile->address_ar }}</p></div>
                    <div><x-input-label :value="__('Address (English)')" /><p class="mt-1 text-gray-900">{{ $profile->address_en }}</p></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div><x-input-label :value="__('City')" /><p class="mt-1 text-gray-900">{{ config('provider.cities')[$profile->city] ?? $profile->city }}</p></div>
                    <div><x-input-label :value="__('Region')" /><p class="mt-1 text-gray-900">{{ config('provider.regions')[$profile->region] ?? $profile->region }}</p></div>
                </div>
                @if($profile->location)<div class="mt-4"><x-input-label :value="__('Location')" /><p class="mt-1 text-gray-900">{{ $profile->location }}</p></div>@endif
            </section>

            {{-- Operating Information --}}
            <section class="rounded-lg border border-slate-200 p-6 bg-white">
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
                    <div><x-input-label :value="__('Daily Capacity')" /><p class="mt-1 text-gray-900">{{ $operating->daily_capacity }}</p></div>
                    <div><x-input-label :value="__('Estimated Preparation Time')" /><p class="mt-1 text-gray-900">{{ $operating->estimated_preparation_order_time }}</p></div>
                </div>
                <div class="mt-4"><x-input-label :value="__('Service Type')" /><p class="mt-1 text-gray-900">{{ implode(', ', array_map(fn($s) => ucfirst(str_replace('_', ' ', $s)), $operating->service_type ?? [])) }}</p></div>
                @if($operating->adoption_support ?? null)
                <div class="mt-4"><x-input-label :value="__('Adopt orders as community support')" /><p class="mt-1 text-gray-900">{{ $adoptionLabels[$operating->adoption_support] ?? $operating->adoption_support }}</p></div>
                @endif
            </section>

            {{-- Financial Information --}}
            <section class="rounded-lg border border-slate-200 p-6 bg-white">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Financial Information') }}</h2>
                <div class="space-y-4">
                    <div><x-input-label :value="__('Bank Name')" /><p class="mt-1 text-gray-900">{{ $financial->bank_name }}</p></div>
                    <div><x-input-label :value="__('IBAN')" /><p class="mt-1 text-gray-900">{{ $financial->iban }}</p></div>
                    <div><x-input-label :value="__('Account Holder')" /><p class="mt-1 text-gray-900">{{ $financial->account_holder_name }}</p></div>
                </div>
            </section>

            {{-- Documents with images --}}
            @if($documents)
            <section class="rounded-lg border border-slate-200 p-6 bg-white">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Documents') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-input-label :value="__('Business License')" />
                        @php $ext = strtolower(pathinfo($documents->business_license_path, PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                            <a href="{{ route('admin.users.file', [$user, 'business_license']) }}" target="_blank" class="block mt-2">
                                <img src="{{ route('admin.users.file', [$user, 'business_license']) }}" alt="Business License" class="max-w-full max-h-64 rounded-lg border object-contain" />
                            </a>
                        @else
                            <a href="{{ route('admin.users.file', [$user, 'business_license']) }}" target="_blank" class="mt-2 inline-flex items-center px-3 py-2 bg-nubl-teal-100 text-nubl-teal-700 rounded-lg text-sm font-medium hover:bg-nubl-teal-200">
                                {{ __('View / Download') }}
                            </a>
                        @endif
                    </div>
                    <div>
                        <x-input-label :value="__('ID / Iqama')" />
                        @php $ext = strtolower(pathinfo($documents->id_or_iqama_path, PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                            <a href="{{ route('admin.users.file', [$user, 'id_or_iqama']) }}" target="_blank" class="block mt-2">
                                <img src="{{ route('admin.users.file', [$user, 'id_or_iqama']) }}" alt="ID / Iqama" class="max-w-full max-h-64 rounded-lg border object-contain" />
                            </a>
                        @else
                            <a href="{{ route('admin.users.file', [$user, 'id_or_iqama']) }}" target="_blank" class="mt-2 inline-flex items-center px-3 py-2 bg-nubl-teal-100 text-nubl-teal-700 rounded-lg text-sm font-medium hover:bg-nubl-teal-200">
                                {{ __('View / Download') }}
                            </a>
                        @endif
                    </div>
                </div>
            </section>
            @endif

            {{-- Actions --}}
            @if(in_array($user->status, [\App\Models\User::STATUS_PENDING_APPROVAL, \App\Models\User::STATUS_REJECTED]))
            <div class="flex gap-3">
                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">{{ __('Approve') }}</button>
                </form>
                @if($user->status === \App\Models\User::STATUS_PENDING_APPROVAL)
                <a href="{{ route('admin.users.reject.form', $user) }}" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">{{ __('Reject') }}</a>
                @endif
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
