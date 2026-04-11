<x-app-layout title="{{ __('Maintenance mode') }}" is-header-blur="true">
    <div class="mx-auto max-w-3xl space-y-6 pb-24 pt-4 lg:pb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <a href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-primary dark:text-navy-300 dark:hover:text-accent">
                <i class="fa-solid fa-arrow-left rtl:rotate-180" aria-hidden="true"></i>
                {{ __('Back to admin dashboard') }}
            </a>
        </div>

        <div class="mb-2 flex items-start gap-4">
            <div
                class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent">
                <i class="fa-solid fa-screwdriver-wrench text-xl" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1 space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-navy-50">
                    {{ __('Maintenance mode') }}
                </h1>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-navy-300" dir="auto">
                    {{ __('Uses Laravel maintenance mode (artisan down / up). Visitors see the default maintenance response until they use the bypass link, or you turn maintenance off.') }}
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success dark:border-success/40 dark:bg-success/15 dark:text-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="rounded-lg border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning dark:border-warning/40 dark:bg-warning/15">
                {{ session('warning') }}
            </div>
        @endif
        @if (session('info'))
            <div class="rounded-lg border border-info/30 bg-info/10 px-4 py-3 text-sm text-info dark:border-info/40 dark:bg-info/15">
                {{ session('info') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-error/30 bg-error/10 px-4 py-3 text-sm text-error dark:border-error/40 dark:bg-error/15">
                {{ session('error') }}
            </div>
        @endif

        <div
            class="card rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-navy-600 dark:bg-navy-800/40 sm:p-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-800 dark:text-navy-100">{{ __('Status') }}</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">
                        @if ($maintenanceActive)
                            <span class="font-medium text-warning">{{ __('Maintenance mode is ON') }}</span>
                        @else
                            <span class="font-medium text-success">{{ __('Maintenance mode is OFF') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            @if ($maintenanceActive && $bypassUrl)
                <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-navy-600 dark:bg-navy-900/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-navy-400">
                        {{ __('Bypass URL') }}</p>
                    <p class="mt-2 break-all font-mono text-sm text-slate-800 dark:text-navy-100" dir="ltr">{{ $bypassUrl }}</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-navy-400" dir="auto">
                        {{ __('Open this URL in your browser to set the bypass cookie for this device.') }}
                    </p>
                </div>
            @endif

            <div class="flex flex-col gap-4 border-t border-slate-200 pt-6 dark:border-navy-600 sm:flex-row sm:items-center sm:justify-between">
                @if (! $maintenanceActive)
                    <form method="POST" action="{{ route('admin.settings.maintenance.enable') }}" class="inline"
                        onsubmit="return confirm(@json(__('Enable maintenance mode? The public site will show the maintenance page.')));">
                        @csrf
                        <x-lineone-button type="submit" variant="warning">{{ __('Enable maintenance mode') }}</x-lineone-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.settings.maintenance.disable') }}" class="inline"
                        onsubmit="return confirm(@json(__('Disable maintenance mode and bring the site live?')));">
                        @csrf
                        <x-lineone-button type="submit" variant="primary">{{ __('Disable maintenance mode') }}</x-lineone-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
