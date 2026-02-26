{{-- Admin view: Recipient application with ID and address photos --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Recipient Application') }} — {{ $user->name }}
            </h2>
            <a href="{{ route('admin.users.pending') }}" class="text-sm text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent font-medium">{{ __('Back to pending') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @php
                $profile = $user->recipientProfile;
                $kyc = $user->recipientKycDetails;
            @endphp

            {{-- Basic Info --}}
            <section class="rounded-lg border border-slate-200 p-6 bg-white">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Personal Information') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label :value="__('Name')" /><p class="mt-1 text-gray-900">{{ $user->name }}</p></div>
                    <div><x-input-label :value="__('Email')" /><p class="mt-1 text-gray-900">{{ $user->email }}</p></div>
                    <div><x-input-label :value="__('Phone')" /><p class="mt-1 text-gray-900">{{ $user->phone_number }}</p></div>
                    <div><x-input-label :value="__('Nationality')" /><p class="mt-1 text-gray-900">{{ $profile->nationality ?? '-' }}</p></div>
                    <div><x-input-label :value="__('ID Type')" /><p class="mt-1 text-gray-900">{{ ucfirst(str_replace('_', ' ', $profile->id_type ?? '-')) }}</p></div>
                    <div><x-input-label :value="__('Address')" /><p class="mt-1 text-gray-900">{{ $profile->short_address ?? '-' }}</p></div>
                </div>
            </section>

            {{-- KYC Details --}}
            @if($kyc)
            <section class="rounded-lg border border-slate-200 p-6 bg-white">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('KYC Details') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label :value="__('Income Band')" /><p class="mt-1 text-gray-900">{{ $kyc->income_band ?? '-' }}</p></div>
                    <div><x-input-label :value="__('Household Size')" /><p class="mt-1 text-gray-900">{{ $kyc->household_size ?? '-' }}</p></div>
                    <div><x-input-label :value="__('Marital Status')" /><p class="mt-1 text-gray-900">{{ ucfirst($kyc->marital_status ?? '-') }}</p></div>
                    <div><x-input-label :value="__('Student')" /><p class="mt-1 text-gray-900">{{ $kyc->is_student ? __('Yes') : __('No') }}</p></div>
                </div>
            </section>
            @endif

            {{-- Documents / Photos --}}
            <section class="rounded-lg border border-slate-200 p-6 bg-white">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Documents') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @if($profile->id_photo_path ?? null)
                    <div>
                        <x-input-label :value="__('ID Photo')" />
                        <a href="{{ route('admin.users.file', [$user, 'id_photo']) }}" target="_blank" class="block mt-2">
                            <img src="{{ route('admin.users.file', [$user, 'id_photo']) }}" alt="ID Photo" class="max-w-full max-h-64 rounded-lg border object-contain" />
                        </a>
                    </div>
                    @endif
                    @if($kyc && ($kyc->address_confirmation ?? null))
                    <div>
                        <x-input-label :value="__('Address Confirmation')" />
                        <a href="{{ route('admin.users.file', [$user, 'address_confirmation']) }}" target="_blank" class="block mt-2">
                            <img src="{{ route('admin.users.file', [$user, 'address_confirmation']) }}" alt="Address Confirmation" class="max-w-full max-h-64 rounded-lg border object-contain" />
                        </a>
                    </div>
                    @endif
                </div>
            </section>

            {{-- Actions --}}
            @if(in_array($user->status, [\App\Models\User::STATUS_PENDING_APPROVAL, \App\Models\User::STATUS_REJECTED]))
            <div class="flex gap-3">
                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                    @csrf
                    <x-lineone-button type="submit" variant="success">{{ __('Approve') }}</x-lineone-button>
                </form>
                @if($user->status === \App\Models\User::STATUS_PENDING_APPROVAL)
                <a href="{{ route('admin.users.reject.form', $user) }}" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">{{ __('Reject') }}</a>
                @endif
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
