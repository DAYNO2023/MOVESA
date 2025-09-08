<?php
namespace Services;

class ImportService
{
    private $pdo;
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Importa un archivo (CSV, XLSX, XLS) al staging.
     * Detecta el tipo por extensión y usa PhpSpreadsheet para xls/xlsx si está disponible.
     * La primera fila debe ser la cabecera con nombres de columnas.
     */
    public function importCsvToStaging(string $filePath, bool $runSp = false): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Columnas esperadas (en mayúsculas)
            $expected = [
                'DOCENTRY','DOCNUM','NUMERO_PRESTAMO','ALMACEN_ENTREGA','DOCDATE','DIA','MES','ANIO',
                'CARDCODE','CARDNAME','GROUPCODE','TIPO','QUANTITY','TOTAL_USD',
                'SERIE','U_AMODELO','MODELO','U_AMARCA','MARCA','U_ACOLOR','COLOR','SEGMENTO',
                'VENDEDOR','NOMBRE_VENDEDOR','SUPERVISOR','ALMACEN VENTA','NOMBRE_ALMACEN_VENTA','CANAL'
            ];

    // Nota: no preparamos aquí una sentencia con placeholders con espacios.
    // El INSERT por lotes se construye dinámicamente más abajo usando nombres de columna entre corchetes
    // y placeholders sanitizados para evitar parámetros PDO con espacios en blanco.

    // Prepared statement to check duplicates: prefer DOCENTRY+DOCNUM, fallback to NUMERO_PRESTAMO
    $checkByDoc = $this->pdo->prepare("SELECT COUNT(1) as c FROM dbo.StagingVentasImport WHERE DOCENTRY = :DOCENTRY AND DOCNUM = :DOCNUM");
    $checkByPrestamo = $this->pdo->prepare("SELECT COUNT(1) as c FROM dbo.StagingVentasImport WHERE NUMERO_PRESTAMO = :NUMERO_PRESTAMO");

    $processed = 0;
    $inserted = 0;
    $errors = 0;
    $duplicates = 0;
    $duplicateSamples = [];

    // Procesamiento por lotes y streaming para CSV
    $batchSize = 5000; // commit cada N filas (ajustado para menor overhead)
        $currentInBatch = 0;

        // Precargar claves existentes en memoria para evitar un SELECT por fila
        $existingDoc = [];
        $existingPrestamo = [];
        $preloadLoaded = false;
        try {
            $q = $this->pdo->query("SELECT DOCENTRY, DOCNUM, NUMERO_PRESTAMO FROM dbo.StagingVentasImport");
            $preloadLoaded = true;
            while ($r = $q->fetch(\PDO::FETCH_ASSOC)) {
                $de = isset($r['DOCENTRY']) ? trim((string)$r['DOCENTRY']) : '';
                $dn = isset($r['DOCNUM']) ? trim((string)$r['DOCNUM']) : '';
                if ($de !== '' && $dn !== '') $existingDoc[$de . '|' . $dn] = true;
                $np = isset($r['NUMERO_PRESTAMO']) ? trim((string)$r['NUMERO_PRESTAMO']) : '';
                if ($np !== '') $existingPrestamo[$np] = true;
            }
        } catch (\Throwable $t) {
            // si falla la precarga, seguimos con la comprobación por consulta preparada (fallback)
        }

        // Iterator que devuelve pares [header,arrayRow]
        $iterator = function() use ($filePath, $ext) {
            if ($ext === 'csv') {
                $handle = fopen($filePath, 'r');
                if ($handle === false) throw new \Exception('No se puede abrir el archivo CSV.');
                $headerRaw = fgetcsv($handle);
                if ($headerRaw === false) { fclose($handle); return; }
                $header = array_map(function($h){ return strtoupper(trim($h)); }, $headerRaw);
                // also provide normalized header map (spaces -> underscore)
                $headerNormalized = array_map(function($h){ return strtoupper(str_replace(' ', '_', trim($h))); }, $headerRaw);
                while (($row = fgetcsv($handle)) !== false) {
                    yield [$header, $row];
                }
                fclose($handle);
            } else {
                $rows = $this->getRowsFromFile($filePath, $ext);
                if (empty($rows)) return;
                $headerRaw = array_shift($rows);
                $header = array_map(function($h){ return strtoupper(trim($h)); }, $headerRaw);
                // also provide normalized header map (spaces -> underscore)
                $headerNormalized = array_map(function($h){ return strtoupper(str_replace(' ', '_', trim($h))); }, $headerRaw);
                foreach ($rows as $row) yield [$header, $row];
            }
        };

        // Iterar y procesar en batches
        $this->pdo->beginTransaction();
        try {
            // Parámetros para inserciones por lotes (INSERT ... VALUES (...),(...))
            $insertChunk = 5000; // número de filas por INSERT multi-row (ajustable)
            // Ajustar insertChunk para respetar el límite de parámetros de SQL Server (2100)
            $maxSqlServerParams = 2000; // margen antes de 2100
            $colsCount = count($expected);
            $maxAllowedChunk = (int) floor($maxSqlServerParams / max(1, $colsCount));
            if ($insertChunk > $maxAllowedChunk && $maxAllowedChunk > 0) {
                $insertChunk = $maxAllowedChunk;
                // opcional: log ajuste
                try { file_put_contents(__DIR__ . '/../uploads/import_progress.log', date('Y-m-d H:i:s') . " | insertChunk adjusted to {$insertChunk} to fit SQL Server param limit\n", FILE_APPEND | LOCK_EX); } catch (\Throwable $t) { }
            }
            $batchInsertRows = [];

            $doInsertBatch = function(array $rows) use ($expected) {
                if (empty($rows)) return 0;
                // Construir SQL con columnas entre corchetes (soporta espacios) y placeholders sanitizados
                $colsSqlParts = array_map(function($c){ return '[' . $c . ']'; }, $expected);
                $colsSql = implode(',', $colsSqlParts);
                $placeGroups = [];
                $params = [];
                $i = 0;
                foreach ($rows as $r) {
                    $placeholders = [];
                    foreach ($expected as $col) {
                        // sanitizar nombre para usar en placeholder (sin espacios ni caracteres inválidos)
                        $san = preg_replace('/[^A-Z0-9_]/', '_', strtoupper(str_replace(' ', '_', $col)));
                        $ph = ':' . $san . '_' . $i;
                        $placeholders[] = $ph;
                        $params[$ph] = $r[$san] ?? null;
                    }
                    $placeGroups[] = '(' . implode(',', $placeholders) . ')';
                    $i++;
                }
                $sql = "INSERT INTO dbo.StagingVentasImport (" . $colsSql . ") VALUES " . implode(',', $placeGroups);
                try {
                    $stmt = $this->pdo->prepare($sql);
                    // bind parameters
                    foreach ($params as $ph => $val) {
                        $stmt->bindValue($ph, $val);
                    }
                    $stmt->execute();
                    return count($rows);
                } catch (\Throwable $t) {
                    // registrar el SQL reducido y el error para diagnóstico
                    try {
                        $logPath = __DIR__ . '/../uploads/import_errors.log';
                        $msg = date('Y-m-d H:i:s') . " | doInsertBatch failed | rows=" . count($rows) . " | error=" . $t->getMessage() . "\n";
                        file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
                    } catch (\Throwable $__) { }
                    throw $t;
                }
            };
            foreach ($iterator() as $pair) {
                list($header, $row) = $pair;
                $processed++;

                // Normalizar fila a assoc por cabecera (incluir versión normalizada con underscores)
                $rowAssoc = [];
                foreach ($header as $i => $h) {
                    $val = isset($row[$i]) ? (is_string($row[$i]) ? trim($row[$i]) : $row[$i]) : null;
                    $rowAssoc[$h] = $val;
                    // also store normalized key (spaces -> underscore)
                    $rowAssoc[str_replace(' ', '_', $h)] = $val;
                }

                try {
                    // check duplicate
                    $isDuplicate = false;
                    $docentry = $rowAssoc['DOCENTRY'] ?? null;
                    $docnum = $rowAssoc['DOCNUM'] ?? null;
                    $numeroPrestamo = $rowAssoc['NUMERO_PRESTAMO'] ?? null;

                    if ($docentry !== null && $docnum !== null && $docentry !== '' && $docnum !== '') {
                        $key = trim((string)$docentry) . '|' . trim((string)$docnum);
                        if (isset($existingDoc[$key])) {
                            $isDuplicate = true;
                        } else {
                            // Si precarga exitosa, evitamos consultas por fila y consideramos nuevo
                            if (!$preloadLoaded) {
                                try {
                                    $checkByDoc->execute([':DOCENTRY' => $docentry, ':DOCNUM' => $docnum]);
                                    $c = $checkByDoc->fetchColumn();
                                    if ($c > 0) {
                                        $isDuplicate = true;
                                        $existingDoc[$key] = true; // actualizar caché
                                    }
                                } catch (\Throwable $t) {
                                    // ignore
                                }
                            }
                        }
                    } elseif ($numeroPrestamo !== null && $numeroPrestamo !== '') {
                        $k2 = trim((string)$numeroPrestamo);
                        if (isset($existingPrestamo[$k2])) {
                            $isDuplicate = true;
                        } else {
                            if (!$preloadLoaded) {
                                try {
                                    $checkByPrestamo->execute([':NUMERO_PRESTAMO' => $numeroPrestamo]);
                                    $c = $checkByPrestamo->fetchColumn();
                                    if ($c > 0) {
                                        $isDuplicate = true;
                                        $existingPrestamo[$k2] = true;
                                    }
                                } catch (\Throwable $t) {
                                    // ignore
                                }
                            }
                        }
                    }

                    if ($isDuplicate) {
                        $duplicates++;
                        if (count($duplicateSamples) < 20) {
                            $duplicateSamples[] = ['DOCENTRY' => $docentry, 'DOCNUM' => $docnum, 'NUMERO_PRESTAMO' => $numeroPrestamo];
                        }
                    } else {
                        // Mapear a array para inserción por lotes
                        // Normalizar claves: convertir nombres esperados a forma SAN (sin espacios, mayúsculas)
                        $rowForInsert = [];
                        foreach ($expected as $col) {
                            $norm = strtoupper(str_replace(' ', '_', $col));
                            // aceptar tanto cabeceras con espacio como con guion_bajo en el CSV
                            $val = null;
                            if (array_key_exists($col, $rowAssoc)) $val = $rowAssoc[$col];
                            elseif (array_key_exists($norm, $rowAssoc)) $val = $rowAssoc[$norm];
                            elseif (array_key_exists(str_replace('_', ' ', $norm), $rowAssoc)) $val = $rowAssoc[str_replace('_', ' ', $norm)];
                            // store under normalized key used by placeholders
                            $rowForInsert[$norm] = $val ?? null;
                        }
                        $batchInsertRows[] = $rowForInsert;

                        // actualizar cachés inmediatamente para evitar que filas siguientes se dupliquen dentro del mismo archivo
                        if (!empty($docentry) && !empty($docnum)) {
                            $existingDoc[trim((string)$docentry) . '|' . trim((string)$docnum)] = true;
                        }
                        if (!empty($numeroPrestamo)) {
                            $existingPrestamo[trim((string)$numeroPrestamo)] = true;
                        }

                        // Si alcanzamos chunk para INSERT multi-row, ejecutarlo
                        if (count($batchInsertRows) >= $insertChunk) {
                            $added = $doInsertBatch($batchInsertRows);
                            $inserted += $added;
                            $batchInsertRows = [];
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    // opcional: log
                }

                $currentInBatch++;
                if ($currentInBatch >= $batchSize) {
                    // Antes de commitear, asegurarnos de volcar cualquier INSERT acumulado que no haya alcanzado insertChunk
                    if (!empty($batchInsertRows)) {
                        try {
                            $added = $doInsertBatch($batchInsertRows);
                            $inserted += $added;
                        } catch (\Throwable $t) {
                            // si falla el insert en medio de un batch, rollBack y relanzar
                            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
                            throw $t;
                        }
                        $batchInsertRows = [];
                    }

                    $this->pdo->commit();
                    // Log de progreso por lote
                    try {
                        $logPath = __DIR__ . '/../uploads/import_progress.log';
                        $line = date('Y-m-d H:i:s') . " | batch committed | processed={$processed} | inserted={$inserted} | duplicates={$duplicates}\n";
                        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
                    } catch (\Throwable $t) { /* ignore logging errors */ }
                    // comenzar nueva transacción
                    $this->pdo->beginTransaction();
                    $currentInBatch = 0;
                }

                // liberar memoria temporal
                unset($rowAssoc, $params, $row);
                if (($processed % 1000) === 0) gc_collect_cycles();
            }

            // flush remaining batched inserts
            if (!empty($batchInsertRows)) {
                try {
                    $added = $doInsertBatch($batchInsertRows);
                    $inserted += $added;
                    $batchInsertRows = [];
                } catch (\Throwable $t) {
                    // Si falla el insert final, marcar error y continuar con rollback abajo
                    throw $t;
                }
            }

            if ($this->pdo->inTransaction()) $this->pdo->commit();
            // Si se solicita, ejecutar el procedimiento almacenado para distribuir los datos
            $spResult = null;
            if ($runSp) {
                try {
                    // Ejecutar el SP usando placeholder posicional para mayor compatibilidad con PDO SQL Server
                    // Algunos drivers no manejan bien named params en la clausula EXEC; use '?' y un array posicional
                    $stmtSp = $this->pdo->prepare("EXEC dbo.sp_ImportFromStaging @OnlyUnimported = ?");
                    $stmtSp->execute([1]);

                    // Intentar recolectar todos los resultsets si el procedimiento devuelve varios
                    $collected = [];
                    if ($stmtSp !== false) {
                        do {
                            $f = $stmtSp->fetchAll(\PDO::FETCH_ASSOC);
                            if ($f !== false && count($f) > 0) {
                                $collected = array_merge($collected, $f);
                            }
                        } while ($stmtSp->nextRowset());
                    }

                    if (!empty($collected)) {
                        $spResult = $collected;
                    } else {
                        // Si no hubo filas devueltas, devolvemos estado de ejecución y rowCount
                        $rowCount = null;
                        try { $rowCount = $stmtSp->rowCount(); } catch (\Throwable $__) { $rowCount = null; }
                        $spResult = ['executed' => true, 'rowCount' => $rowCount];
                    }

                    // Log simple de ejecución para trazabilidad
                    try {
                        $logPath = __DIR__ . '/../uploads/import_progress.log';
                        file_put_contents($logPath, date('Y-m-d H:i:s') . " | SP executed by ImportService | file={$filePath} | result_summary=" . json_encode(is_array($spResult) ? array_slice($spResult,0,5) : $spResult, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
                    } catch (\Throwable $__) { /* ignore logging errors */ }

                } catch (\Throwable $t) {
                    try {
                        $logFile = __DIR__ . '/../uploads/import_errors.log';
                        $entry = date('Y-m-d H:i:s') . " | SP ERROR from ImportService: " . $t->getMessage() . "\n" . $t->getTraceAsString() . "\n----\n";
                        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
                    } catch (\Throwable $__) { }
                    $spResult = ['error' => $t->getMessage()];
                }
            }
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }

        $out = [
            'processed' => $processed,
            'inserted' => $inserted,
            'duplicates' => $duplicates,
            'duplicate_samples' => $duplicateSamples,
            'errors' => $errors
        ];
        if ($runSp) $out['sp_result'] = $spResult;
        return $out;
    }

    /**
     * Previsualiza un archivo y devuelve conteos: total filas leídas, duplicados detectados y muestras.
     * No modifica la base de datos.
     */
    public function previewFileCounts(string $filePath, int $limit = 1000): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rows = $this->getRowsFromFile($filePath, $ext);
        if (empty($rows)) return ['total' => 0, 'checked' => 0, 'duplicates' => 0, 'samples' => []];

        $headerRaw = array_shift($rows);
    $header = array_map(function($h){ return strtoupper(trim($h)); }, $headerRaw);
    $headerNormalized = array_map(function($h){ return strtoupper(str_replace(' ', '_', trim($h))); }, $headerRaw);

        $checked = 0;
        $duplicates = 0;
        $samples = [];

        // prepared duplicate checks
        $checkByDoc = $this->pdo->prepare("SELECT COUNT(1) as c FROM dbo.StagingVentasImport WHERE DOCENTRY = :DOCENTRY AND DOCNUM = :DOCNUM");
        $checkByPrestamo = $this->pdo->prepare("SELECT COUNT(1) as c FROM dbo.StagingVentasImport WHERE NUMERO_PRESTAMO = :NUMERO_PRESTAMO");

        foreach ($rows as $row) {
            if ($checked >= $limit) break;
            $checked++;
            $rowAssoc = [];
            foreach ($header as $i => $h) {
                $val = isset($row[$i]) ? (is_string($row[$i]) ? trim($row[$i]) : $row[$i]) : null;
                $rowAssoc[$h] = $val;
                $rowAssoc[$headerNormalized[$i]] = $val;
            }

            $isDuplicate = false;
            $docentry = $rowAssoc['DOCENTRY'] ?? null;
            $docnum = $rowAssoc['DOCNUM'] ?? null;
            $numeroPrestamo = $rowAssoc['NUMERO_PRESTAMO'] ?? null;

            if ($docentry !== null && $docnum !== null && $docentry !== '' && $docnum !== '') {
                $checkByDoc->execute([':DOCENTRY' => $docentry, ':DOCNUM' => $docnum]);
                $c = $checkByDoc->fetchColumn();
                if ($c > 0) $isDuplicate = true;
            } elseif ($numeroPrestamo !== null && $numeroPrestamo !== '') {
                $checkByPrestamo->execute([':NUMERO_PRESTAMO' => $numeroPrestamo]);
                $c = $checkByPrestamo->fetchColumn();
                if ($c > 0) $isDuplicate = true;
            }

            if ($isDuplicate) {
                $duplicates++;
                if (count($samples) < 20) $samples[] = ['DOCENTRY'=>$docentry,'DOCNUM'=>$docnum,'NUMERO_PRESTAMO'=>$numeroPrestamo];
            }
        }

        return ['total' => count($rows) + 1, 'checked' => $checked, 'duplicates' => $duplicates, 'samples' => $samples];
    }

    /**
     * Devuelve un array de filas (cada fila es array indexado) a partir del archivo.
     * Para XLS/XLSX usa PhpSpreadsheet si está disponible.
     */
    private function getRowsFromFile(string $path, string $ext): array
    {
        $ext = strtolower($ext);
        if ($ext === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle === false) throw new \Exception('No se puede abrir el archivo CSV.');
            $rows = [];
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
            return $rows;
        }

        // xls/xlsx
        // intentar cargar autoload de composer
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            // intentar ruta relativa al root
            $autoload = __DIR__ . '/../../vendor/autoload.php';
        }
        if (!file_exists($autoload)) {
            throw new \Exception('PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet');
        }
        require_once $autoload;

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            // toArray devuelve filas indexadas desde 0 si usamos false, true, true, true devuelve columna por letra
            $data = $sheet->toArray(null, true, true, false);
            return $data;
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            throw new \Exception('Error al leer archivo Excel: ' . $e->getMessage());
        }
    }
}
