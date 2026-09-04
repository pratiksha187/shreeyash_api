<?php

$outPath = __DIR__ . '/../reports/ConstructKaro_ERP_Client_Quotation_With_Mobile_App_AWS_Included.docx';

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "ZipArchive extension is not available.\n");
    exit(1);
}

if (! is_dir(dirname($outPath))) {
    mkdir(dirname($outPath), 0777, true);
}

function x(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function para(string $text = '', string $style = 'BodyText', string $align = 'left'): string
{
    return '<w:p><w:pPr><w:pStyle w:val="' . $style . '"/><w:jc w:val="' . $align . '"/></w:pPr><w:r><w:t xml:space="preserve">' . x($text) . '</w:t></w:r></w:p>';
}

function richPara(array $runs, string $style = 'BodyText', string $align = 'left'): string
{
    $xml = '<w:p><w:pPr><w:pStyle w:val="' . $style . '"/><w:jc w:val="' . $align . '"/></w:pPr>';

    foreach ($runs as $run) {
        $text = x((string) ($run['text'] ?? ''));
        $bold = ! empty($run['bold']) ? '<w:b/>' : '';
        $color = isset($run['color']) ? '<w:color w:val="' . $run['color'] . '"/>' : '';
        $size = isset($run['size']) ? '<w:sz w:val="' . ((int) $run['size'] * 2) . '"/>' : '';
        $xml .= '<w:r><w:rPr>' . $bold . $color . $size . '</w:rPr><w:t xml:space="preserve">' . $text . '</w:t></w:r>';
    }

    return $xml . '</w:p>';
}

function bulletItem(string $text): string
{
    return '<w:p><w:pPr><w:pStyle w:val="ListParagraph"/><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t xml:space="preserve">' . x($text) . '</w:t></w:r></w:p>';
}

function pageBreak(): string
{
    return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
}

function tableCell(string $text, int $width, bool $header = false, string $align = 'left', string $fill = ''): string
{
    $shd = $header ? '<w:shd w:fill="0B2545"/>' : ($fill ? '<w:shd w:fill="' . $fill . '"/>' : '');
    $color = $header ? '<w:color w:val="FFFFFF"/>' : '<w:color w:val="1F2937"/>';
    $bold = $header ? '<w:b/>' : '';

    return '<w:tc>'
        . '<w:tcPr><w:tcW w:w="' . $width . '" w:type="dxa"/><w:vAlign w:val="center"/>' . $shd . '</w:tcPr>'
        . '<w:p><w:pPr><w:jc w:val="' . $align . '"/></w:pPr><w:r><w:rPr>' . $bold . $color . '</w:rPr><w:t xml:space="preserve">' . x($text) . '</w:t></w:r></w:p>'
        . '</w:tc>';
}

function quoteTable(array $rows, array $widths, array $aligns = []): string
{
    $grid = '';
    foreach ($widths as $width) {
        $grid .= '<w:gridCol w:w="' . $width . '"/>';
    }

    $xml = '<w:tbl><w:tblPr>'
        . '<w:tblStyle w:val="TableGrid"/>'
        . '<w:tblW w:w="9360" w:type="dxa"/>'
        . '<w:tblInd w:w="120" w:type="dxa"/>'
        . '<w:tblLook w:firstRow="1" w:lastRow="0" w:firstColumn="0" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/>'
        . '</w:tblPr><w:tblGrid>' . $grid . '</w:tblGrid>';

    foreach ($rows as $rowIndex => $row) {
        $xml .= '<w:tr>';
        foreach ($row as $cellIndex => $value) {
            $fill = $rowIndex % 2 === 0 && $rowIndex !== 0 ? 'F8FAFC' : '';
            $xml .= tableCell((string) $value, $widths[$cellIndex], $rowIndex === 0, $aligns[$cellIndex] ?? 'left', $fill);
        }
        $xml .= '</w:tr>';
    }

    return $xml . '</w:tbl>' . para('');
}

function callout(string $label, string $text): string
{
    return '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/><w:tblInd w:w="120" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="6" w:color="93C5FD"/><w:left w:val="single" w:sz="18" w:color="2E74B5"/><w:bottom w:val="single" w:sz="6" w:color="93C5FD"/><w:right w:val="single" w:sz="6" w:color="93C5FD"/></w:tblBorders><w:tblCellMar><w:top w:w="140" w:type="dxa"/><w:left w:w="180" w:type="dxa"/><w:bottom w:w="140" w:type="dxa"/><w:right w:w="180" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid><w:gridCol w:w="9360"/></w:tblGrid><w:tr><w:tc><w:tcPr><w:tcW w:w="9360" w:type="dxa"/><w:shd w:fill="EFF6FF"/></w:tcPr>'
        . richPara([['text' => $label . ': ', 'bold' => true, 'color' => '0B2545'], ['text' => $text]], 'BodyText')
        . '</w:tc></w:tr></w:tbl>' . para('');
}

$modules = [
    'Admin dashboard and secure admin login',
    'Multi-company setup, company status, subscription renewal, and permission control',
    'Employee management, mobile application login, attendance clock-in and clock-out',
    'Leave requests, missed attendance approval, attendance reports, and export',
    'Labour site, contractor, labour master, labour attendance, and labour costing',
    'Vehicle management, driver attendance, vehicle maintenance, diesel purchase, and machinery diesel logs',
    'DPR reports with photos, challans with PDF, site reports, FDD, MIR, and complaints',
    'Material master, stock list, stock adjustment, material requests, and material issue workflow',
    'Safety store, safety items, safety requests, and safety issue workflow',
    'Suppliers, units, product purchase, purchase workflow, purchase order, and PDF generation',
    'Project management with project structure, tasks, BOQ import/export, and progress tracking',
    'Mobile application with API backend support',
    'WhatsApp message integration and PDF/report generation support',
];

$body = '';
$body .= para('COMMERCIAL QUOTATION', 'SmallCaps', 'center');
$body .= para('ConstructKaro ERP', 'Title', 'center');
$body .= para('Construction Attendance, Labour, Purchase, Inventory, Vehicle and Site Management System', 'Subtitle', 'center');
$body .= para('Quotation includes application setup, admin panel, mobile application, mobile API backend, support options, and AWS hosting on developer-managed AWS infrastructure.', 'Lead', 'center');
$body .= para('');
$body .= quoteTable([
    ['Quotation For', 'Construction company / contractor'],
    ['Prepared By', 'ConstructKaro ERP Developer'],
    ['Hosting Model', 'Application hosted on developer AWS account'],
    ['Currency', 'Indian Rupees (Rs.)'],
    ['Validity', '15 days from quotation date'],
], [2600, 6760]);

$body .= callout('Recommended Offer', 'Rs. 75,000 setup fee + Rs. 14,999/month including admin panel, mobile application, AWS hosting, standard support, backup monitoring, and all major modules.');

$body .= para('Scope of Software', 'Heading1');
$body .= para('The system is a construction ERP platform with admin panel and mobile application for managing site attendance, labour, purchases, inventory, vehicles, daily progress, challans, payments, safety store, reports, and project work.');
foreach ($modules as $module) {
    $body .= bulletItem($module);
}

$body .= para('Monthly Subscription Packages', 'Heading1');
$body .= para('These packages include software usage, admin panel access, mobile application access, mobile API backend, AWS hosting on the developer account, server maintenance, backup monitoring, and standard support within the selected package. No separate server charge is added to the client.');
$body .= quoteTable([
    ['Plan', 'Best For', 'Package Includes', 'Setup Fee', 'Monthly Fee'],
    ['Starter', 'Small contractor, 1-2 sites', 'Admin panel, mobile app, AWS hosting, backup check, basic support', 'Rs. 35,000', 'Rs. 7,999/month'],
    ['Growth', 'Medium contractor, up to 5 sites', 'Admin panel, mobile app, all main modules, AWS hosting, maintenance, monthly support', 'Rs. 60,000', 'Rs. 14,999/month'],
    ['Business', 'Multiple sites and departments', 'Admin panel, mobile app, all modules, AWS hosting, priority support, backup monitoring', 'Rs. 85,000', 'Rs. 24,999/month'],
    ['Enterprise', 'Large company with custom workflow', 'Admin panel, mobile app, custom hosting, support, reports, and workflow changes', 'Rs. 1,25,000+', 'Rs. 39,999/month+'],
], [1400, 2400, 2900, 1300, 1360], ['left', 'left', 'left', 'center', 'center']);

$body .= para('Plan Recommendation', 'Heading2');
$body .= bulletItem('For most clients, quote the Growth Plan: Rs. 60,000 setup + Rs. 14,999/month.');
$body .= bulletItem('For a serious multi-site construction company, quote the Business Plan: Rs. 85,000 setup + Rs. 24,999/month.');
$body .= bulletItem('The package price already includes AWS server hosting and normal maintenance.');
$body .= bulletItem('Do not mention AWS/server cost as a separate client charge.');

$body .= para('Hosting Included In Package', 'Heading1');
$body .= para('AWS hosting is included inside the package price. The client does not need to pay a separate server bill for normal usage. The package covers admin panel hosting, mobile application API hosting, server maintenance, normal storage, normal traffic, backup checks, and basic monitoring.');
$body .= quoteTable([
    ['Included Item', 'Details'],
    ['Application hosting', 'Laravel admin panel, mobile application API, and backend hosted on developer AWS account'],
    ['Server maintenance', 'Basic server updates, uptime checks, and technical monitoring'],
    ['Backup check', 'Regular backup monitoring for application/database data'],
    ['Normal storage and traffic', 'Included for regular business usage under the selected plan'],
    ['Support', 'Basic or priority support depending on selected package'],
], [3000, 6360], ['left', 'left']);

$body .= callout('Important Note', 'Server/AWS hosting is included in the package. Only domain, SMS, WhatsApp API, email service, payment gateway, or very high storage/traffic requirements may be quoted separately if required.');

$body .= pageBreak();
$body .= para('One-Time Sale Model', 'Heading1');
$body .= para('A one-time sale is suitable when the client wants fixed-cost deployment. In the packages below, first-year AWS hosting and maintenance are included inside the quoted package price.');
$body .= quoteTable([
    ['Package', 'Included Work', 'One-Time Price', 'Server Cost'],
    ['Basic Setup', 'Admin panel, mobile app, setup, branding, admin training, basic configuration, first-year hosting', 'Rs. 1,50,000', 'Included'],
    ['Standard ERP Setup', 'Admin panel, mobile app, full deployment, module setup, training, 1 month support, first-year hosting', 'Rs. 2,75,000', 'Included'],
    ['Premium ERP Setup', 'Admin panel, mobile app, full setup, custom reports, minor custom changes, 3 months support, first-year hosting', 'Rs. 4,75,000', 'Included'],
    ['Full Source Code Sale', 'Complete source code handover and ownership transfer terms', 'Rs. 7,50,000+', 'As per agreement'],
], [1900, 3300, 2100, 2060], ['left', 'left', 'center', 'center']);

$body .= para('Recommended One-Time Quote', 'Heading2');
$body .= bulletItem('Quote Rs. 4,75,000 for Premium ERP Setup if client wants serious business usage with first-year hosting included.');
$body .= bulletItem('Minimum one-time sale should not go below Rs. 2,75,000 if setup, training, and AWS hosting support are included.');
$body .= bulletItem('Full source code sale should start from Rs. 7,50,000 because the client gets the right to use or modify the full system.');

$body .= para('Customization Charges', 'Heading1');
$body .= quoteTable([
    ['Customization Work', 'Suggested Charge'],
    ['Small text, logo, color, or layout changes', 'Rs. 2,000 to Rs. 5,000'],
    ['New report or PDF format', 'Rs. 7,500 to Rs. 20,000'],
    ['New module or major workflow', 'Rs. 35,000 to Rs. 1,00,000'],
    ['Mobile application/API changes', 'Rs. 15,000 to Rs. 75,000'],
    ['WhatsApp/SMS/payment gateway integration', 'Rs. 15,000 to Rs. 50,000 plus vendor charges'],
    ['Data import from Excel', 'Rs. 5,000 to Rs. 25,000'],
    ['On-site training', 'Rs. 10,000 to Rs. 25,000/day plus travel'],
], [5200, 4160], ['left', 'center']);

$body .= para('Payment Terms', 'Heading1');
$body .= para('For Monthly Subscription', 'Heading2');
foreach ([
    '60% setup fee advance before deployment work starts.',
    '40% setup fee after live deployment.',
    'Monthly subscription is payable in advance.',
    'Service can be paused if payment is delayed beyond 7 days.',
    'AWS hosting is included only while monthly payment is active.',
] as $item) {
    $body .= bulletItem($item);
}
$body .= para('For One-Time Sale', 'Heading2');
foreach ([
    '50% advance before work starts.',
    '30% after demo/live deployment.',
    '20% before final handover.',
    'First-year AWS hosting is included in the package price.',
] as $item) {
    $body .= bulletItem($item);
}
$body .= para('For Source Code Sale', 'Heading2');
foreach ([
    '70% advance before source preparation.',
    '30% before final source code handover.',
    'Source code, database, and deployment credentials should be shared only after full payment.',
] as $item) {
    $body .= bulletItem($item);
}

$body .= para('Terms and Conditions', 'Heading1');
foreach ([
    'Monthly plan includes admin panel, mobile application access, software usage, AWS hosting, standard maintenance, and basic support as per selected package.',
    'Client data belongs to the client.',
    'Software ownership remains with the developer unless full source code sale is agreed in writing.',
    'Domain, SMS, WhatsApp API, email, payment gateway, and third-party vendor charges are extra only if required.',
    'Large photo storage, heavy downloads, bulk users, and very high traffic may require a package upgrade.',
    'Custom requirements not mentioned in this quotation will be charged separately.',
    'Training is provided online unless on-site training is separately agreed.',
    'Backup monitoring is included, but disaster recovery, high availability, and 24/7 support require an enterprise plan.',
] as $item) {
    $body .= bulletItem($item);
}

$body .= para('Final Suggested Quotation To Client', 'Heading1');
$body .= callout('Best Monthly Quote', 'Rs. 75,000 setup fee + Rs. 14,999/month including admin panel, mobile application, AWS hosting, support, and all major modules.');
$body .= callout('Best One-Time Quote', 'Rs. 4,75,000 one-time including admin panel, mobile application, first-year AWS hosting, and maintenance.');
$body .= callout('Minimum Safe Price', 'Do not go below Rs. 2,75,000 one-time or Rs. 9,999/month subscription when AWS hosting is included in your package.');

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 wp14">'
    . '<w:body>' . $body
    . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1080" w:right="1080" w:bottom="1080" w:left="1080" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
    . '</w:body></w:document>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/><w:color w:val="1F2937"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="300" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
    . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr><w:pPr><w:spacing w:after="120" w:line="300" w:lineRule="auto"/></w:pPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="BodyText"><w:name w:val="Body Text"/><w:basedOn w:val="Normal"/><w:qFormat/><w:pPr><w:spacing w:after="120" w:line="300" w:lineRule="auto"/></w:pPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="SmallCaps"><w:name w:val="Small Caps"/><w:basedOn w:val="Normal"/><w:qFormat/><w:pPr><w:spacing w:before="120" w:after="40"/></w:pPr><w:rPr><w:b/><w:caps/><w:color w:val="2E74B5"/><w:spacing w:val="18"/><w:sz w:val="22"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:qFormat/><w:pPr><w:spacing w:before="80" w:after="100"/></w:pPr><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:b/><w:color w:val="0B2545"/><w:sz w:val="60"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:qFormat/><w:pPr><w:spacing w:after="120"/></w:pPr><w:rPr><w:color w:val="334155"/><w:sz w:val="26"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Lead"><w:name w:val="Lead"/><w:basedOn w:val="Normal"/><w:qFormat/><w:pPr><w:spacing w:after="220" w:line="320" w:lineRule="auto"/></w:pPr><w:rPr><w:color w:val="475569"/><w:sz w:val="23"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="BodyText"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="300" w:after="150"/></w:pPr><w:rPr><w:b/><w:color w:val="2E74B5"/><w:sz w:val="32"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="BodyText"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="220" w:after="100"/></w:pPr><w:rPr><w:b/><w:color w:val="1F4D78"/><w:sz w:val="26"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="ListParagraph"><w:name w:val="List Paragraph"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="70" w:line="290" w:lineRule="auto"/><w:ind w:left="540" w:hanging="260"/></w:pPr></w:style>'
    . '<w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="5" w:space="0" w:color="CBD5E1"/><w:left w:val="single" w:sz="5" w:space="0" w:color="CBD5E1"/><w:bottom w:val="single" w:sz="5" w:space="0" w:color="CBD5E1"/><w:right w:val="single" w:sz="5" w:space="0" w:color="CBD5E1"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/></w:tblBorders><w:tblCellMar><w:top w:w="110" w:type="dxa"/><w:left w:w="140" w:type="dxa"/><w:bottom w:w="110" w:type="dxa"/><w:right w:w="140" w:type="dxa"/></w:tblCellMar></w:tblPr></w:style>'
    . '</w:styles>';

$numberingXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="•"/><w:lvlJc w:val="left"/><w:pPr><w:tabs><w:tab w:val="num" w:pos="540"/></w:tabs><w:ind w:left="540" w:hanging="260"/></w:pPr><w:rPr><w:rFonts w:ascii="Symbol" w:hAnsi="Symbol" w:hint="default"/></w:rPr></w:lvl></w:abstractNum>'
    . '<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num>'
    . '</w:numbering>';

$contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
    . '<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
    . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
    . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
    . '</Types>';

$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    . '</Relationships>';

$documentRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>'
    . '</Relationships>';

$coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
    . '<dc:title>ConstructKaro ERP Client Quotation With AWS</dc:title><dc:creator>ConstructKaro</dc:creator><cp:lastModifiedBy>ConstructKaro</cp:lastModifiedBy>'
    . '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>'
    . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:modified>'
    . '</cp:coreProperties>';

$appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>ConstructKaro ERP</Application></Properties>';

$zip = new ZipArchive();
if ($zip->open($outPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Unable to create {$outPath}\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', $contentTypesXml);
$zip->addFromString('_rels/.rels', $relsXml);
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/_rels/document.xml.rels', $documentRelsXml);
$zip->addFromString('word/styles.xml', $stylesXml);
$zip->addFromString('word/numbering.xml', $numberingXml);
$zip->addFromString('docProps/core.xml', $coreXml);
$zip->addFromString('docProps/app.xml', $appXml);
$zip->close();

echo $outPath . PHP_EOL;
