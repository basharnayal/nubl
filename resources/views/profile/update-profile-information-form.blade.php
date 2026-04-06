@php
    $providerProfile = $providerProfile ?? null;
    $recipientProfile = $recipientProfile ?? null;
    $profileForLogo = $providerProfile ?? $recipientProfile;
    $logoUrl = $profileForLogo?->logo_url;
@endphp

<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-navy-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    @if ($profileForLogo)
        <form method="post" action="{{ route('profile.photo.upload') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-navy-600 dark:bg-navy-900/40 sm:flex-row sm:items-start">
                <div class="shrink-0">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt=""
                            class="size-20 rounded-xl border border-slate-200 object-cover dark:border-navy-600 sm:size-24" />
                    @else
                        <div
                            class="flex size-20 items-center justify-center rounded-xl bg-primary/10 text-base font-bold text-primary dark:bg-accent/15 dark:text-accent-light sm:size-24 sm:text-lg">
                            {{ \App\Support\ProviderDisplay::initials($user->name) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                    <span class="block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Profile photo') }}</span>
                    <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('Shown in the sidebar and here. Max 2 MB — PNG, JPG, or WebP.') }}</p>
                    @if ($logoUrl)
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-navy-300">
                            <input type="checkbox" name="remove_profile_logo" value="1"
                                class="rounded border-slate-300 text-primary focus:ring-primary dark:border-navy-500"
                                @checked(old('remove_profile_logo')) />
                            {{ __('Remove photo') }}
                        </label>
                    @endif
                    <input type="file" name="profile_logo" accept="image/png,image/jpeg,image/webp"
                        class="block w-full max-w-md text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary dark:text-navy-300 dark:file:bg-accent/15 dark:file:text-accent-light" />
                    @error('profile_logo')
                        <p class="text-xs text-error">{{ $message }}</p>
                    @enderror
                    <div>
                        <x-primary-button type="submit">{{ __('Save photo') }}</x-primary-button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
