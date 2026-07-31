<?php

return [
    'monthly_report' => [
        'cc' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('MONTHLY_ATTENDANCE_REPORT_CC', 'Shreeyashconstructionjobs@gmail.com'))
        ))),
    ],
];
