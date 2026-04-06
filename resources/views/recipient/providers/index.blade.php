<x-app-layout title="{{ __('Browse Providers') }}" is-header-blur="true">
    <div class="mt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('Browse Providers') }}
                    </h2>
                </div>

                {{-- Search --}}
                <form action="{{ route('recipient.providers.index') }}" method="GET" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-navy-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="search" name="search" value="{{ request('search') }}"
                            class="form-input form-input-lineone pl-10"
                            placeholder="{{ __('Search providers by name, business name...') }}">
                    </div>
                    <x-lineone-button type="submit" variant="primary" size="sm">{{ __('Search') }}</x-lineone-button>
                </form>

                {{-- Provider list (horizontal rows) --}}
                <div class="mt-4 space-y-3 sm:space-y-4">
                    @forelse($providers as $provider)
                        @php
                            $profile = $provider->providerProfile;
                            $operating = $provider->providerOperatingInfo;
                            $rowTitle = \App\Support\ProviderDisplay::businessTitle($profile, $provider->name);
                        @endphp
                        <a href="{{ route('recipient.providers.show', $provider->id) }}"
                            class="card flex flex-col gap-3 overflow-hidden p-4 transition-colors hover:border-primary/35 sm:flex-row sm:items-center sm:gap-4 sm:p-5 dark:hover:border-accent/35">
                            <div class="flex min-w-0 flex-1 flex-row gap-3 sm:contents">
                            <x-provider-profile-avatar :profile="$profile" :title="$rowTitle" />
                            <div class="flex min-w-0 flex-1 flex-col justify-center gap-1">
                                <h5 class="text-base font-bold tracking-tight text-slate-800 dark:text-navy-100 sm:text-lg">
                                    {{ $rowTitle }}
                                </h5>
                                <p class="line-clamp-1 text-sm text-slate-600 dark:text-navy-300">
                                    {{ \App\Support\ProviderDisplay::businessCategoryLine($profile?->business_category) ?? __('General Provider') }}
                                </p>
                                @if($operating && !empty($operating->service_type))
                                    <p class="line-clamp-1 text-xs text-slate-500 dark:text-navy-400">
                                        {{ \App\Support\ProviderDisplay::serviceTypeLine($operating->service_type) }}
                                    </p>
                                @endif
                                <p class="line-clamp-1 text-xs text-slate-500 dark:text-navy-400">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ \App\Support\ProviderDisplay::cityLabel($profile?->city) }}
                                    </span>
                                </p>
                            </div>
                            </div>
                            <div class="flex shrink-0 flex-col justify-center sm:items-center">
                                <span
                                    class="btn pointer-events-none inline-flex w-full items-center justify-center bg-primary text-white sm:w-auto dark:bg-accent">
                                    {{ __('View Menu & Order') }}
                                    <svg class="ms-1 size-4 rtl:rotate-180 sm:ms-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="py-12 text-center">
                            <p class="text-slate-500 dark:text-navy-300">{{ __('No providers found matching your criteria.') }}</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $providers->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
