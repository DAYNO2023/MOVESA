<?php
namespace Models;

require_once __DIR__ . '/../config.php';

class StagingModel
{
    private $pdo;
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countUnimported(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS c FROM dbo.StagingVentasImport WHERE Importado = 0");
        $row = $stmt->fetch();
        return (int)$row['c'];
    }
}
