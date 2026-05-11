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
    .badge-auth         { background: #e0e7ff; color: #3730a3; }
    .badge-finance      { background: #fef3c7; color: #92400e; }
    .badge-registration { background: #ede9fe; color: #5b21b6; }
    .badge-user         { background: #dbeafe; color: #1e40af; }
    .badge-default      { background: #e2e8f0; color: #334155; }
    .footer { margin-top: 10px; font-size: 7px; color: #94a3b8; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>Audit Logs Report</h1>
    <p>Generated: {{ $generated_at->format('Y-m-d H:i:s') }} &nbsp;|&nbsp; Total records: {{ $logs->count() }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Event</th>
            <th>Entity</th>
            <th>Causer</th>
            <th>IP</th>
            <th>SHA-256 Hash</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logs as $log)
            @php
                $entity = $log->properties['entity'] ?? (explode('.', $log->description ?? '')[0] ?? '');
                $badgeClass = match($entity) {
                    'auth'         => 'badge-auth',
                    'finance'      => 'badge-finance',
                    'registration' => 'badge-registration',
                    'user'         => 'badge-user',
                    default        => 'badge-default',
                };
            @endphp
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                <td>{{ $log->description }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ $entity }}</span></td>
                <td>{{ $log->causer?->name ?? ($log->causer_id ? '#'.$log->causer_id : '—') }}</td>
                <td>{{ $log->properties['ip'] ?? '—' }}</td>
                <td style="font-size: 6px; word-break: break-all;">{{ $log->sha256_hash }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center; padding: 12px; color: #94a3b8;">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">NUBL &mdash; Audit Logs Export</div>

</body>
</html>
