<?php
// Ejecuta ImportService directamente desde CLI para aislar problemas de Apache/PHP
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Services/ImportService.php';

$uploads = __DIR__ . '/../uploads';
$arg = $argv[1] ?? null;
if ($arg) {
    $file = $arg;
} else {
    $files = glob($uploads . DIRECTORY_SEPARATOR . '*data_ventas.*');
    if (empty($files)) {
        echo "No hay archivos data_ventas en uploads\n";
        exit(1);
    }
    $file = $files[0];
}

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    echo "Fallo al obtener conexión PDO: " . $e->getMessage() . "\n";
    exit(2);
}

$service = new Services\ImportService($pdo);
try {
    echo "Procesando file: $file\n";
    $t0 = microtime(true);
    $res = $service->importCsvToStaging($file);
    $t1 = microtime(true);
    echo "Resultado:\n" . json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "Tiempo: " . round($t1 - $t0, 2) . "s\n";
} catch (Throwable $e) {
    echo "Excepción durante import: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(3);
}
