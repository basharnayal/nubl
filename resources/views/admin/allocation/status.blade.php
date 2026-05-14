<x-app-layout title="{{ __('Allocation Engine') }}" is-header-blur="true">
    <div class="mx-auto max-w-4xl space-y-6 pb-24 pt-4 lg:pb-8">

        {{-- Back --}}
        <a href="{{ route('admin.dashboard') }}"
            class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-primary dark:text-navy-300 dark:hover:text-accent">
            <i class="fa-solid fa-arrow-left rtl:rotate-180" aria-hidden="true"></i>
            {{ __('Back to admin dashboard') }}
        </a>

        {{-- Page header --}}
        <div class="flex items-start gap-4">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent">
                <i class="fa-solid fa-circle-nodes text-xl" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1 space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-navy-50">
                    {{ __('Allocation Engine') }}
                </h1>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-navy-300" dir="auto">
                    {{ __('Pause or resume the fund allocation engine globally or per-provider.') }}
                </p>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-medium text-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-error/30 bg-error/10 px-4 py-3 text-sm font-medium text-error">
                {{ session('error') }}
            </div>
        @endif

        {{-- Global status card --}}
        <div class="card rounded-xl border p-6 shadow-sm sm:p-8
            {{ $globallyPaused ? 'border-warning/30 bg-warning/5 dark:border-warning/20 dark:bg-warning/5' : 'border-success/30 bg-success/5 dark:border-success/20 dark:bg-success/5' }}">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-navy-400">
                        {{ __('Global Status') }}
                    </p>
                    <p class="mt-1 text-lg font-bold {{ $globallyPaused ? 'text-warning' : 'text-success' }}">
                        {{ $globallyPaused ? __('Paused') : __('Running') }}
                    </p>
                    @if ($pendingCount > 0)
                        <p class="mt-1 text-sm text-slate-600 dark:text-navy-300">
                            {{ __(':count pending allocation(s) in queue.', ['count' => $pendingCount]) }}
                        </p>
                    @endif
                </div>

                @can('allocation.pause_global')
                <div class="flex gap-3">
                    @if ($globallyPaused)
                        <form method="POST" action="{{ route('admin.allocation.resume') }}">
                            @csrf
                            <button type="submit"
                                class="btn inline-flex min-h-[2.75rem] items-center gap-2 rounded-xl bg-success px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-success/80">
                                <i class="fa-solid fa-play" aria-hidden="true"></i>
                                {{ __('Resume All') }}
                            </button>
                        </form>
                    @else
                        <button type="button"
                            @click="$dispatch('open-modal', 'pause-all-allocation')"
                            class="btn inline-flex min-h-[2.75rem] items-center gap-2 rounded-xl bg-warning px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-warning/80">
                            <i class="fa-solid fa-pause" aria-hidden="true"></i>
                            {{ __('Pause All') }}
                        </button>
                    @endif
                </div>
                @endcan
            </div>
        </div>

        @can('allocation.pause_global')
        <x-lineone-modal id="pause-all-allocation" title="{{ __('Pause all allocations globally?') }}" size="md">
            <p class="mb-6 text-sm leading-relaxed text-slate-600 dark:text-navy-300">
                {{ __('This pauses fund allocation for every provider until you resume globally.') }}
            </p>
            <form method="POST" action="{{ route('admin.allocation.pause') }}" class="flex flex-wrap justify-end gap-3">
                @csrf
                <button type="button" @click="$dispatch('close-modal', 'pause-all-allocation')"
                    class="btn min-h-[2.5rem] rounded-xl border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-navy-500 dark:bg-navy-600 dark:text-navy-100 dark:hover:bg-navy-500">
                    {{ __('Cancel') }}
                </button>
                <button type="submit"
                    class="btn inline-flex min-h-[2.5rem] items-center gap-2 rounded-xl bg-warning px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-warning/80">
                    <i class="fa-solid fa-pause" aria-hidden="true"></i>
                    {{ __('Pause All') }}
                </button>
            </form>
        </x-lineone-modal>
        @endcan

        {{-- Per-provider table --}}
        <div class="card rounded-xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-navy-600">
                <h2 class="font-semibold text-slate-800 dark:text-navy-50">{{ __('Per-Provider Controls') }}</h2>
                @if ($globallyPaused)
                    <p class="mt-2 text-sm text-slate-600 dark:text-navy-300">
                        {{ __('Per-provider pause and resume are unavailable while global pause is active.') }}
                    </p>
                @endif
            </div>

            @forelse ($providers as $provider)
                @php
                    $rowStatusGlobal = $globallyPaused;
                    $rowStatusProviderOnly = ! $globallyPaused && $provider->allocation_paused;
                @endphp
                <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between
                    {{ ! $loop->last ? 'border-b border-slate-100 dark:border-navy-700' : '' }}">
                    <div>
                        <p class="font-medium text-slate-800 dark:text-navy-100">{{ $provider->name }}</p>
                        <p class="text-xs {{ $rowStatusGlobal || $rowStatusProviderOnly ? 'text-warning' : 'text-success' }}">
                            @if ($rowStatusGlobal)
                                {{ __('Paused (system-wide)') }}
                            @elseif ($rowStatusProviderOnly)
                                {{ __('Paused') }}
                            @else
                                {{ __('Running') }}
                            @endif
                        </p>
                    </div>

                    @can('allocation.pause_per_provider')
                    <div>
                        @if ($globallyPaused)
                            <span class="inline-flex min-h-[2.25rem] items-center text-xs font-medium text-slate-400 dark:text-navy-500"
                                title="{{ __('Per-provider pause and resume are unavailable while global pause is active.') }}">—</span>
                        @elseif ($provider->allocation_paused)
                            <form method="POST" action="{{ route('admin.allocation.provider.resume', $provider) }}">
                                @csrf
                                <button type="submit"
                                    class="btn inline-flex min-h-[2.25rem] items-center gap-2 rounded-lg bg-success/10 px-4 py-1.5 text-sm font-semibold text-success hover:bg-success/20">
                                    <i class="fa-solid fa-play" aria-hidden="true"></i>
                                    {{ __('Resume') }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.allocation.provider.pause', $provider) }}">
                                @csrf
                                <button type="submit"
                                    class="btn inline-flex min-h-[2.25rem] items-center gap-2 rounded-lg bg-warning/10 px-4 py-1.5 text-sm font-semibold text-warning hover:bg-warning/20">
                                    <i class="fa-solid fa-pause" aria-hidden="true"></i>
                                    {{ __('Pause') }}
                                </button>
                            </form>
                        @endif
                    </div>
                    @endcan
                </div>
            @empty
                <p class="px-6 py-8 text-center text-sm text-slate-500 dark:text-navy-400">
                    {{ __('No providers found.') }}
                </p>
            @endforelse
        </div>

    </div>
</x-app-layout>
