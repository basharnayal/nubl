@php
    $needsProof = $request->needsProviderFulfillmentProof();
    $statusConfig = [
        'REQUESTED' => ['class' => 'bg-warning/10 text-warning border border-warning/20', 'label' => __('Requested'), 'icon' => null],
        'APPROVED' => ['class' => 'bg-info/10 text-info border border-info/20', 'label' => __('Approved'), 'icon' => null],
        'ADMIN_PENDING' => ['class' => 'bg-warning/10 text-warning border border-warning/20', 'label' => __('Admin Pending'), 'icon' => null],
        'ADMIN_APPROVED' => ['class' => 'bg-success/10 text-success border border-success/20', 'label' => __('Admin Approved'), 'icon' => null],
        'REDEEMABLE' => ['class' => 'bg-success/10 text-success border border-success/20', 'label' => __('Redeemable'), 'icon' => null],
        'FULFILLED' => ['class' => 'bg-success/15 text-success border border-success/25', 'label' => __('Fulfilled'), 'icon' => 'fa-circle-check'],
        'REJECTED' => ['class' => 'bg-error/10 text-error border border-error/20', 'label' => __('Rejected'), 'icon' => null],
        'ADMIN_REJECTED' => ['class' => 'bg-error/10 text-error border border-error/20', 'label' => __('Rejected'), 'icon' => null],
    ];
    if ($needsProof) {
        $config = ['class' => 'bg-warning/15 text-warning border border-warning/30', 'label' => __('Proof pending'), 'icon' => 'fa-clock'];
    } else {
        $config = $statusConfig[$request->status] ?? ['class' => 'bg-slate-200/80 text-slate-600 border border-slate-200/80 dark:bg-navy-500 dark:border-navy-600 dark:text-navy-200', 'label' => $request->status, 'icon' => null];
    }
@endphp
<span class="badge inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $config['class'] }}">
    @if (! empty($config['icon']))
        <i class="fa-solid {{ $config['icon'] }} text-[0.65rem]" aria-hidden="true"></i>
    @endif
    {{ $config['label'] }}
</span>
