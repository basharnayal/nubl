{{-- Admin view: Recipient application with ID and address photos --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.users.pending') }}" class="flex size-9 shrink-0 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-navy-300 dark:hover:bg-navy-600 dark:hover:text-navy-100" title="{{ __('Back to pending') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Recipient Application') }} — {{ $user->name }}
            </h2>
            <div class="size-9 shrink-0"></div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @php
                $profile = $user->recipientProfile;
                $kyc = $user->recipientKycDetails;
            @endphp

            {{-- Registration Device Info (compact bar) --}}
            @if($registrationLog)
                @php
                    $regIp = $registrationLog->properties['ip'] ?? null;
                    $regUa = $registrationLog->properties['user_agent'] ?? null;
                    $regDevice = null;
                    if ($regUa) {
                        $ua = strtolower($regUa);
                        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
                            $regDevice = __('Mobile');
                        } elseif (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
                            $regDevice = __('Tablet');
                        } else {
                            $regDevice = __('Desktop');
                        }
                    }
                @endphp
                <div class="flex flex-wrap items-center gap-x-6 gap-y-1 rounded-lg border border-slate-200 bg-slate-50 px-5 py-2.5 text-xs">
                    <span class="font-semibold text-slate-500">{{ __('Registration Device Info') }}</span>
                    <span class="text-slate-400">|</span>
                    <span class="text-slate-500">{{ __('IP Address') }}:</span>
                    <span class="font-mono text-slate-800">{{ $regIp ?? '—' }}</span>
                    <span class="text-slate-400">|</span>
                    <span class="text-slate-500">{{ __('Device') }}:</span>
                    @if($regDevice)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $regDevice === __('Mobile') ? 'bg-green-100 text-green-700' : ($regDevice === __('Tablet') ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700') }}">{{ $regDevice }}</span>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                    <span class="text-slate-400">|</span>
                    <span class="text-slate-500">{{ __('Browser') }}:</span>
                    <span class="text-slate-600 truncate max-w-xs" title="{{ $regUa ?? '' }}">{{ $regUa ? Str::limit($regUa, 60) : '—' }}</span>
                </div>
            @endif

            {{-- Basic Info --}}
            <section class="rounded-lg border border-slate-200 p-6 bg-white">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Personal Information') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label :value="__('Name')" /><p class="mt-1 text-gray-900">{{ $user->name }}</p></div>
                    <div><x-input-label :value="__('Email')" /><p class="mt-1 text-gray-900">{{ $user->email }}</p></div>
                    <div><x-input-label :value="__('Phone')" /><p class="mt-1 text-gray-900">{{ $user->phone_number }}</p></div>
                    <div><x-input-label :value="__('Nationality')" /><p class="mt-1 text-gray-900">{{ $profile->nationality ?? '-' }}</p></div>
                    <div><x-input-label :value="__('ID Type')" /><p class="mt-1 text-gray-900">{{ ucfirst(str_replace('_', ' ', $profile->id_type ?? '-')) }}</p></div>
                    <div><x-input-label :value="__('ID Number')" /><p class="mt-1 text-gray-900">{{ $profile->id_number ?? '-' }}</p></div>
                    <div><x-input-label :value="__('Address')" /><p class="mt-1 text-gray-900">{{ $profile->short_address ?? '-' }}</p></div>
                    @if($profile->location ?? null)
                    <div>
                        <x-input-label :value="__('GPS Location')" />
                        @php
                            $loc = is_string($profile->location) ? json_decode($profile->location, true) : null;
                        @endphp
                        @if(is_array($loc) && isset($loc['lat'], $loc['lng']))
                            <p class="mt-1 text-gray-900 font-mono text-sm">{{ $loc['lat'] }}, {{ $loc['lng'] }}</p>
                        @else
                            <p class="mt-1 text-gray-500">-</p>
                        @endif
                    </div>
                    @endif
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
                    @if($kyc->employment_status ?? null)
                    <div><x-input-label :value="__('Employment Status')" /><p class="mt-1 text-gray-900">{{ ucfirst(str_replace('_', ' ', $kyc->employment_status)) }}</p></div>
                    @endif
                    @if($kyc->situation_description ?? null)
                    <div class="sm:col-span-2"><x-input-label :value="__('Situation Description')" /><p class="mt-1 text-gray-900 whitespace-pre-wrap">{{ $kyc->situation_description }}</p></div>
                    @endif
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
