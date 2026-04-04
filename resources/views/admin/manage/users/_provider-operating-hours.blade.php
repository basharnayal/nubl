@php
    $weekdayKeys = $weekdayKeys ?? array_keys(config('provider.weekdays'));
    $oh = $oh ?? [];
@endphp
<div class="space-y-3">
    @foreach ($weekdayKeys as $dayKey)
        @php
            $dayData = $oh[$dayKey] ?? ['closed' => true];
            $closed = ! empty($dayData['closed']);
        @endphp
        <div
            class="provider-oh-row rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-navy-600 dark:bg-navy-900/30 sm:p-5"
            data-day="{{ $dayKey }}">
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <span
                    class="font-semibold text-slate-800 dark:text-navy-100">{{ __('provider.weekday.'.$dayKey) }}</span>
                <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm dark:border-navy-500 dark:bg-navy-800">
                    <input type="checkbox" name="operating_hours[{{ $dayKey }}][closed]" value="1"
                        class="provider-oh-closed rounded border-slate-300 text-primary" @checked($closed) />
                    <span class="text-slate-600 dark:text-navy-300">{{ __('Closed') }}</span>
                </label>
            </div>
            <div
                class="provider-oh-hours flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4 rtl:sm:flex-row-reverse">
                <div class="min-w-0 flex-1 sm:max-w-[12rem]">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('Open') }}</label>
                    <div dir="ltr" class="w-full">
                        <input type="time" name="operating_hours[{{ $dayKey }}][open]"
                            value="{{ old("operating_hours.$dayKey.open", $dayData['open'] ?? '09:00') }}"
                            @disabled($closed)
                            class="provider-oh-open w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-50" />
                    </div>
                </div>
                <div class="min-w-0 flex-1 sm:max-w-[12rem]">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-500 dark:text-navy-400">{{ __('Close') }}</label>
                    <div dir="ltr" class="w-full">
                        <input type="time" name="operating_hours[{{ $dayKey }}][close]"
                            value="{{ old("operating_hours.$dayKey.close", $dayData['close'] ?? '17:00') }}"
                            @disabled($closed)
                            class="provider-oh-close w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-50" />
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<x-input-error :messages="$errors->get('operating_hours')" class="mt-2" />
@foreach ($weekdayKeys as $dayKey)
    <x-input-error :messages="$errors->get('operating_hours.'.$dayKey)" class="mt-1" />
@endforeach
