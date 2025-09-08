<?php
// Muestra extensiones y drivers relevantes para diagnosticar conectividad a SQL Server
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
try {
    $data = [];
    $data['php_sapi'] = php_sapi_name();
    $data['php_version'] = phpversion();
    $data['loaded_extensions'] = get_loaded_extensions();
    $data['ini_extension_dir'] = ini_get('extension_dir');
    if (class_exists('PDO')) {
        $data['pdo_drivers'] = 
            defined('PDO::ATTR_DRIVER_NAME') ? 
            
            PDO::getAvailableDrivers() : PDO::getAvailableDrivers();
    } else {
        $data['pdo_drivers'] = [];
    }
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $t) {
    echo json_encode(['error' => $t->getMessage()], JSON_UNESCAPED_UNICODE);
}
