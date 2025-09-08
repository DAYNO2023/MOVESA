<!-- Incluye Bootstrap y DataTables -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../Views/Resources/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">

<style>
  /* Estilos específicos para los filtros del pivot */
  .pivot-filters .input-group-text {
    font-size: 1.05rem;
    padding: 0.6rem 0.9rem;
  }
  .pivot-filters .form-control-lg,
  .pivot-filters .custom-select-lg {
    font-size: 1rem;
    padding: 0.5rem 0.75rem;
    min-width: 220px;
    color: #212529; /* asegurar color legible */
    background-color: #fff;
  }
  .pivot-filters .btn-lg {
    padding: 0.55rem 1rem;
    font-size: 1rem;
  }
  .pivot-filters .form-row { margin-bottom: 0.75rem; }
</style>

<div class="container mt-4">
  <div class="card">
    <div class="card-header bg-warning text-brown font-weight-bold">
      Indicador en formato pivot
      <div class="small font-weight-normal text-dark">
        Filtra por año y mes para ver el desglose de ventas por sucursal.
      </div>
    </div>
    <div class="card-body">
      <!-- Filtro de año-mes -->
      <!-- Reemplazamos el input tipo date por dos selects: mes y año -->
  <?php
    // Generar meses y años en servidor para evitar problemas de renderizado JS
    $mesesArr = [
      '1' => 'Enero','2' => 'Febrero','3' => 'Marzo','4' => 'Abril','5' => 'Mayo','6' => 'Junio',
      '7' => 'Julio','8' => 'Agosto','9' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre'
    ];
    $currentYearSrv = (int)date('Y');
    $yearsBackSrv = 5;
    $defaultMonthSrv = date('m');
    $defaultYearSrv = date('Y');
  ?>

  <form class="mb-3 pivot-filters">
    <div class="form-row align-items-center">
          <div class="col-auto form-group">
            <label class="sr-only" for="filtroMes">Mes</label>
            <div class="input-group">
              <div class="input-group-prepend"><div class="input-group-text">Mes</div></div>
              <select id="filtroMes" class="custom-select custom-select-lg">
                <option value="" disabled>Seleccione mes</option>
                <?php foreach($mesesArr as $mv => $mt): ?>
                  <option value="<?= $mv ?>" <?= $mv === $defaultMonthSrv ? 'selected' : '' ?>><?= $mt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="col-auto form-group">
            <label class="sr-only" for="filtroAno">Año</label>
            <div class="input-group">
              <div class="input-group-prepend"><div class="input-group-text">Año</div></div>
              <select id="filtroAno" class="custom-select custom-select-lg">
                <option value="" disabled>Seleccione año</option>
                <?php for ($y = $currentYearSrv; $y >= $currentYearSrv - $yearsBackSrv; $y--): ?>
                  <option value="<?= $y ?>" <?= $y == $defaultYearSrv ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>

          <div class="col-auto form-group">
            <button type="button" id="btnFiltrar" class="btn btn-primary btn-lg">Filtrar</button>
            <button type="button" id="btnPreview" class="btn btn-secondary btn-lg ml-2">Vista previa (PDF)</button>
          </div>
        </div>
      </form>
      <div id="pivotStatus" class="mb-3"></div>
      <div class="table-responsive">
        <table id="tablaPivot" class="table table-bordered table-striped">
          <thead></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal para vista previa PDF -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" role="dialog" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document" style="max-width:95%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pdfPreviewModalLabel">Vista previa PDF</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <iframe id="pdfPreviewFrame" style="width:100%;height:80vh;border:0;" frameborder="0"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <a id="pdfDownloadLink" class="btn btn-primary" href="#" download="resumen.pdf">Descargar PDF</a>
      </div>
    </div>
  </div>
</div>

<!-- Scripts de DataTables -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="../Views/Resources/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../Views/Resources/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
$(function() {
  // Enlazar botón filtrar
  $('#btnFiltrar').on('click', filtrarTabla);
  // Enlazar preview
  $('#btnPreview').on('click', function() {
    // generar vista previa solo si hay tabla con contenido
    if ($('#tablaPivot tbody tr').length === 0) {
      $('#pivotStatus').removeClass().addClass('alert alert-warning').text('No hay datos para generar la vista previa.');
      return;
    }
    generatePdfPreview();
  });

  // Cargar datos inicialmente para los valores por defecto del servidor
  const defaultMonth = parseInt($('#filtroMes').val(), 10);
  const defaultYear = parseInt($('#filtroAno').val(), 10);
  if (defaultMonth && defaultYear) cargarPivot(defaultYear, defaultMonth);

  function filtrarTabla() {
    const mes = parseInt($('#filtroMes').val(), 10);
    const ano = parseInt($('#filtroAno').val(), 10);
    const $status = $('#pivotStatus');
    $status.removeClass().empty();
    if (!mes || !ano) {
      $status.addClass('alert alert-warning');
      $status.text('Selecciona mes y año antes de filtrar.');
      return;
    }

    $status.addClass('alert alert-info');
    $status.text('Cargando datos...');
    cargarPivot(ano, mes);
  }

  function cargarPivot(year, month) {
    const $table = $('#tablaPivot');
    $table.find('thead').html('<tr><th>...cargando</th></tr>');
    $table.find('tbody').html('');
    $('#pivotStatus').removeClass().addClass('alert alert-info').text('Cargando...');

    $.ajax({
      url: '/MOVESA/Controllers/ResumenPivotApi.php',
      method: 'GET',
      data: { anio: year, mes: month },
      dataType: 'json'
    }).done(function(resp) {
      if (!resp || !resp.data) {
        $table.find('thead').html('<tr><th>No hay datos</th></tr>');
        $('#pivotStatus').removeClass().addClass('alert alert-secondary').text('No hay datos recibidos del servidor.');
        return;
      }

      const rows = resp.data;
      if (rows.length === 0) {
        $table.find('thead').html('<tr><th>No hay datos para el periodo</th></tr>');
        $('#pivotStatus').removeClass().addClass('alert alert-secondary').text('No hay datos para el periodo seleccionado.');
        return;
      }

      // Preparar estructura de semanas para el mes
      const daysInMonth = new Date(year, month, 0).getDate(); // month es 1..12
      const firstDate = new Date(year, month - 1, 1);
      // weekday 0=Dom,1=Lun..6=Sab
      const firstWeekday = firstDate.getDay();
      // calcular primer domingo (día número)
      const firstSunday = (firstWeekday === 0) ? 1 : (1 + (7 - firstWeekday));

      // Agrupar días por semana (semanas terminan en domingo)
      const weeks = [];
      let currentWeek = [];
      for (let d = 1; d <= daysInMonth; d++) {
        currentWeek.push(d);
        // si es domingo o último día del mes, cerrar semana
        const date = new Date(year, month - 1, d);
        if (date.getDay() === 0 || d === daysInMonth) {
          weeks.push(currentWeek);
          currentWeek = [];
        }
      }

      // Construir encabezado: CANAL, TIPO, luego días por semana con columna TS al final de cada semana, y TOTAL al final
      let theadHtml = '<tr class="bg-info text-white">';
      theadHtml += '<th class="text-center">Sucursal</th>';
      theadHtml += '<th class="text-center">TIPO</th>';

      // Para cada semana crear los th de días
      weeks.forEach((wk, wi) => {
        wk.forEach(d => {
          const weekday = new Date(year, month - 1, d).getDay();
          const weekdayLabels = ['do.', 'lu.', 'ma.', 'mi.', 'ju.', 'vi.', 'sá.'];
          theadHtml += `<th class="text-center small">${weekdayLabels[weekday]} ${('0'+d).slice(-2)}</th>`;
        });
        // th de TS para la semana
        theadHtml += `<th class="text-center" style="background:#cfe8f7">TS</th>`;
      });

      theadHtml += '<th class="text-center font-weight-bold" style="background:#ffd54f">TOTAL</th>';
      theadHtml += '</tr>';
      $table.find('thead').html(theadHtml);

      // Rellenar body
      // Inicializar acumuladores por columna para fila TOTAL al final
      const totalsByDay = {};
      const totalsByWeekTS = Array(weeks.length).fill(0);
      let grandTotal = 0;

      let bodyHtml = '';
      rows.forEach(r => {
        bodyHtml += '<tr>';
        // CANAL y TIPO - tratar claves posiblemente en mayúsculas/minúsculas
        const canalKey = Object.keys(r).find(k => k.toLowerCase().includes('sucursal')) || Object.keys(r)[0];
        const tipoKey = Object.keys(r).find(k => k.toLowerCase().includes('tipo')) || Object.keys(r)[1] || Object.keys(r)[0];
        bodyHtml += `<td class="text-left">${r[canalKey] ?? ''}</td>`;
        bodyHtml += `<td class="text-left">${r[tipoKey] ?? ''}</td>`;

        // Para cada semana sumar y mostrar cada día
        let rowTotal = 0;
        weeks.forEach((wk, wi) => {
          let weekSum = 0;
          wk.forEach(d => {
            const key = 'D' + ('0' + d).slice(-2);
            let val = 0;
            if (Object.prototype.hasOwnProperty.call(r, key)) {
              val = r[key] == null ? 0 : Number(r[key]);
            }
            // render celda
            bodyHtml += `<td class="text-right">${val}</td>`;
            weekSum += val;

            // acumular para totales por columna
            totalsByDay[key] = (totalsByDay[key] || 0) + val;
          });
          // columna TS con suma de la semana
          bodyHtml += `<td class="text-right" style="background:#cfe8f7">${weekSum}</td>`;
          totalsByWeekTS[wi] += weekSum;
          rowTotal += weekSum;
        });

        bodyHtml += `<td class="text-right font-weight-bold" style="background:#ffd54f">${rowTotal}</td>`;
        grandTotal += rowTotal;
        bodyHtml += '</tr>';
      });

      // Fila TOTAL final
      let totalRowHtml = '<tr class="font-weight-bold" style="background:#ffd54f">';
      totalRowHtml += '<td class="text-center">TOTAL</td>';
      totalRowHtml += '<td></td>';

      // Por cada semana, añadir totales por día y TS intercalado
      weeks.forEach((wk, wi) => {
        wk.forEach(d => {
          const key = 'D' + ('0' + d).slice(-2);
          const v = totalsByDay[key] || 0;
          totalRowHtml += `<td class="text-right">${v}</td>`;
        });
        // TS total de la semana
        totalRowHtml += `<td class="text-right">${totalsByWeekTS[wi] || 0}</td>`;
      });

      totalRowHtml += `<td class="text-right">${grandTotal}</td>`;
      totalRowHtml += '</tr>';

      $table.find('tbody').html(bodyHtml + totalRowHtml);

      $('#pivotStatus').removeClass().addClass('alert alert-success').text('Datos cargados. Filas: ' + rows.length);

    }).fail(function(xhr, status, err) {
      $table.find('thead').html('<tr><th>Error al obtener datos</th></tr>');
      $table.find('tbody').html('<tr><td>' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Error') + '</td></tr>');
      $('#pivotStatus').removeClass().addClass('alert alert-danger').text('Error al obtener datos: ' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : xhr.statusText || 'Error'));
    });
  }
});

async function loadScript(url) {
  return new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = url;
    s.onload = () => resolve();
    s.onerror = () => reject(new Error('Failed to load ' + url));
    document.head.appendChild(s);
  });
}

async function ensurePdfLibs() {
  // html2canvas
  if (typeof html2canvas === 'undefined') {
    await loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
  }
  // jsPDF (UMD)
  if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
    await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
  }
}

async function generatePdfPreview() {
  try {
    await ensurePdfLibs();
  } catch (e) {
    $('#pivotStatus').removeClass().addClass('alert alert-danger').text('No se pudieron cargar librerías PDF: ' + e.message);
    return;
  }
  // Clonar encabezado visible y la tabla dentro de un contenedor temporal
  const headerEl = document.querySelector('.card-header');
  const tableEl = document.getElementById('tablaPivot');
  if (!tableEl) {
    $('#pivotStatus').removeClass().addClass('alert alert-warning').text('No se encontró la tabla para generar PDF.');
    return;
  }

  // Crear contenedor temporal fuera de pantalla
  const container = document.createElement('div');
  container.style.position = 'absolute';
  container.style.left = '-9999px';
  container.style.top = '0';
  container.style.background = '#ffffff';
  container.style.padding = '12px'; // pequeño padding interior
  container.style.boxSizing = 'border-box';

  // Clonar encabezado si existe y añadir estilos inline para asegurar apariencia
  if (headerEl) {
    const headerClone = headerEl.cloneNode(true);
    // Asegurar fondo y color legible similar a la vista
    headerClone.style.background = getComputedStyle(headerEl).backgroundColor || '#ffc107';
    headerClone.style.color = getComputedStyle(headerEl).color || '#5a3f00';
    headerClone.style.padding = '12px 16px';
    headerClone.style.marginBottom = '8px';
    headerClone.style.borderRadius = '4px';
    // Forzar ancho completo
    headerClone.style.width = '100%';
    container.appendChild(headerClone);
  }

  // Clonar la tabla y forzar algunos estilos para impresión
  const tableClone = tableEl.cloneNode(true);
  tableClone.style.width = '100%';
  tableClone.style.borderCollapse = 'collapse';
  // Quitar id duplicado para evitar conflictos
  tableClone.removeAttribute('id');
  container.appendChild(tableClone);

  document.body.appendChild(container);

  // aumentar escala para mejor resolución
  const scale = 2;
  const canvas = await html2canvas(container, { scale: scale, useCORS: true, logging: false });
  // limpiar contenedor temporal
  document.body.removeChild(container);

  const imgData = canvas.toDataURL('image/png');

  const { jsPDF } = window.jspdf;
  // margen en puntos (pt). 1 in = 72 pt. Usamos 20-36pt como margen razonable. Aquí 24pt.
  const marginPt = 24;
  const pdf = new jsPDF('l', 'pt', 'a4'); // landscape
  const pdfWidth = pdf.internal.pageSize.getWidth();
  const pdfHeight = pdf.internal.pageSize.getHeight();

  // Area utilizable restando márgenes
  const usableWidth = pdfWidth - marginPt * 2;
  const usableHeight = pdfHeight - marginPt * 2;

  const imgProps = { width: canvas.width, height: canvas.height };
  // Ratio para ajustar el ancho del canvas al ancho utilizable del PDF
  const ratio = imgProps.width / usableWidth;
  const imgHeightInPdf = imgProps.height / ratio;

  if (imgHeightInPdf <= usableHeight) {
    // cabe en una sola página, centrar horizontalmente con margen
    pdf.addImage(imgData, 'PNG', marginPt, marginPt, usableWidth, imgHeightInPdf);
  } else {
    // cortar por páginas teniendo en cuenta márgenes
    let remainingHeight = imgProps.height;
    let offsetY = 0;
    const pageCanvas = document.createElement('canvas');
    const pageCtx = pageCanvas.getContext('2d');
    pageCanvas.width = imgProps.width;
    // altura en píxeles que corresponde a la altura utilizable en el PDF
    const pageHeightPx = Math.floor(usableHeight * ratio);

    let firstPage = true;
    while (remainingHeight > 0) {
      pageCanvas.height = Math.min(pageHeightPx, remainingHeight);
      pageCtx.clearRect(0, 0, pageCanvas.width, pageCanvas.height);
      pageCtx.drawImage(canvas, 0, offsetY, pageCanvas.width, pageCanvas.height, 0, 0, pageCanvas.width, pageCanvas.height);
      const pageData = pageCanvas.toDataURL('image/png');
      const imgH = (pageCanvas.height) / ratio;
      if (!firstPage) pdf.addPage();
      pdf.addImage(pageData, 'PNG', marginPt, marginPt, usableWidth, imgH);
      remainingHeight -= pageCanvas.height;
      offsetY += pageCanvas.height;
      firstPage = false;
    }
  }

  // Generar blob y mostrar en iframe del modal
  const pdfBlob = pdf.output('blob');
  const blobUrl = URL.createObjectURL(pdfBlob);
  document.getElementById('pdfPreviewFrame').src = blobUrl;
  const dl = document.getElementById('pdfDownloadLink');
  dl.href = blobUrl;

  // mostrar modal (usa Bootstrap JS modal)
  $('#pdfPreviewModal').modal('show');
}
</script>