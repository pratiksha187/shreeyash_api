<?php

return [
    'email' => env('ADMIN_EMAIL', 'constructkaroadmin@gmail.com'),
    'password' => env('ADMIN_PASSWORD', 'constructkaroadmin123456'),
    'permissions' => env('ADMIN_PERMISSIONS', '*'),
    'super_admin_permissions' => [
        'dashboard',
        'companies',
    ],
    'company_admin_permissions' => [
        'dashboard',
        'employees',
        'attendance_reports',
        'missed_requests',
        'labour_attendance',
        'payments',
        'dpr_reports',
        'challans',
        'complaints',
        'fdd_test_records',
        'mir_file_reports',
        'vehicles',
        'diesel_purchases',
    ],

    'navigation' => [
        [
            'label' => 'ConstructKaro',
            'items' => [
                [
                    'key' => 'companies',
                    'label' => 'Companies',
                    'route' => 'admin.companies.index',
                    'active' => 'admin.companies.*',
                ],
            ],
        ],
        [
            'label' => 'Overview',
            'items' => [
                [
                    'key' => 'dashboard',
                    'label' => 'Dashboard',
                    'route' => 'admin.dashboard',
                    'active' => 'admin.dashboard',
                ],
            ],
        ],
        [
            'label' => 'HR',
            'items' => [
                [
                    'key' => 'employees',
                    'label' => 'Employees',
                    'route' => 'admin.employees.index',
                    'active' => 'admin.employees.*',
                ],
                [
                    'key' => 'attendance_reports',
                    'label' => 'Attendance Reports',
                    'route' => 'admin.attendance-reports.index',
                    'active' => 'admin.attendance-reports.*',
                ],
                [
                    'key' => 'missed_requests',
                    'label' => 'Missed Requests',
                    'route' => 'admin.missed-requests.index',
                    'active' => 'admin.missed-requests.*',
                ],
                [
                    'key' => 'labour_attendance',
                    'label' => 'Labour Attendance',
                    'route' => 'admin.labour-attendance.index',
                    'active' => 'admin.labour-attendance.*',
                ],
                [
                    'key' => 'payments',
                    'label' => 'Payments',
                    'route' => 'admin.payments.index',
                    'active' => 'admin.payments.*',
                ],
            ],
        ],
        [
            'label' => 'Site Work',
            'items' => [
                [
                    'key' => 'dpr_reports',
                    'label' => 'DPR Reports',
                    'route' => 'admin.dpr-reports.index',
                    'active' => 'admin.dpr-reports.*',
                ],
                [
                    'key' => 'challans',
                    'label' => 'Challans',
                    'route' => 'admin.challans.index',
                    'active' => 'admin.challans.*',
                ],
                [
                    'key' => 'complaints',
                    'label' => 'Complaints',
                    'route' => 'admin.complaints.index',
                    'active' => 'admin.complaints.*',
                ],
            ],
        ],
        [
            'label' => 'Quality',
            'items' => [
                [
                    'key' => 'fdd_test_records',
                    'label' => 'FDD Test Records',
                    'route' => 'admin.fdd-test-records.index',
                    'active' => 'admin.fdd-test-records.*',
                ],
                [
                    'key' => 'mir_file_reports',
                    'label' => 'MIR File Reports',
                    'route' => 'admin.mir-file-reports.index',
                    'active' => 'admin.mir-file-reports.*',
                ],
            ],
        ],
        [
            'label' => 'Fleet',
            'items' => [
                [
                    'key' => 'vehicles',
                    'label' => 'Vehicles',
                    'route' => 'admin.vehicles.index',
                    'active' => 'admin.vehicles.*',
                ],
                [
                    'key' => 'diesel_purchases',
                    'label' => 'Diesel Purchase',
                    'route' => 'admin.diesel-purchases.index',
                    'active' => 'admin.diesel-purchases.*',
                ],
            ],
        ],
    ],

    'route_permissions' => [
        'admin.dashboard' => 'dashboard',
        'admin.companies.*' => 'companies',
        'admin.attendance-reports.*' => 'attendance_reports',
        'admin.missed-requests.*' => 'missed_requests',
        'admin.labour-attendance.*' => 'labour_attendance',
        'admin.labour-sites.*' => 'labour_attendance',
        'admin.contractors.*' => 'labour_attendance',
        'admin.labours.*' => 'labour_attendance',
        'admin.dpr-reports.*' => 'dpr_reports',
        'admin.fdd-test-records.*' => 'fdd_test_records',
        'admin.fdd-road-sections.*' => 'fdd_test_records',
        'admin.mir-file-reports.*' => 'mir_file_reports',
        'admin.challans.*' => 'challans',
        'admin.complaints.*' => 'complaints',
        'admin.payments.*' => 'payments',
        'admin.diesel-purchases.*' => 'diesel_purchases',
        'admin.vehicles.*' => 'vehicles',
        'admin.employees.*' => 'employees',
    ],
];
