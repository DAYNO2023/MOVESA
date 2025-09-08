<?php
namespace Models;

require_once __DIR__ . '/../config.php';

class ResumenModel
{
    private $pdo;
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ejecuta el stored procedure y devuelve filas asociativas.
     */
    public function getPivotByMonth(int $anio, int $mes, ?string $canal = null): array
    {
        // Usamos parámetros nombrados que espera el SP: @anio y @mes
        $sql = "EXEC dbo.sp_sales_pivot_by_sucursal_month @anio = ?, @mes = ?";
        $params = [$anio, $mes];
        if ($canal !== null) {
            $sql .= ", @Canal = ?";
            $params[] = $canal;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $rows ?: [];
        } catch (\Throwable $e) {
            // Lanzar una excepción clara para que el controller la capture
            throw new \Exception('Error ejecutando SP: ' . $e->getMessage());
        }
    }

    /**
     * Ejecuta dbo.sp_sales_report y devuelve el resultado como array asociativo.
     * Parámetros: fechas 'YYYY-MM-DD', nivel 'diario'|'semanal'|'mensual'|'anual', groupby, canal, reportType ('resumen'|'detallado')
     */
    public function runSalesReport(string $startDate, string $endDate, string $periodLevel = 'diario', string $groupBy = 'sucursal', ?string $canal = null, string $reportType = 'resumen'): array
    {
        // Construir EXEC con parámetros posicionales
        $sql = "EXEC dbo.sp_sales_report @StartDate = ?, @EndDate = ?, @PeriodLevel = ?, @GroupBy = ?, @Canal = ?, @ReportType = ?";
        $params = [$startDate, $endDate, $periodLevel, $groupBy, $canal, $reportType];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $rows ?: [];
        } catch (\Throwable $e) {
            throw new \Exception('Error ejecutando sp_sales_report: ' . $e->getMessage());
        }
    }
}
