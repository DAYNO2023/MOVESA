<?php
// Endpoint: subir archivo y devolver conteo de filas / duplicados sin insertar
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Services/ImportService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método no permitido']);
    exit;
}

if (!isset($_FILES['archivo'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'No se recibió archivo']);
    exit;
}

$file = $_FILES['archivo'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Error en la subida']);
    exit;
}

$uploads = __DIR__ . '/../uploads';
if (!is_dir($uploads)) mkdir($uploads,0755,true);
$safe = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/','_',basename($file['name']));
$dest = $uploads . DIRECTORY_SEPARATOR . $safe;
if (!move_uploaded_file($file['tmp_name'],$dest)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'No se pudo mover archivo']);
    exit;
}

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'No se pudo conectar a DB: '.$e->getMessage()]);
    exit;
}

$service = new Services\ImportService($pdo);
$preview = $service->previewFileCounts($dest, 1000);

echo json_encode(['success'=>true,'file'=>$safe,'preview'=>$preview], JSON_UNESCAPED_UNICODE);
exit;
