<?php

$outPath = __DIR__ . '/../reports/ConstructKaro_ERP_Pricing_Structure.docx';

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "ZipArchive extension is not available.\n");
    exit(1);
}

if (! is_dir(dirname($outPath))) {
    mkdir(dirname($outPath), 0777, true);
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function p(string $text = '', string $style = 'BodyText'): string
{
    $text = esc($text);

    return "<w:p><w:pPr><w:pStyle w:val=\"{$style}\"/></w:pPr><w:r><w:t xml:space=\"preserve\">{$text}</w:t></w:r></w:p>";
}

function bullet(string $text): string
{
    $text = esc($text);

    return '<w:p><w:pPr><w:pStyle w:val="ListParagraph"/><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p>';
}

function cell(string $text, int $width, bool $header = false, string $align = 'left'): string
{
    $fill = $header ? '<w:shd w:fill="E8EEF5"/>' : '';
    $boldStart = $header ? '<w:b/>' : '';
    $text = esc($text);

    return '<w:tc>'
        . '<w:tcPr><w:tcW w:w="' . $width . '" w:type="dxa"/><w:vAlign w:val="center"/>' . $fill . '</w:tcPr>'
        . '<w:p><w:pPr><w:jc w:val="' . $align . '"/></w:pPr><w:r><w:rPr>' . $boldStart . '</w:rPr><w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p>'
        . '</w:tc>';
}

function table(array $rows, array $widths, array $aligns = []): string
{
    $grid = '';
    foreach ($widths as $width) {
        $grid .= '<w:gridCol w:w="' . $width . '"/>';
    }

    $xml = '<w:tbl>'
        . '<w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="9360" w:type="dxa"/><w:tblInd w:w="120" w:type="dxa"/><w:tblLook w:firstRow="1" w:lastRow="0" w:firstColumn="0" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/></w:tblPr>'
        . '<w:tblGrid>' . $grid . '</w:tblGrid>';

    foreach ($rows as $index => $row) {
        $xml .= '<w:tr>';
        foreach ($row as $cellIndex => $value) {
            $xml .= cell((string) $value, $widths[$cellIndex], $index === 0, $aligns[$cellIndex] ?? 'left');
        }
        $xml .= '</w:tr>';
    }

    return $xml . '</w:tbl>' . p('');
}

$modules = [
    'Admin dashboard',
    'Multi-company and company subscription management',
    'Employee management and attendance',
    'Leave requests and missed attendance requests',
    'Labour attendance, labour master, contractor master, and site master',
    'Labour costing',
    'Vehicle management, driver attendance, vehicle maintenance, and diesel logs',
    'Daily Progress Reports with photos',
    'Challan management with PDF download',
    'Payment records and payment slips',
    'Material master, stock, material requests, and material issues',
    'Safety store',
    'Supplier master and unit master',
    'Product purchases, purchase workflows, and purchase orders',
    'Project management, BOQ management, and site reports',
    'FDD test records, MIR file reports, and complaint management',
    'Mobile API backend support',
    'WhatsApp message integration',
];

$body = '';
$body .= p('ConstructKaro ERP', 'Title');
$body .= p('Pricing Structure for Construction Attendance, Labour, Purchase, Inventory and Site Management System', 'Subtitle');
$body .= p('Prepared for client discussion and commercial quotation.', 'BodyText');
$body .= p('Project Overview', 'Heading1');
$body .= p('ConstructKaro ERP is a construction business management system with an admin panel, mobile API backend, attendance, labour, project, purchase, inventory, vehicle, safety, report, PDF, and company management modules.');

$body .= p('Main Modules Included', 'Heading1');
foreach ($modules as $module) {
    $body .= bullet($module);
}

$body .= p('Option 1: Monthly Subscription Model', 'Heading1');
$body .= p('In this model, the client pays a one-time setup fee and then pays monthly for using the system.');
$body .= table([
    ['Plan', 'Best For', 'Setup Fee', 'Monthly Fee'],
    ['Basic', 'Small contractor or single site', 'Rs. 25,000', 'Rs. 4,999/month'],
    ['Standard', 'Medium construction company', 'Rs. 50,000', 'Rs. 9,999/month'],
    ['Professional', 'Multiple sites and more users', 'Rs. 75,000', 'Rs. 14,999/month'],
    ['Enterprise', 'Large company with custom needs', 'Rs. 1,00,000+', 'Rs. 24,999/month+'],
], [1700, 3700, 1900, 2060], ['left', 'left', 'center', 'center']);

$body .= p('Monthly Plan Details', 'Heading1');
$body .= p('Basic Plan - Rs. 4,999/month', 'Heading2');
foreach (['1 company', '1 to 2 sites', 'Employee attendance', 'Labour attendance', 'Basic reports', 'Admin panel access', 'Basic support'] as $item) {
    $body .= bullet($item);
}

$body .= p('Standard Plan - Rs. 9,999/month', 'Heading2');
foreach (['1 company', 'Up to 5 sites', 'Attendance and labour modules', 'DPR reports', 'Challans', 'Payments', 'Vehicle management', 'Material stock', 'Purchase records', 'PDF reports', 'WhatsApp integration support', 'Monthly support'] as $item) {
    $body .= bullet($item);
}

$body .= p('Professional Plan - Rs. 14,999/month', 'Heading2');
foreach (['Multiple sites', 'All Standard Plan features', 'Project management', 'BOQ management', 'Purchase orders', 'Safety store', 'Labour costing', 'Vehicle maintenance', 'Advanced reports', 'Priority support'] as $item) {
    $body .= bullet($item);
}

$body .= p('Enterprise Plan - Rs. 24,999/month+', 'Heading2');
foreach (['All modules included', 'Custom reports', 'Custom changes', 'Dedicated server setup', 'Staff training', 'Priority support', 'Data backup support', 'Custom branding'] as $item) {
    $body .= bullet($item);
}

$body .= p('Recommended Monthly Offer', 'Heading2');
$body .= p('Rs. 50,000 setup fee + Rs. 9,999/month');
$body .= p('This is suitable for a medium construction company and gives recurring monthly income.');

$body .= p('Option 2: One-Time Sale Model', 'Heading1');
$body .= p('In this model, the client pays one fixed amount for setup and usage.');
$body .= table([
    ['Package', 'What Is Included', 'Price'],
    ['Basic One-Time', 'Setup, branding, basic training', 'Rs. 75,000 to Rs. 1,25,000'],
    ['Standard One-Time', 'Setup, branding, training, 1 month support', 'Rs. 1,50,000 to Rs. 2,50,000'],
    ['Premium One-Time', 'Full setup, custom changes, reports, 3 months support', 'Rs. 3,00,000 to Rs. 5,00,000'],
    ['Full Source Code Sale', 'Complete source code handover', 'Rs. 5,00,000 to Rs. 10,00,000+'],
], [2500, 4200, 2660], ['left', 'left', 'center']);

$body .= p('Recommended One-Time Offer', 'Heading2');
$body .= p('For a serious client, quote Rs. 3,50,000 one-time.');
$body .= bullet('Best price: Rs. 3,50,000');
$body .= bullet('Acceptable price: Rs. 2,50,000');
$body .= bullet('Minimum price: Rs. 1,50,000');
$body .= bullet('Do not sell the full source code below Rs. 5,00,000 if the buyer wants complete ownership.');

$body .= p('Customization Charges', 'Heading1');
$body .= table([
    ['Work Type', 'Suggested Charge'],
    ['Small text/design changes', 'Rs. 2,000 to Rs. 5,000'],
    ['New report', 'Rs. 5,000 to Rs. 15,000'],
    ['New module', 'Rs. 25,000 to Rs. 75,000'],
    ['Mobile app changes', 'Rs. 10,000 to Rs. 50,000'],
    ['WhatsApp/SMS integration', 'Rs. 10,000 to Rs. 25,000'],
    ['Server deployment', 'Rs. 10,000 to Rs. 25,000'],
    ['Data import from Excel', 'Rs. 5,000 to Rs. 20,000'],
], [5000, 4360], ['left', 'center']);

$body .= p('Support Charges', 'Heading1');
$body .= table([
    ['Support Type', 'Price'],
    ['Basic monthly support', 'Rs. 5,000/month'],
    ['Standard monthly support', 'Rs. 10,000/month'],
    ['Priority support', 'Rs. 20,000/month'],
    ['On-site training', 'Rs. 10,000 to Rs. 25,000/day'],
], [5000, 4360], ['left', 'center']);

$body .= p('Payment Terms', 'Heading1');
$body .= p('For Monthly Subscription', 'Heading2');
foreach (['50% setup fee advance', '50% setup fee after deployment', 'Monthly payment in advance', 'Support starts after final setup payment'] as $item) {
    $body .= bullet($item);
}
$body .= p('For One-Time Sale', 'Heading2');
foreach (['50% advance before work starts', '30% after demo/deployment', '20% after final handover'] as $item) {
    $body .= bullet($item);
}
$body .= p('For Source Code Sale', 'Heading2');
foreach (['70% advance', '30% before source code handover', 'Source code should be shared only after full payment'] as $item) {
    $body .= bullet($item);
}

$body .= p('Important Conditions', 'Heading1');
foreach ([
    'Hosting/server cost is extra unless included in the plan.',
    'Domain cost is extra.',
    'SMS/WhatsApp API charges are extra.',
    'Payment gateway charges are extra.',
    'Custom changes are not included unless written in the agreement.',
    'Source code ownership is not included in monthly subscription.',
    'Client data belongs to the client.',
    'Software ownership remains with the developer unless full source code sale is agreed.',
] as $item) {
    $body .= bullet($item);
}

$body .= p('Best Business Recommendation', 'Heading1');
$body .= p('Best monthly model: Rs. 50,000 setup fee + Rs. 9,999/month');
$body .= p('For bigger clients: Rs. 75,000 setup fee + Rs. 14,999/month');
$body .= p('For one-time sale: start quotation at Rs. 3,50,000');
$body .= p('Minimum one-time sale price: Rs. 1,50,000');
$body .= p('Minimum full source code sale price: Rs. 5,00,000');

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 wp14">'
    . '<w:body>' . $body
    . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
    . '</w:body></w:document>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/><w:color w:val="1F2937"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="300" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
    . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr><w:pPr><w:spacing w:after="120" w:line="300" w:lineRule="auto"/></w:pPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="BodyText"><w:name w:val="Body Text"/><w:basedOn w:val="Normal"/><w:qFormat/><w:pPr><w:spacing w:after="120" w:line="300" w:lineRule="auto"/></w:pPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:qFormat/><w:pPr><w:spacing w:before="0" w:after="120"/></w:pPr><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:b/><w:color w:val="0B2545"/><w:sz w:val="56"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:qFormat/><w:pPr><w:spacing w:after="240"/></w:pPr><w:rPr><w:color w:val="4B5563"/><w:sz w:val="24"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="BodyText"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="320" w:after="160"/></w:pPr><w:rPr><w:b/><w:color w:val="2E74B5"/><w:sz w:val="32"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="BodyText"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="240" w:after="120"/></w:pPr><w:rPr><w:b/><w:color w:val="1F4D78"/><w:sz w:val="26"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="ListParagraph"><w:name w:val="List Paragraph"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="80" w:line="300" w:lineRule="auto"/><w:ind w:left="720" w:hanging="360"/></w:pPr></w:style>'
    . '<w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="D0D7DE"/><w:left w:val="single" w:sz="4" w:space="0" w:color="D0D7DE"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="D0D7DE"/><w:right w:val="single" w:sz="4" w:space="0" w:color="D0D7DE"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="D0D7DE"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="D0D7DE"/></w:tblBorders><w:tblCellMar><w:top w:w="80" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tblCellMar></w:tblPr></w:style>'
    . '</w:styles>';

$numberingXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="•"/><w:lvlJc w:val="left"/><w:pPr><w:tabs><w:tab w:val="num" w:pos="720"/></w:tabs><w:ind w:left="720" w:hanging="360"/></w:pPr><w:rPr><w:rFonts w:ascii="Symbol" w:hAnsi="Symbol" w:hint="default"/></w:rPr></w:lvl></w:abstractNum>'
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
    . '<dc:title>ConstructKaro ERP Pricing Structure</dc:title><dc:creator>ConstructKaro</dc:creator><cp:lastModifiedBy>ConstructKaro</cp:lastModifiedBy>'
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
