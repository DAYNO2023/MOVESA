<?php
// Prueba de conexión TCP al host y puerto (útil para comprobar firewall/alcance)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
ini_set('display_errors', '0');
try {
    $host = $DB_HOST;
    $port = 1433;
    $result = ['host' => $host, 'port' => $port, 'open' => false, 'error' => null];
    $errno = 0; $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($fp) {
        $result['open'] = true;
        fclose($fp);
    } else {
        $result['error'] = "{$errno} - {$errstr}";
    }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $t) {
    echo json_encode(['error' => $t->getMessage()], JSON_UNESCAPED_UNICODE);
}
