<x-app-layout>
    <div class="py-12" data-module="recipient-providers">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if($providers->isEmpty())
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <p class="text-gray-500 text-lg">{{ __('No available providers at the moment.') }}</p>
                    <p class="text-gray-400 text-sm mt-2">{{ __('Check back later or contact support.') }}</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($providers as $provider)
                        @php
                            $profile = $provider->providerProfile;
                            $operating = $provider->providerOperatingInfo;
                        @endphp
                        <div class="provider-card bg-white rounded-lg shadow hover:shadow-md transition-shadow border border-gray-100 overflow-hidden cursor-pointer"
                            role="button"
                            tabindex="0"
                            data-menu-url="{{ route('recipient.providers.menu', ['provider' => $provider->id]) }}"
                            data-provider-name="{{ $profile->full_name_en ?? $profile->full_name_ar ?? $provider->name }}"
                            aria-label="{{ __('View menu for') }} {{ $profile->full_name_en ?? $profile->full_name_ar ?? $provider->name }}">
                            <div class="p-5">
                                <h3 class="text-lg font-semibold text-gray-900 truncate" title="{{ $profile->full_name_en ?? $provider->name }}">
                                    {{ $profile->full_name_en ?? $provider->name }}
                                </h3>
                                @if($profile->business_name_en ?? $profile->business_name_ar)
                                    <p class="mt-1 text-sm text-nubl-teal-600 font-medium">
                                        {{ $profile->business_name_en ?? $profile->business_name_ar }}
                                    </p>
                                @endif
                                @if($profile->city || $profile->region)
                                    <p class="mt-2 text-sm text-gray-500 flex items-center gap-1">
                                        <span aria-hidden="true">📍</span>
                                        {{ implode(', ', array_filter([$profile->city, $profile->region])) }}
                                    </p>
                                @endif
                                @if($profile->business_category && is_array($profile->business_category))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach(array_slice($profile->business_category, 0, 3) as $cat)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                                {{ is_string($cat) ? $cat : ($cat['name'] ?? $cat) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($operating && $operating->daily_capacity)
                                    <p class="mt-2 text-xs text-gray-400">
                                        {{ __('Capacity') }}: {{ $operating->daily_capacity }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Provider menu modal (Flowbite); content loaded via fetch in recipient-providers.js --}}
    <div id="provider-menu-modal" tabindex="-1" aria-hidden="true" class="hidden flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 id="provider-menu-modal-title" class="text-xl font-semibold text-gray-900">{{ __('Menu') }}</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="provider-menu-modal">
                        <span class="sr-only">{{ __('Close modal') }}</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
                <div id="provider-menu-modal-body" class="p-4 md:p-5 space-y-4">
                    <p class="text-gray-500">{{ __('Loading…') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>