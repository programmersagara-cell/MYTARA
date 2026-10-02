<?php
// TEMP: parse xlsx content and dump rows for inspection
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Asia/Manila');

$file = $argv[1] ?? 'c:/Users/administrator/Downloads/Book1.xlsx';

$zip = new ZipArchive();
if ($zip->open($file) !== true) {
    fwrite(STDERR, "Cannot open zip: $file\n");
    exit(1);
}

// read shared strings (<si> ... <t>)
$shared = [];
$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
if ($sharedXml !== false) {
    if (preg_match_all('#<si[^>]*>(.*?)</si>#s', $sharedXml, $m)) {
        foreach ($m[1] as $si) {
            $text = '';
            if (preg_match_all('#<t[^>]*>(.*?)</t>#s', $si, $tm)) {
                foreach ($tm[1] as $t) {
                    $text .= html_entity_decode($t, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
            $shared[] = $text;
        }
    }
}

// list sheet files
$sheetFiles = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
        $sheetFiles[] = $name;
    }
}
sort($sheetFiles);

foreach ($sheetFiles as $sheet) {
    echo "\n==== SHEET: $sheet ====\n";
    $xml = $zip->getFromName($sheet);
    if ($xml === false) continue;
    $sxml = new SimpleXMLElement($xml);
    foreach ($sxml->sheetData->row as $row) {
        $vals = [];
        foreach ($row->c as $c) {
            $ref = (string) $c['r'];
            $type = (string) $c['t'];
            $v = (string) $c->v;
            $is = (string) $c->is;
            if ($type === 's') {
                $val = $shared[(int) $v] ?? '';
            } elseif ($type === 'inlineStr' && $is !== '') {
                $inner = new SimpleXMLElement($is);
                $val = '';
                foreach ($inner->xpath('.//t') as $t) $val .= (string) $t;
            } elseif ($type === 'b') {
                $val = $v;
            } else {
                $val = $v;
            }
            $vals[] = "$ref=" . json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        echo implode(' | ', $vals) . "\n";
    }
}
$zip->close();