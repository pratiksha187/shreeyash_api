<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel')</title>
    <script>
        try {
            if (localStorage.getItem('adminSidebarCollapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (exception) {
            /* localStorage may be unavailable in private browsing. */
        }
    </script>
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
            transition: grid-template-columns 180ms ease;
        }

        .sidebar {
            position: sticky;
            top: 0;
            z-index: 20;
            height: 100vh;
            overflow-y: auto;
            padding: 22px 16px;
            background: var(--sidebar);
            color: #f8fafc;
            transition: padding 180ms ease, transform 180ms ease, visibility 180ms ease;
        }

        .sidebar-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 8px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            flex: 0 0 auto;
            padding: 0;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            cursor: pointer;
            font-weight: 900;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .sidebar-toggle:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .sidebar-toggle-icon {
            position: relative;
            display: block;
            width: 18px;
            height: 14px;
        }

        .sidebar-toggle-icon::before,
        .sidebar-toggle-icon::after,
        .sidebar-toggle-icon span {
            position: absolute;
            left: 0;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
            content: '';
        }

        .sidebar-toggle-icon::before {
            top: 0;
        }

        .sidebar-toggle-icon span {
            top: 5px;
        }

        .sidebar-toggle-icon::after {
            bottom: 0;
        }

        .sidebar-close-icon {
            position: relative;
            display: block;
            width: 15px;
            height: 15px;
        }

        .sidebar-close-icon::before,
        .sidebar-close-icon::after {
            position: absolute;
            top: 6px;
            left: 0;
            width: 15px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
            content: '';
        }

        .sidebar-close-icon::before {
            transform: rotate(45deg);
        }

        .sidebar-close-icon::after {
            transform: rotate(-45deg);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            padding: 0;
        }

        .sidebar .sidebar-toggle {
            width: 42px;
            height: 42px;
            border-color: rgba(255, 255, 255, 0.16);
            background: rgba(15, 23, 42, 0.4);
            color: #f8fafc;
        }

        .sidebar .sidebar-toggle:hover {
            background: var(--sidebar-soft);
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
            justify-content: space-between;
            gap: 10px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        .nav-item-label {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nav-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: #f97316;
            color: #fff;
            font-size: 12px;
            font-weight: 900;
            line-height: 1;
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

        html.sidebar-collapsed .admin-shell {
            grid-template-columns: 76px minmax(0, 1fr);
        }

        html.sidebar-collapsed .sidebar {
            padding: 18px 10px;
            transform: none;
        }

        html.sidebar-collapsed .sidebar-head {
            align-items: center;
            flex-direction: column;
            gap: 14px;
            padding: 4px 0 18px;
        }

        html.sidebar-collapsed .brand {
            justify-content: center;
        }

        html.sidebar-collapsed .brand strong,
        html.sidebar-collapsed .brand span {
            display: none;
        }

        html.sidebar-collapsed .nav {
            gap: 10px;
            margin-top: 18px;
        }

        html.sidebar-collapsed .nav-group {
            border-color: rgba(255, 255, 255, 0.12);
            background: transparent;
        }

        html.sidebar-collapsed .nav-group summary {
            justify-content: center;
            min-height: 44px;
            padding: 0;
            border-radius: 8px;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        html.sidebar-collapsed .nav-group summary span {
            display: none;
        }

        html.sidebar-collapsed .nav-group summary::before {
            content: attr(data-short);
            color: #f8fafc;
            font-size: 13px;
            font-weight: 900;
        }

        html.sidebar-collapsed .nav-group summary::after,
        html.sidebar-collapsed .nav-group-links {
            display: none;
        }

        html.sidebar-collapsed .nav-group.active summary,
        html.sidebar-collapsed .nav-group summary:hover {
            background: var(--sidebar-soft);
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

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
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

        .employee-search-field {
            grid-column: span 2;
        }

        .employee-actions {
            display: grid;
            gap: 8px;
        }

        .employee-actions .btn,
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

        .help-text {
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
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
        .inline-status-form input,
        .inline-status-form textarea {
            min-height: 36px;
            padding: 7px 9px;
            font-size: 13px;
        }

        .inline-time-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .inline-time-fields label {
            display: grid;
            gap: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .inline-status-form textarea {
            min-height: 64px;
        }

        .inline-status-form .btn {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 13px;
        }

        .today-attendance-page .main {
            max-width: none;
        }

        .today-attendance-table-wrap {
            overflow-x: visible;
        }

        .today-attendance-table th,
        .today-attendance-table td {
            white-space: normal;
            vertical-align: top;
        }

        .today-attendance-table tbody tr:hover td {
            background: #f8fbff;
        }

        @media (max-width: 760px) {
            .today-attendance-table,
            .today-attendance-table tbody,
            .today-attendance-table tr,
            .today-attendance-table td {
                display: block;
                width: 100%;
            }

            .today-attendance-table thead {
                display: none;
            }

            .today-attendance-table tbody {
                display: grid;
                gap: 12px;
                padding: 12px;
            }

            .today-attendance-table tr {
                border: 1px solid var(--line);
                border-radius: 8px;
                background: #fff;
                overflow: hidden;
            }

            .today-attendance-table td {
                display: grid;
                grid-template-columns: 104px minmax(0, 1fr);
                gap: 12px;
                padding: 12px;
            }

            .today-attendance-table td::before {
                content: attr(data-label);
                color: var(--muted);
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
            }
        }

        .missed-requests-page .main {
            max-width: none;
        }

        .vehicle-show-page .main {
            max-width: none;
        }

        .missed-requests-table-wrap {
            overflow-x: visible;
        }

        .missed-requests-table {
            table-layout: fixed;
        }

        .missed-requests-table .missed-toggle-column {
            width: 7%;
        }

        .missed-requests-table .missed-date-column {
            width: 10%;
        }

        .missed-requests-table .missed-employee-column {
            width: 16%;
        }

        .missed-requests-table .missed-type-column {
            width: 9%;
        }

        .missed-requests-table .missed-reason-column {
            width: 28%;
        }

        .missed-requests-table .missed-status-column {
            width: 11%;
        }

        .missed-requests-table .missed-submitted-column {
            width: 19%;
        }

        .missed-requests-table th,
        .missed-requests-table td {
            vertical-align: middle;
        }

        .missed-requests-table td:nth-child(3),
        .missed-requests-table td:nth-child(4),
        .missed-requests-table td:nth-child(5),
        .missed-requests-table td:nth-child(6) {
            white-space: normal;
        }

        .missed-requests-table .text-wrap {
            min-width: 0;
            max-width: none;
        }

        .missed-toggle-cell {
            padding-right: 4px;
            text-align: center;
        }

        .missed-toggle-button {
            width: 30px;
            height: 30px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
        }

        .missed-toggle-button:hover {
            background: var(--primary-dark);
        }

        .missed-action-row td {
            padding: 16px;
            background: #f8fafc;
            white-space: normal;
        }

        .missed-action-form {
            display: grid;
            grid-template-columns: 150px 230px 230px minmax(240px, 1fr) 120px;
            gap: 12px;
            align-items: end;
        }

        .missed-action-form label {
            display: grid;
            gap: 5px;
            min-width: 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .missed-action-form select,
        .missed-action-form input,
        .missed-action-form textarea {
            width: 100%;
            min-width: 0;
            min-height: 40px;
            padding: 8px 10px;
            font-size: 13px;
        }

        .missed-action-form textarea {
            height: 40px;
            min-height: 40px;
        }

        .admin-time-picker {
            display: grid;
            grid-template-columns: minmax(54px, 1fr) auto minmax(54px, 1fr) minmax(72px, 1fr);
            gap: 6px;
            align-items: center;
        }

        .admin-time-picker .time-separator {
            color: var(--ink);
            font-size: 16px;
            font-weight: 800;
            line-height: 1;
            text-align: center;
        }

        .sheet-time-picker {
            min-width: 210px;
        }

        .admin-time-text-wrap {
            position: relative;
            display: block;
            min-width: 140px;
        }

        .admin-time-text {
            width: 100%;
            padding-right: 34px !important;
        }

        .admin-time-text-wrap::before {
            position: absolute;
            top: 50%;
            right: 12px;
            width: 13px;
            height: 13px;
            border: 2px solid currentColor;
            border-radius: 50%;
            color: #111827;
            content: '';
            pointer-events: none;
            transform: translateY(-50%);
        }

        .admin-time-text-wrap::after {
            position: absolute;
            top: 50%;
            right: 18px;
            width: 5px;
            height: 6px;
            border-right: 2px solid currentColor;
            border-bottom: 2px solid currentColor;
            color: #111827;
            content: '';
            pointer-events: none;
            transform: translateY(-67%);
        }

        .missed-update-button {
            min-height: 40px;
        }

        @media (max-width: 1200px) {
            .missed-action-form {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .missed-note-field {
                grid-column: span 2;
            }
        }

        @media (max-width: 700px) {
            .missed-action-form {
                grid-template-columns: 1fr;
            }

            .missed-note-field {
                grid-column: auto;
            }
        }

        .leave-requests-page .main {
            max-width: none;
        }

        .leave-requests-table-wrap {
            overflow-x: auto;
        }

        .leave-requests-table {
            table-layout: fixed;
            min-width: 1180px;
        }

        .leave-requests-table .leave-employee-column {
            width: 16%;
        }

        .leave-requests-table .leave-date-column {
            width: 12%;
        }

        .leave-requests-table .leave-type-column {
            width: 10%;
        }

        .leave-requests-table .leave-status-column {
            width: 9%;
        }

        .leave-requests-table .leave-year-column {
            width: 14%;
        }

        .leave-requests-table .leave-remarks-column {
            width: 23%;
        }

        .leave-requests-table .leave-action-column {
            width: 16%;
        }

        .leave-requests-table th,
        .leave-requests-table td {
            padding: 16px 14px;
            vertical-align: top;
            white-space: normal;
        }

        .leave-requests-table tbody tr:hover td {
            background: #f8fbff;
        }

        .leave-remarks-cell {
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .leave-reason {
            color: #0f172a;
            font-weight: 700;
        }

        .leave-action-form {
            display: grid;
            gap: 7px;
            margin: 0;
        }

        .leave-action-form label {
            margin-bottom: 0;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .leave-action-form select,
        .leave-action-form input {
            min-height: 36px;
            padding: 7px 10px;
            font-size: 13px;
        }

        .leave-action-buttons {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7px;
            margin-top: 3px;
        }

        .leave-action-buttons .btn {
            width: 100%;
            min-height: 36px;
            padding-right: 8px;
            padding-left: 8px;
        }

        .leave-approve-button {
            background: #16a34a;
        }

        .leave-approve-button:hover {
            background: #15803d;
        }

        .leave-filter-action {
            align-self: end;
        }

        @media (max-width: 1180px) {
            .leave-requests-table-wrap {
                overflow-x: visible;
            }

            .leave-requests-table,
            .leave-requests-table tbody,
            .leave-requests-table tr,
            .leave-requests-table td {
                display: block;
                width: 100%;
            }

            .leave-requests-table thead,
            .leave-requests-table colgroup {
                display: none;
            }

            .leave-requests-table tbody {
                display: grid;
                gap: 14px;
                padding: 12px;
            }

            .leave-requests-table tr {
                border: 1px solid var(--line);
                border-radius: 8px;
                background: #fff;
                overflow: hidden;
            }

            .leave-requests-table td {
                display: grid;
                grid-template-columns: 118px minmax(0, 1fr);
                gap: 12px;
                padding: 12px;
            }

            .leave-requests-table td::before {
                content: attr(data-label);
                color: var(--muted);
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .leave-action-cell {
                grid-template-columns: 1fr;
                padding: 14px;
                background: #f8fafc;
            }

            .leave-action-cell::before {
                display: none;
            }

            .leave-action-form {
                min-width: 0;
            }

            .leave-action-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                align-items: end;
            }

            .leave-action-form label {
                margin-bottom: -3px;
            }

            .leave-action-buttons {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 640px) {
            .leave-requests-table tbody {
                padding: 10px;
            }

            .leave-requests-table td {
                grid-template-columns: 96px minmax(0, 1fr);
            }

            .leave-action-form {
                grid-template-columns: 1fr;
            }
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

        .password-eye-wrap {
            position: relative;
        }

        .password-eye-wrap input {
            padding-right: 48px;
        }

        .password-eye-button {
            position: absolute;
            top: 50%;
            right: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            padding: 0;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #334155;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .password-eye-button:hover {
            background: #f1f5f9;
        }

        .password-eye-icon {
            position: relative;
            display: block;
            width: 18px;
            height: 12px;
            border: 2px solid currentColor;
            border-radius: 999px / 680px;
        }

        .password-eye-icon::after {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            content: '';
            transform: translate(-50%, -50%);
        }

        .password-eye-button.is-visible .password-eye-icon::before {
            position: absolute;
            top: 50%;
            left: -3px;
            width: 22px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
            content: '';
            transform: rotate(-35deg);
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

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(160px, 1fr));
            gap: 10px;
        }

        .checkbox-grid.compact {
            grid-template-columns: repeat(2, minmax(150px, 1fr));
            margin-bottom: 10px;
        }

        .checkbox-option {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            margin: 0;
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #f8fafc;
            color: #1f2937;
            font-size: 13px;
            font-weight: 800;
        }

        .checkbox-option input {
            width: 16px;
            min-height: 16px;
            height: 16px;
            padding: 0;
            flex: 0 0 auto;
        }

        .module-permission-form {
            min-width: 360px;
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

        .vehicle-sheet-table {
            width: 100%;
            min-width: 1320px;
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

        @media print {
            body.vehicle-show-page {
                background: #fff;
            }

            .vehicle-show-page .sidebar,
            .vehicle-show-page .topbar,
            .vehicle-show-page .report-filter,
            .vehicle-show-page .sheet-actions,
            .vehicle-show-page .no-print,
            .vehicle-show-page .alert-success,
            .vehicle-show-page .alert-error,
            .vehicle-show-page .page-header .actions,
            .vehicle-show-page footer {
                display: none !important;
            }

            .vehicle-show-page .admin-shell {
                display: block;
            }

            .vehicle-show-page .content,
            .vehicle-show-page .main {
                margin: 0;
                padding: 0;
                max-width: none;
                width: 100%;
            }

            .vehicle-show-page .page-header {
                margin: 0 0 10px;
                padding: 0;
                break-after: avoid;
            }

            .vehicle-show-page .detail-grid,
            .vehicle-show-page .stats-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 6px;
                margin-bottom: 10px;
            }

            .vehicle-show-page .card,
            .vehicle-show-page .table-wrap {
                border: 0;
                box-shadow: none;
            }

            .vehicle-show-page .vehicle-sheet-table {
                min-width: 0;
                width: 100%;
                font-size: 10px;
            }

            .vehicle-show-page .vehicle-sheet-table th,
            .vehicle-show-page .vehicle-sheet-table td {
                padding: 4px;
                border: 1px solid #111827;
            }

            .vehicle-show-page .vehicle-sheet-table th {
                background: #f3f4f6 !important;
                color: #111827 !important;
            }

            .vehicle-show-page input,
            .vehicle-show-page textarea,
            .vehicle-show-page select {
                border: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                padding: 0 !important;
                min-width: 0 !important;
                width: 100% !important;
                font-size: inherit !important;
            }

            .vehicle-show-page .admin-time-text-wrap::after {
                display: none;
            }

            .vehicle-show-page .sheet-summary-grid {
                grid-template-columns: 1fr 1fr;
                gap: 18px;
                page-break-inside: avoid;
            }
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

        .employee-calendar-title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid var(--line);
        }

        .employee-calendar-title h3 {
            margin: 0;
            font-size: 18px;
        }

        .employee-calendar-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px 14px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .employee-calendar-legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .legend-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: #94a3b8;
        }

        .legend-dot.present {
            background: #16a34a;
        }

        .legend-dot.completed-hours {
            background: #0f766e;
        }

        .legend-dot.late-short {
            background: #f97316;
        }

        .legend-dot.leave {
            background: #d97706;
        }

        .legend-dot.absent {
            background: #dc2626;
        }

        .legend-dot.half-day {
            background: #2563eb;
        }

        .legend-dot.holiday {
            background: #7c3aed;
        }

        .legend-dot.sunday {
            background: #e11d48;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(120px, 1fr));
            overflow-x: auto;
        }

        .calendar-head,
        .calendar-day,
        .calendar-empty {
            min-height: 142px;
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

        .calendar-sunday-head {
            color: #be123c;
            background: #fff1f2;
        }

        .calendar-empty {
            background: #f8fafc;
        }

        .calendar-sunday {
            background: #fff7f7;
        }

        .calendar-holiday {
            background: #f5f3ff;
        }

        .calendar-date {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-weight: 800;
        }

        .calendar-date small {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .calendar-sunday .calendar-date {
            color: #be123c;
        }

        .calendar-sunday .calendar-date small {
            color: #e11d48;
        }

        .calendar-holiday .calendar-date {
            color: #5b21b6;
        }

        .calendar-holiday .calendar-date small {
            color: #7c3aed;
        }

        .calendar-note {
            margin-top: 8px;
            color: #4c1d95;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.35;
        }

        .calendar-time-grid {
            display: grid;
            gap: 4px;
            margin-top: 8px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            line-height: 1.25;
        }

        .calendar-time-grid span {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .calendar-time-grid strong {
            color: #0f172a;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .calendar-time-note {
            margin-top: 6px;
            color: #9a3412;
            font-size: 11px;
            font-weight: 900;
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

        .status-completed_hours {
            background: #ccfbf1;
            color: #115e59;
        }

        .status-late_short,
        .status-short_hours {
            background: #ffedd5;
            color: #9a3412;
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

        .status-sunday {
            background: #ffe4e6;
            color: #be123c;
        }

        .status-holiday {
            background: #ede9fe;
            color: #5b21b6;
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
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                width: min(82vw, 300px);
                height: 100vh;
                box-shadow: 0 20px 50px rgba(15, 23, 42, 0.35);
                transform: translateX(-100%);
                visibility: hidden;
                pointer-events: none;
            }

            html.sidebar-open .sidebar {
                visibility: visible;
                pointer-events: auto;
                transform: translateX(0);
            }

            html.sidebar-collapsed .admin-shell {
                grid-template-columns: 1fr;
            }

            html.sidebar-collapsed .sidebar {
                padding: 22px 16px;
                transform: translateX(-100%);
            }

            html.sidebar-open .sidebar {
                transform: translateX(0);
            }

            html.sidebar-collapsed .sidebar-head {
                align-items: flex-start;
                flex-direction: row;
                padding: 8px 8px 24px;
            }

            html.sidebar-collapsed .nav {
                display: grid;
            }

            html.sidebar-collapsed .nav-group summary {
                justify-content: space-between;
                min-height: 38px;
                padding: 10px 12px;
                letter-spacing: 0.08em;
            }

            html.sidebar-collapsed .nav-group summary span,
            html.sidebar-collapsed .nav-group-links {
                display: grid;
            }

            html.sidebar-collapsed .nav-group summary::before {
                display: none;
            }

            html.sidebar-collapsed .nav-group summary::after {
                display: block;
            }

            html.sidebar-collapsed .brand strong,
            html.sidebar-collapsed .brand span {
                display: block;
            }

            html.sidebar-collapsed .brand {
                justify-content: flex-start;
            }

            .nav {
                grid-template-columns: 1fr;
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

            .topbar-left,
            .admin-user {
                width: 100%;
            }

            .nav,
            .stats-grid,
            .detail-grid,
            .form-grid,
            .form-grid.three,
            .checkbox-grid,
            .checkbox-grid.compact {
                grid-template-columns: 1fr;
            }

            .module-permission-form {
                min-width: 0;
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
<body class="@yield('bodyClass')">
    <div class="admin-shell">
        <aside class="sidebar" id="admin-sidebar">
            <div class="sidebar-head">
                <div class="brand">
                    <div class="brand-mark">A</div>
                    <div>
                        <strong>Attendance Admin</strong>
                        <span>Employee management</span>
                    </div>
                </div>
                <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="admin-sidebar" aria-label="Toggle sidebar">
                    <span class="sidebar-toggle-icon" aria-hidden="true"><span></span></span>
                </button>
            </div>

            <nav class="nav">
                @foreach (app(\App\Support\AdminNavigation::class)->groups(request()) as $group)
                    <details class="nav-group {{ $group['active'] ? 'active' : '' }}" {{ $group['active'] ? 'open' : '' }}>
                        @php
                            $shortLabel = collect(explode(' ', $group['label']))
                                ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                                ->implode('');
                        @endphp
                        <summary data-short="{{ $shortLabel }}" title="{{ $group['label'] }}">
                            <span>{{ $group['label'] }}</span>
                        </summary>
                        <div class="nav-group-links">
                            @foreach ($group['items'] as $item)
                                <a class="{{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}">
                                    <span class="nav-item-label">{{ $item['label'] }}</span>
                                    @if (! empty($item['badge']))
                                        <span class="nav-badge">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </nav>
        </aside>

        <div class="content-shell">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="admin-sidebar" aria-label="Open sidebar">
                        <span class="sidebar-toggle-icon" aria-hidden="true"><span></span></span>
                    </button>
                    <div class="topbar-title">
                        <strong>@yield('headerTitle', 'Admin Panel')</strong>
                        <span>@yield('headerSubtitle', 'Manage attendance app data')</span>
                    </div>
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
    <script>
        (function () {
            var root = document.documentElement;
            var mobileQuery = window.matchMedia('(max-width: 900px)');

            function isMobile() {
                return mobileQuery.matches;
            }

            function setSidebarState(open) {
                if (isMobile()) {
                    root.classList.toggle('sidebar-open', open);
                    root.classList.remove('sidebar-collapsed');
                    return;
                }

                root.classList.toggle('sidebar-collapsed', ! open);
                root.classList.remove('sidebar-open');

                try {
                    localStorage.setItem('adminSidebarCollapsed', open ? '0' : '1');
                } catch (exception) {
                    /* localStorage may be unavailable in private browsing. */
                }
            }

            function sidebarIsOpen() {
                return isMobile()
                    ? root.classList.contains('sidebar-open')
                    : ! root.classList.contains('sidebar-collapsed');
            }

            document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setSidebarState(! sidebarIsOpen());
                });
            });

            mobileQuery.addEventListener('change', function () {
                root.classList.remove('sidebar-open');

                if (! isMobile()) {
                    try {
                        root.classList.toggle('sidebar-collapsed', localStorage.getItem('adminSidebarCollapsed') === '1');
                    } catch (exception) {
                        root.classList.remove('sidebar-collapsed');
                    }
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && isMobile()) {
                    root.classList.remove('sidebar-open');
                }
            });

            document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = document.getElementById(button.getAttribute('aria-controls'));

                    if (! input) {
                        return;
                    }

                    var shouldShow = input.type === 'password';

                    input.type = shouldShow ? 'text' : 'password';
                    button.classList.toggle('is-visible', shouldShow);
                    button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
                });
            });
        })();
    </script>
</body>
</html>
