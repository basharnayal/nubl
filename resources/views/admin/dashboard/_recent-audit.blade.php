{{--
    RECENT AUDIT ACTIVITY — Last 8 admin audit log entries.
    Privacy: shows causer (admin) name only — no subject PII.
--}}

<section aria-labelledby="audit-heading"
         class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-navy-600 dark:bg-navy-800">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-navy-700">
        <div class="flex items-center gap-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-300">
                <i class="fa-solid fa-shield-halved text-sm" aria-hidden="true"></i>
            </div>
            <div>
                <h2 id="audit-heading" class="font-semibold text-slate-800 dark:text-navy-50">
                    {{ __('dashboard.audit.title') }}
                </h2>
                <p class="text-xs text-slate-400 dark:text-navy-400">
                    {{ __('dashboard.audit.subtitle') }}
                </p>
            </div>
        </div>
        <a href="{{ route('admin.audit-logs.index') }}"
           class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-200 dark:hover:border-navy-500 dark:hover:bg-navy-600">
            {{ __('dashboard.audit.full_log') }} →
        </a>
    </div>

    {{-- Activity list --}}
    @if (count($activities) === 0)

        <div class="flex flex-col items-center justify-center gap-3 px-5 py-12 text-center">
            <div class="flex size-12 items-center justify-center rounded-full bg-slate-100 dark:bg-navy-700">
                <i class="fa-solid fa-shield-halved text-xl text-slate-400 dark:text-navy-400" aria-hidden="true"></i>
            </div>
            <p class="text-sm text-slate-400 dark:text-navy-400">{{ __('dashboard.audit.no_activity') }}</p>
        </div>

    @else

        <ul role="list" class="divide-y divide-slate-100 dark:divide-navy-700">
            @foreach ($activities as $activity)
                @php
                    $desc = $activity['description'];

                    // Map description → translation key
                    $labelKey = match (true) {
                        str_starts_with($desc, 'maintenance.enabled')           => 'dashboard.audit.action.maintenance_enabled',
                        str_starts_with($desc, 'maintenance.disabled')          => 'dashboard.audit.action.maintenance_disabled',
                        str_starts_with($desc, 'qr_settings.')                  => 'dashboard.audit.action.qr_updated',
                        str_starts_with($desc, 'allowance_settings.')           => 'dashboard.audit.action.allowance_updated',
                        str_starts_with($desc, 'account_approval.approved')     => 'dashboard.audit.action.account_approved',
                        str_starts_with($desc, 'account_approval.rejected')     => 'dashboard.audit.action.account_rejected',
                        str_starts_with($desc, 'user.deactivated')              => 'dashboard.audit.action.user_deactivated',
                        str_starts_with($desc, 'user.reactivated')              => 'dashboard.audit.action.user_reactivated',
                        str_starts_with($desc, 'provider_payout.confirmed')     => 'dashboard.audit.action.payout_confirmed',
                        str_starts_with($desc, 'provider_payout.rejected')      => 'dashboard.audit.action.payout_rejected',
                        str_starts_with($desc, 'payment.')                      => 'dashboard.audit.action.payment_exported',
                        str_starts_with($desc, 'request.')                      => 'dashboard.audit.action.request_updated',
                        str_starts_with($desc, 'allocation_engine.paused')      => 'dashboard.audit.action.engine_paused',
                        str_starts_with($desc, 'allocation_engine.resumed')     => 'dashboard.audit.action.engine_resumed',
                        str_starts_with($desc, 'provider_allocation.paused')    => 'dashboard.audit.action.provider_allocation_paused',
                        str_starts_with($desc, 'provider_allocation.resumed')   => 'dashboard.audit.action.provider_allocation_resumed',
                        str_starts_with($desc, 'menu_item.')                    => 'dashboard.audit.action.menu_item_updated',
                        default                                                  => null,
                    };

                    // Map description → icon style
                    [$icon, $iconColor, $iconBg] = match (true) {
                        str_starts_with($desc, 'maintenance.')         => ['fa-solid fa-triangle-exclamation', 'text-rose-500 dark:text-rose-400',    'bg-rose-50 dark:bg-rose-500/10'],
                        str_starts_with($desc, 'qr_settings.')        => ['fa-solid fa-qrcode',               'text-blue-500 dark:text-blue-400',     'bg-blue-50 dark:bg-blue-500/10'],
                        str_starts_with($desc, 'allowance_settings.') => ['fa-solid fa-hand-holding-heart',   'text-pink-500 dark:text-pink-400',     'bg-pink-50 dark:bg-pink-500/10'],
                        str_starts_with($desc, 'account_approval.')   => ['fa-solid fa-user-check',           'text-emerald-600 dark:text-emerald-400','bg-emerald-50 dark:bg-emerald-500/10'],
                        str_starts_with($desc, 'user.')               => ['fa-solid fa-user',                 'text-primary dark:text-accent-light',  'bg-primary/10 dark:bg-accent/15'],
                        str_starts_with($desc, 'provider_payout.')    => ['fa-solid fa-money-bill-transfer',  'text-amber-600 dark:text-amber-400',   'bg-amber-50 dark:bg-amber-500/10'],
                        str_starts_with($desc, 'payment.')            => ['fa-solid fa-receipt',              'text-slate-600 dark:text-navy-300',    'bg-slate-100 dark:bg-navy-700'],
                        str_starts_with($desc, 'allocation_engine.')  => ['fa-solid fa-gears',                'text-purple-600 dark:text-purple-400', 'bg-purple-50 dark:bg-purple-500/10'],
                        default                                        => ['fa-solid fa-shield-halved',        'text-slate-500 dark:text-navy-400',    'bg-slate-100 dark:bg-navy-700'],
                    };

                    $label = $labelKey
                        ? __($labelKey)
                        : ucfirst(str_replace(['.', '_'], ' ', $desc));

                    $time = \Carbon\Carbon::parse($activity['created_at']);
                @endphp

                <li class="flex items-start gap-3.5 px-5 py-3.5 transition-colors hover:bg-slate-50/60 dark:hover:bg-navy-750/30">

                    {{-- Icon --}}
                    <div class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg {{ $iconBg }} {{ $iconColor }}">
                        <i class="{{ $icon }} text-[11px]" aria-hidden="true"></i>
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800 dark:text-navy-100">
                            {{ $label }}
                        </p>
                        <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-400 dark:text-navy-400">
                            <i class="fa-regular fa-user text-[10px]" aria-hidden="true"></i>
                            <span class="font-medium text-slate-600 dark:text-navy-300">
                                {{ $activity['causer_name'] === 'System'
                                    ? __('dashboard.audit.system')
                                    : $activity['causer_name'] }}
                            </span>
                            <span aria-hidden="true">·</span>
                            <time datetime="{{ $time->toIso8601String() }}"
                                  title="{{ $time->format('d M Y, H:i') }}">
                                {{ $time->diffForHumans() }}
                            </time>
                        </p>
                    </div>

                </li>
            @endforeach
        </ul>

    @endif
</section>
