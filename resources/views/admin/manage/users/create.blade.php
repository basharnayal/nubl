<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Create User') }}</h2>
            <a href="{{ route('admin.manage.users.index') }}" class="text-sm text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent font-medium">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.manage.users.store') }}" enctype="multipart/form-data" x-data="{ type: '{{ old('membership_type', 'donor') }}' }">
                @csrf

                <div class="bg-white rounded-lg shadow p-6 space-y-6">
                    <div>
                        <x-input-label for="membership_type" :value="__('User Type')" required />
                        <select id="membership_type" name="membership_type" required x-model="type"
                            class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary dark:focus:border-accent dark:focus:ring-accent">
                            <option value="donor">{{ __('Donor') }}</option>
                            <option value="recipient">{{ __('Recipient') }}</option>
                            <option value="provider">{{ __('Provider') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Name')" required />
                        <x-text-input id="name" name="name" value="{{ old('name') }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" required />
                        <x-text-input id="email" name="email" type="email" value="{{ old('email') }}" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone_number" :value="__('Phone (Saudi)')" required />
                        <x-text-input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" placeholder="05XXXXXXXX" class="block mt-1 w-full" maxlength="10" required />
                        <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password')" required />
                        <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" required />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required />
                    </div>

                    {{-- Recipient fields (disabled when not selected, so not submitted) --}}
                    <fieldset x-show="type === 'recipient'" x-cloak x-bind:disabled="type !== 'recipient'" class="space-y-4 pt-4 border-t border-gray-200">
                        <h3 class="font-medium text-gray-900">{{ __('Recipient Details') }}</h3>
                        @include('admin.manage.users._recipient-fields', ['old' => old(), 'nationalities' => $nationalities ?? config('nationalities', [])])
                    </fieldset>

                    {{-- Provider fields --}}
                    <fieldset x-show="type === 'provider'" x-cloak x-bind:disabled="type !== 'provider'" class="space-y-4 pt-4 border-t border-gray-200">
                        <h3 class="font-medium text-gray-900">{{ __('Provider Details') }}</h3>
                        @include('admin.manage.users._provider-fields', ['old' => old(), 'businessCategories' => $businessCategories, 'serviceTypes' => $serviceTypes])
                    </fieldset>
                </div>

                <div class="mt-6">
                    <x-lineone-button type="submit" variant="primary">{{ __('Create User') }}</x-lineone-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
