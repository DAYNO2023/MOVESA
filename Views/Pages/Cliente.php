<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../Views/Resources/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">

<div class="container mt-4">
  <button class="btn btn-success" id="btnMostrarCrearCliente">Crear</button>
  <button class="btn btn-secondary" id="btnAtrasCliente" style="display:none;">Atrás</button>
  <div class="card" id="cardTablaClientes">
    <div class="card-header bg-warning font-weight-bold text-center">Clientes</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tablaClientes">
          <thead>
            <tr>
              <th>ID</th>
              <th>Código</th>
              <th>Nombre</th>
              <th>Dirección</th>
              <th>Teléfono</th>
              <th>Correo</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>CL001</td>
              <td>Juan Pérez</td>
              <td>Calle Falsa 123</td>
              <td>99999999</td>
              <td>juan@correo.com</td>
              <td>Activo</td>
              <td>
                <div class="d-flex justify-content-between">
                  <button class="btn btn-primary btn-sm flex-fill mx-1" onclick="mostrarModificarCliente()">Modificar</button>
                  <button class="btn btn-danger btn-sm flex-fill mx-1" onclick="mostrarModalEliminarCliente()">Eliminar</button>
                  <button class="btn btn-info btn-sm flex-fill mx-1" onclick="mostrarDetalleCliente()">Detalle</button>
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

<!-- Crear Cliente (oculto por defecto) -->
<div class="container mt-4" id="cardCrearCliente" style="display:none;">
  <div class="card">
    <div class="card-header bg-success text-white font-weight-bold">Crear Cliente</div>
    <div class="card-body">
      <form id="formClienteCrear">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="clie_Codigo">Código</label>
            <input type="text" class="form-control" id="clie_Codigo" name="clie_Codigo">
          </div>
          <div class="form-group col-md-4">
            <label for="clie_nombre">Nombre</label>
            <input type="text" class="form-control" id="clie_nombre" name="clie_nombre">
          </div>
          <div class="form-group col-md-4">
            <label for="clie_direccion">Dirección</label>
            <input type="text" class="form-control" id="clie_direccion" name="clie_direccion">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="clie_telefono">Teléfono</label>
            <input type="text" class="form-control" id="clie_telefono" name="clie_telefono">
          </div>
          <div class="form-group col-md-4">
            <label for="clie_correo">Correo</label>
            <input type="email" class="form-control" id="clie_correo" name="clie_correo">
          </div>
          <div class="form-group col-md-4">
            <label for="clie_estado">Estado</label>
            <select class="form-control" id="clie_estado" name="clie_estado">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-success mt-3" id="btnOcultarCrearCliente">Guardar Cliente</button>
      </form>
    </div>
  </div>
</div>

<!-- Modificar Cliente (oculto por defecto) -->
<div class="container mt-4" id="cardModificarCliente" style="display:none;">
  <div class="card">
    <div class="card-header bg-primary text-white font-weight-bold">Modificar Cliente</div>
    <div class="card-body">
      <form id="formClienteModificar">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="mod_clie_Codigo">Código</label>
            <input type="text" class="form-control" id="mod_clie_Codigo" name="mod_clie_Codigo" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_clie_nombre">Nombre</label>
            <input type="text" class="form-control" id="mod_clie_nombre" name="mod_clie_nombre" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_clie_direccion">Dirección</label>
            <input type="text" class="form-control" id="mod_clie_direccion" name="mod_clie_direccion" value="">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="mod_clie_telefono">Teléfono</label>
            <input type="text" class="form-control" id="mod_clie_telefono" name="mod_clie_telefono" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_clie_correo">Correo</label>
            <input type="email" class="form-control" id="mod_clie_correo" name="mod_clie_correo" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_clie_estado">Estado</label>
            <select class="form-control" id="mod_clie_estado" name="mod_clie_estado">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3" id="btnGuardarModificarCliente">Guardar Cambios</button>
      </form>
    </div>
  </div>
</div>

<!-- Detalle Cliente (oculto por defecto) -->
<div class="container mt-4" id="cardDetalleCliente" style="display:none;">
  <div class="card">
    <div class="card-header bg-info text-white font-weight-bold">Detalle de Cliente</div>
    <div class="card-body">
      <div class="mb-3">
        <h5>Datos del Cliente</h5>
        <div class="row">
          <div class="col-md-4"><strong>Código:</strong> <span id="detalle_clie_Codigo">CL001</span></div>
          <div class="col-md-4"><strong>Nombre:</strong> <span id="detalle_clie_nombre">Juan Pérez</span></div>
          <div class="col-md-4"><strong>Dirección:</strong> <span id="detalle_clie_direccion">Calle Falsa 123</span></div>
        </div>
        <div class="row mt-2">
          <div class="col-md-4"><strong>Teléfono:</strong> <span id="detalle_clie_telefono">99999999</span></div>
          <div class="col-md-4"><strong>Correo:</strong> <span id="detalle_clie_correo">juan@correo.com</span></div>
          <div class="col-md-4"><strong>Estado:</strong> <span id="detalle_clie_estado">Activo</span></div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary mt-3" id="btnAtrasDetalleCliente">Atrás</button>
    </div>
  </div>
</div>

<!-- Modal de confirmación para eliminar cliente -->
<div class="modal fade" id="modalEliminarCliente" tabindex="-1" role="dialog" aria-labelledby="modalEliminarClienteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalEliminarClienteLabel">Confirmar eliminación</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ¿Está seguro que desea eliminar este cliente?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarEliminarCliente">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS y dependencias -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Mostrar/Ocultar Crear Cliente y Tabla
document.getElementById('btnMostrarCrearCliente').addEventListener('click', function() {
  document.getElementById('cardTablaClientes').style.display = 'none';
  document.getElementById('cardCrearCliente').style.display = 'block';
  document.getElementById('cardModificarCliente').style.display = 'none';
  document.getElementById('cardDetalleCliente').style.display = 'none';
  document.getElementById('btnMostrarCrearCliente').style.display = 'none';
  document.getElementById('btnAtrasCliente').style.display = 'inline-block';
});

document.getElementById('btnAtrasCliente').addEventListener('click', function() {
  document.getElementById('cardCrearCliente').style.display = 'none';
  document.getElementById('cardModificarCliente').style.display = 'none';
  document.getElementById('cardDetalleCliente').style.display = 'none';
  document.getElementById('cardTablaClientes').style.display = 'block';
  document.getElementById('btnMostrarCrearCliente').style.display = 'inline-block';
  document.getElementById('btnAtrasCliente').style.display = 'none';
});

// Guardar cliente: vuelve a mostrar tabla y botón crear, oculta crear cliente y botón atrás
document.getElementById('btnOcultarCrearCliente').addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('cardCrearCliente').style.display = 'none';
  document.getElementById('cardModificarCliente').style.display = 'none';
  document.getElementById('cardDetalleCliente').style.display = 'none';
  document.getElementById('cardTablaClientes').style.display = 'block';
  document.getElementById('btnMostrarCrearCliente').style.display = 'inline-block';
  document.getElementById('btnAtrasCliente').style.display = 'none';
});

// Mostrar/Ocultar Modificar Cliente y Tabla
function mostrarModificarCliente() {
  document.getElementById('cardTablaClientes').style.display = 'none';
  document.getElementById('cardCrearCliente').style.display = 'none';
  document.getElementById('cardDetalleCliente').style.display = 'none';
  document.getElementById('cardModificarCliente').style.display = 'block';
  document.getElementById('btnMostrarCrearCliente').style.display = 'none';
  document.getElementById('btnAtrasCliente').style.display = 'inline-block';
}

// Guardar cambios en modificar cliente
document.getElementById('btnGuardarModificarCliente').addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('cardModificarCliente').style.display = 'none';
  document.getElementById('cardCrearCliente').style.display = 'none';
  document.getElementById('cardDetalleCliente').style.display = 'none';
  document.getElementById('cardTablaClientes').style.display = 'block';
  document.getElementById('btnMostrarCrearCliente').style.display = 'inline-block';
  document.getElementById('btnAtrasCliente').style.display = 'none';
});

// Mostrar/Ocultar Detalle Cliente y Tabla
function mostrarDetalleCliente() {
  document.getElementById('cardTablaClientes').style.display = 'none';
  document.getElementById('cardCrearCliente').style.display = 'none';
  document.getElementById('cardModificarCliente').style.display = 'none';
  document.getElementById('cardDetalleCliente').style.display = 'block';
  document.getElementById('btnMostrarCrearCliente').style.display = 'none';
  document.getElementById('btnAtrasCliente').style.display = 'none';
}

// Botón atrás en detalle
document.getElementById('btnAtrasDetalleCliente').addEventListener('click', function() {
  document.getElementById('cardDetalleCliente').style.display = 'none';
  document.getElementById('cardTablaClientes').style.display = 'block';
  document.getElementById('btnMostrarCrearCliente').style.display = 'inline-block';
  document.getElementById('btnAtrasCliente').style.display = 'none';
});

// Mostrar modal de confirmación al eliminar
function mostrarModalEliminarCliente() {
  $('#modalEliminarCliente').modal('show');
}

// Acción al confirmar eliminación
document.getElementById('btnConfirmarEliminarCliente').addEventListener('click', function() {
  $('#modalEliminarCliente').modal('hide');
  alert('Cliente eliminado correctamente.');
});
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="../Views/Resources/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../Views/Resources/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
  $(document).ready(function() {
    $('#tablaClientes').DataTable({
      paging: true,
      searching: true,
      info: true,
      scrollX: true
    });
  });
</script>