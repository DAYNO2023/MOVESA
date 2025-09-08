<?php
// Endpoint: Controllers/ResumenPivotApi.php
// Parámetros: ?year=YYYY&month=MM or ?anio=YYYY&mes=MM[&canal=...]
// Devuelve JSON con filas: [{Sucursal, 01,02,...,31, TotalMes}, ...]

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Services/ResumenService.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Aceptar tanto 'year'/'month' como 'anio'/'mes'
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (isset($_GET['anio']) ? (int)$_GET['anio'] : null);
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (isset($_GET['mes']) ? (int)$_GET['mes'] : null);
    $canal = isset($_GET['canal']) ? trim($_GET['canal']) : null;

    if (empty($year) || empty($month)) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetros year/month o anio/mes son requeridos']);
        exit;
    }

    $pdo = getDbConnection();
    $service = new \Services\ResumenService($pdo);
    $rows = $service->obtenerPivot($year, $month, $canal);

    echo json_encode(['data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al ejecutar la consulta', 'detail' => $t->getMessage()]);
}
