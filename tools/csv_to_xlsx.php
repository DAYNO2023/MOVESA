<?php
// Convierte un CSV a XLSX usando PhpSpreadsheet
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$csvPath = __DIR__ . '/../uploads/sample_data_ventas.csv';
$xlsPath = __DIR__ . '/../uploads/sample_data_ventas.xlsx';

if (!file_exists($csvPath)) {
    fwrite(STDERR, "CSV no encontrado: $csvPath\n");
    exit(1);
}

$handle = fopen($csvPath, 'r');
if ($handle === false) {
    fwrite(STDERR, "No se pudo abrir CSV: $csvPath\n");
    exit(1);
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$rowNum = 1;

function colLetter($col)
{
    $letters = '';
    while ($col > 0) {
        $mod = ($col - 1) % 26;
        $letters = chr(65 + $mod) . $letters;
        $col = intval(($col - 1) / 26);
    }
    return $letters;
}

while (($data = fgetcsv($handle, 0, ',')) !== false) {
    $col = 1;
    foreach ($data as $cell) {
        // Escribir valor tal cual (no forzar tipos)
        $cellAddress = colLetter($col) . $rowNum;
        $sheet->setCellValue($cellAddress, $cell);
        $col++;
    }
    $rowNum++;
}
fclose($handle);

$writer = new Xlsx($spreadsheet);
$writer->save($xlsPath);

echo "Generado: " . realpath($xlsPath) . PHP_EOL;
