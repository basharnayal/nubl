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
    .badge-in      { background: #d1fae5; color: #065f46; }
    .badge-out     { background: #ffe4e1; color: #9b1c1c; }
    .badge-default { background: #e2e8f0; color: #334155; }
    .footer { margin-top: 10px; font-size: 7px; color: #94a3b8; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>Fund Transactions (Ledger) Report</h1>
    <p>Generated: {{ $generated_at->format('Y-m-d H:i:s') }} &nbsp;|&nbsp; Total records: {{ $transactions->count() }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Wallet ID</th>
            <th>Wallet Owner Type</th>
            <th>Direction</th>
            <th>Source</th>
            <th>Amount (<x-sar-symbol />)</th>
            <th>Payment ID</th>
            <th>Request ID</th>
            <th>Redemption ID</th>
            <th>Donor ID</th>
            <th>Donor</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $tx)
            @php
                $donorName = $tx->sponsor?->name ?? ($tx->payment?->is_guest ? __('Guest Donor') : null);
            @endphp
            <tr>
                <td>{{ $tx->id }}</td>
                <td>{{ $tx->wallet_id }}</td>
                <td>{{ $tx->wallet?->owner_type }}</td>
                <td>
                    <span class="badge {{ $tx->direction === 'IN' ? 'badge-in' : 'badge-out' }}">
                        {{ $tx->direction }}
                    </span>
                </td>
                <td>{{ $tx->source }}</td>
                <td>{{ number_format($tx->amount, 2) }}</td>
                <td>{{ $tx->payment_id }}</td>
                <td>{{ $tx->request_id }}</td>
                <td>{{ $tx->order_redemption_id }}</td>
                <td>{{ $tx->sponsor_id }}</td>
                <td>{{ $donorName }}</td>
                <td>{{ $tx->created_at?->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="12" style="text-align:center; padding: 12px; color: #94a3b8;">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">NUBL &mdash; Fund Transactions Export</div>

</body>
</html>
