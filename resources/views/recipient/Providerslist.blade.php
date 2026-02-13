<x-app-layout>
    <div class="py-12">
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
                        <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow border border-gray-100 overflow-hidden">
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
                            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
                                <span class="text-sm text-gray-400">{{ __('Request support') }} ({{ __('coming soon') }})</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
