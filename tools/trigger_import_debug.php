<?php
// Script CLI: hace POST a ImportApi.php?debug=1 con un archivo de uploads y muestra la respuesta
$uploads = __DIR__ . '/../uploads';
$files = glob($uploads . DIRECTORY_SEPARATOR . '*data_ventas.xlsx');
if (empty($files)) {
    echo "No hay archivos de prueba en uploads\n";
    exit(1);
}
$file = $files[0];
$url = 'http://localhost/MOVESA/Controllers/ImportApi.php?debug=1';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Expect:' ]);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [ 'archivo' => new CURLFile($file) ]);

$resp = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "CURL ERROR: " . ($err ?: '(none)') . "\n";
echo "HTTP CODE: " . ($info['http_code'] ?? 'n/a') . "\n";
echo "RESPONSE:\n" . ($resp ?? '') . "\n";
