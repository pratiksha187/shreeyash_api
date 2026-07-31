<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #0f172a; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #dbe3ef; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; color: #334155; }
        .summary td { font-weight: 700; }
        .notice { background: #fff7ed; border: 1px solid #fed7aa; margin: 16px 0; padding: 12px; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <p>Dear {{ $employee->name }},</p>

    <p>
        Please find your attendance report for
        <strong>{{ $monthStart->format('F Y') }}</strong>.
    </p>

    <table class="summary">
        <tbody>
            <tr>
                <td>Present</td>
                <td>{{ $summary['present'] ?? 0 }}</td>
                <td>Half Day</td>
                <td>{{ $summary['half_day'] ?? 0 }}</td>
                <td>Leave</td>
                <td>{{ $summary['leave'] ?? 0 }}</td>
                <td>Absent</td>
                <td>{{ $summary['absent'] ?? 0 }}</td>
                <td>Not Marked</td>
                <td>{{ $summary['not_marked'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>

    @if ($missingItems->isNotEmpty())
        <div class="notice">
            <strong>Action required:</strong>
            Some login/logout entries are missing. Please send a missed attendance request from the attendance app for the dates below.
            <ul>
                @foreach ($missingItems as $item)
                    <li>
                        {{ $item['date']->format('d M Y') }}:
                        missing {{ implode(' and ', $item['missing']) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Status</th>
                <th>Login</th>
                <th>Logout</th>
                <th>Total Hours</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['date']->format('d M Y') }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['login'] }}</td>
                    <td>{{ $row['logout'] }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $row['remarks'] ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted">
        This is an automatic email from {{ $company->name }} Attendance Admin.
    </p>
</body>
</html>
