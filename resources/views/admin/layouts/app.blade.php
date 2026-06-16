<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel')</title>
    <style>
        :root {
            --bg: #f3f6fb;
            --panel: #ffffff;
            --text: #1f2937;
            --muted: #64748b;
            --line: #e2e8f0;
            --primary: #1d4ed8;
            --primary-dark: #1e3a8a;
            --sidebar: #111827;
            --sidebar-soft: #1f2937;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --danger: #b91c1c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            color: inherit;
        }

        .admin-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            padding: 22px 16px;
            background: var(--sidebar);
            color: #f8fafc;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 8px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 8px;
            background: var(--primary);
            font-weight: 800;
        }

        .brand strong {
            display: block;
            font-size: 17px;
        }

        .brand span {
            display: block;
            margin-top: 3px;
            color: #cbd5e1;
            font-size: 12px;
        }

        .nav {
            display: grid;
            gap: 12px;
            margin-top: 22px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        .nav a.active,
        .nav a:hover {
            background: var(--sidebar-soft);
            color: #fff;
        }

        .nav-group {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.35);
        }

        .nav-group summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 38px;
            padding: 10px 12px;
            color: #f8fafc;
            cursor: pointer;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            list-style: none;
            text-transform: uppercase;
        }

        .nav-group summary::-webkit-details-marker {
            display: none;
        }

        .nav-group summary::after {
            content: '+';
            color: #94a3b8;
            font-size: 16px;
            line-height: 1;
        }

        .nav-group[open] summary::after {
            content: '-';
        }

        .nav-group.active {
            border-color: rgba(59, 130, 246, 0.45);
        }

        .nav-group-links {
            display: grid;
            gap: 4px;
            padding: 0 6px 8px;
        }

        .nav-group-links a {
            min-height: 36px;
            padding: 8px 10px;
            border-radius: 6px;
        }

        .content-shell {
            display: grid;
            min-width: 0;
            grid-template-rows: auto 1fr auto;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 72px;
            padding: 0 28px;
            border-bottom: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
        }

        .topbar-title {
            min-width: 0;
        }

        .topbar-title strong {
            display: block;
            font-size: 18px;
        }

        .topbar-title span {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 13px;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-weight: 700;
        }

        .avatar {
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border-radius: 50%;
            background: #dbeafe;
            color: var(--primary-dark);
        }

        .logout-form {
            margin: 0;
        }

        .logout-button {
            min-height: 36px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: #334155;
            cursor: pointer;
            font-weight: 800;
        }

        .main {
            width: 100%;
            max-width: 1180px;
            padding: 28px;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        h1 {
            margin: 0;
            font-size: 28px;
            line-height: 1.2;
        }

        p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 10px 14px;
            border: 0;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            font-weight: 800;
            text-decoration: none;
        }

        .btn.secondary {
            background: #e2e8f0;
            color: #1f2937;
        }

        .btn.danger {
            background: #dc2626;
        }

        .btn.whatsapp {
            background: #16a34a;
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.18);
        }

        .btn.whatsapp:hover {
            background: #15803d;
        }

        .btn.small {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 13px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .stat-card {
            padding: 18px;
        }

        .stat-card span {
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .stat-card strong {
            display: block;
            margin-top: 10px;
            font-size: 30px;
        }

        .alert-success {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            background: var(--success-bg);
            color: var(--success-text);
            font-weight: 700;
        }

        .alert-error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
            font-weight: 700;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .employees-table-wrap {
            overflow-x: visible;
        }

        .employees-table {
            table-layout: fixed;
        }

        .employees-table th,
        .employees-table td {
            padding: 16px 18px;
            vertical-align: middle;
            white-space: normal;
        }

        .employees-table tbody tr:hover td {
            background: #f8fbff;
        }

        .employee-name {
            display: grid;
            gap: 4px;
        }

        .employee-id,
        .employee-subtext {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .employee-contact {
            display: grid;
            gap: 4px;
            overflow-wrap: anywhere;
        }

        .designation-pill {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25;
        }

        .date-stack {
            display: grid;
            gap: 4px;
        }

        .employee-action-form {
            margin: 0;
        }

        .employee-action-form .btn {
            width: 100%;
            white-space: nowrap;
        }

        .table-link {
            color: var(--primary);
            font-weight: 800;
            text-decoration: none;
        }

        .table-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .table-subtext {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            white-space: normal;
        }

        .text-wrap {
            min-width: 220px;
            max-width: 340px;
            line-height: 1.5;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .hour-list {
            display: grid;
            gap: 12px;
            min-width: 320px;
            max-width: 520px;
        }

        .hour-item {
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
            white-space: normal;
        }

        .hour-item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .hour-item strong {
            display: block;
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 13px;
        }

        .hour-item p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
            white-space: normal;
        }

        .thumb-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .thumb {
            display: block;
            width: 72px;
            height: 72px;
            border: 1px solid var(--line);
            border-radius: 8px;
            object-fit: cover;
            background: #f8fafc;
        }

        .inline-status-form {
            display: grid;
            gap: 8px;
            min-width: 260px;
        }

        .inline-status-form select,
        .inline-status-form textarea {
            min-height: 36px;
            padding: 7px 9px;
            font-size: 13px;
        }

        .inline-status-form textarea {
            min-height: 64px;
        }

        .inline-status-form .btn {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #f8fafc;
            color: var(--muted);
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .empty {
            padding: 28px;
            text-align: center;
            color: var(--muted);
        }

        .form-card {
            max-width: none;
            padding: 24px;
        }

        .report-filter {
            margin-bottom: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .form-section {
            margin-bottom: 28px;
        }

        .form-section:last-of-type {
            margin-bottom: 0;
        }

        .section-title {
            margin: 0 0 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c88f32;
            color: #0f172a;
            font-size: 24px;
            line-height: 1.2;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 800;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            background: #fff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .error {
            margin-top: 6px;
            color: var(--danger);
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 22px;
        }

        .footer {
            padding: 18px 28px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            background: #fff;
            font-size: 13px;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
        }

        .pagination-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            color: var(--muted);
            font-size: 14px;
        }

        .pagination-summary {
            white-space: nowrap;
        }

        .pagination-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 6px;
        }

        .pagination-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            min-height: 34px;
            padding: 7px 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: #334155;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
        }

        .pagination-link:hover {
            border-color: #bfdbfe;
            color: var(--primary);
        }

        .pagination-link.active {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }

        .pagination-link.disabled {
            background: #f8fafc;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .sheet-summary-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 22px;
            margin-top: 28px;
        }

        .vehicle-sheet-table td,
        .vehicle-sheet-table th {
            padding: 10px 12px;
        }

        .editable-sheet th {
            background: #1f2937;
            color: #fff;
            letter-spacing: 0;
        }

        .editable-sheet input {
            min-width: 108px;
            min-height: 34px;
            padding: 6px 8px;
            border-radius: 6px;
            font-size: 14px;
        }

        .editable-sheet .sheet-number {
            min-width: 94px;
        }

        .editable-sheet input[readonly] {
            background: #f8fafc;
        }

        .sheet-actions {
            display: flex;
            gap: 10px;
            padding: 18px;
        }

        .vehicle-sheet-table tr.selected-row td {
            background: #eff6ff;
        }

        .fdd-report-title,
        .sheet-report-title {
            padding: 14px 18px;
            border-bottom: 2px solid #111827;
            color: #020617;
            font-size: 26px;
            font-weight: 900;
            text-align: center;
        }

        .fdd-table th,
        .fdd-table td,
        .sheet-table th,
        .sheet-table td {
            border: 1px solid #111827;
            white-space: normal;
        }

        .fdd-table th,
        .sheet-table th {
            background: #d9d9d9;
            color: #020617;
            font-size: 14px;
            letter-spacing: 0;
            text-align: center;
            text-transform: none;
        }

        .fdd-table td,
        .sheet-table td {
            padding: 8px 10px;
            color: #020617;
            font-size: 14px;
            vertical-align: middle;
        }

        .fdd-table .fdd-sr,
        .fdd-table .fdd-date,
        .fdd-table .fdd-material,
        .sheet-table .sheet-center {
            text-align: center;
            white-space: nowrap;
        }

        .sheet-table .sheet-text {
            min-width: 240px;
            text-align: center;
        }

        .fdd-table .fdd-section-row td {
            background: #fff;
        }

        .fdd-table .fdd-section-name {
            background: #ffff00;
            font-size: 18px;
            font-weight: 800;
            text-align: center;
        }

        .table-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 130px;
        }

        .table-actions form {
            margin: 0;
        }

        .detail-item {
            padding: 16px;
        }

        .detail-item span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .detail-item strong {
            display: block;
            margin-top: 8px;
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        .calendar-card {
            overflow: hidden;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(120px, 1fr));
            overflow-x: auto;
        }

        .calendar-head,
        .calendar-day,
        .calendar-empty {
            min-height: 112px;
            padding: 12px;
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .calendar-head {
            min-height: auto;
            background: #f8fafc;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .calendar-empty {
            background: #f8fafc;
        }

        .calendar-date {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-weight: 800;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            margin-top: 10px;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            text-transform: capitalize;
        }

        .status-present {
            background: #dcfce7;
            color: #166534;
        }

        .status-leave {
            background: #fef3c7;
            color: #92400e;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-half_day {
            background: #dbeafe;
            color: #1e3a8a;
        }

        .status-empty {
            background: #e2e8f0;
            color: #475569;
        }

        .status-open {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in_progress {
            background: #dbeafe;
            color: #1e3a8a;
        }

        .status-approved,
        .status-resolved {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected,
        .status-closed {
            background: #e2e8f0;
            color: #475569;
        }

        .calendar-meta {
            margin-top: 8px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
            white-space: normal;
        }

        @media (max-width: 900px) {
            .admin-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                height: auto;
            }

            .nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                align-items: start;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .detail-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sheet-summary-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 0 18px;
            }

            .main {
                padding: 22px 18px;
            }
        }

        @media (max-width: 640px) {
            .page-header,
            .topbar {
                align-items: flex-start;
                flex-direction: column;
                justify-content: center;
                padding-top: 14px;
                padding-bottom: 14px;
            }

            .nav,
            .stats-grid,
            .detail-grid,
            .form-grid,
            .form-grid.three {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            .employees-table-wrap {
                overflow-x: visible;
            }

            .employees-table,
            .employees-table tbody,
            .employees-table tr,
            .employees-table td {
                display: block;
                width: 100%;
            }

            .employees-table thead,
            .employees-table colgroup {
                display: none;
            }

            .employees-table tbody {
                display: grid;
                gap: 12px;
                padding: 12px;
            }

            .employees-table tr {
                border: 1px solid var(--line);
                border-radius: 8px;
                background: #fff;
                overflow: hidden;
            }

            .employees-table td {
                display: grid;
                grid-template-columns: 96px minmax(0, 1fr);
                gap: 12px;
                padding: 12px;
            }

            .employees-table td::before {
                content: attr(data-label);
                color: var(--muted);
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .employees-table td:last-child {
                grid-template-columns: 1fr;
            }

            .employees-table td:last-child::before {
                display: none;
            }

            .pagination-nav {
                align-items: flex-start;
                flex-direction: column;
            }

            .pagination-summary {
                white-space: normal;
            }

            .pagination-links {
                justify-content: flex-start;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">A</div>
                <div>
                    <strong>Attendance Admin</strong>
                    <span>Employee management</span>
                </div>
            </div>

            <nav class="nav">
                @foreach (app(\App\Support\AdminNavigation::class)->groups(request()) as $group)
                    <details class="nav-group {{ $group['active'] ? 'active' : '' }}" {{ $group['active'] ? 'open' : '' }}>
                        <summary>{{ $group['label'] }}</summary>
                        <div class="nav-group-links">
                            @foreach ($group['items'] as $item)
                                <a class="{{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </nav>
        </aside>

        <div class="content-shell">
            <header class="topbar">
                <div class="topbar-title">
                    <strong>@yield('headerTitle', 'Admin Panel')</strong>
                    <span>@yield('headerSubtitle', 'Manage attendance app data')</span>
                </div>
                <div class="admin-user">
                    <div class="avatar">AD</div>
                    <span>{{ session('admin_email', 'Admin') }}</span>
                    <form class="logout-form" method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="logout-button" type="submit">Logout</button>
                    </form>
                </div>
            </header>

            <main class="main">
                @if (session('error'))
                    <div class="alert-error">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>

            <footer class="footer">
                Attendance Admin Panel - {{ now()->format('Y') }}
            </footer>
        </div>
    </div>
</body>
</html>
