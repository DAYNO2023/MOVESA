<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../Views/Resources/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">

<div class="container mt-4">
  <button class="btn btn-success" id="btnMostrarCrearAlmacen">Crear</button>
  <button class="btn btn-secondary" id="btnAtrasAlmacen" style="display:none;">Atrás</button>
  <div class="card" id="cardTablaAlmacenes">
    <div class="card-header bg-warning font-weight-bold text-center">Almacenes</div>
    <div class="card-body p-0">
      <div class="table-responsive">
  <table class="table table-bordered table-striped w-100" id="tablaAlmacenes" style="min-width:600px;">
<style>
  /* Ajuste para que la tabla no se desborde y sea responsiva */
  .table-responsive {
    overflow-x: auto;
  }
  #tablaAlmacenes {
    width: 100% !important;
    table-layout: auto;
  }
</style>
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>ID Zona</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>Almacén Central</td>
              <td>10</td>
              <td>
                <div class="d-flex justify-content-between">
                  <button class="btn btn-primary btn-sm flex-fill mx-1" onclick="mostrarModificarAlmacen()">Modificar</button>
                  <button class="btn btn-danger btn-sm flex-fill mx-1" onclick="mostrarModalEliminarAlmacen()">Eliminar</button>
                  <button class="btn btn-info btn-sm flex-fill mx-1" onclick="mostrarDetalleAlmacen()">Detalle</button>
                </div>
              </td>
            </tr>
            <!-- Más filas aquí -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Crear Almacén (oculto por defecto) -->
<div class="container mt-4" id="cardCrearAlmacen" style="display:none;">
  <div class="card">
    <div class="card-header bg-success text-white font-weight-bold">Crear Almacén</div>
    <div class="card-body">
      <form id="formAlmacenCrear">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="alm_nombre">Nombre</label>
            <input type="text" class="form-control" id="alm_nombre" name="alm_nombre">
          </div>
          <div class="form-group col-md-6">
            <label for="alm_zona_id">ID Zona</label>
            <input type="number" class="form-control" id="alm_zona_id" name="alm_zona_id">
          </div>
        </div>
        <button type="submit" class="btn btn-success mt-3" id="btnOcultarCrearAlmacen">Guardar Almacén</button>
      </form>
    </div>
  </div>
</div>

<!-- Modificar Almacén (oculto por defecto) -->
<div class="container mt-4" id="cardModificarAlmacen" style="display:none;">
  <div class="card">
    <div class="card-header bg-primary text-white font-weight-bold">Modificar Almacén</div>
    <div class="card-body">
      <form id="formAlmacenModificar">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="mod_alm_nombre">Nombre</label>
            <input type="text" class="form-control" id="mod_alm_nombre" name="mod_alm_nombre" value="">
          </div>
          <div class="form-group col-md-6">
            <label for="mod_alm_zona_id">ID Zona</label>
            <input type="number" class="form-control" id="mod_alm_zona_id" name="mod_alm_zona_id" value="">
          </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3" id="btnGuardarModificarAlmacen">Guardar Cambios</button>
      </form>
    </div>
  </div>
</div>

<!-- Detalle Almacén (oculto por defecto) -->
<div class="container mt-4" id="cardDetalleAlmacen" style="display:none;">
  <div class="card">
    <div class="card-header bg-info text-white font-weight-bold">Detalle de Almacén</div>
    <div class="card-body">
      <div class="mb-3">
        <h5>Datos del Almacén</h5>
        <div class="row">
          <div class="col-md-6"><strong>Nombre:</strong> <span id="detalle_alm_nombre">Almacén Central</span></div>
          <div class="col-md-6"><strong>ID Zona:</strong> <span id="detalle_alm_zona_id">10</span></div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary mt-3" id="btnAtrasDetalleAlmacen">Atrás</button>
    </div>
  </div>
</div>

<!-- Modal de confirmación para eliminar almacén -->
<div class="modal fade" id="modalEliminarAlmacen" tabindex="-1" role="dialog" aria-labelledby="modalEliminarAlmacenLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalEliminarAlmacenLabel">Confirmar eliminación</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ¿Está seguro que desea eliminar este almacén?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarEliminarAlmacen">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS y dependencias -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Mostrar/Ocultar Crear Almacén y Tabla
document.getElementById('btnMostrarCrearAlmacen').addEventListener('click', function() {
  document.getElementById('cardTablaAlmacenes').style.display = 'none';
  document.getElementById('cardCrearAlmacen').style.display = 'block';
  document.getElementById('cardModificarAlmacen').style.display = 'none';
  document.getElementById('cardDetalleAlmacen').style.display = 'none';
  document.getElementById('btnMostrarCrearAlmacen').style.display = 'none';
  document.getElementById('btnAtrasAlmacen').style.display = 'inline-block';
});

document.getElementById('btnAtrasAlmacen').addEventListener('click', function() {
  document.getElementById('cardCrearAlmacen').style.display = 'none';
  document.getElementById('cardModificarAlmacen').style.display = 'none';
  document.getElementById('cardDetalleAlmacen').style.display = 'none';
  document.getElementById('cardTablaAlmacenes').style.display = 'block';
  document.getElementById('btnMostrarCrearAlmacen').style.display = 'inline-block';
  document.getElementById('btnAtrasAlmacen').style.display = 'none';
});

// Guardar almacén: vuelve a mostrar tabla y botón crear, oculta crear almacén y botón atrás
document.getElementById('btnOcultarCrearAlmacen').addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('cardCrearAlmacen').style.display = 'none';
  document.getElementById('cardModificarAlmacen').style.display = 'none';
  document.getElementById('cardDetalleAlmacen').style.display = 'none';
  document.getElementById('cardTablaAlmacenes').style.display = 'block';
  document.getElementById('btnMostrarCrearAlmacen').style.display = 'inline-block';
  document.getElementById('btnAtrasAlmacen').style.display = 'none';
});

// Mostrar/Ocultar Modificar Almacén y Tabla
function mostrarModificarAlmacen() {
  document.getElementById('cardTablaAlmacenes').style.display = 'none';
  document.getElementById('cardCrearAlmacen').style.display = 'none';
  document.getElementById('cardDetalleAlmacen').style.display = 'none';
  document.getElementById('cardModificarAlmacen').style.display = 'block';
  document.getElementById('btnMostrarCrearAlmacen').style.display = 'none';
  document.getElementById('btnAtrasAlmacen').style.display = 'inline-block';
}

// Guardar cambios en modificar almacén
document.getElementById('btnGuardarModificarAlmacen').addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('cardModificarAlmacen').style.display = 'none';
  document.getElementById('cardCrearAlmacen').style.display = 'none';
  document.getElementById('cardDetalleAlmacen').style.display = 'none';
  document.getElementById('cardTablaAlmacenes').style.display = 'block';
  document.getElementById('btnMostrarCrearAlmacen').style.display = 'inline-block';
  document.getElementById('btnAtrasAlmacen').style.display = 'none';
});

// Mostrar/Ocultar Detalle Almacén y Tabla
function mostrarDetalleAlmacen() {
  document.getElementById('cardTablaAlmacenes').style.display = 'none';
  document.getElementById('cardCrearAlmacen').style.display = 'none';
  document.getElementById('cardModificarAlmacen').style.display = 'none';
  document.getElementById('cardDetalleAlmacen').style.display = 'block';
  document.getElementById('btnMostrarCrearAlmacen').style.display = 'none';
  document.getElementById('btnAtrasAlmacen').style.display = 'none';
}

// Botón atrás en detalle
document.getElementById('btnAtrasDetalleAlmacen').addEventListener('click', function() {
  document.getElementById('cardDetalleAlmacen').style.display = 'none';
  document.getElementById('cardTablaAlmacenes').style.display = 'block';
  document.getElementById('btnMostrarCrearAlmacen').style.display = 'inline-block';
  document.getElementById('btnAtrasAlmacen').style.display = 'none';
});

// Mostrar modal de confirmación al eliminar
function mostrarModalEliminarAlmacen() {
  $('#modalEliminarAlmacen').modal('show');
}

// Acción al confirmar eliminación
document.getElementById('btnConfirmarEliminarAlmacen').addEventListener('click', function() {
  $('#modalEliminarAlmacen').modal('hide');
  alert('Almacén eliminado correctamente.');
});
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="../Views/Resources/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../Views/Resources/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
  $(document).ready(function() {
    $('#tablaAlmacenes').DataTable({
      paging: true,
      searching: true,
      info: true,
      scrollX: true
    });
  });
</script>