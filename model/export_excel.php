<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

$action = isset($_GET['action']) ? $_GET['action'] : '';
if ( $action !== 'exportar_excel' ) {
 die('Invalid action');
}

$mysqli = new mysqli('localhost', 'root', 'P3m3xCatalina*2026', 'emitircfdi_db', 3306);
if ($mysqli->connect_error) { die('DB error'); }
$mysqli->set_charset('utf8mb4');

function colLetter($n) {
 $result = '';
 while ($n >= 0) {
  $result = chr(65 + ($n % 26)) . $result;
  $n = intdiv($n, 26) - 1;
 }
 return $result;
}

$result = $mysqli->query("SELECT * FROM xml_importados ORDER BY serie ASC, CAST(folio AS UNSIGNED) ASC, id ASC");
$rows = [];
while ( $r = $result->fetch_assoc() ) {
 $rows[] = $r;
}
$result->free();

$headers = [
 'Fecha y Hora Emisión',
 'UUID (Folio Fiscal)',
 'Serie',
 'Folio',
 'Folio Cobro',
 'Serie Vital',
 'RFC Cliente',
 'Nombre Cliente',
 'Subtotal',
 'Descuento',
 'IVA',
 'Total',
 'Forma de Pago',
 'Método de Pago',
 'Estado',
 'EsGlobal'
];

$fields = [
 'fecha',
 'timbre_uuid',
 'serie',
 'folio',
 '',
 '',
 'receptor_rfc',
 'receptor_nombre',
 'subtotal',
 'descuento',
 'impuestos_traslados',
 'total',
 'forma_pago',
 'metodo_pago',
 'estado',
 'es_global'
];

$sheetData = '';
$rowNum = 1;

$sheetData .= '<row r="' . $rowNum . '">';
foreach ( $headers as $i => $h ) {
 $cellRef = colLetter($i) . $rowNum;
 $sheetData .= '<c r="' . $cellRef . '" t="inlineStr" s="1"><is><t>' . htmlspecialchars($h) . '</t></is></c>';
}
$sheetData .= '</row>';
$rowNum++;

foreach ( $rows as $r ) {
 $isGlobal = ($r['es_global'] == 1);
 $styleIdx = $isGlobal ? 2 : 0;

 $sheetData .= '<row r="' . $rowNum . '">';
 foreach ( $fields as $i => $f ) {
   $cellRef = colLetter($i) . $rowNum;
   $val = $f !== '' ? $r[$f] : '';

   if ($f === 'estado' && !empty($val)) {
     $val = strtolower($val);
   }

   if ( in_array($f, ['subtotal','descuento','total','impuestos_traslados','impuestos_retenidos']) ) {
     $sheetData .= '<c r="' . $cellRef . '" t="n" s="' . $styleIdx . '"><v>' . (float)$val . '</v></c>';
   } else if ($f === 'es_global') {
     $sheetData .= '<c r="' . $cellRef . '" t="inlineStr" s="' . $styleIdx . '"><is><t>' . ($val == 1 ? 'Global' : 'Normal') . '</t></is></c>';
   } else {
     $sheetData .= '<c r="' . $cellRef . '" t="inlineStr" s="' . $styleIdx . '"><is><t>' . htmlspecialchars($val) . '</t></is></c>';
   }
 }
 $sheetData .= '</row>';
 $rowNum++;
}

$cols = '';
foreach ( $headers as $i => $h ) {
 $w = 18;
 if ( in_array($i, [0]) ) $w = 22;
 if ( in_array($i, [1]) ) $w = 42;
 if ( in_array($i, [6,7]) ) $w = 35;
 if ( in_array($i, [8,9,10,11]) ) $w = 15;
 $cols .= '<col min="' . ($i+1) . '" max="' . ($i+1) . '" width="' . $w . '" customWidth="1"/>';
}

$xmlSheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
           xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<cols>' . $cols . '</cols>
<sheetData>' . $sheetData . '</sheetData>
</worksheet>';

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
$zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="XML Importados" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>');

$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

$zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2">
<font><sz val="11"/><color rgb="FF000000"/></font>
<font><b/><sz val="11"/><color rgb="FF000000"/></font>
</fonts>
<fills count="4">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFD6E4F0"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFFF00"/></patternFill></fill>
</fills>
<borders count="1">
<border><left/><right/><top/><bottom/><diagonal/></border>
</borders>
<cellStyleXfs count="1">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
</cellStyleXfs>
<cellXfs count="4">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
<xf numFmtId="0" fontId="0" fillId="3" borderId="0" xfId="0" applyFill="1"/>
<xf numFmtId="2" fontId="0" fillId="3" borderId="0" xfId="0" applyNumberFormat="1" applyFill="1"/>
</cellXfs>
</styleSheet>');

$zip->addFromString('xl/worksheets/sheet1.xml', $xmlSheet);
$zip->close();

$mysqli->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="xml_importados_' . date('Y-m-d') . '.xlsx"');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
unlink($tmpFile);
exit;
