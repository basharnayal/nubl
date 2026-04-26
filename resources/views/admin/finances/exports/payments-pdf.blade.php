<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
    .header { margin-bottom: 14px; border-bottom: 2px solid #2563eb; padding-bottom: 8px; }
    .header h1 { font-size: 14px; font-weight: bold; color: #1e293b; }
    .header p { font-size: 8px; color: #64748b; margin-top: 3px; }
    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: #1e293b; color: #fff;
        padding: 5px 6px; text-align: left;
        font-size: 8px; font-weight: bold;
        border: 1px solid #334155;
    }
    tbody tr:nth-child(even) { background: #eff6ff; }
    tbody tr:nth-child(odd)  { background: #ffffff; }
    tbody td { padding: 4px 6px; border: 1px solid #e2e8f0; vertical-align: top; }
    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-error   { background: #ffe4e1; color: #9b1c1c; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-default { background: #e2e8f0; color: #334155; }
    .footer { margin-top: 10px; font-size: 7px; color: #94a3b8; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>Payments Report</h1>
    <p>Generated: {{ $generated_at->format('Y-m-d H:i:s') }} &nbsp;|&nbsp; Total records: {{ $payments->count() }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Sponsor ID</th>
            <th>Donor Name</th>
            <th>Donor Email</th>
            <th>Gateway</th>
            <th>External Payment ID</th>
            <th>Status</th>
            <th>Amount (<x-sar-symbol />)</th>
            <th>Created At</th>
            <th>Updated At</th>
        </tr>
    </thead>
    <tbody>
        @forelse($payments as $p)
            @php
                $badgeClass = match($p->status) {
                    'SUCCEEDED'  => 'badge-success',
                    'FAILED'     => 'badge-error',
                    'PENDING', 'PROCESSING', 'INITIATED' => 'badge-warning',
                    default      => 'badge-default',
                };
            @endphp
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->sponsor_id }}</td>
                <td>{{ $p->sponsor?->name }}</td>
                <td>{{ $p->sponsor?->email }}</td>
                <td>{{ $p->gateway }}</td>
                <td>{{ $p->external_payment_id }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ $p->status }}</span></td>
                <td>{{ number_format($p->amount, 2) }}</td>
                <td>{{ $p->created_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $p->updated_at?->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" style="text-align:center; padding: 12px; color: #94a3b8;">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">NUBL &mdash; Payments Export</div>

</body>
</html>
