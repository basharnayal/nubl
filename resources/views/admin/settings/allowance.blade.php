<x-app-layout title="{{ __('Weekly allowance') }}" is-header-blur="true">
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
                <i class="fa-solid fa-wallet text-xl" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1 space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-navy-50">
                    {{ __('Weekly allowance') }}
                </h1>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-navy-300" dir="auto">
                    {{ __('Set the system-wide weekly spending limit for beneficiaries (city fund orders). Changes apply from the start of next week so recipients are not surprised mid-week.') }}
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-medium text-success">
                {{ session('success') }}
            </div>
        @endif

        <div
            class="card rounded-xl border border-primary/20 bg-gradient-to-b from-primary/[0.06] to-white p-6 shadow-sm dark:border-navy-600 dark:from-navy-800/50 dark:to-navy-800/40 sm:p-8">
            <dl class="mb-6 space-y-3 text-sm">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-100 pb-3 dark:border-navy-600">
                    <dt class="font-semibold text-slate-700 dark:text-navy-200">{{ __('Current effective limit (this week)') }}</dt>
                    <dd class="tabular-nums text-lg font-bold text-slate-900 dark:text-navy-50" dir="ltr">
                        <x-sar-symbol /> {{ number_format($effectiveLimit, 2) }}
                    </dd>
                </div>
                @if ($pendingValue !== null && $pendingValue !== '' && $pendingEffectiveAt)
                    <div class="flex flex-wrap items-baseline justify-between gap-2 rounded-lg bg-warning/10 px-3 py-2 dark:bg-warning/5">
                        <dt class="font-semibold text-slate-700 dark:text-navy-200">{{ __('Scheduled limit') }}</dt>
                        <dd class="text-right">
                            <span class="tabular-nums font-bold text-warning" dir="ltr">{{ number_format((float) $pendingValue, 2) }}
                                <x-sar-symbol /></span>
                            <p class="mt-1 text-xs text-slate-600 dark:text-navy-400" dir="auto">
                                {{ __('Effective from') }}:
                                <time class="font-medium text-slate-700 dark:text-navy-200" datetime="{{ $pendingEffectiveAt->toIso8601String() }}">{{ $pendingEffectiveFormatted }}</time>
                                <span class="text-slate-500 dark:text-navy-500">({{ $appTimezone }})</span>
                            </p>
                        </dd>
                    </div>
                @endif
                <div class="text-xs text-slate-500 dark:text-navy-400" dir="auto">
                    {{ __('Next week starts') }}:
                    <time class="font-medium text-slate-700 dark:text-navy-200" datetime="{{ $nextBoundary->toIso8601String() }}">{{ $nextBoundaryFormatted }}</time>
                    <span class="text-slate-500 dark:text-navy-500">({{ $appTimezone }})</span>
                </div>
            </dl>

            <form method="POST" action="{{ route('admin.settings.allowances.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="weekly_allowance_sar" class="mb-2 block text-sm font-semibold text-slate-800 dark:text-navy-100">
                        {{ __('New weekly limit') }} (<x-sar-symbol />)
                    </label>
                    <div dir="ltr" class="max-w-md text-left">
                        <input type="number" name="weekly_allowance_sar" id="weekly_allowance_sar"
                            value="{{ old('weekly_allowance_sar', $pendingValue !== null && $pendingValue !== '' ? round((float) $pendingValue, 2) : round($effectiveLimit, 2)) }}"
                            min="{{ $min }}" max="{{ $max }}" step="0.01" required inputmode="decimal" autocomplete="off"
                            class="form-input form-input-lineone w-full min-h-[2.75rem] text-base tabular-nums [appearance:textfield] [&::-webkit-inner-spin-button]:m-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:m-0 [&::-webkit-outer-spin-button]:appearance-none">
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-navy-400" dir="auto">
                        {{ __('Allowed range: :min–:max. Config default if never saved: :default.', ['min' => $min, 'max' => $max, 'default' => number_format($default, 2)]) }} (<x-sar-symbol />)
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-navy-400" dir="auto">
                        {{ __('Saving schedules this value for the start of next allowance week (Sunday). It replaces any previous unsaved schedule.') }}
                    </p>
                    @error('weekly_allowance_sar')
                        <p class="mt-2 text-sm font-medium text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 dark:border-navy-600 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500 dark:text-navy-400">
                        {{ __('Week is Sunday 00:00 through Saturday 23:59:59 (app timezone).') }}
                    </p>
                    <button type="submit"
                        class="btn inline-flex min-h-[2.75rem] w-full items-center justify-center gap-2 rounded-xl bg-primary px-8 py-2.5 font-semibold text-white shadow-sm hover:bg-primary-focus focus:ring-2 focus:ring-primary/30 sm:w-auto sm:min-w-[10rem] dark:bg-accent dark:hover:bg-accent-focus dark:focus:ring-accent/30">
                        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                        {{ __('Schedule change') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
