<?php
// Controllers/ReporteApi.php
// Recibe parámetros via GET (startDate, endDate, periodLevel, groupBy, canal, reportType)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Services/ResumenService.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $startDate = isset($_GET['startDate']) ? trim($_GET['startDate']) : null;
    $endDate = isset($_GET['endDate']) ? trim($_GET['endDate']) : null;
    $periodLevel = isset($_GET['periodLevel']) ? trim($_GET['periodLevel']) : 'diario';
    $groupBy = isset($_GET['groupBy']) ? trim($_GET['groupBy']) : 'sucursal';
    $canal = isset($_GET['canal']) ? trim($_GET['canal']) : null;
    $reportType = isset($_GET['reportType']) ? trim($_GET['reportType']) : 'resumen';

    if (empty($startDate) || empty($endDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetros startDate y endDate son requeridos']);
        exit;
    }

    $pdo = getDbConnection();
    $service = new \Services\ResumenService($pdo);
    $rows = $service->obtenerReporte($startDate, $endDate, $periodLevel, $groupBy, $canal, $reportType);

    echo json_encode(['data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al ejecutar el reporte', 'detail' => $t->getMessage()]);
}
