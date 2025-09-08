<?php
// Script de diagnóstico: intenta ejecutar el importCsvToStaging sobre un archivo en uploads
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Services/ImportService.php';

$uploads = __DIR__ . '/../uploads';
$files = glob($uploads . DIRECTORY_SEPARATOR . '*data_ventas.xlsx');
if (empty($files)) {
    echo "No se encontraron archivos de prueba en uploads\n";
    exit(1);
}
$testFile = $files[0];
try {
    $pdo = getDbConnection();
    $service = new Services\ImportService($pdo);
    $res = $service->importCsvToStaging($testFile);
    echo "Import OK:\n" . json_encode($res, JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $t) {
    $log = __DIR__ . '/../uploads/import_errors.log';
    $entry = date('Y-m-d H:i:s') . " | CLI import error:\n" . $t->getMessage() . "\n" . $t->getTraceAsString() . "\n----\n";
    file_put_contents($log, $entry, FILE_APPEND | LOCK_EX);
    echo "Error durante import: " . $t->getMessage() . "\nVer detalles en " . $log . "\n";
    exit(2);
}
