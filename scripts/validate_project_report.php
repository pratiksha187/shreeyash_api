<?php

$reportName = $argv[1] ?? 'attendance_api_project_report.docx';
$path = dirname(__DIR__).DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.$reportName;
$zip = new ZipArchive();

if ($zip->open($path) !== true) {
    fwrite(STDERR, "Could not open DOCX.\n");
    exit(1);
}

$required = [
    '[Content_Types].xml',
    '_rels/.rels',
    'word/document.xml',
    'word/styles.xml',
    'word/_rels/document.xml.rels',
    'docProps/core.xml',
    'docProps/app.xml',
];

foreach ($required as $part) {
    if ($zip->locateName($part) === false) {
        fwrite(STDERR, "Missing $part\n");
        exit(1);
    }
}

$document = $zip->getFromName('word/document.xml');
$zip->close();

echo "DOCX package OK\n";
echo substr_count($document, '<w:tbl>')." tables\n";
echo substr_count($document, '<w:p>')." paragraphs\n";
echo filesize($path)." bytes\n";
