<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../Views/Resources/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../Views/Resources/plugins/datatables-bs4/css/responsive.bootstrap4.min.css">

<div class="container mt-4">
  <div class="card" id="cardTablaUsuarios">
    <div class="card-header bg-warning position-relative">
      <div class="d-flex align-items-center">
        <div class="mr-2">
          <button class="btn btn-success" id="btnMostrarCrearUsuario"><i class="fas fa-user-plus"></i> Crear</button>
          <button class="btn btn-secondary" id="btnAtrasUsuario" style="display:none;"><i class="fas fa-arrow-left"></i> Atrás</button>
        </div>
      </div>
      <h4 class="center-title mb-0 font-weight-bold" style="font-size:1.2rem;">Usuarios</h4>
    </div>
    <div class="card-body p-0" style="background:#fff;">
      <div class="table-responsive">
        <table class="table table-bordered table-striped mb-0" id="tablaUsuarios">
          <thead class="thead-light">
            <tr>
              <th style="width:5%">ID</th>
              <th style="width:25%">Usuario</th>
              <th style="width:40%">Nombre</th>
              <th style="width:10%">Administrador</th>
              <th style="width:20%">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <!-- Las filas se cargarán por AJAX -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>



<!-- Crear Usuario (oculto por defecto) -->
<div class="container mt-4" id="cardCrearUsuario" style="display:none;">
  <div class="card">
    <div class="card-header bg-success text-white font-weight-bold">Crear Usuario</div>
    <div class="card-body">
      <form id="formUsuarioCrear">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="username">Usuario (username)</label>
            <input type="text" class="form-control" id="username" name="username">
          </div>
          <div class="form-group col-md-4">
            <label for="nombre">Nombre completo</label>
            <input type="text" class="form-control" id="nombre" name="nombre">
          </div>
          <div class="form-group col-md-4">
            <label for="password">Clave</label>
            <input type="password" class="form-control" id="password" name="password">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4 d-flex align-items-center">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="es_admin" name="es_admin" value="1">
              <label class="custom-control-label" for="es_admin">Administrador</label>
            </div>
          </div>
        </div>
  <button type="submit" class="btn btn-success mt-3" id="btnOcultarCrearUsuario">Guardar Usuario</button>
  <button type="button" class="btn btn-secondary mt-3 ml-2" id="btnCancelarCrearUsuario">Cancelar</button>
      </form>
    </div>
  </div>
</div>

<!-- Modificar Usuario (oculto por defecto) -->
<div class="container mt-4" id="cardModificarUsuario" style="display:none;">
  <div class="card">
    <div class="card-header bg-primary text-white font-weight-bold">Modificar Usuario</div>
    <div class="card-body">
      <form id="formUsuarioModificar">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="mod_username">Usuario (username)</label>
            <input type="text" class="form-control" id="mod_username" name="mod_username" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_nombre">Nombre completo</label>
            <input type="text" class="form-control" id="mod_nombre" name="mod_nombre" value="">
          </div>
          <div class="form-group col-md-4">
            <label for="mod_password">Clave</label>
            <input type="password" class="form-control" id="mod_password" name="mod_password" value="">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4 d-flex align-items-center">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="mod_es_admin" name="mod_es_admin" value="1">
              <label class="custom-control-label" for="mod_es_admin">Administrador</label>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3" id="btnGuardarModificarUsuario">Guardar Cambios</button>
  <button type="button" class="btn btn-secondary mt-3 ml-2" id="btnCancelarModificarUsuario">Cancelar</button>
      </form>
    </div>
  </div>
</div>

<!-- Detalle Usuario (oculto por defecto) -->
<div class="container mt-4" id="cardDetalleUsuario" style="display:none;">
  <div class="card">
    <div class="card-header bg-info text-white font-weight-bold">Detalle de Usuario</div>
    <div class="card-body">
      <div class="mb-3">
        <h5>Datos del Usuario</h5>
        <div class="row">
          <div class="col-md-4"><strong>Nombre:</strong> <span id="detalle_usua_nombre">Administrador</span></div>
          <div class="col-md-4"><strong>Correo:</strong> <span id="detalle_usua_correo">admin@correo.com</span></div>
          <div class="col-md-4"><strong>Rol:</strong> <span id="detalle_usua_rol_id">1</span></div>
        </div>
  <!-- Información simplificada: solo Nombre/Correo/Rol -->
      </div>
      <button type="button" class="btn btn-secondary mt-3" id="btnAtrasDetalleUsuario">Atrás</button>
    </div>
  </div>
</div>

<!-- Modal de confirmación para eliminar usuario -->
<div class="modal fade" id="modalEliminarUsuario" tabindex="-1" role="dialog" aria-labelledby="modalEliminarUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalEliminarUsuarioLabel">Confirmar eliminación</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ¿Está seguro que desea eliminar este usuario?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarEliminarUsuario">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<!-- Incluir dependencias en el orden correcto -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="../Views/Resources/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../Views/Resources/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../Views/Resources/plugins/datatables-bs4/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function(){
  const api = '/MOVESA/Controllers/UsuarioController.php';

  async function cargarUsuarios(){
    try{
      const res = await fetch(api + '?action=list');
      const j = await res.json();
      if(!j.ok){ alert('Error cargando usuarios'); return; }
      const $tbody = $('#tablaUsuarios tbody').empty();
  j.data.forEach(function(u){
        var actions = '<div class="d-flex justify-content-start">'
          + '<button class="btn btn-primary btn-sm mr-2 btn-edit" data-id="'+u.UsuarioId+'"><i class="fas fa-edit"></i> Modificar</button>'
          + '<button class="btn btn-danger btn-sm mr-2 btn-delete" data-id="'+u.UsuarioId+'"><i class="fas fa-trash"></i> Eliminar</button>'
          + '<button class="btn btn-info btn-sm btn-detail" data-id="'+u.UsuarioId+'"><i class="fas fa-info-circle"></i> Detalle</button>'
          + '</div>';
  var isAdmin = (String(u.Es_Admin) === '1' || Number(u.Es_Admin) === 1);
  var rolHtml = isAdmin ? '<span class="badge badge-success"><i class="fas fa-user-shield"></i> Admin</span>' : '<span class="badge badge-secondary"><i class="fas fa-user"></i> Usuario</span>';
        var $row = $('<tr/>')
          .append($('<td/>').text(u.UsuarioId))
          .append($('<td/>').text(u.Username).addClass('usuario-nombre'))
          .append($('<td/>').text(u.NombreCompleto || ''))
          .append($('<td/>').html(rolHtml))
          .append($('<td/>').html(actions));
        $tbody.append($row);
      });
      // si se pidió resaltar una fila por username, aplicar clase
      try{
        if(window._highlightUsername){
          var needle = window._highlightUsername.toString();
          $('#tablaUsuarios tbody tr').each(function(){
            var usernameCell = $(this).find('td').eq(1).text().trim();
            if(usernameCell === needle){
              $(this).addClass('table-success');
              // quitar el highlight después de 3s
              var $tr = $(this);
              setTimeout(function(){ $tr.removeClass('table-success'); }, 3000);
            }
          });
          // limpiar la marca
          window._highlightUsername = null;
        }
      }catch(e){ console.error('Highlight error', e); }
      if ($.fn.DataTable.isDataTable('#tablaUsuarios')) { $('#tablaUsuarios').DataTable().destroy(); }
      $('#tablaUsuarios').DataTable({ paging:true, searching:true, info:true, scrollX:true, responsive:true, autoWidth:false });
    }catch(e){ console.error(e); alert('Error cargando usuarios'); }
  }

  // Delegated handlers for action buttons
  $('#tablaUsuarios').on('click', '.btn-edit', function(){ editarUsuario($(this).data('id')); });
  $('#tablaUsuarios').on('click', '.btn-delete', function(){ confirmEliminarUsuario($(this).data('id')); });
  $('#tablaUsuarios').on('click', '.btn-detail', function(){ detalleUsuario($(this).data('id')); });

  window.editarUsuario = async function(id){
    const res = await fetch(api + '?action=get&id=' + id);
    const j = await res.json(); if(!j.ok){ alert('Error: '+j.msg); return; }
    var u = j.data;
    $('#cardTablaUsuarios').hide(); $('#cardModificarUsuario').show(); $('#btnMostrarCrearUsuario').hide(); $('#btnAtrasUsuario').show();
  $('#mod_username').val(u.Username);
  $('#mod_nombre').val(u.NombreCompleto || '');
  $('#mod_password').val('');
  // checkbox switch (comparación explícita)
  if(String(u.Es_Admin) === '1' || Number(u.Es_Admin) === 1){ $('#mod_es_admin').prop('checked', true); } else { $('#mod_es_admin').prop('checked', false); }
    $('#btnGuardarModificarUsuario').data('id', id);
  };

  $('#formUsuarioCrear').on('submit', async function(e){
    e.preventDefault();
    var fd = new FormData(this);
    fd.append('action','create');
    // checkbox value
    fd.set('es_admin', $('#es_admin').prop('checked') ? '1' : '0');
    // debug: mostrar lo que se envía
    try{ console.group('Crear Usuario - payload'); for (var pair of fd.entries()) { console.log(pair[0]+':', pair[1]); } console.groupEnd(); }catch(e){}
    try{
  // marcar para resaltar la fila resultado
  window._highlightUsername = fd.get('username') || null;
  const res = await fetch(api, { method:'POST', body:fd });
      console.log('HTTP status', res.status);
      const j = await res.json();
      console.log('Respuesta create:', j);
      if(!j.ok){ alert('Error: '+j.msg); return; }
      alert('Usuario creado');
      // limpiar formulario y volver
      $('#formUsuarioCrear')[0].reset(); $('#es_admin').prop('checked', false);
      $('#btnAtrasUsuario').click();
      // recargar tabla y validar que la fila exista; si no aparece, recargar página como fallback
      var createdUsername = fd.get('username');
      cargarUsuarios();
      setTimeout(function(){
        try{
          var found = false;
          $('#tablaUsuarios tbody tr').each(function(){ if($(this).find('td').eq(1).text().trim() === (createdUsername||'').toString()){ found = true; } });
          if(!found){ console.warn('Fila creada no encontrada, recargando página como fallback'); window.location.reload(); }
        }catch(e){ console.error('Check created row error', e); }
      }, 1200);
    }catch(err){ console.error('Fetch error create:', err); alert('Error de red al crear usuario'); }
  });

  $('#btnGuardarModificarUsuario').on('click', async function(e){
    e.preventDefault();
    var id = $(this).data('id');
    var fd = new FormData();
    fd.append('action','update'); fd.append('id', id);
    fd.append('username', $('#mod_username').val()); fd.append('nombre', $('#mod_nombre').val()); fd.append('password', $('#mod_password').val());
    fd.append('es_admin', $('#mod_es_admin').prop('checked') ? '1' : '0');
    try{ console.group('Actualizar Usuario - payload'); for (var pair of fd.entries()) { console.log(pair[0]+':', pair[1]); } console.groupEnd(); }catch(e){}
    try{
      // marcar para resaltar la fila actualizada (por username)
      window._highlightUsername = fd.get('username') || null;
      const res = await fetch(api, { method:'POST', body:fd });
      console.log('HTTP status', res.status);
      const j = await res.json();
      console.log('Respuesta update:', j);
      if(!j.ok){ alert('Error: '+j.msg); return; }
      alert('Usuario actualizado');
  // intentar actualizar la fila en la tabla sin recargar todo
      try{
        var idText = id.toString();
        $('#tablaUsuarios tbody tr').each(function(){
          var $tds = $(this).find('td');
          if($tds.eq(0).text().trim() === idText){
            // actualizar columnas: Usuario (1), Nombre (2), Administrador (3)
            $tds.eq(1).text($('#mod_username').val());
            $tds.eq(2).text($('#mod_nombre').val());
            var rolHtml = $('#mod_es_admin').prop('checked') ? '<span class="badge badge-success"><i class="fas fa-user-shield"></i> Admin</span>' : '<span class="badge badge-secondary"><i class="fas fa-user"></i> Usuario</span>';
            $tds.eq(3).html(rolHtml);
            // aplicar highlight temporal
            $(this).addClass('table-success');
            var $tr = $(this);
            setTimeout(function(){ $tr.removeClass('table-success'); }, 3000);
          }
        });
      }catch(e){ console.error('DOM update error', e); }
      $('#formUsuarioModificar')[0].reset(); $('#mod_es_admin').prop('checked', false); $('#btnAtrasUsuario').click();
      // recargar tabla como fallback/consistencia y validar existencia; si no aparece, recargar página
      cargarUsuarios();
      setTimeout(function(){
        try{
          var found = false;
          $('#tablaUsuarios tbody tr').each(function(){ if($(this).find('td').eq(0).text().trim() === idText){ found = true; } });
          if(!found){ console.warn('Fila actualizada no encontrada, recargando página como fallback'); window.location.reload(); }
        }catch(e){ console.error('Check updated row error', e); }
      }, 1200);
    }catch(err){ console.error('Fetch error update:', err); alert('Error de red al actualizar usuario'); }
  });

  window.confirmEliminarUsuario = function(id){
    $('#modalEliminarUsuario').modal('show');
    $('#btnConfirmarEliminarUsuario').off('click').on('click', async function(){
      $('#modalEliminarUsuario').modal('hide');
      var fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
      try{
        const res = await fetch(api, { method:'POST', body:fd });
        const j = await res.json();
        if(!j.ok){ alert('Error: '+j.msg); return; }
        // eliminar fila del DOM si está presente
        $('#tablaUsuarios tbody tr').each(function(){ if($(this).find('td').eq(0).text().trim() === String(id)){ $(this).remove(); } });
        alert('Usuario eliminado');
        // recargar tabla y fallback a recarga de página si sigue apareciendo
        cargarUsuarios();
        setTimeout(function(){
          try{ var found=false; $('#tablaUsuarios tbody tr').each(function(){ if($(this).find('td').eq(0).text().trim()===String(id)){ found=true; } }); if(found){ console.warn('Fila eliminada aún presente, recargando página'); window.location.reload(); } }catch(e){}
        },1200);
      }catch(err){ console.error('Error delete', err); alert('Error de red al eliminar usuario'); }
    });
  };

  window.detalleUsuario = async function(id){
    const res = await fetch(api + '?action=get&id=' + id);
    const j = await res.json(); if(!j.ok){ alert('Error: '+j.msg); return; }
    var u = j.data;
  $('#detalle_usua_nombre').text(u.Username);
  $('#detalle_usua_correo').text(u.NombreCompleto || '');
  var isAdmin = (String(u.Es_Admin) === '1' || Number(u.Es_Admin) === 1);
  var rolBadge = isAdmin ? '<span class="badge badge-success"><i class="fas fa-user-shield"></i> Administrador</span>' : '<span class="badge badge-secondary"><i class="fas fa-user"></i> Usuario</span>';
  $('#detalle_usua_rol_id').html(rolBadge);
    $('#cardTablaUsuarios').hide(); $('#cardDetalleUsuario').show(); $('#btnMostrarCrearUsuario').hide(); $('#btnAtrasUsuario').hide();
  };

  $('#btnMostrarCrearUsuario').on('click', function(){ $('#cardTablaUsuarios').hide(); $('#cardCrearUsuario').show(); $('#btnMostrarCrearUsuario').hide(); $('#btnAtrasUsuario').show(); });
  $('#btnAtrasUsuario').on('click', function(){ $('#cardCrearUsuario,#cardModificarUsuario,#cardDetalleUsuario').hide(); $('#cardTablaUsuarios').show(); $('#btnMostrarCrearUsuario').show(); $('#btnAtrasUsuario').hide(); });
  $('#btnCancelarModificarUsuario').on('click', function(){ $('#btnAtrasUsuario').click(); });
  $('#btnCancelarCrearUsuario').on('click', function(){ $('#btnAtrasUsuario').click(); });
  $('#btnAtrasDetalleUsuario').on('click', function(){ $('#cardDetalleUsuario').hide(); $('#cardTablaUsuarios').show(); $('#btnMostrarCrearUsuario').show(); $('#btnAtrasUsuario').hide(); });

  cargarUsuarios();

});
</script>

<style>
  .usuario-nombre:hover { color: #0056b3; text-decoration: underline; cursor: pointer; }
  /* Mejoras visuales */
  #cardTablaUsuarios { box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
  #cardTablaUsuarios .card-header { border-bottom: 0; }
  /* Título centrado y botones a la izquierda */
  #cardTablaUsuarios .card-header { position: relative; padding: 1rem 1rem; }
  #cardTablaUsuarios .center-title { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); margin: 0; }
  #cardTablaUsuarios .card-header > .d-flex { z-index: 2; }
  @media (max-width: 575px){
    #cardTablaUsuarios .center-title { position: static; transform: none; text-align: center; margin-bottom: 0.5rem; }
  }
  .table thead th { vertical-align: middle; }
  .badge { font-size: 0.9rem; }
  .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0.2rem 0.6rem; }
  @media (max-width: 575px){
    .card-header h4 { font-size: 1rem; }
    .table td, .table th { font-size: 0.9rem; }
    .btn { font-size: 0.85rem; }
  }
</style>


