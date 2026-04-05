<x-app-layout title="{{ __('Profile') }}" is-header-blur="true">
    <div class="mt-4 pb-8">
        <div class="mx-auto max-w-3xl space-y-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-navy-50">{{ __('Profile') }}</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">{{ __('Manage your account and how your business appears on NUBL.') }}</p>
            </div>

            @if (session('status') === 'profile-updated')
                <x-lineone-alert type="success" dismissible>{{ __('Account details saved.') }}</x-lineone-alert>
            @endif
            @if (session('status') === 'business-profile-updated')
                <x-lineone-alert type="success" dismissible>{{ __('Business profile saved.') }}</x-lineone-alert>
            @endif
            @if (session('status') === 'financial-profile-updated')
                <x-lineone-alert type="success" dismissible>{{ __('Payment details saved.') }}</x-lineone-alert>
            @endif

            {{-- Account: name, email, password (all roles) --}}
            <div class="card overflow-hidden rounded-2xl border border-slate-200/90 p-6 shadow-sm dark:border-navy-600 dark:bg-navy-800/40 sm:p-8">
                @include('profile.update-profile-information-form')
            </div>

            @if ($user->hasRole('provider') && $providerProfile)
                {{-- Provider: business / legal storefront info --}}
                <div
                    class="card overflow-hidden rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/[0.06] via-white to-white p-6 shadow-sm dark:border-accent/20 dark:from-navy-800/80 dark:via-navy-800/50 dark:to-navy-800/40 sm:p-8">
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light">
                            <i class="fa-solid fa-store text-xl" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0 flex-1 space-y-1">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-navy-100">{{ __('Business profile') }}</h2>
                            <p class="text-sm leading-relaxed text-slate-600 dark:text-navy-300">
                                {{ __('Names, phone, category, and address shown to recipients and used for your storefront.') }}
                            </p>
                        </div>
                    </div>
                    @include('profile.partials.provider-business-form', ['providerProfile' => $providerProfile])
                </div>

                @if ($providerFinancial)
                    <div
                        class="card overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-navy-600 dark:bg-navy-800/40 sm:p-8">
                        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-200">
                                <i class="fa-solid fa-building-columns text-xl" aria-hidden="true"></i>
                            </div>
                            <div class="min-w-0 flex-1 space-y-1">
                                <h2 class="text-lg font-bold text-slate-900 dark:text-navy-100">{{ __('Payment & bank details') }}</h2>
                                <p class="text-sm leading-relaxed text-slate-600 dark:text-navy-300">
                                    {{ __('Same information as registration: bank name, IBAN, and account holder for payouts.') }}
                                </p>
                            </div>
                        </div>
                        @include('profile.partials.provider-financial-form', ['providerFinancial' => $providerFinancial])
                    </div>
                @endif

                {{-- Link to operating hours / capacity (separate route) --}}
                <div
                    class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-navy-600 dark:bg-navy-800/40 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div class="flex gap-4">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-200">
                            <i class="fa-solid fa-clock text-xl" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900 dark:text-navy-100">{{ __('Hours, capacity & pickup') }}</h2>
                            <p class="mt-0.5 text-sm text-slate-600 dark:text-navy-400">
                                {{ __('Set weekly hours, order capacity, service types, and pickup notes.') }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('provider.profile.edit') }}"
                        class="btn inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-primary/30 bg-primary/10 px-5 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary/15 dark:border-accent/30 dark:bg-accent/10 dark:text-accent-light dark:hover:bg-accent/20">
                        <span>{{ __('Edit operating profile') }}</span>
                        <i class="fa-solid fa-arrow-right rtl:rotate-180" aria-hidden="true"></i>
                    </a>
                </div>
            @endif

            <div class="card rounded-2xl border border-slate-200/90 p-6 dark:border-navy-600 dark:bg-navy-800/40 sm:p-8">
                @include('profile.update-password-form')
            </div>

            <div class="card rounded-2xl border border-slate-200/90 p-6 dark:border-navy-600 dark:bg-navy-800/40 sm:p-8">
                @include('profile.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
