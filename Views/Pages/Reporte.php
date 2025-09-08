<!-- Pantalla para generar reporte, vista previa PDF en modal y exportar -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<div class="container mt-4">
  <div class="card">
    <div class="card-header bg-warning font-weight-bold">
      Generador de Reportes de Ventas
    </div>
    <div class="card-body">
      <!-- Formulario de filtros con campos separados y ordenados -->
<form class="mb-4">
  <div class="form-row">
    <div class="form-group col-md-3">
      <label for="fecha_inicio">Fecha inicio</label>
      <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio">
    </div>
    <div class="form-group col-md-3">
      <label for="fecha_fin">Fecha fin</label>
      <input type="date" class="form-control" name="fecha_fin" id="fecha_fin">
    </div>
    <div class="form-group col-md-3">
      <label for="nivel_reporte">Nivel de Reporte</label>
      <select class="form-control" name="nivel_reporte" id="nivel_reporte">
        <option value="diario">Diario</option>
        <option value="semanal">Semanal</option>
        <option value="mensual">Mensual</option>
      </select>
    </div>
    <div class="form-group col-md-3">
      <label for="tipo_reporte">Tipo de Reporte</label>
      <select class="form-control" name="tipo_reporte" id="tipo_reporte">
        <option value="resumen">Resumen (General/Zona)</option>
        <option value="detalle">Detalle (Sucursal/Vendedor/Modelo)</option>
      </select>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group col-md-3">
      <label for="agrupar_por">Agrupar por</label>
      <select class="form-control" name="agrupar_por" id="agrupar_por">

        <option value="sucursal">Sucursal</option>
        <option value="vendedor">Vendedor</option>
        <option value="modelo">Modelo</option>
      </select>
    </div>
    <div class="form-group col-md-9 d-flex align-items-end justify-content-end">
      <button type="button" id="btnCreateReport" class="btn btn-secondary mr-2">Crear reporte</button>
      <button type="button" id="btnPreviewPDF" class="btn btn-primary mr-2">Vista Previa PDF</button>
      <button type="button" id="btnExportPDF" class="btn btn-success mr-2">Exportar PDF</button>
      <button type="button" id="btnExportExcel" class="btn btn-outline-success">Exportar Excel</button>
    </div>
  </div>
</form>
      <div id="vistaPreviaPDF" class="table-responsive bg-white p-3" style="border:1px solid #ccc;">
        <h5 class="text-center mb-3">Reporte de Ventas</h5>
        <div id="reportTableContainer">
          <!-- Tabla generada dinámicamente -->
          <div class="text-center text-muted">Seleccione filtros y presione "Vista Previa PDF" o "Exportar PDF"</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Bootstrap para vista previa PDF -->
<div class="modal fade" id="modalPDF" tabindex="-1" role="dialog" aria-labelledby="modalPDFLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPDFLabel">Vista Previa PDF</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <iframe id="pdfViewer" style="width:100%;height:500px;border:1px solid #ccc;"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS y dependencias -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  let pdfBlobUrl = null;

async function mostrarModalPDF() {
  // Antes de generar PDF, asegurar que la tabla está cargada
  if (!window.lastReportHtml) {
    await fetchAndRenderReport();
    if (!window.lastReportHtml) { alert('No hay datos para generar PDF'); return; }
  }
  const vista = document.getElementById('vistaPreviaPDF');
  // clonar encabezado principal de la tarjeta (si existe) y añadir temporalmente a la vista previa
  const pageHeader = document.querySelector('.card .card-header');
  let headerClone = null;
  if (pageHeader) {
    headerClone = pageHeader.cloneNode(true);
    headerClone.style.marginBottom = '8px';
    // insertar al principio de la vista (antes de su contenido)
    vista.insertBefore(headerClone, vista.firstChild);
  }

  const blobUrl = await createPdfBlobUrlFromElement(vista);
  // remover clone si fue insertado
  if (headerClone && headerClone.parentNode) headerClone.parentNode.removeChild(headerClone);
  if (!blobUrl) { alert('No se pudo generar PDF'); return; }
  pdfBlobUrl = blobUrl;
  document.getElementById('pdfViewer').src = pdfBlobUrl;
  // Abre el modal
  $('#modalPDF').modal('show');
}

async function descargarPDF() {
  if (!window.lastReportHtml) {
    await fetchAndRenderReport();
    if (!window.lastReportHtml) { alert('No hay datos para generar PDF'); return; }
  }
  // Generar PDF y forzar descarga
  const vista = document.getElementById('vistaPreviaPDF');
  // insertar temporalmente el encabezado de la tarjeta en la vista previa para el PDF
  const pageHeader = document.querySelector('.card .card-header');
  let headerClone = null;
  if (pageHeader) {
    headerClone = pageHeader.cloneNode(true);
    headerClone.style.marginBottom = '8px';
    vista.insertBefore(headerClone, vista.firstChild);
  }

  const blobUrl = await createPdfBlobUrlFromElement(vista);
  if (headerClone && headerClone.parentNode) headerClone.parentNode.removeChild(headerClone);
  if (!blobUrl) { alert('No se pudo generar PDF'); return; }
  // Descargar
  const a = document.createElement('a');
  a.href = blobUrl;
  a.download = 'reporte_ventas.pdf';
  document.body.appendChild(a);
  a.click();
  a.remove();
}

// Crea un Blob URL de un PDF A4 vertical (p) a partir del elemento HTML, paginando si es necesario
async function createPdfBlobUrlFromElement(element) {
  // Renderizar con mayor escala para mejor calidad
  const canvas = await html2canvas(element, { scale: 2 });
  const pdf = new window.jspdf.jsPDF('p', 'mm', 'a4');
  const pageWidthMm = pdf.internal.pageSize.getWidth(); // e.g., 210
  const pageHeightMm = pdf.internal.pageSize.getHeight(); // e.g., 297
  const marginMm = 10; // margen superior e izquierdo
  const usableWidthMm = pageWidthMm - marginMm * 2;
  const pxPerMm = canvas.width / usableWidthMm; // ratio px per mm

  // Altura en mm que representa el canvas completo
  const totalHeightMm = canvas.height / pxPerMm;

  // Altura en px que cabe en una página
  const usableHeightMm = pageHeightMm - marginMm * 2;
  const sliceHeightPx = Math.floor(usableHeightMm * pxPerMm);

  let y = 0;
  let pageIndex = 0;
  while (y < canvas.height) {
    const h = Math.min(sliceHeightPx, canvas.height - y);
    // Crear canvas temporal con la porción
    const tmpCanvas = document.createElement('canvas');
    tmpCanvas.width = canvas.width;
    tmpCanvas.height = h;
    const ctx = tmpCanvas.getContext('2d');
    ctx.fillStyle = '#ffffff'; ctx.fillRect(0,0,tmpCanvas.width,tmpCanvas.height);
    ctx.drawImage(canvas, 0, y, canvas.width, h, 0, 0, canvas.width, h);
    const dataUrl = tmpCanvas.toDataURL('image/png');

    const imgHeightMm = h / pxPerMm;
    if (pageIndex > 0) pdf.addPage();
    pdf.addImage(dataUrl, 'PNG', marginMm, marginMm, usableWidthMm, imgHeightMm);

    y += h;
    pageIndex += 1;
  }

  // Generar blob y URL
  try {
    const blob = pdf.output('blob');
    const url = URL.createObjectURL(blob);
    return url;
  } catch (e) {
    console.error('Error creando blob PDF', e);
    return null;
  }
}

// Nueva lógica: solicitar reporte al backend y renderizar tabla
async function fetchAndRenderReport() {
  const startDate = document.getElementById('fecha_inicio').value;
  const endDate = document.getElementById('fecha_fin').value;
  const periodLevel = document.getElementById('nivel_reporte').value;
  const groupBy = document.getElementById('agrupar_por').value;
  const reportType = document.getElementById('tipo_reporte').value === 'detalle' ? 'detallado' : 'resumen';

  if (!startDate || !endDate) { alert('Seleccione fecha inicio y fin'); return; }

  const qs = new URLSearchParams({ startDate, endDate, periodLevel, groupBy, reportType });
  const url = '/MOVESA/Controllers/ReporteApi.php?' + qs.toString();
  try {
    const res = await fetch(url);
    const json = await res.json();
    if (json.error) { alert('Error: ' + json.error); return; }
  const rows = json.data || [];
  renderReportTable(rows);
  // store html and rows for PDF/Excel generation
  window.lastReportHtml = document.getElementById('reportTableContainer').innerHTML;
  window.lastReportRows = rows;
  } catch (err) {
    console.error(err);
    alert('Error al obtener reporte');
  }
}

function renderReportTable(rows) {
  const container = document.getElementById('reportTableContainer');
  if (!rows || rows.length === 0) {
    container.innerHTML = '<div class="text-center text-muted">No hay datos para los filtros seleccionados</div>';
    return;
  }

  // Construir tabla dinámica según keys del primer row
  const cols = Object.keys(rows[0]);
  // detectar columnas que parecen montos (monto, total, importe, valor)
  const montoCols = cols.filter(c => /monto|total|importe|valor|amount/i.test(c));

  let html = '<table class="table table-bordered table-sm"><thead><tr>';
  for (const c of cols) html += '<th>' + c + '</th>';
  html += '</tr></thead><tbody>';
  
  // Inicializar acumulador por cada columna de monto
  const totals = Object.create(null);
  for (const mc of montoCols) totals[mc] = 0;
  for (const r of rows) {
    html += '<tr>';
    for (const c of cols) {
      const val = r[c];
      // si es columna monto intentar parsear número
      if (montoCols.indexOf(c) !== -1) {
        // eliminar símbolos de moneda y miles, aceptar comas y puntos
        const n = (val === null || val === undefined || val === '') ? 0 : Number(String(val).replace(/[^0-9\-.,]/g, '').replace(/,/g, '.'));
        const disp = (val === null || val === undefined || val === '') ? '' : val;
        html += '<td class="text-right">' + disp + '</td>';
        totals[c] += isNaN(n) ? 0 : n;
      } else {
        html += '<td>' + (val === null ? '' : val) + '</td>';
      }
    }
    html += '</tr>';
  }

  // fila de totales si hay columnas de monto
  if (montoCols.length > 0) {
    html += '<tr class="font-weight-bold" style="background:#f8f9fa">';
    for (const c of cols) {
      if (montoCols.indexOf(c) !== -1) {
        // formatear número con dos decimales y separador de miles
        const v = totals[c] || 0;
        const formatted = v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        html += '<td class="text-right">' + formatted + '</td>';
      } else {
        // para la primera columna ponemos 'TOTAL' y para las otras celdas vacías
        if (cols.indexOf(c) === 0) html += '<td>TOTAL</td>'; else html += '<td></td>';
      }
    }
    html += '</tr>';
  }
  html += '</tbody></table>';
  container.innerHTML = html;
}

// Bind a botones
document.getElementById('btnCreateReport')?.addEventListener('click', async function() { await fetchAndRenderReport(); });
document.getElementById('btnPreviewPDF')?.addEventListener('click', async function() { await fetchAndRenderReport(); mostrarModalPDF(); });
document.getElementById('btnExportPDF')?.addEventListener('click', async function() { await fetchAndRenderReport(); descargarPDF(); });

// Generar CSV y descargar (abre en Excel)
function exportToExcelCsv() {
  const rows = window.lastReportRows;
  if (!rows || rows.length === 0) { alert('No hay datos para exportar'); return; }

  const cols = Object.keys(rows[0]);
  const lines = [];
  // header
  lines.push(cols.map(h => '"' + String(h).replace(/"/g, '""') + '"').join(','));
  for (const r of rows) {
    const vals = cols.map(c => {
      const v = r[c];
      if (v === null || v === undefined) return '""';
      const s = String(v);
      return '"' + s.replace(/"/g, '""') + '"';
    });
    lines.push(vals.join(','));
  }

  const csv = lines.join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'reporte_ventas.csv';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

document.getElementById('btnExportExcel')?.addEventListener('click', async function() { if (!window.lastReportRows) await fetchAndRenderReport(); exportToExcelCsv(); });
</script>