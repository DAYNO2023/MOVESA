<?php
// Endpoint API para recibir archivo y devolver JSON limpio
// No incluye vistas ni imprime nada adicional

// Evitar que warnings/errores se impriman en la salida y romper el JSON
// Se puede habilitar temporalmente debug agregando ?debug=1 a la URL desde localhost
// (esto retorna stacktrace en la respuesta JSON). No dejar activado en producción.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(0);

$debugMode = false;
if (isset($_GET['debug']) && ($_GET['debug'] === '1')) {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($remote === '127.0.0.1' || $remote === '::1') {
        $debugMode = true;
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
    }
}

// Iniciar buffering desde el inicio para atrapar cualquier salida accidental
if (!ob_get_level()) ob_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Services/ImportService.php';

// Helper para enviar JSON seguro (cierra buffers, fija código HTTP y sale)
function sendJson($data, $httpCode = 200)
{
    // Cerrar todos los buffers y eliminar contenido accidental
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Asegurar cabecera JSON
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    http_response_code($httpCode);
    echo json_encode($data);
    exit;
}

try {
    // Si hay cualquier salida en el buffer (warnings, HTML, etc.) la guardamos en un log
    $buf = '';
    if (ob_get_length()) {
        $buf = ob_get_contents();
    }
    if ($buf !== false && trim($buf) !== '') {
        $uploadsDirForLog = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDirForLog)) mkdir($uploadsDirForLog, 0755, true);
        $outLog = $uploadsDirForLog . DIRECTORY_SEPARATOR . 'import_output.log';
        $entry = date('Y-m-d H:i:s') . " | BUFFER CONTENT:\n" . $buf . "\n----\n";
        file_put_contents($outLog, $entry, FILE_APPEND | LOCK_EX);
    }

    // Limpiar buffer y empezar limpio
    if (ob_get_level()) ob_clean();

    // Log temporal: metadata de la petición para depuración de uploads
    try {
        $uploadsDirForLog = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDirForLog)) mkdir($uploadsDirForLog, 0755, true);
        $metaLog = $uploadsDirForLog . DIRECTORY_SEPARATOR . 'import_errors.log';
        $meta = [];
        $meta['time'] = date('Y-m-d H:i:s');
        $meta['remote_addr'] = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $meta['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'N/A';
        $meta['content_length'] = $_SERVER['CONTENT_LENGTH'] ?? ($_SERVER['HTTP_CONTENT_LENGTH'] ?? null);
        $files_meta = [];
        foreach ($_FILES as $k => $f) {
            $files_meta[$k] = ['name' => $f['name'] ?? null, 'size' => $f['size'] ?? null, 'error' => $f['error'] ?? null, 'type' => $f['type'] ?? null];
        }
        $meta['files'] = $files_meta;
        $meta['post'] = $_POST;
        file_put_contents($metaLog, json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n----\n", FILE_APPEND | LOCK_EX);
    } catch (Throwable $t) {
        // ignore logging errors
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['success' => false, 'message' => 'Método no permitido'], 405);
    }

    if (!isset($_FILES['archivo']) && !isset($_FILES['excel'])) {
        sendJson(['success' => false, 'message' => 'No se recibió archivo'], 400);
    }

    $fileField = isset($_FILES['archivo']) ? 'archivo' : 'excel';
    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        sendJson(['success' => false, 'message' => 'Error en la subida del archivo.'], 400);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv','xls','xlsx'])) {
        sendJson(['success' => false, 'message' => 'Formato no soportado. Tipos permitidos: csv, xls, xlsx.'], 415);
    }

    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

        // Restricción opcional: solo permitir 1 archivo de datos por día (extensiones csv/xls/xlsx)
        try {
            if (defined('ALLOW_ONE_UPLOAD_PER_DAY') && ALLOW_ONE_UPLOAD_PER_DAY) {
                $allowedExt = ['csv','xls','xlsx'];
                $today = date('Y-m-d');
                $foundToday = 0;
                $dh = opendir($uploadsDir);
                if ($dh !== false) {
                    while (($file = readdir($dh)) !== false) {
                        $full = $uploadsDir . DIRECTORY_SEPARATOR . $file;
                        if (!is_file($full)) continue;
                        $extf = strtolower(pathinfo($full, PATHINFO_EXTENSION));
                        if (!in_array($extf, $allowedExt, true)) continue;
                        $mtime = filemtime($full);
                        if ($mtime !== false && date('Y-m-d', $mtime) === $today) {
                            $foundToday++;
                            break; // basta con uno
                        }
                    }
                    closedir($dh);
                }
                if ($foundToday > 0) {
                    sendJson(['success' => false, 'message' => 'Solo se permite subir un archivo de datos por día. Ya existe un archivo subido hoy.'], 429);
                }
            }
        } catch (\Throwable $t) {
            // Si falla la comprobación, permitimos continuar (no bloquear por fallo del check)
        }

    $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
    $destPath = $uploadsDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        sendJson(['success' => false, 'message' => 'No se pudo mover el archivo al servidor.'], 500);
    }

    // Nota: solicitaste que el procedimiento almacenado se ejecute durante la subida.
    // Ignoramos la encolación async y procesamos siempre en forma síncrona para evitar duplicar trabajo.
    $isAsync = (isset($_POST['async']) && ($_POST['async'] == '1')) || (isset($_GET['async']) && ($_GET['async'] == '1'));
    if ($isAsync) {
        try {
            $uploadsDirForLog = __DIR__ . '/../uploads';
            if (!is_dir($uploadsDirForLog)) mkdir($uploadsDirForLog, 0755, true);
            $logFile = $uploadsDirForLog . DIRECTORY_SEPARATOR . 'import_errors.log';
            $logLine = date('Y-m-d H:i:s') . " | Async requested but processing synchronously per configuration | File: {$safeName}\n";
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $t) { /* ignore logging errors */ }
        // no encolamos, continuamos el flujo síncrono que ejecuta el SP
    }

    // Verificar que las extensiones/drivers para SQL Server estén disponibles antes de intentar conectar
    $pdoAvailable = false;
    try {
        if (extension_loaded('pdo_sqlsrv') || extension_loaded('sqlsrv')) {
            $pdoAvailable = true;
        } else {
            // verificar si PDO tiene el driver 'sqlsrv'
            if (class_exists('PDO')) {
                $drivers = \PDO::getAvailableDrivers();
                if (in_array('sqlsrv', $drivers, true)) $pdoAvailable = true;
            }
        }
    } catch (\Throwable $t) {
        $pdoAvailable = false;
    }

    if (!$pdoAvailable) {
        // Registrar en log local
        $uploadsDirForLog = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDirForLog)) mkdir($uploadsDirForLog, 0755, true);
        $logFile = $uploadsDirForLog . DIRECTORY_SEPARATOR . 'import_errors.log';
        $logLine = date('Y-m-d H:i:s') . " | Partial save | File: {$safeName} | Reason: missing pdo_sqlsrv/sqlsrv or driver\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        // Devuelve respuesta parcial: archivo guardado pero no se procesó en BD.
        sendJson([
            'success' => true,
            'partial' => true,
            'file_saved' => true,
            'file' => $safeName,
            'message' => 'Archivo guardado en el servidor, pero no se pudo conectar a la base de datos (falta PDO_SQLSRV/SQLSRV o driver sqlsrv). Instale los Microsoft Drivers para PHP for SQL Server para procesar los datos.'
        ]);
    }

    try {
        $pdo = getDbConnection();
    } catch (Exception $ex) {
        // Si la conexión falla por cualquier razón, guardamos el archivo, registramos stacktrace y devolvemos partial success
        try {
            $uploadsDirForLog = __DIR__ . '/../uploads';
            if (!is_dir($uploadsDirForLog)) mkdir($uploadsDirForLog, 0755, true);
            $logFile = $uploadsDirForLog . DIRECTORY_SEPARATOR . 'import_errors.log';
            $ctx = [];
            $ctx[] = "TIME: " . date('Y-m-d H:i:s');
            $ctx[] = "EVENT: DB connection failed during import request";
            $ctx[] = "SAFE_NAME: " . (isset($safeName) ? $safeName : 'N/A');
            $ctx[] = "EXCEPTION: " . $ex->getMessage();
            $ctx[] = "FILE: " . $ex->getFile() . ':' . $ex->getLine();
            $ctx[] = "TRACE:\n" . $ex->getTraceAsString();
            // Add minimal server info
            $ctx[] = "PHP_VERSION: " . phpversion();
            $ctx[] = "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A');
            $entry = implode("\n", $ctx) . "\n----\n";
            file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        } catch (Throwable $t) {
            // If logging fails, ignore to avoid further exceptions
        }

        sendJson([
            'success' => true,
            'partial' => true,
            'file_saved' => true,
            'file' => $safeName ?? null,
            'message' => 'Archivo guardado en el servidor, pero fallo al conectar a la base de datos.'
        ]);
    }

    $service = new Services\ImportService($pdo);
    // Ejecutar insert a staging y luego ejecutar el SP desde el servicio para centralizar manejo
    $result = $service->importCsvToStaging($destPath, true);

    sendJson(['success' => true, 'result' => $result, 'file' => $safeName]);
    // sendJson ya hace exit
    } catch (\Throwable $e) {
    // Registrar stacktrace detallado en uploads/import_errors.log para diagnóstico
    try {
        $uploadsDirForLog = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDirForLog)) mkdir($uploadsDirForLog, 0755, true);
        $logFile = $uploadsDirForLog . DIRECTORY_SEPARATOR . 'import_errors.log';
        $ctx = [];
        $ctx[] = "TIME: " . date('Y-m-d H:i:s');
        $ctx[] = "EVENT: Uncaught exception in ImportApi endpoint";
        $ctx[] = "EXCEPTION: " . $e->getMessage();
        $ctx[] = "FILE: " . $e->getFile() . ':' . $e->getLine();
        $ctx[] = "TRACE:\n" . $e->getTraceAsString();
        $ctx[] = "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A');
        $ctx[] = "PHP_VERSION: " . phpversion();
        // If available, log the uploaded file name
        if (isset($safeName)) $ctx[] = "SAFE_NAME: " . $safeName;
        $entry = implode("\n", $ctx) . "\n----\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    } catch (Throwable $t) {
        // ignore logging errors
    }

    // Respuesta para el cliente: si estamos en debugMode (localhost + ?debug=1) incluimos trace
    $clientPayload = ['success' => false, 'message' => 'Error interno en el servidor. Se registró la excepción para diagnóstico.'];
    if (!empty($debugMode)) {
        $clientPayload['debug_message'] = $e->getMessage();
        $clientPayload['debug_trace'] = $e->getTraceAsString();
    }
    sendJson($clientPayload, 500);
}
