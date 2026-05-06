<x-app-layout :title="__('audit_logs.page.title')" is-header-blur="true">
    <div class="pt-4" x-data="{ open: false, selected: null }">

        {{-- ── Header ── --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-wide text-slate-800 dark:text-navy-50">{{ __('audit_logs.page.title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-navy-300">{{ __('audit_logs.page.subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- ── Summary Cards ── --}}
        <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-5">

            {{-- Total --}}
            <div class="card flex items-center gap-3 p-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xs text-slate-500 dark:text-navy-300">{{ __('audit_logs.cards.total') }}</p>
                    <p class="mt-0.5 text-xl font-bold text-primary dark:text-accent-light">{{ number_format($totalCount) }}</p>
                </div>
            </div>

            {{-- Today --}}
            <div class="card flex items-center gap-3 p-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xs text-slate-500 dark:text-navy-300">{{ __('audit_logs.cards.today') }}</p>
                    <p class="mt-0.5 text-xl font-bold text-success">{{ number_format($todayCount) }}</p>
                </div>
            </div>

            {{-- Finance --}}
            <div class="card flex items-center gap-3 p-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xs text-slate-500 dark:text-navy-300">{{ __('audit_logs.cards.finance') }}</p>
                    <p class="mt-0.5 text-xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($financeCount) }}</p>
                </div>
            </div>

            {{-- Auth --}}
            <div class="card flex items-center gap-3 p-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xs text-slate-500 dark:text-navy-300">{{ __('audit_logs.cards.auth') }}</p>
                    <p class="mt-0.5 text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($authCount) }}</p>
                </div>
            </div>

            {{-- Registration --}}
            <div class="card flex items-center gap-3 p-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xs text-slate-500 dark:text-navy-300">{{ __('audit_logs.cards.registration') }}</p>
                    <p class="mt-0.5 text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($registrationCount) }}</p>
                </div>
            </div>

        </div>

        {{-- ── Filters ── --}}
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="card mt-4 p-4">
            <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-navy-100">{{ __('audit_logs.filters.title') }}</h3>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="{{ __('audit_logs.filters.search_placeholder') }}"
                    class="form-input form-input-lineone w-full min-w-0">

                <select name="log_name" class="form-select form-select-lineone w-full">
                    <option value="">{{ __('audit_logs.filters.all_log_names') }}</option>
                    @foreach($logNames as $ln)
                        <option value="{{ $ln }}" {{ request('log_name') === $ln ? 'selected' : '' }}>{{ $ln }}</option>
                    @endforeach
                </select>

                <input
                    type="number"
                    name="causer_id"
                    value="{{ request('causer_id') }}"
                    placeholder="{{ __('audit_logs.filters.causer_id') }}"
                    class="form-input form-input-lineone w-full min-w-0">

                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="form-input form-input-lineone w-full">

                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="form-input form-input-lineone w-full">

                <select name="entity" class="form-select form-select-lineone w-full">
                    <option value="">{{ __('audit_logs.filters.all_entities') }}</option>
                    @foreach(['auth','finance','registration','user','donation','request','wallet','role','menu_item','qr_settings','account_approval','application'] as $ent)
                        <option value="{{ $ent }}" {{ request('entity') === $ent ? 'selected' : '' }}>{{ $ent }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="submit" class="btn bg-primary text-white dark:bg-accent">{{ __('audit_logs.filters.apply') }}</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn border border-slate-300 dark:border-navy-500 dark:text-navy-100">{{ __('audit_logs.filters.clear') }}</a>
            </div>
        </form>

        {{-- ── Table ── --}}
        <div class="card mt-4">
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <table class="is-hoverable w-full text-left">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">#</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.action') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.description') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.entity') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.causer') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.ip') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.device') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.properties') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.entry_hash') }}</th>
                            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('audit_logs.table.created_at') }}</th>
                            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $parts   = explode('.', $log->description ?? '');
                                $entity  = $log->properties['entity'] ?? ($parts[0] ?? '');
                                $action  = $log->properties['action']  ?? ($parts[1] ?? '');

                                $entityBadge = match($entity) {
                                    'auth'             => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
                                    'finance'          => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                                    'registration'     => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300',
                                    'qr_settings'      => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-300',
                                    'user'             => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
                                    'donation'         => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                                    'account_approval' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
                                    default            => 'bg-slate-100 text-slate-600 dark:bg-navy-500 dark:text-navy-200',
                                };

                                // Extract meaningful properties (skip entity/action/ip/user_agent which have dedicated columns)
                                $displayProps = collect($log->properties ?? [])
                                    ->except(['entity', 'action', 'ip', 'user_agent'])
                                    ->filter(fn($v) => $v !== null && $v !== '' && $v !== [])
                                    ->take(4);

                                // Client info
                                $logIp        = $log->properties['ip'] ?? null;
                                $logUserAgent = $log->properties['user_agent'] ?? null;
                                $logDevice    = null;
                                if ($logUserAgent) {
                                    $ua = strtolower($logUserAgent);
                                    if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
                                        $logDevice = __('Mobile');
                                    } elseif (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
                                        $logDevice = __('Tablet');
                                    } else {
                                        $logDevice = __('Desktop');
                                    }
                                }

                                // Causer resolution
                                $isUserCauser = $log->causer_type === 'App\\Models\\User' && $log->causer_id;
                                $causerName   = $log->causer?->name;
                                $causerEmail  = $log->causer?->email;

                                // Entry hash (column is sha256_hash)
                                $hashFull    = $log->sha256_hash;
                                $hashPreview = $hashFull ? substr($hashFull, 0, 10).'…' : null;

                                $logData = [
                                    'id'           => $log->id,
                                    'log_name'     => $log->log_name,
                                    'description'  => $log->description,
                                    'event'        => $log->event,
                                    'causer_type'  => $log->causer_type,
                                    'causer_id'    => $log->causer_id,
                                    'causer_name'  => $causerName,
                                    'causer_email' => $causerEmail,
                                    'subject_type' => $log->subject_type,
                                    'subject_id'   => $log->subject_id,
                                    'properties'   => $log->properties instanceof \Illuminate\Support\Collection
                                                        ? $log->properties->toArray()
                                                        : (array) $log->properties,
                                    'entry_hash'   => $hashFull,
                                    'ip'           => $logIp,
                                    'user_agent'   => $logUserAgent,
                                    'device'       => $logDevice,
                                    'batch_uuid'   => $log->batch_uuid,
                                    'created_at'   => $log->created_at?->toDateTimeString(),
                                    'updated_at'   => $log->updated_at?->toDateTimeString(),
                                ];
                            @endphp
                            <tr class="border-b border-slate-200 dark:border-navy-500">

                                {{-- ID --}}
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    <span class="font-medium text-primary dark:text-accent-light">#{{ $log->id }}</span>
                                </td>

                                {{-- Action badge --}}
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    @if($action)
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-navy-500 dark:text-navy-200">{{ $action }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Description --}}
                                <td class="px-4 py-3 sm:px-5">
                                    <span class="font-mono text-xs text-slate-700 dark:text-navy-100">{{ $log->description ?? '—' }}</span>
                                </td>

                                {{-- Entity badge --}}
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    @if($entity)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $entityBadge }}">{{ $entity }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Causer --}}
                                <td class="px-4 py-3 sm:px-5">
                                    @if($log->causer_id)
                                        @if($isUserCauser)
                                            <a href="{{ route('admin.manage.users.show', $log->causer_id) }}"
                                               class="group inline-flex flex-col">
                                                <span class="text-sm font-medium text-primary group-hover:underline dark:text-accent-light">
                                                    #{{ $log->causer_id }}{{ $causerName ? ' — '.$causerName : '' }}
                                                </span>
                                                @if($causerEmail)
                                                    <span class="mt-0.5 text-xs text-slate-400 dark:text-navy-400">{{ $causerEmail }}</span>
                                                @endif
                                            </a>
                                        @else
                                            <span class="text-sm text-slate-600 dark:text-navy-200">#{{ $log->causer_id }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- IP Address --}}
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    @if($logIp)
                                        <span class="font-mono text-xs text-slate-700 dark:text-navy-100">{{ $logIp }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Device --}}
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    @if($logDevice)
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $logDevice === __('Mobile') ? 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300' : ($logDevice === __('Tablet') ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300') }}">
                                            {{ $logDevice }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Properties (readable badges) --}}
                                <td class="px-4 py-3 sm:px-5">
                                    @if($displayProps->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($displayProps as $key => $val)
                                                @php
                                                    if (is_array($val)) {
                                                        if (empty($val)) {
                                                            continue;
                                                        }
                                                        // Associative array: show key=value pairs
                                                        $isAssoc = array_keys($val) !== range(0, count($val) - 1);
                                                        if ($isAssoc) {
                                                            $parts2 = [];
                                                            foreach (array_slice($val, 0, 2, true) as $k => $v) {
                                                                $parts2[] = $k.'='.($v ?? 'null');
                                                            }
                                                            $label = $key.': '.implode(', ', $parts2);
                                                        } else {
                                                            $label = $key.': '.count($val).' items';
                                                        }
                                                    } elseif (is_bool($val)) {
                                                        $label = $key.': '.($val ? 'true' : 'false');
                                                    } else {
                                                        $label = $key.': '.$val;
                                                    }
                                                    if (mb_strlen($label) > 32) {
                                                        $label = mb_substr($label, 0, 29).'…';
                                                    }
                                                @endphp
                                                <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600 dark:bg-navy-600 dark:text-navy-200">{{ $label }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Entry Hash (sha256_hash) --}}
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    @if($hashPreview)
                                        <span class="font-mono text-xs text-slate-500 dark:text-navy-300">{{ $hashPreview }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    <div class="text-xs font-medium text-slate-700 dark:text-navy-100">{{ $log->created_at->format('d M Y') }}</div>
                                    <div class="mt-0.5 text-xs text-slate-400 dark:text-navy-400">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>

                                {{-- Details button --}}
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    <button
                                        type="button"
                                        @click='selected = @json($logData); open = true'
                                        title="{{ __('audit_logs.table.view_details') }}"
                                        class="btn size-8 rounded-full bg-primary/10 p-0 text-primary hover:bg-primary/20 focus:bg-primary/20 dark:bg-accent/10 dark:text-accent-light dark:hover:bg-accent/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.572-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                        </svg>
                                    </button>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-14 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-400 dark:text-navy-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-12 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                        </svg>
                                        <p class="text-sm font-semibold">{{ __('audit_logs.empty.title') }}</p>
                                        <p class="text-xs">{{ __('audit_logs.empty.subtitle') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Pagination ── --}}
        @if($logs->hasPages())
            <div class="mt-4 flex justify-center">
                {{ $logs->links() }}
            </div>
        @endif

        {{-- ── Details Modal ── --}}
        <template x-teleport="body">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @keydown.escape.window="open = false"
                class="fixed inset-0 z-[200] flex items-end justify-center p-4 sm:items-center"
                style="display: none;">

                {{-- Backdrop --}}
                <div @click="open = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

                {{-- Panel --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-navy-700"
                    style="max-height: 88vh; overflow-y: auto;">

                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-navy-500">
                        <div class="flex items-center gap-2.5">
                            <div class="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-slate-800 dark:text-navy-50">{{ __('audit_logs.modal.title') }}</h3>
                        </div>
                        <button
                            @click="open = false"
                            class="btn size-8 rounded-full p-0 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-navy-300 dark:hover:bg-navy-600 dark:hover:text-navy-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="space-y-5 p-6">

                        {{-- ID / Log name --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-navy-800">
                                <p class="text-xs font-medium text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.id') }}</p>
                                <p class="mt-1 font-mono text-sm font-bold text-primary dark:text-accent-light" x-text="selected ? '#' + selected.id : ''"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-navy-800">
                                <p class="text-xs font-medium text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.log_name') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-navy-100" x-text="selected?.log_name ?? '—'"></p>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.description') }}</p>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 font-mono text-sm text-slate-800 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-100" x-text="selected?.description ?? '—'"></div>
                        </div>

                        {{-- Causer / Subject --}}
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.causer') }}</p>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-navy-500 dark:bg-navy-800">
                                    <p class="text-xs text-slate-500 dark:text-navy-300" x-text="selected?.causer_type ? selected.causer_type.split('\\\\').pop() : '—'"></p>
                                    <p class="mt-0.5 font-semibold text-slate-700 dark:text-navy-100"
                                       x-text="selected?.causer_name
                                           ? '#' + selected.causer_id + ' — ' + selected.causer_name
                                           : (selected?.causer_id ? '#' + selected.causer_id : '—')">
                                    </p>
                                    <p x-show="selected?.causer_email"
                                       class="mt-0.5 text-xs text-slate-400 dark:text-navy-400"
                                       x-text="selected?.causer_email ?? ''"></p>
                                </div>
                            </div>
                            <div>
                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.subject') }}</p>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-navy-500 dark:bg-navy-800">
                                    <p class="text-xs text-slate-500 dark:text-navy-300" x-text="selected?.subject_type ? selected.subject_type.split('\\\\').pop() : '—'"></p>
                                    <p class="mt-0.5 font-semibold text-slate-700 dark:text-navy-100" x-text="selected?.subject_id ? 'ID #' + selected.subject_id : '—'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Event --}}
                        <div x-show="selected?.event">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.event') }}</p>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-100"
                                 x-text="selected?.event"></div>
                        </div>

                        {{-- Properties JSON --}}
                        <div>
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.properties') }}</p>
                            <pre
                                class="max-h-52 overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-4 font-mono text-xs leading-relaxed text-slate-700 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-100"
                                x-text="selected?.properties ? JSON.stringify(selected.properties, null, 2) : '—'"></pre>
                        </div>

                        {{-- Entry Hash --}}
                        <div>
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.entry_hash') }}</p>
                            <div
                                class="break-all rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 font-mono text-xs text-slate-500 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-300"
                                x-text="selected?.entry_hash ?? '—'"></div>
                        </div>

                        {{-- IP / Device / User-Agent --}}
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-navy-800">
                                <p class="text-xs font-medium text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.ip') }}</p>
                                <p class="mt-1 font-mono text-sm font-semibold text-slate-700 dark:text-navy-100" x-text="selected?.ip ?? '—'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-navy-800">
                                <p class="text-xs font-medium text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.device') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-navy-100" x-text="selected?.device ?? '—'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-navy-800">
                                <p class="text-xs font-medium text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.user_agent') }}</p>
                                <p class="mt-1 break-all text-xs text-slate-600 dark:text-navy-200" x-text="selected?.user_agent ?? '—'"></p>
                            </div>
                        </div>

                        {{-- Batch UUID --}}
                        <div>
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.batch_uuid') }}</p>
                            <div
                                class="break-all rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 font-mono text-xs text-slate-500 dark:border-navy-500 dark:bg-navy-800 dark:text-navy-300"
                                x-text="selected?.batch_uuid ?? '—'"></div>
                        </div>

                        {{-- Timestamps --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-navy-800">
                                <p class="text-xs font-medium text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.created_at') }}</p>
                                <p class="mt-1 text-sm text-slate-700 dark:text-navy-100" x-text="selected?.created_at ?? '—'"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-navy-800">
                                <p class="text-xs font-medium text-slate-400 dark:text-navy-400">{{ __('audit_logs.modal.updated_at') }}</p>
                                <p class="mt-1 text-sm text-slate-700 dark:text-navy-100" x-text="selected?.updated_at ?? '—'"></p>
                            </div>
                        </div>

                    </div>{{-- /body --}}
                </div>{{-- /panel --}}
            </div>{{-- /overlay --}}
        </template>

    </div>
</x-app-layout>
