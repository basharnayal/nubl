<x-app-layout title="{{ __('Profile') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="max-w-xl">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    {{ __('Profile') }}
                </h2>

                <div class="card mt-3 p-6">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="card mt-4 p-6">
                    @include('profile.partials.update-password-form')
                </div>

                <div class="card mt-4 p-6">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
