<x-app-layout>
    <div class="py-5 lg:py-6" data-module="recipient-providers" x-data="providerMenuModal()">
        <div class="max-w-7xl mx-auto">

            @if($providers->isEmpty())
                <div class="card p-8 text-center">
                    <p class="text-slate-600 dark:text-navy-200 text-lg">{{ __('No available providers at the moment.') }}</p>
                    <p class="text-slate-400 dark:text-navy-300 text-sm mt-2">{{ __('Check back later or contact support.') }}</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($providers as $provider)
                        @php
                            $profile = $provider->providerProfile;
                            $operating = $provider->providerOperatingInfo;
                        @endphp
                        <div @click="open('{{ route('recipient.providers.menu', ['provider' => $provider->id]) }}', '{{ addslashes($profile->full_name_en ?? $profile->full_name_ar ?? $provider->name) }}')"
                            @keydown.enter="open('{{ route('recipient.providers.menu', ['provider' => $provider->id]) }}', '{{ addslashes($profile->full_name_en ?? $profile->full_name_ar ?? $provider->name) }}')"
                            @keydown.space.prevent="open('{{ route('recipient.providers.menu', ['provider' => $provider->id]) }}', '{{ addslashes($profile->full_name_en ?? $profile->full_name_ar ?? $provider->name) }}')"
                            class="provider-card card cursor-pointer transition-shadow hover:shadow-md"
                            role="button"
                            tabindex="0"
                            aria-label="{{ __('View menu for') }} {{ $profile->full_name_en ?? $profile->full_name_ar ?? $provider->name }}">
                            <div class="p-5">
                                <h3 class="text-lg font-semibold text-slate-800 dark:text-navy-100 truncate">
                                    {{ $profile->full_name_en ?? $provider->name }}
                                </h3>
                                @if($profile->business_name_en ?? $profile->business_name_ar)
                                    <p class="mt-1 text-sm text-primary dark:text-accent-light font-medium">
                                        {{ $profile->business_name_en ?? $profile->business_name_ar }}
                                    </p>
                                @endif
                                @if($profile->city || $profile->region)
                                    <p class="mt-2 text-sm text-slate-500 dark:text-navy-300 flex items-center gap-1">
                                        <span aria-hidden="true">📍</span>
                                        {{ implode(', ', array_filter([$profile->city, $profile->region])) }}
                                    </p>
                                @endif
                                @if($profile->business_category && is_array($profile->business_category))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach(array_slice($profile->business_category, 0, 3) as $cat)
                                            <span class="badge bg-slate-200 text-slate-700 dark:bg-navy-600 dark:text-navy-200">
                                                {{ is_string($cat) ? $cat : ($cat['name'] ?? $cat) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($operating && $operating->daily_capacity)
                                    <p class="mt-2 text-xs text-slate-400 dark:text-navy-400">
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

    {{-- Provider menu modal (Alpine) --}}
    <template x-teleport="body">
        <div x-show="show" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4"
            role="dialog" aria-modal="true" aria-labelledby="provider-menu-modal-title"
            @keydown.escape.window="close()">
            <div x-show="show" x-transition:enter="ease-out" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60" @click="close()"></div>
            <div x-show="show" x-transition:enter="ease-out" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-lg bg-white shadow-soft dark:bg-navy-700">
                <div class="flex items-center justify-between border-b border-slate-150 p-4 dark:border-navy-600">
                    <h3 id="provider-menu-modal-title" class="text-xl font-semibold text-slate-800 dark:text-navy-100" x-text="title"></h3>
                    <button type="button" @click="close()"
                        class="btn size-8 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 md:p-5 space-y-4" x-html="body"></div>
            </div>
        </div>
    </template>
</x-app-layout>
