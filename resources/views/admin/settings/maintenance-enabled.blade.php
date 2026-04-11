<x-app-layout title="{{ __('Maintenance mode enabled') }}" is-header-blur="true">
    <div class="mx-auto max-w-xl pb-24 pt-6 sm:pt-8">
        <div
            class="overflow-hidden rounded-2xl border border-success/25 bg-gradient-to-b from-success/[0.08] to-white shadow-lg shadow-slate-200/80 dark:border-success/20 dark:from-success/[0.12] dark:to-navy-800 dark:shadow-none">
            <div class="border-b border-success/15 bg-success/[0.06] px-6 py-8 text-center dark:border-success/10 dark:bg-success/[0.08]">
                <div
                    class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-success/15 text-success ring-4 ring-success/10 dark:bg-success/20 dark:text-success dark:ring-success/20">
                    <i class="fa-solid fa-circle-check text-3xl" aria-hidden="true"></i>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-slate-800 dark:text-navy-50 sm:text-2xl">
                    {{ __('Maintenance mode enabled') }}
                </h1>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-600 dark:text-navy-300" dir="auto">
                    {{ __('maintenance_enabled_bypass_help') }}
                </p>
            </div>

            <div class="space-y-5 px-6 py-8">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-navy-600 dark:bg-navy-900/50">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-navy-400">
                        {{ __('Bypass URL') }}
                    </p>
                    <p
                        class="break-all rounded-lg bg-white px-3 py-2.5 font-mono text-xs leading-relaxed text-slate-800 shadow-inner dark:bg-navy-800 dark:text-navy-100 sm:text-sm"
                        dir="ltr"
                        id="maintenance-bypass-url">{{ $bypassUrl }}</p>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ $bypassUrl }}"
                            class="btn inline-flex w-full items-center justify-center gap-2 bg-success px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-success/90 focus:outline-none focus:ring-2 focus:ring-success/50 sm:w-auto sm:min-w-[200px]">
                            <i class="fa-solid fa-arrow-up-right-from-square text-sm" aria-hidden="true"></i>
                            {{ __('Open bypass link') }}
                        </a>

                        <button type="button"
                            x-data="{ copied: false }"
                            x-on:click="navigator.clipboard.writeText(document.getElementById('maintenance-bypass-url').textContent.trim()); copied = true; setTimeout(() => copied = false, 2000)"
                            class="btn inline-flex w-full items-center justify-center gap-2 border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-100 dark:hover:bg-navy-700 sm:w-auto">
                            <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            <span x-show="!copied">{{ __('Copy URL') }}</span>
                            <span x-show="copied" x-cloak class="text-success">{{ __('Copied') }}</span>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 border-t border-slate-100 pt-6 dark:border-navy-600">
                    <a href="{{ $settingsUrl }}"
                        class="inline-flex items-center gap-2 text-sm font-medium text-primary transition hover:text-primary-focus dark:text-accent-light dark:hover:text-accent">
                        <i class="fa-solid fa-arrow-left rtl:rotate-180" aria-hidden="true"></i>
                        {{ __('Back to maintenance settings') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
