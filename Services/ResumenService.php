<?php
namespace Services;

require_once __DIR__ . '/../Models/ResumenModel.php';
require_once __DIR__ . '/../config.php';

use Models\ResumenModel;

class ResumenService
{
    private $model;

    public function __construct(\PDO $pdo)
    {
        $this->model = new ResumenModel($pdo);
    }

    public function obtenerPivot(int $year, int $month, ?string $canal = null): array
    {
        return $this->model->getPivotByMonth($year, $month, $canal);
    }

    /**
     * Wrapper para ejecutar el stored procedure sp_sales_report
     */
    public function obtenerReporte(string $startDate, string $endDate, string $periodLevel = 'diario', string $groupBy = 'sucursal', ?string $canal = null, string $reportType = 'resumen'): array
    {
        return $this->model->runSalesReport($startDate, $endDate, $periodLevel, $groupBy, $canal, $reportType);
    }
}
