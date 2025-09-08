<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Services/ImportService.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        $error = 'No se subió archivo o hubo un error en la subida.';
    } else {
        $tmpPath = $_FILES['excel']['tmp_name'];
        $originalName = basename($_FILES['excel']['name']);
        $uploadsDir = __DIR__ . '/../../uploads';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $destPath = $uploadsDir . '/' . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $error = 'Error al mover el archivo subido.';
        } else {
            try {
                $pdo = getDbConnection();
                $service = new Services\ImportService($pdo);
                $result = $service->importCsvToStaging($destPath);
                $success = "Archivo importado. Filas procesadas: {$result['processed']}, insertadas: {$result['inserted']}, errores: {$result['errors']}";
            } catch (Exception $e) {
                $error = 'Error al procesar el archivo: ' . $e->getMessage();
            }
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Importar CSV a Staging</title>
</head>
<body>
    <h1>Importar CSV a Staging</h1>

    <?php if (!empty($error)): ?>
        <div style="color:red"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="color:green"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label for="excel">Archivo CSV (exportado desde Excel):</label>
        <input type="file" name="excel" id="excel" accept=".csv" required />
        <button type="submit">Subir e importar</button>
    </form>

    <p>Nota: la primera fila debe contener las cabeceras que coincidan con las columnas esperadas (ej: DOCENTRY,DOCNUM,NUMERO_PR,...)</p>

    <?php
    // Mostrar resumen de filas en staging (últimas 10)
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT TOP 10 RowId, DOCENTRY, DOCNUM, SERIE, CARDCODE, Importado, ErrorMensaje, FechaCarga FROM dbo.StagingVentasImport ORDER BY RowId DESC");
        $rows = $stmt->fetchAll();
        if ($rows) {
            echo '<h2>Últimas filas en staging</h2>';
            echo '<table border="1" cellpadding="4" cellspacing="0">';
            echo '<tr><th>RowId</th><th>DOCENTRY</th><th>DOCNUM</th><th>SERIE</th><th>CARDCODE</th><th>Importado</th><th>Error</th><th>FechaCarga</th></tr>';
            foreach ($rows as $r) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($r['RowId']) . '</td>';
                echo '<td>' . htmlspecialchars($r['DOCENTRY']) . '</td>';
                echo '<td>' . htmlspecialchars($r['DOCNUM']) . '</td>';
                echo '<td>' . htmlspecialchars($r['SERIE']) . '</td>';
                echo '<td>' . htmlspecialchars($r['CARDCODE']) . '</td>';
                echo '<td>' . ($r['Importado'] ? 'Sí' : 'No') . '</td>';
                echo '<td>' . htmlspecialchars($r['ErrorMensaje']) . '</td>';
                echo '<td>' . htmlspecialchars($r['FechaCarga']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        // no mostrar error crítico en la vista; opcionalmente loguear
    }
    ?>
</body>
</html>
