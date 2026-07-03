<?php

$root = dirname(__DIR__);
$outDir = $root.DIRECTORY_SEPARATOR.'reports';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$outFile = $outDir.DIRECTORY_SEPARATOR.'attendance_api_project_report.docx';
$zipTarget = $outFile.'.build';
@unlink($zipTarget);

function x(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function p(string $text = '', string $style = 'Normal'): string
{
    $styleXml = $style !== 'Normal' ? '<w:pPr><w:pStyle w:val="'.x($style).'"/></w:pPr>' : '';
    return '<w:p>'.$styleXml.'<w:r><w:t xml:space="preserve">'.x($text).'</w:t></w:r></w:p>';
}

function bullet(string $text): string
{
    return '<w:p><w:pPr><w:pStyle w:val="Bullet"/></w:pPr><w:r><w:t xml:space="preserve">'.x($text).'</w:t></w:r></w:p>';
}

function cell(string $text, int $width, bool $header = false): string
{
    $fill = $header ? '<w:shd w:fill="F2F4F7"/>' : '';
    $boldOpen = $header ? '<w:b/>' : '';
    return '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'.$fill.
        '<w:tcMar><w:top w:w="80" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tcMar></w:tcPr>'.
        '<w:p><w:r><w:rPr>'.$boldOpen.'</w:rPr><w:t xml:space="preserve">'.x($text).'</w:t></w:r></w:p></w:tc>';
}

function table(array $headers, array $rows, array $widths): string
{
    $grid = '';
    foreach ($widths as $width) {
        $grid .= '<w:gridCol w:w="'.$width.'"/>';
    }

    $xml = '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/><w:tblInd w:w="120" w:type="dxa"/><w:tblBorders>'.
        '<w:top w:val="single" w:sz="4" w:space="0" w:color="D9E2EC"/>'.
        '<w:left w:val="single" w:sz="4" w:space="0" w:color="D9E2EC"/>'.
        '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="D9E2EC"/>'.
        '<w:right w:val="single" w:sz="4" w:space="0" w:color="D9E2EC"/>'.
        '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="D9E2EC"/>'.
        '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="D9E2EC"/>'.
        '</w:tblBorders></w:tblPr><w:tblGrid>'.$grid.'</w:tblGrid>';

    $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
    foreach ($headers as $i => $header) {
        $xml .= cell($header, $widths[$i], true);
    }
    $xml .= '</w:tr>';

    foreach ($rows as $row) {
        $xml .= '<w:tr>';
        foreach ($row as $i => $value) {
            $xml .= cell($value, $widths[$i] ?? 1800);
        }
        $xml .= '</w:tr>';
    }

    return $xml.'</w:tbl>'.p('');
}

$moduleRows = [
    ['Admin / SaaS', 'Companies, subscriptions, tenant database provisioning, company admins, per-user module permissions.', 'Company, CompanySubscription, SubscriptionPlan, User'],
    ['HR', 'Employees, mobile attendance, today/monthly reports, leave approvals, missed attendance requests, payments.', 'User, Attendance, Payment, MissedAttendanceRequest'],
    ['Labour', 'Site, contractor, labour master and labour attendance with engineer, photo, status and payment-ready records.', 'LabourSite, Contractor, Labour, LabourAttendance'],
    ['Site Work', 'Daily progress reports with hourly rows and photos; complaints and challan PDF flow.', 'DailyProgressReport, DailyProgressReportHour, DailyProgressReportPhoto, Complaint, Challan'],
    ['Quality', 'FDD road sections and test records; MIR file reports with export support.', 'FddRoadSection, FddTestRecord, MirFileReport'],
    ['Fleet', 'Vehicle master, monthly vehicle sheet, camper billing, driver master and vehicle driver attendance.', 'Vehicle, VehicleLog, VehicleDriver, VehicleDriverAttendance'],
    ['Purchase', 'Daily diesel purchase sheet, machinery diesel balance sheet, product purchase sheet with PCS/weight/rate calculation.', 'DailyDieselPurchase, DailyDieselPurchaseSiteEntry, MachineryDieselLog, ProductPurchase'],
    ['Stock', 'Material master, stock balances, material requests, issues, and stock movements ledger.', 'Material, MaterialStock, MaterialRequest, MaterialIssue, StockMovement'],
];

$relationshipRows = [
    ['Company', 'hasMany users, subscriptions; hasOne activeSubscription', 'Central company record. Tenant DB name is stored here.'],
    ['User', 'belongsTo company; hasMany attendances, payments, DPRs, complaints, challans, missed requests, labour attendances', 'Used for employees and company admins. Admin permissions live on this model.'],
    ['Attendance', 'belongsTo user, company', 'Mobile employee attendance, leave status and local check-in/out helpers.'],
    ['Payment', 'belongsTo user, company', 'Salary slip records and generated PDF path.'],
    ['LabourSite', 'hasMany contractors, labourAttendances; belongsTo company', 'Site master shared by labour, machinery diesel, stock and purchase flows.'],
    ['Contractor', 'belongsTo LabourSite; hasMany labours, labourAttendances', 'Contractor may be nullable after decoupling migration.'],
    ['Labour', 'belongsTo Contractor; hasMany labourAttendances', 'Labour master linked to attendance records.'],
    ['LabourAttendance', 'belongsTo engineer User, LabourSite, Contractor, Labour', 'Company-scoped attendance record for contractor labour.'],
    ['DailyProgressReport', 'belongsTo User; hasMany hours; hasManyThrough photos', 'DPR parent record. Allows multiple reports per day after later migration.'],
    ['DailyProgressReportHour', 'belongsTo DailyProgressReport; hasMany photos', 'Hourly work rows for DPR.'],
    ['DailyProgressReportPhoto', 'belongsTo DailyProgressReportHour', 'Stores photo path and public URL helpers.'],
    ['Vehicle', 'hasMany vehicleLogs, drivers, driverAttendances', 'Vehicle master and billing configuration.'],
    ['VehicleLog', 'belongsTo Vehicle', 'Daily/monthly vehicle sheet entries. Remarks also store split-session time metadata.'],
    ['VehicleDriver', 'belongsTo Vehicle; hasMany attendances', 'Driver master for vehicles with multiple drivers.'],
    ['VehicleDriverAttendance', 'belongsTo Vehicle and VehicleDriver', 'Driver attendance with date, status, time in/out and remarks.'],
    ['DailyDieselPurchase', 'hasMany siteEntries', 'Monthly daily diesel purchase parent row per date.'],
    ['DailyDieselPurchaseSiteEntry', 'belongsTo DailyDieselPurchase and LabourSite', 'Site-wise opening, supply, used, and balance.'],
    ['MachineryDieselLog', 'belongsTo engineer User and LabourSite', 'Auto-calculates issue, expected closing, difference and remarks before saving.'],
    ['Material', 'hasMany stocks, requests', 'Material master.'],
    ['MaterialStock', 'belongsTo Material and LabourSite', 'Current stock by material/site.'],
    ['MaterialRequest', 'belongsTo engineer User, LabourSite, Material; hasMany issues', 'Request workflow with approval and issue quantities.'],
    ['MaterialIssue', 'belongsTo MaterialRequest, Material, LabourSite, issuer User', 'Stock issue record.'],
    ['StockMovement', 'belongsTo Material and LabourSite', 'Ledger for purchase-in, issue-out and manual adjustments.'],
    ['ProductPurchase', 'belongsTo Material and stockSite LabourSite', 'Purchase row that can add stock.'],
    ['FddRoadSection', 'hasMany FddTestRecord', 'Quality master for FDD sections.'],
    ['FddTestRecord', 'belongsTo FddRoadSection', 'Quality test rows.'],
    ['CompanySubscription', 'belongsTo Company and SubscriptionPlan', 'Plan lifecycle and renewal history.'],
    ['SubscriptionPlan', 'hasMany CompanySubscription', 'Reusable subscription plan master.'],
];

$modelDetailRows = [
    ['Company', 'One customer/company using the system.', 'It separates data for each business and stores the company database name.', 'Users, subscriptions, tenant database.'],
    ['User', 'A person who can log in: employee, engineer, or company admin.', 'It controls login, mobile/API access, salary settings, permissions and employee profile information.', 'Company, attendance, payments, DPR, complaints, challans.'],
    ['Attendance', 'One employee attendance or leave record for one date.', 'It stores present/absent/leave status, check-in/out time and location.', 'User and company.'],
    ['Payment', 'One salary/payment slip for a date range.', 'It stores salary breakup, deductions, net payable and generated PDF path.', 'User and company.'],
    ['MissedAttendanceRequest', 'Employee request to correct or add missed attendance.', 'It supports approval/rejection flow for attendance mistakes.', 'User and company.'],
    ['LabourSite', 'A work site/project location.', 'It is reused across labour attendance, diesel, stock and purchase reporting.', 'Contractors, labour attendance, stock, machinery diesel.'],
    ['Contractor', 'A contractor working on a site.', 'It groups labour workers and their attendance.', 'Labour site, labours, labour attendance.'],
    ['Labour', 'A worker under a contractor.', 'It is the master record used when taking labour attendance.', 'Contractor and labour attendance.'],
    ['LabourAttendance', 'Daily attendance for one labour worker.', 'It records which engineer submitted attendance, site, contractor, labour, status, time and photo.', 'Engineer user, site, contractor, labour.'],
    ['DailyProgressReport', 'Daily site work report.', 'It is the main DPR entry for work done on a day.', 'User, hourly DPR rows and photos.'],
    ['DailyProgressReportHour', 'Hourly/work-detail line inside a DPR.', 'It stores work progress details by hour or activity.', 'DPR and photos.'],
    ['DailyProgressReportPhoto', 'Photo uploaded for a DPR activity.', 'It provides visual proof for DPR work.', 'DPR hour row.'],
    ['Complaint', 'Complaint or issue raised by a user.', 'It tracks category, priority, status and admin note.', 'User and company.'],
    ['Challan', 'Delivery/material/machinery challan record.', 'It stores challan number, party, vehicle, material/machine and PDF path.', 'User and company.'],
    ['FddRoadSection', 'Road section master for quality testing.', 'It groups FDD test records by road section.', 'FDD test records.'],
    ['FddTestRecord', 'Quality test record for FDD.', 'It stores test measurements and reporting/export data.', 'Road section and company.'],
    ['MirFileReport', 'MIR/material inspection report entry.', 'It stores material, quantity, unit and location details.', 'Company.'],
    ['Vehicle', 'Vehicle or machinery master record.', 'It stores vehicle number, owner/driver, billing rates and default vehicle settings.', 'Vehicle logs, drivers, driver attendance.'],
    ['VehicleLog', 'Daily vehicle sheet entry.', 'It stores challan, diesel, odometer, time, site and billing-related daily data.', 'Vehicle and company.'],
    ['VehicleDriver', 'Driver assigned to a vehicle.', 'It supports multiple drivers for one vehicle.', 'Vehicle and driver attendance.'],
    ['VehicleDriverAttendance', 'Attendance record for a vehicle driver.', 'It stores date, status, time in/out and remarks for a selected vehicle and driver.', 'Vehicle and vehicle driver.'],
    ['DailyDieselPurchase', 'Daily diesel purchase row.', 'It stores challan/camper, litres, rate and amount for each date.', 'Site-wise diesel balance entries.'],
    ['DailyDieselPurchaseSiteEntry', 'Site-wise diesel balance line.', 'It stores opening balance, today supply, used diesel and closing balance per site.', 'Diesel purchase and labour site.'],
    ['MachineryDieselLog', 'Diesel issue and balance for machinery.', 'It auto-calculates issue quantity, expected use, closing balance, difference and remarks.', 'Engineer user, labour site and company.'],
    ['Material', 'Material master item.', 'It stores material name, type, unit and minimum stock.', 'Stock, material requests.'],
    ['MaterialStock', 'Current stock balance for a material/site.', 'It shows available quantity and supports stock tracking.', 'Material and labour site.'],
    ['MaterialRequest', 'Material request raised from site/API.', 'It tracks requested, approved and issued quantity with status.', 'Engineer user, site, material, material issues.'],
    ['MaterialIssue', 'Issued material record.', 'It records quantity issued against a request and reduces stock.', 'Material request, material, site, issuer.'],
    ['StockMovement', 'Stock ledger entry.', 'It keeps history for stock in, stock out and adjustments.', 'Material, site, reference record.'],
    ['ProductPurchase', 'Product/material purchase entry.', 'It stores supplier, invoice, product, size, PCS/weight, rate and amount. It can also add stock.', 'Material and stock site.'],
    ['SubscriptionPlan', 'Subscription plan master.', 'It stores plan name, price, employee limit and features.', 'Company subscriptions.'],
    ['CompanySubscription', 'Company subscription history.', 'It stores active/expired plan period, amount, reference and notes.', 'Company and subscription plan.'],
    ['Location', 'Saved geofence/location master.', 'It stores latitude, longitude and allowed radius for attendance/location use.', 'Company.'],
];

$riskRows = [
    ['Tenant validation consistency', 'Medium', 'Most models use BelongsToCompany and tenant connection, but validation must consistently use Rule::exists($this->tenantTable(...)) with company scope. One issue was fixed in DailyDieselPurchaseController.'],
    ['Permission fallback breadth', 'Medium', 'AdminNavigation grants grouped fallback permissions. Review whether fallback to company_admin_permissions should apply for restricted company admins.'],
    ['Route duplication aliases', 'Low', 'API exposes duplicate spellings such as labour/labor, challan/challans and machine-diesel/machinery-diesel. Useful for compatibility, but document externally.'],
    ['Remarks as metadata storage', 'Medium', 'Vehicle split-session times and vehicle billing overrides are partly stored in remarks. Consider formal columns when these fields become reporting-critical.'],
    ['Manual SQL workflow', 'Medium', 'Project has migrations, but operational changes are sometimes done through phpMyAdmin. Keep a SQL change log aligned with migrations to prevent drift.'],
    ['Testing coverage', 'Medium', 'No dedicated tests were observed for calculation-heavy flows such as vehicle billing, machinery diesel, stock movements, salary slips and attendance rules.'],
];

$recommendations = [
    'Standardize tenant validation with a shared helper or FormRequest base class.',
    'Move calculation rules into service classes for vehicle billing, machinery diesel and salary/payment generation.',
    'Create a small automated test suite for critical calculations and permission filtering.',
    'Replace overloaded remarks metadata with explicit columns when reporting/export depends on it.',
    'Maintain a deployment SQL file for users who are not running migrations.',
    'Add a short module permission matrix for company admins so support can quickly set correct access.',
];

$body = '';
$body .= p('Attendance API / Admin ERP Project Report', 'Title');
$body .= p('Generated on 03 Jul 2026 from local source code in C:\\xampp\\htdocs\\attendance_api.', 'Subtitle');
$body .= p('This report summarizes the Laravel application structure, tenant/database design, model relationships, admin/API modules, and practical improvement recommendations.', 'Normal');

$body .= p('Executive Summary', 'Heading1');
$body .= p('The project is a Laravel 11 PHP application for employee attendance, labour attendance, site work reporting, fleet tracking, purchases, stock, quality records, complaints, challans, salary payments, and company subscription management. It has a browser-based admin panel and a mobile/API surface for employees and engineers.');
$body .= p('The strongest architectural pattern is company isolation through the BelongsToCompany trait and Tenant support classes. Many business models automatically use a tenant database connection when a company database is active, while super-admin and provisioning flows remain on the central database.');
$body .= p('The codebase is feature-rich and practical. The highest-risk areas are calculation-heavy modules, permission fallback behavior, and keeping tenant validation/database changes consistent when some changes are applied manually through SQL.');

$body .= p('Technology Snapshot', 'Heading1');
$body .= table(['Area', 'Details'], [
    ['Framework', 'Laravel 11.31+, PHP 8.2+'],
    ['Important packages', 'barryvdh/laravel-dompdf for PDF generation; Laravel Tinker for console inspection.'],
    ['UI layer', 'Blade templates under resources/views/admin with custom CSS.'],
    ['Admin route count', '103 admin routes observed through php artisan route:list --path=admin.'],
    ['API route count', '73 API routes observed through php artisan route:list --path=api.'],
    ['Authentication', 'Admin session middleware for web panel; API token middleware and AuthController for mobile/API users.'],
], [2500, 6860]);

$body .= p('Tenant and Database Architecture', 'Heading1');
$body .= bullet('Company records can have a dedicated database_name. TenantDatabaseManager creates/configures that database and runs migrations on the tenant connection.');
$body .= bullet('BelongsToCompany uses UsesTenantConnection, so company-scoped models automatically switch to the tenant connection when a company is selected.');
$body .= bullet('scopeForCurrentCompany filters company_id and prevents unrestricted company_admin access when no current company is set.');
$body .= bullet('Tenant provisioning syncs company, subscription plans, company subscriptions, and company admins into the tenant database.');
$body .= bullet('Central tables still matter for super-admin company management and subscription lifecycle.');

$body .= p('Admin Module Map', 'Heading1');
$body .= table(['Module', 'Purpose', 'Primary Models'], $moduleRows, [1700, 4300, 3360]);

$body .= p('All Model Details in Simple Words', 'Heading1');
$body .= p('This section explains every important model as a business record, so non-technical readers can understand what the system stores and how the records connect.');
$body .= table(['Model', 'Simple Meaning', 'Why It Matters', 'Connected With'], $modelDetailRows, [1700, 2400, 3300, 1960]);

$body .= p('Model Relationship Matrix', 'Heading1');
$body .= table(['Model', 'Relationships Found', 'Notes'], $relationshipRows, [2100, 4150, 3110]);

$body .= p('Route and Permission Overview', 'Heading1');
$body .= bullet('Navigation groups are configured in config/admin.php: Overview, HR, Purchase, Stock, Masters, Site Work, Quality, and Fleet.');
$body .= bullet('Company admin permissions include dashboard, HR records, labour attendance, driver attendance, site/contractor/labour master, payments, DPR, challans, quality files, fleet, diesel/purchase and stock modules.');
$body .= bullet('Route permissions map admin route patterns to module permission keys. The navigation is filtered through App\\Support\\AdminNavigation.');
$body .= bullet('The API provides attendance clock-in/out, leave, missed requests, challans, complaints, DPRs, labour attendance, machinery diesel, material requests and payments.');

$body .= p('Important Business Logic', 'Heading1');
$body .= table(['Area', 'Observed Logic'], [
    ['Attendance', 'Employee attendance stores check-in/out, geolocation, status, leave approval and local timezone helpers.'],
    ['Labour attendance', 'Engineer submits site/contractor/labour attendance. Admin manages site, contractor and labour master records.'],
    ['Payments', 'Payment generation calculates salary components, deductions and PDF slip output.'],
    ['Vehicle sheet', 'Supports monthly entries, camper billing calculations, split first/second half time for non-camper vehicles and print view.'],
    ['Machinery diesel', 'Model saving hook calculates diesel issue, extra issue, expected use, closing balance, difference, tomorrow issue and auto remarks.'],
    ['Diesel purchase', 'Monthly purchase rows plus per-site opening/supply/used/balance sheet.'],
    ['Material stock', 'Stock service centralizes add/reduce operations and writes stock movement history.'],
    ['Product purchase', 'Amount calculation supports weight, PCS or quantity and can feed stock when linked to material/site.'],
], [2100, 7260]);

$body .= p('Database Table Groups', 'Heading1');
$body .= bullet('Core/admin: users, companies, subscription_plans, company_subscriptions, roles, sessions/cache/jobs.');
$body .= bullet('HR/payroll: attendances, missed_attendance_requests, payments.');
$body .= bullet('Labour: labour_sites, contractors, labours, labour_attendances.');
$body .= bullet('Site work/quality: daily_progress_reports, daily_progress_report_hours, daily_progress_report_photos, complaints, challans, fdd_road_sections, fdd_test_records, mir_file_reports.');
$body .= bullet('Fleet/purchase/diesel: vehicles, vehicle_logs, vehicle_drivers, vehicle_driver_attendances, daily_diesel_purchases, daily_diesel_purchase_site_entries, machinery_diesel_logs, product_purchases.');
$body .= bullet('Stock/material: materials, material_stocks, material_requests, material_issues, stock_movements.');

$body .= p('Findings and Risks', 'Heading1');
$body .= table(['Finding', 'Risk', 'Recommendation'], $riskRows, [2600, 1200, 5560]);

$body .= p('Recommended Next Steps', 'Heading1');
foreach ($recommendations as $recommendation) {
    $body .= bullet($recommendation);
}

$body .= p('Appendix: Key Files Reviewed', 'Heading1');
$body .= bullet('config/admin.php - permissions, navigation groups and route permission map.');
$body .= bullet('app/Support/Tenant.php and TenantDatabaseManager.php - tenant connection and database provisioning.');
$body .= bullet('app/Models and app/Models/Concerns - Eloquent models, company scoping and relationships.');
$body .= bullet('routes/web.php and routes/api.php - admin and API route surfaces.');
$body .= bullet('database/migrations - table design, foreign keys, indexes and later feature additions.');

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
    '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'.
    '<w:body>'.$body.
    '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'.
    '</w:body></w:document>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
    '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'.
    '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:pPr><w:spacing w:after="120" w:line="264" w:lineRule="auto"/></w:pPr><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:style>'.
    '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:pPr><w:spacing w:after="120"/></w:pPr><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:b/><w:color w:val="0B2545"/><w:sz w:val="40"/></w:rPr></w:style>'.
    '<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:pPr><w:spacing w:after="240"/></w:pPr><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:color w:val="64748B"/><w:sz w:val="22"/></w:rPr></w:style>'.
    '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="320" w:after="160"/></w:pPr><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:b/><w:color w:val="2E74B5"/><w:sz w:val="32"/></w:rPr></w:style>'.
    '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="240" w:after="120"/></w:pPr><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:b/><w:color w:val="2E74B5"/><w:sz w:val="26"/></w:rPr></w:style>'.
    '<w:style w:type="paragraph" w:styleId="Bullet"><w:name w:val="Bullet"/><w:basedOn w:val="Normal"/><w:pPr><w:ind w:left="540" w:hanging="270"/><w:spacing w:after="80"/></w:pPr><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:style>'.
    '</w:styles>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
    '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'.
    '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'.
    '<Default Extension="xml" ContentType="application/xml"/>'.
    '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'.
    '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'.
    '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'.
    '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'.
    '</Types>';

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'.
    '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'.
    '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'.
    '</Relationships>';

$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'.
    '</Relationships>';

$core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
    '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.
    '<dc:title>Attendance API / Admin ERP Project Report</dc:title><dc:creator>Codex</dc:creator><cp:lastModifiedBy>Codex</cp:lastModifiedBy>'.
    '<dcterms:created xsi:type="dcterms:W3CDTF">2026-07-03T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-07-03T00:00:00Z</dcterms:modified>'.
    '</cp:coreProperties>';

$app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
    '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'.
    '<Application>Codex OOXML Builder</Application></Properties>';

$zip = new ZipArchive();
$zip->open($zipTarget, ZipArchive::CREATE);
$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/styles.xml', $stylesXml);
$zip->addFromString('word/_rels/document.xml.rels', $docRels);
$zip->addFromString('docProps/core.xml', $core);
$zip->addFromString('docProps/app.xml', $app);
$zip->close();

$sourceFile = file_exists($zipTarget) ? $zipTarget : null;
if (! $sourceFile) {
    $candidates = glob($zipTarget.'.*') ?: [];
    usort($candidates, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));
    $sourceFile = $candidates[0] ?? null;
}

if (! $sourceFile || ! copy($sourceFile, $outFile)) {
    throw new RuntimeException('Could not create final DOCX report.');
}

echo $outFile.PHP_EOL;
