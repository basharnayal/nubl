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

                {{-- Provider Grid --}}
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($providers as $provider)
                        <div class="card p-5 transition-colors hover:border-primary/30 dark:hover:border-accent/30">
                            <h5 class="mb-2 text-lg font-bold tracking-tight text-slate-800 dark:text-navy-100">
                                {{ $provider->providerProfile->business_name_en ?? $provider->name }}
                            </h5>
                            <p class="mb-3 truncate text-sm text-slate-600 dark:text-navy-300">
                                {{ $provider->providerProfile->business_category ? implode(', ', $provider->providerProfile->business_category) : __('General Provider') }}
                            </p>

                            <div class="mb-4 flex items-center text-sm text-slate-500 dark:text-navy-400">
                                <svg class="mr-1 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $provider->providerProfile->city ?? __('Unknown City') }}
                            </div>

                            <a href="{{ route('recipient.providers.show', $provider->id) }}"
                                class="btn inline-flex items-center bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus">
                                {{ __('View Menu & Order') }}
                                <svg class="ml-2 size-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </a>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center">
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
