<?php
// Script de diagnóstico: prueba la conexión PDO usando getDbConnection() y devuelve JSON.
ini_set('display_errors','0');
ini_set('log_errors','0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

$resp = ['ok' => false, 'message' => null];
try {
    $pdo = getDbConnection();
    // Si llegamos aquí, la conexión fue exitosa
    $resp['ok'] = true;
    $resp['message'] = 'Conexión correcta (PDO creada)';
} catch (Throwable $t) {
    $resp['ok'] = false;
    $resp['message'] = $t->getMessage();
}

echo json_encode($resp, JSON_UNESCAPED_UNICODE);
