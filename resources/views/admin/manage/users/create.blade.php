<x-app-layout title="{{ __('Create User') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="tabs mb-4 flex border-b border-slate-200 dark:border-navy-500">
            <a href="{{ route('admin.manage.users.index') }}"
                class="btn shrink-0 rounded-none border-b-2 px-4 py-2.5 font-medium {{ request()->routeIs('admin.manage.users.index') ? 'border-primary text-primary dark:border-accent dark:text-accent-light' : 'border-transparent text-slate-600 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100' }}">
                {{ __('Users') }}
            </a>
            <a href="{{ route('admin.users.pending') }}"
                class="btn shrink-0 rounded-none border-b-2 px-4 py-2.5 font-medium {{ request()->routeIs('admin.users.pending') ? 'border-primary text-primary dark:border-accent dark:text-accent-light' : 'border-transparent text-slate-600 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100' }}">
                {{ __('Pending users') }}
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('Create User') }}
                    </h2>
                    <a href="{{ route('admin.manage.users.index') }}" class="text-sm font-medium text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent">
                        {{ __('Back to List') }}
                    </a>
                </div>

                <form method="POST" action="{{ route('admin.manage.users.store') }}" enctype="multipart/form-data"
                    x-data="{ type: '{{ old('membership_type', 'donor') }}' }"
                    @submit="if (type === 'provider') { const fs = $el.querySelector('fieldset[data-provider-fields]'); if (fs) fs.disabled = false }">
                    @csrf

                    <div class="card mt-3 px-4 py-5 sm:px-5">
                        <div class="space-y-6">
                            {{-- Account Section --}}
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-navy-300">{{ __('Account Information') }}</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="membership_type" :value="__('User Type')" required />
                                        <select id="membership_type" name="membership_type" required x-model="type"
                                            class="form-select form-select-lineone mt-1.5 w-full">
                                            <option value="donor">{{ __('Donor') }}</option>
                                            <option value="recipient">{{ __('Recipient') }}</option>
                                            <option value="provider">{{ __('Provider') }}</option>
                                        </select>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('membership_type_hint') }}</p>
                                    </div>
                                    <div>
                                        <x-input-label for="name" :value="__('Name')" required />
                                        <x-text-input id="name" name="name" value="{{ old('name') }}" class="mt-1.5 w-full" required />
                                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="email" :value="__('Email')" required />
                                        <x-text-input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1.5 w-full" required />
                                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="phone_number" :value="__('Phone (Saudi)')" required />
                                        <x-text-input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" placeholder="{{ __('Phone placeholder') }}" class="mt-1.5 w-full" maxlength="10" required />
                                        <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="password" :value="__('Password')" required />
                                        <x-text-input id="password" name="password" type="password" class="mt-1.5 w-full" required />
                                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" required />
                                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1.5 w-full" required />
                                    </div>
                                </div>

                                @include('admin.manage.users._roles-fields', [
                                    'allRoles' => $allRoles,
                                    'selected' => old('roles', [old('membership_type', 'donor')]),
                                ])
                            </div>

                            {{-- Recipient fields --}}
                            <fieldset x-show="type === 'recipient'" x-cloak x-bind:disabled="type !== 'recipient'"
                                class="space-y-4 pt-6 border-t border-slate-200 dark:border-navy-500">
                                <h3 class="font-medium text-slate-800 dark:text-navy-100">{{ __('Recipient Details') }}</h3>
                                @include('admin.manage.users._recipient-fields', ['old' => old(), 'nationalities' => $nationalities ?? config('nationalities', [])])
                            </fieldset>

                            {{-- Provider fields --}}
                            <fieldset data-provider-fields x-show="type === 'provider'" x-cloak x-bind:disabled="type !== 'provider'"
                                class="space-y-4 pt-6 border-t border-slate-200 dark:border-navy-500">
                                <h3 class="font-medium text-slate-800 dark:text-navy-100">{{ __('Provider Details') }}</h3>
                                @include('admin.manage.users._provider-fields', ['old' => old(), 'businessCategories' => $businessCategories, 'serviceTypes' => $serviceTypes])
                            </fieldset>
                        </div>

                        <div class="mt-8 flex justify-end border-t border-slate-200 pt-6 dark:border-navy-500">
                            <x-lineone-button type="submit" variant="primary">{{ __('Create User') }}</x-lineone-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
