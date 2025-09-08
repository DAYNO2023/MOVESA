<?php
// Endpoint ligero para probar subida: devuelve metadata de $_FILES y $_POST en JSON
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido, use POST']);
    exit;
}

$out = [
    'success' => true,
    'received' => [],
    'post' => $_POST,
    'php_version' => phpversion()
];

foreach ($_FILES as $k => $f) {
    $out['received'][$k] = [
        'name' => $f['name'] ?? null,
        'size' => $f['size'] ?? null,
        'error' => $f['error'] ?? null,
        'tmp_name' => $f['tmp_name'] ?? null,
        'type' => $f['type'] ?? null
    ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;
