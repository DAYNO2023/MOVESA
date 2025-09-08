<?php
// Configuración de conexión a SQL Server (PDO)
$DB_HOST = 'DBMOVESA.mssql.somee.com';
$DB_NAME = 'DBMOVESA';
$DB_USER = 'Dayno100_SQLLogin_1';
$DB_PASS = 'btbx9m38la';

/**
 * Devuelve una conexión PDO a SQL Server.
 * Requiere el driver PDO_SQLSRV instalado en PHP (Windows/XAMPP).
 * Uso: require 'config.php'; $pdo = getDbConnection();
 */
function getDbConnection(): PDO
{
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;

    $dsn = "sqlsrv:Server={$DB_HOST};Database={$DB_NAME}";

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
        // Opcional: asegurar formato de fecha
        return $pdo;
    } catch (PDOException $e) {
        // Registrar el error en un archivo seguro para debugging
        $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $msg = date('c') . " - DB connection error: " . $e->getMessage() . "\n";
        @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'db_error.log', $msg, FILE_APPEND | LOCK_EX);

        // Lanzar excepción genérica al caller (no exponemos detalles en la salida JSON)
        throw new \Exception('Error de conexión a la base de datos');
    }
}

// Config: controlar si se permite sólo 1 upload de datos por día
if (!defined('ALLOW_ONE_UPLOAD_PER_DAY')) define('ALLOW_ONE_UPLOAD_PER_DAY', false);


