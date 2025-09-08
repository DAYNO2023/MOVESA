<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Services/ImportService.php';

use Services\ImportService;

// Simple controller para mostrar formulario y procesar upload CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        $error = 'No se subió archivo o hubo un error en la subida.';
    } else {
        $tmpPath = $_FILES['excel']['tmp_name'];
        $originalName = basename($_FILES['excel']['name']);
        $uploadsDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $destPath = $uploadsDir . '/' . time() . '_' . $originalName;
        if (!move_uploaded_file($tmpPath, $destPath)) {
            $error = 'Error al mover el archivo subido.';
        } else {
            try {
                $pdo = getDbConnection();
                $service = new ImportService($pdo);
                $result = $service->importCsvToStaging($destPath);
                $success = "Filas procesadas: {$result['processed']}, insertadas: {$result['inserted']}, errores: {$result['errors']}";
            } catch (Exception $e) {
                $error = 'Error al procesar el archivo: ' . $e->getMessage();
            }
        }
    }
}

// Cargar la vista
require_once __DIR__ . '/../Views/Pages/Import.php';
