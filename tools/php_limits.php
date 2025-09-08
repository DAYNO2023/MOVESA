<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
try {
    $keys = ['upload_max_filesize','post_max_size','max_execution_time','memory_limit','error_log','display_errors'];
    $out = [];
    foreach ($keys as $k) $out[$k] = ini_get($k);
    $uploads = __DIR__ . '/../uploads';
    $out['uploads_path'] = $uploads;
    $out['uploads_exists'] = is_dir($uploads);
    $out['uploads_writable'] = is_writable($uploads) || (!is_dir($uploads) && is_writable(dirname($uploads)));
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $t) {
    echo json_encode(['error' => $t->getMessage()], JSON_UNESCAPED_UNICODE);
}
