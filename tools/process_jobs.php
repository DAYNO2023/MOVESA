<?php
// worker CLI simple: procesa jobs en uploads/jobs/*.json
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Services/ImportService.php';

$jobsDir = __DIR__ . '/../uploads/jobs';
if (!is_dir($jobsDir)) {
    echo "No jobs dir\n";
    exit(0);
}

$files = glob($jobsDir . DIRECTORY_SEPARATOR . '*.json');
if (empty($files)) {
    echo "No pending jobs\n";
    exit(0);
}

foreach ($files as $f) {
    $data = json_decode(file_get_contents($f), true);
    if (!is_array($data)) continue;
    $jobId = $data['id'] ?? basename($f, '.json');
    $resultFile = $jobsDir . DIRECTORY_SEPARATOR . $jobId . '.result.json';

    // Skip processed
    if (file_exists($resultFile)) continue;

    echo "Processing job: $jobId\n";
    $filePath = $data['file'] ?? null;
    if (!$filePath || !file_exists($filePath)) {
        file_put_contents($resultFile, json_encode(['status'=>'error','message'=>'file not found','job'=>$data], JSON_UNESCAPED_UNICODE));
        continue;
    }

    try {
        $pdo = getDbConnection();
    $service = new Services\ImportService($pdo);
    // Ejecutar import y SP desde el servicio (runSp=true)
    $stagingRes = $service->importCsvToStaging($filePath, true);

    $out = ['status'=>'done','result'=>$stagingRes,'processed_at'=>date('c')];
        file_put_contents($resultFile, json_encode($out, JSON_UNESCAPED_UNICODE));

    } catch (\Throwable $e) {
        file_put_contents($resultFile, json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE));
    }
}

echo "Done\n";
