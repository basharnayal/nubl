<x-login-layout :title="__('auth.legal.privacy')" variant="legal">
    @php
        $return = request('return');
        $safeReturn = (is_string($return) && str_starts_with($return, '/') && ! str_starts_with($return, '//'))
            ? $return
            : '/login';
    @endphp

    <div class="legal-page mx-auto w-full max-w-5xl pb-16 text-start">
        <header class="legal-page__header">
            <h1 class="legal-page__title">
                {{ __('auth.legal.privacy') }}
            </h1>
            <p class="legal-page__meta">
                {{ __('auth.legal.last_updated') }}: 2026-04-16
            </p>
        </header>

        <div class="legal-page__sheet mx-auto max-w-[min(100%,42rem)]">
            @if (app()->getLocale() === 'ar')
                <article class="legal-doc legal-doc--ar" lang="ar">
                    @include('legal.partials.privacy-body-ar')
                </article>
            @else
                <article lang="en" class="legal-doc legal-doc--en">
                    @include('legal.partials.privacy-body-en')
                </article>
            @endif
        </div>

        <div class="mx-auto mt-12 flex max-w-[min(100%,42rem)] flex-col gap-3 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ $safeReturn }}"
                class="btn h-10 w-full bg-primary px-6 font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90 sm:w-auto">
                {{ __('Back') }}
            </a>
            <a href="/login" class="text-center text-sm font-medium text-slate-600 underline hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100 sm:text-start">
                {{ __('auth.legal.back_to_login') }}
            </a>
        </div>
    </div>
</x-login-layout>
