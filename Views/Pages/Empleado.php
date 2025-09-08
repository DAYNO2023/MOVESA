<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../Views/Resources/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">

<div class="container mt-4">
  <button class="btn btn-success" id="btnMostrarCrearEmpleado">Crear</button>
  <button class="btn btn-secondary" id="btnAtrasEmpleado" style="display:none;">Atrás</button>
  <div class="card" id="cardTablaEmpleados">
    <div class="card-header bg-warning font-weight-bold text-center">Empleados</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tablaEmpleados">
          <thead>
            <tr>
              <th>ID</th>
              <th>Supervisor</th>
              <th>Código</th>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Teléfono</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>2</td>
              <td>EMP001</td>
              <td>Pedro Gómez</td>
              <td>pedro@correo.com</td>
              <td>88888888</td>
              <td>Activo</td>
              <td>
                <div class="d-flex justify-content-between">
                  <button class="btn btn-primary btn-sm flex-fill mx-1" onclick="mostrarModificarEmpleado()">Modificar</button>
                  <button class="btn btn-danger btn-sm flex-fill mx-1" onclick="mostrarModalEliminarEmpleado()">Eliminar</button>
                  <button class="btn btn-info btn-sm flex-fill mx-1" onclick="mostrarDetalleEmpleado()">Detalle</button>
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

<!-- Crear Empleado (oculto por defecto) -->
<div class="container mt-4" id="cardCrearEmpleado" style="display:none;">
  <div class="card">
    <div class="card-header bg-success text-white font-weight-bold">Crear Empleado</div>
    <div class="card-body">
      <form id="formEmpleadoCrear">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="supe_Id">Supervisor (ID)</label>
            <input type="number" class="form-control" id="supe_Id" name="supe_Id">
          </div>
          <div class="form-group col-md-4">
            <label for="empl_codigo">Código</label>
            <input type="text" class="form-control" id="empl_codigo" name="empl_codigo">
          </div>
          <div class="form-group col-md-4">
            <label for="empl_nombre">Nombre</label>
            <input type="text" class="form-control" id="empl_nombre" name="empl_nombre">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="empl_correo">Correo</label>
            <input type="email" class="form-control" id="empl_correo" name="empl_correo">
          </div>
          <div class="form-group col-md-4">
            <label for="empl_telefono">Teléfono</label>
            <input type="text" class="form-control" id="empl_telefono" name="empl_telefono">
          </div>
          <div class="form-group col-md-4">
            <label for="empl_estado">Estado</label>
            <select class="form-control" id="empl_estado" name="empl_estado">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-success mt-3" id="btnOcultarCrearEmpleado">Guardar Empleado</button>
      </form>
    </div>
  </div>
</div>

<!-- Modificar Empleado (oculto por defecto) -->
<div class="container mt-4" id="cardModificarEmpleado" style="display:none;">
  <div class="card">
    <div class="card-header bg-primary text-white font-weight-bold">Modificar Empleado</div>
    <div class="card-body">
      <form id="formEmpleadoModificar">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="mod_supe_Id">Supervisor (ID)</label>
            <input type="number" class="form-control" id="mod_supe_Id" name="mod_supe_Id" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_empl_codigo">Código</label>
            <input type="text" class="form-control" id="mod_empl_codigo" name="mod_empl_codigo" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_empl_nombre">Nombre</label>
            <input type="text" class="form-control" id="mod_empl_nombre" name="mod_empl_nombre" value="">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="mod_empl_correo">Correo</label>
            <input type="email" class="form-control" id="mod_empl_correo" name="mod_empl_correo" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_empl_telefono">Teléfono</label>
            <input type="text" class="form-control" id="mod_empl_telefono" name="mod_empl_telefono" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_empl_estado">Estado</label>
            <select class="form-control" id="mod_empl_estado" name="mod_empl_estado">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3" id="btnGuardarModificarEmpleado">Guardar Cambios</button>
      </form>
    </div>
  </div>
</div>

<!-- Detalle Empleado (oculto por defecto) -->
<div class="container mt-4" id="cardDetalleEmpleado" style="display:none;">
  <div class="card">
    <div class="card-header bg-info text-white font-weight-bold">Detalle de Empleado</div>
    <div class="card-body">
      <div class="mb-3">
        <h5>Datos del Empleado</h5>
        <div class="row">
          <div class="col-md-4"><strong>Supervisor:</strong> <span id="detalle_supe_Id">2</span></div>
          <div class="col-md-4"><strong>Código:</strong> <span id="detalle_empl_codigo">EMP001</span></div>
          <div class="col-md-4"><strong>Nombre:</strong> <span id="detalle_empl_nombre">Pedro Gómez</span></div>
        </div>
        <div class="row mt-2">
          <div class="col-md-4"><strong>Correo:</strong> <span id="detalle_empl_correo">pedro@correo.com</span></div>
          <div class="col-md-4"><strong>Teléfono:</strong> <span id="detalle_empl_telefono">88888888</span></div>
          <div class="col-md-4"><strong>Estado:</strong> <span id="detalle_empl_estado">Activo</span></div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary mt-3" id="btnAtrasDetalleEmpleado">Atrás</button>
    </div>
  </div>
</div>

<!-- Modal de confirmación para eliminar empleado -->
<div class="modal fade" id="modalEliminarEmpleado" tabindex="-1" role="dialog" aria-labelledby="modalEliminarEmpleadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalEliminarEmpleadoLabel">Confirmar eliminación</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ¿Está seguro que desea eliminar este empleado?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarEliminarEmpleado">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS y dependencias -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Mostrar/Ocultar Crear Empleado y Tabla
document.getElementById('btnMostrarCrearEmpleado').addEventListener('click', function() {
  document.getElementById('cardTablaEmpleados').style.display = 'none';
  document.getElementById('cardCrearEmpleado').style.display = 'block';
  document.getElementById('cardModificarEmpleado').style.display = 'none';
  document.getElementById('cardDetalleEmpleado').style.display = 'none';
  document.getElementById('btnMostrarCrearEmpleado').style.display = 'none';
  document.getElementById('btnAtrasEmpleado').style.display = 'inline-block';
});

document.getElementById('btnAtrasEmpleado').addEventListener('click', function() {
  document.getElementById('cardCrearEmpleado').style.display = 'none';
  document.getElementById('cardModificarEmpleado').style.display = 'none';
  document.getElementById('cardDetalleEmpleado').style.display = 'none';
  document.getElementById('cardTablaEmpleados').style.display = 'block';
  document.getElementById('btnMostrarCrearEmpleado').style.display = 'inline-block';
  document.getElementById('btnAtrasEmpleado').style.display = 'none';
});

// Guardar empleado: vuelve a mostrar tabla y botón crear, oculta crear empleado y botón atrás
document.getElementById('btnOcultarCrearEmpleado').addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('cardCrearEmpleado').style.display = 'none';
  document.getElementById('cardModificarEmpleado').style.display = 'none';
  document.getElementById('cardDetalleEmpleado').style.display = 'none';
  document.getElementById('cardTablaEmpleados').style.display = 'block';
  document.getElementById('btnMostrarCrearEmpleado').style.display = 'inline-block';
  document.getElementById('btnAtrasEmpleado').style.display = 'none';
});

// Mostrar/Ocultar Modificar Empleado y Tabla
function mostrarModificarEmpleado() {
  document.getElementById('cardTablaEmpleados').style.display = 'none';
  document.getElementById('cardCrearEmpleado').style.display = 'none';
  document.getElementById('cardDetalleEmpleado').style.display = 'none';
  document.getElementById('cardModificarEmpleado').style.display = 'block';
  document.getElementById('btnMostrarCrearEmpleado').style.display = 'none';
  document.getElementById('btnAtrasEmpleado').style.display = 'inline-block';
}

// Guardar cambios en modificar empleado
document.getElementById('btnGuardarModificarEmpleado').addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('cardModificarEmpleado').style.display = 'none';
  document.getElementById('cardCrearEmpleado').style.display = 'none';
  document.getElementById('cardDetalleEmpleado').style.display = 'none';
  document.getElementById('cardTablaEmpleados').style.display = 'block';
  document.getElementById('btnMostrarCrearEmpleado').style.display = 'inline-block';
  document.getElementById('btnAtrasEmpleado').style.display = 'none';
});

// Mostrar/Ocultar Detalle Empleado y Tabla
function mostrarDetalleEmpleado() {
  document.getElementById('cardTablaEmpleados').style.display = 'none';
  document.getElementById('cardCrearEmpleado').style.display = 'none';
  document.getElementById('cardModificarEmpleado').style.display = 'none';
  document.getElementById('cardDetalleEmpleado').style.display = 'block';
  document.getElementById('btnMostrarCrearEmpleado').style.display = 'none';
  document.getElementById('btnAtrasEmpleado').style.display = 'none';
}

// Botón atrás en detalle
document.getElementById('btnAtrasDetalleEmpleado').addEventListener('click', function() {
  document.getElementById('cardDetalleEmpleado').style.display = 'none';
  document.getElementById('cardTablaEmpleados').style.display = 'block';
  document.getElementById('btnMostrarCrearEmpleado').style.display = 'inline-block';
  document.getElementById('btnAtrasEmpleado').style.display = 'none';
});

// Mostrar modal de confirmación al eliminar
function mostrarModalEliminarEmpleado() {
  $('#modalEliminarEmpleado').modal('show');
}

// Acción al confirmar eliminación
document.getElementById('btnConfirmarEliminarEmpleado').addEventListener('click', function() {
  $('#modalEliminarEmpleado').modal('hide');
  alert('Empleado eliminado correctamente.');
});
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="../Views/Resources/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../Views/Resources/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
  $(document).ready(function() {
    $('#tablaEmpleados').DataTable({
      paging: true,
      searching: true,
      info: true,
      scrollX: true
    });
  });
</script>