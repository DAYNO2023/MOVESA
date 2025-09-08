<?php
// Iniciar buffer de salida desde el principio para evitar salidas accidentales / warnings que rompan JSON
if (!ob_get_level()) ob_start();

// Lógica de recepción de archivo para importación (responde JSON si se envía vía AJAX)
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Services/ImportService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['archivo']) || isset($_FILES['excel']))) {
    // header y manejo de POST (mantener como está)
    header('Content-Type: application/json; charset=utf-8');

    $fileField = isset($_FILES['archivo']) ? 'archivo' : 'excel';
    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Error en la subida del archivo.']);
        if (ob_get_level()) ob_end_flush();
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // Por ahora solo CSV está soportado por el servicio ImportService.
    if (!in_array($ext, ['csv'])) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Formato no soportado. Convierta a CSV y vuelva a intentar.']);
        if (ob_get_level()) ob_end_flush();
        exit;
    }

    $uploadsDir = __DIR__ . '/../../uploads';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

    $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
    $destPath = $uploadsDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'No se pudo mover el archivo al directorio de uploads.']);
        if (ob_get_level()) ob_end_flush();
        exit;
    }

    try {
        $pdo = getDbConnection();
        $service = new Services\ImportService($pdo);
        $result = $service->importCsvToStaging($destPath);

        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'result' => $result, 'file' => $safeName]);
        if (ob_get_level()) ob_end_flush();
    } catch (Exception $e) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Error al procesar: ' . $e->getMessage()]);
        if (ob_get_level()) ob_end_flush();
    }
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Ventas</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
  <style>
    /* Estilos para el dropzone */
    .dropzone {
      border: 2px dashed #6c757d;
      border-radius: 6px;
      padding: 30px;
      text-align: center;
      cursor: pointer;
      color: #6c757d;
      transition: background-color 0.15s, border-color 0.15s;
    }
    .dropzone.dragover {
      background-color: #e9f7ff;
      border-color: #17a2b8;
      color: #0c5460;
    }
    .small-note { font-size: 0.9rem; color: #6c757d; }
  /* Ajustes de DataTables para layout similar al ejemplo */
  div.dataTables_wrapper div.dataTables_length { float: left; }
  div.dataTables_wrapper div.dataTables_filter { float: right; text-align: right; }
  .dataTables_wrapper .dataTables_paginate .paginate_button { padding: .25rem .6rem; }
  .card-header.bg-warning { background-color: #ffc107 !important; }
  .table thead th { vertical-align: middle; }
  /* Alternar color de filas para imitar ejemplo */
  table.dataTable tbody tr:nth-child(even) { background-color: #f8f9fa; }
  </style>
</head>
<body>

<div class="container mt-2">
  <div class="card">
    <div class="card-header bg-warning text-center font-weight-bold" style="border-top-left-radius: .25rem; border-top-right-radius: .25rem;">
      Ventas
    </div>
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <!-- placeholder left (DataTables length will be here) -->
        </div>
        <div>
          <!-- botón superior eliminado: ahora use el botón del hero abajo -->
        </div>
      </div>

      <!-- Alert dinámico resultado import -->
      <div id="importAlertPlaceholder"></div>

      <!-- Pantalla principal elegante: hero + tarjetas de resumen + dropzone central -->
  <div class="py-3 text-center">
        <h2 class="display-5 font-weight-bold">Panel de Ventas</h2>
        <p class="lead text-muted">Sube tus archivos CSV o Excel para procesar las ventas. Usa el botón "Subir Archivo" o arrastra el archivo al área.</p>
      </div>

  <!-- Resumen eliminado por petición: se muestra solo el dropzone hero -->

      <div class="row justify-content-center">
        <div class="col-md-8">
          <div id="mainDropzone" class="dropzone text-center p-3 shadow-sm" tabindex="0">
            <div id="dropzoneHero"><i class="fas fa-file-upload fa-2x mb-3"></i></div>
            <div id="dropzoneTextHero" class="h5">Arrastra tu archivo aquí o haz click para seleccionar</div>
            <div id="fileNameHero" class="mt-2 small-note font-weight-bold"></div>
            <div class="mt-4">
              <button class="btn btn-primary btn-lg" id="btnUploadHero">Subir archivo</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal eliminado: la subida se realiza desde el hero directamente -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

<script>
  $(document).ready(function() {
    // Pantalla principal no usa DataTables
    // Inicializar estadísticas (placeholder)
    $('#stat-total').text('0');
    $('#stat-importe').text('0.00');
    $('#stat-last').text('—');
  });

  // Dropzone logic: crear input file oculto si no existe y enlazar eventos al hero dropzone
  (function() {
    // Asegurar que exista un input file oculto en la página
    let fileInput = document.getElementById('fileInput');
    if (!fileInput) {
      fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.id = 'fileInput';
      fileInput.accept = '.csv,.xls,.xlsx';
      fileInput.style.display = 'none';
      document.body.appendChild(fileInput);
    }

    const heroDropzone = document.getElementById('mainDropzone');
    const fileNameHero = document.getElementById('fileNameHero');

    function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

    // Eventos de arrastre/soltar en el heroDropzone
    ['dragenter','dragover','dragleave','drop'].forEach(evt => {
      heroDropzone.addEventListener(evt, preventDefaults, false);
    });

    ['dragenter','dragover'].forEach(evt => {
      heroDropzone.addEventListener(evt, () => heroDropzone.classList.add('dragover'), false);
    });
    ['dragleave','drop'].forEach(evt => {
      heroDropzone.addEventListener(evt, () => heroDropzone.classList.remove('dragover'), false);
    });

    // Click / teclado para abrir el selector
    heroDropzone.addEventListener('click', () => fileInput.click());
    heroDropzone.addEventListener('keypress', (e) => { if (e.key === 'Enter' || e.key === ' ') fileInput.click(); });

    heroDropzone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      if (!dt) return;
      const files = dt.files;
      handleFiles(files);
    });

    fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

    function handleFiles(files) {
      if (!files || files.length === 0) return;
      const file = files[0];
      const allowed = ['text/csv','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (!allowed.includes(file.type) && !['csv','xls','xlsx'].includes(ext)) {
        alert('Formato no permitido. Use .csv, .xls o .xlsx');
        fileInput.value = '';
        if (fileNameHero) fileNameHero.textContent = '';
        return;
      }
      if (fileNameHero) fileNameHero.textContent = file.name;
      // Guardar archivo seleccionado para la subida real
      fileInput._selectedFile = file;
    }

    // Función reutilizable para subir archivos al servidor
    function performUpload(file, onSuccess, onError, btn){
      if(!file){ if(onError) onError(new Error('No file seleccionado')); return; }
      var fd = new FormData(); fd.append('archivo', file);
      if(btn){ btn.disabled = true; btn.textContent = 'Subiendo...'; }
      fetch('/MOVESA/Controllers/ImportApi.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(res => res.json())
      .then(data => {
        if(btn){ btn.disabled = false; btn.textContent = 'Subir archivo'; }
        if(onSuccess) onSuccess(data);
      }).catch(err => { if(btn){ btn.disabled = false; btn.textContent = 'Subir archivo'; } if(onError) onError(err); });
    }

    // Hero upload button: realizar la subida directamente usando el fileInput
    const btnUploadHero = document.getElementById('btnUploadHero');
    if (btnUploadHero) {
      btnUploadHero.addEventListener('click', function(){
        var f = fileInput._selectedFile;
        var btn = this; if(!f){ alert('Seleccione un archivo antes de subir.'); return; }
        performUpload(f, function(data){
          var placeholder = document.getElementById('importAlertPlaceholder');
          if(data && data.success){
            var r = data.result || {};
            var html = '<div class="alert alert-success alert-dismissible fade show" role="alert">Archivo importado: <strong>'+ (data.file||'') +'</strong><br>Filas procesadas: '+(r.processed||0)+', insertadas: '+(r.inserted||0)+', errores: '+(r.errors||0)+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            placeholder.innerHTML = html;
          } else {
            var html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Error: '+(data.message||'Error desconocido')+'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            placeholder.innerHTML = html;
          }
          // limpiar selección
          fileInput.value = ''; fileInput._selectedFile = null; if (fileNameHero) fileNameHero.textContent = '';
        }, function(err){
          alert('Error en la subida: '+ err.message);
        }, btn);
      });
    }
  })();
</script>

</body>
</html>
