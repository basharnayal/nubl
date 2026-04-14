<x-app-layout title="{{ __('admin.nav.provider_menus') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                {{ __('admin.nav.provider_menus') }}
            </h2>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                <form method="GET" action="{{ route('admin.menus.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search...') }}"
                        class="form-input form-input-lineone min-w-32 sm:w-36">

                    <select name="category" class="form-select form-select-lineone w-auto min-w-24">
                        <option value="">{{ __('All Categories') }}</option>
                        @foreach($allCategories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ __($cat) }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select form-select-lineone w-auto min-w-24">
                        <option value="">{{ __('All statuses') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}
                        </option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                            {{ __('Inactive') }}
                        </option>
                    </select>

                    <button type="submit"
                        class="btn size-9 rounded-full p-0 text-primary hover:bg-primary/10 focus:bg-primary/10 dark:text-accent dark:hover:bg-accent/10 dark:focus:bg-accent/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    @if(request()->anyFilled(['search', 'category', 'status']))
                        <a href="{{ route('admin.menus.index') }}"
                            class="btn size-9 rounded-full p-0 text-error hover:bg-error/10 focus:bg-error/10"
                            title="{{ __('Reset') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card mt-4 shadow-soft">
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <table class="is-hoverable w-full text-left rtl:text-right">
                    <thead>
                        <tr>
                            <th
                                class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                #</th>
                            <th
                                class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                {{ __('Avatar') }}
                            </th>
                            <th
                                class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                {{ __('Name') }}
                            </th>
                            <th
                                class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                {{ __('Category') }}
                            </th>
                            <th
                                class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                {{ __('Status') }}
                            </th>
                            <th
                                class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5 text-center">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($providers as $provider)
                            <tr
                                class="border-y border-transparent border-b-slate-200 transition-colors hover:bg-slate-50 dark:border-b-navy-500 dark:hover:bg-navy-600">
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $provider->id }}</td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    <x-user-avatar :user="$provider" size-class="size-10" />
                                </td>
                                <td class="px-4 py-3 sm:px-5">
                                    <div class="font-medium text-slate-700 dark:text-navy-100">{{ $provider->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-navy-300">
                                        {{ $provider->phone_number || $provider->providerProfile?->phone_number ? \App\Helpers\PhoneHelper::formatForDisplay($provider->phone_number ?? $provider->providerProfile?->phone_number) : '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 sm:px-5">
                                    <div class="flex flex-wrap gap-1">
                                        @php
                                            $categories = $provider->providerProfile?->business_category ?? [];
                                            if (is_string($categories))
                                                $categories = [$categories];
                                        @endphp
                                        @foreach($categories as $cat)
                                            <span
                                                class="badge rounded-full bg-slate-150 py-1 px-2 text-slate-800 dark:bg-navy-500 dark:text-navy-100 text-[10px]">{{ __($cat) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    @if($provider->is_active)
                                        <span
                                            class="badge rounded-full bg-success/10 text-success dark:bg-success/15">{{ __('Active') }}</span>
                                    @else
                                        <span
                                            class="badge rounded-full bg-error/10 text-error dark:bg-error/15">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-center">
                                    <a href="{{ route('admin.menus.show', $provider) }}"
                                        class="btn size-9 rounded-full p-0 text-primary hover:bg-primary/10 focus:bg-primary/10 dark:text-accent-light dark:hover:bg-accent-light/10 dark:focus:bg-accent-light/10"
                                        title="{{ __('Review Items') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">
                                    {{ __('No providers found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($providers->hasPages())
                <div
                    class="flex flex-col justify-between space-y-4 border-t border-slate-200 px-4 py-4 dark:border-navy-500 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
                    <div class="text-xs-plus text-slate-500 dark:text-navy-300">
                        {{ __('Showing') }} {{ $providers->firstItem() ?? 0 }} - {{ $providers->lastItem() ?? 0 }}
                        {{ __('of') }} {{ $providers->total() }} {{ __('entries') }}
                    </div>
                    <div
                        class="[&_.pagination]:flex [&_.pagination]:gap-1 [&_.pagination]:flex-wrap [&_.pagination_li]:rounded-lg [&_.pagination_li]:bg-slate-150 [&_.pagination_li]:dark:bg-navy-500 [&_.pagination_a]:flex [&_.pagination_a]:h-8 [&_.pagination_a]:min-w-[2rem] [&_.pagination_a]:items-center [&_.pagination_a]:justify-center [&_.pagination_a]:rounded-lg [&_.pagination_a]:px-3 [&_.pagination_a]:leading-tight [&_.pagination_a]:transition-colors [&_.pagination_a:hover]:bg-slate-300 [&_.pagination_a]:dark:hover:bg-navy-450 [&_.pagination_.active_a]:bg-primary [&_.pagination_.active_a]:text-white [&_.pagination_.active_a]:dark:bg-accent [&_.pagination_.disabled]:opacity-50">
                        {{ $providers->withQueryString()->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>