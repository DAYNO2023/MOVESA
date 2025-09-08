<?php
require_once '../Services/RolesService.php';
require_once '../Services/PantallaServices.php';

$controller = new RolesController();
$pantallasController = new PantallasController();
try {
    $roles = $controller->listarRoles();
    $pantallas = $pantallasController->listarPantallas();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>

<div id="tabla">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center" style="font-size:34px !important">Roles</h2>

            <button class="btn btn-primary" id="btnNuevo">Nuevo</button>
            <hr>
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Roles</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo $role['Rol_Id']; ?></td>
                            <td><?php echo $role['Rol_Descripcion']; ?></td>
                            <td class="d-flex justify-content-center" style="gap:10px">
                                <a href="#" class="btn btn-dark btn-sm abrir-editar" data-id="<?php echo $role['Rol_Id']; ?>"><i class="fas fa-edit"></i> Editar</a>
                                <a href="#" class="btn btn-secondary btn-sm btn-detalles" data-id="<?php echo $role['Rol_Id']; ?>"><i class="fas fa-eye"></i> Detalles</a>
                                <button class="btn btn-danger btn-sm"><i class="fas fa-eraser"></i> Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="detalles" style="display:none;">
    <div class="card card-dark">
        <div class="card-header">
            <h3 class="card-title" id="form-title">Detalle de Rol</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Rol:</strong> <span id="Detalle_Rol_Descripcion"></span></p>
                </div>

                <div class="col-md-12 " style="background-color: #fff; color: black;">
                                    <table class="table" id="tbPantRole">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="background-color: #D0D3D4  ; color: black; size:50px;">
                                                    Pantallas
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Aquí se llenarán las pantallas asignadas a un rol específico -->
                                        </tbody>
                                    </table>
                                </div>
            </div>
            <hr>
            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Acción</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Creado por</td>
                        <td id="Detalle_Usuario_Creador"></td>
                        <td id="Detalle_Rol_FechaCreacion"></td>
                    </tr>
                    <tr>
                        <td>Modificado por</td>
                        <td id="Detalle_Usuario_Modificador"></td>
                        <td id="Detalle_Rol_FechaModificacion"></td>
                    </tr>
                </tbody>
            </table>
            <div class="d-flex justify-content-end">
                <button class="btn btn-secondary btn-sm" id="btnVolver">Volver</button>
            </div>
        </div>
    </div>
</div>

<div id="insertar" style="display:none;">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-secondary btn-sm" id="Cancelar">Volver</button>
                        </div>
                        <h3 class="card-title" id="form-title">Crear Rol</h3>
                    </div>
                    <div class="card-body">
                        <form id="frmInsertar" method="POST" action="../Services/guardar_rol.php">
                            <input type="hidden" name="Rol_Id" id="Rol_Id">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>ROL:</label>
                                        <input type="text" class="form-control" name="Rol_Descripcion" id="Rol_Descripcion" required>
                                        <span style="color:red" class="error-message" id="errorUsuario"></span>
                                    </div>
                                    <div style="text-align:end">
                                        <div class="form-group">
                                            <input type="submit" value="Guardar" class="btn btn-outline-secondary btn-sm flex-grow-1" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 pantalla" id="pantallas" ondrop="drop(event, 'pantallas')" ondragover="allowDrop(event)" style="background-color:#fff; color: black;">
                                    <table class="table" id="tbPant">
                                        <thead>
                                            <tr>
                                                <th class="text-center">
                                                    Pantallas
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($pantallas as $pantalla): ?>
                                            <tr id="pantalla-<?php echo $pantalla['Ptl_Id']; ?>" class="item" draggable="true" ondragstart="drag(event)" data-pantalla-id="<?php echo $pantalla['Ptl_Id']; ?>" data-rol-id="">
                                                <td><?php echo $pantalla['Ptl_Descripcion']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6 pantallaPorRoles" id="pantallasPorRol" ondrop="drop(event, 'pantallasPorRol')" ondragover="allowDrop(event)" style="background-color: #fff; color: black;">
                                    <table class="table" id="tbPantRole">
                                        <thead>
                                            <tr>
                                                <th class="text-center">
                                                    Pantallas Por Rol
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Aquí se llenarán las pantallas asignadas a un rol específico -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <input type="hidden" name="pantallasSeleccionadas" id="pantallasSeleccionadas" value="">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarLabel">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este Rol?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto para la eliminación del usuario -->
<form id="eliminarUsuarioForm" method="POST" action="../Services/eliminar_rol.php" style="display:none;">
    <input type="hidden" name="id" id="eliminarUsuarioId">
</form>

<!-- jQuery -->
<script src="../Views/Resources/plugins/jquery/jquery.min.js"></script>

<script>
$(document).ready(function() {
    $("#EsquemaAcceso").addClass('menu-open');
    $("#LinkAcceso").addClass('active');
    $("#LinkRoles").addClass('active');
    var table = $("#example1").DataTable({
        "responsive": false,
        "lengthChange": false,
        "autoWidth": false,
    });

    function attachClickEvents() {
        $(".btn-detalles").off('click').on('click', function() {
            const id = $(this).data('id');
            $.ajax({
                url: '../Services/obtener_rol.php',
                type: 'GET',
                data: { id: id },
                success: function(response) {
                    const rol = JSON.parse(response);
                    $("#Detalle_Rol_Descripcion").text(rol.rol.Rol_Descripcion);
                    //$("#Detalle_Rol_Asignada").text(rol.rol.pantallasAsignadas);
                    $("#Detalle_Usuario_Creador").text(rol.rol.Usuario_Creador);
                    $("#Detalle_Rol_FechaCreacion").text(rol.rol.Rol_FechaCreacion);
                    $("#Detalle_Usuario_Modificador").text(rol.rol.Usuario_Modificador);
                    $("#Detalle_Rol_FechaModificacion").text(rol.rol.Rol_FechaModificacion);
                    
                    
                    $("#tbPantRole tbody").empty();
                    if (rol.pantallasAsignadas) {
                        rol.pantallasAsignadas.forEach(pantalla => {
                            $("#tbPantRole tbody").append(
                                `<tr  id="pantalla-${pantalla.Ptl_Id}" class="item text-center" draggable="true" ondragstart="drag(event)" data-pantalla-id="${pantalla.Ptl_Id}">
                                    <td>${pantalla.Ptl_Descripcion}</td>
                                </tr>`
                            );
                        });
                    }

                    $("#tabla").hide();
                    $("#detalles").show();
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener los detalles del rol:', error);
                }
            });
        });

        $(".abrir-editar").off('click').on('click', function() {
            const id = $(this).data('id');
            $.ajax({
                url: '../Services/obtener_rol.php',
                type: 'GET',
                data: { id: id },
                success: function(response) {
                    const rol = JSON.parse(response);
                    $("#form-title").text('Editar Rol');
                    $("#Rol_Id").val(rol.rol.Rol_Id);
                    $("#Rol_Descripcion").val(rol.rol.Rol_Descripcion);

                    // Limpiar las tablas
                    $("#tbPant tbody").empty();
                    $("#tbPantRole tbody").empty();

                    // Añadir las pantallas disponibles
                    if (rol.pantallasDisponibles) {
                        rol.pantallasDisponibles.forEach(pantalla => {
                            $("#tbPant tbody").append(
                                `<tr id="pantalla-${pantalla.Ptl_Id}" class="item" draggable="true" ondragstart="drag(event)" data-pantalla-id="${pantalla.Ptl_Id}">
                                    <td>${pantalla.Ptl_Descripcion}</td>
                                </tr>`
                            );
                        });
                    }

                    // Añadir las pantallas asignadas al rol
                    if (rol.pantallasAsignadas) {
                        rol.pantallasAsignadas.forEach(pantalla => {
                            $("#tbPantRole tbody").append(
                                `<tr id="pantalla-${pantalla.Ptl_Id}" class="item" draggable="true" ondragstart="drag(event)" data-pantalla-id="${pantalla.Ptl_Id}">
                                    <td>${pantalla.Ptl_Descripcion}</td>
                                </tr>`
                            );
                        });
                    }

                    $("#insertar").show();
                    $("#tabla").hide();
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener los detalles del rol:', error);
                }
            });
        });

        $('#example1 tbody').on('click', '.btn-danger', function() {
            const id = $(this).closest('tr').find('.abrir-editar').data('id');
            $('#modalEliminar').data('id', id).modal('show');
        });
    }

    attachClickEvents();

    table.on('draw', function() {
        attachClickEvents();
    });
 
    $("#btnNuevo").click(function() {
        $("#form-title").text('Crear Nuevo Rol');
        $("#Rol_Id").val('');
        $("#Rol_Descripcion").val('');
        $("#tbPant tbody").empty();
        $("#tbPantRole tbody").empty();

        const pantallas = <?php echo json_encode($pantallas); ?>;
        pantallas.forEach(pantalla => {
            $("#tbPant tbody").append(
                `<tr id="pantalla-${pantalla.Ptl_Id}" class="item" draggable="true" ondragstart="drag(event)" data-pantalla-id="${pantalla.Ptl_Id}">
                    <td>${pantalla.Ptl_Descripcion}</td>
                </tr>`
            );
        });

        $("#insertar").show();
        $("#tabla").hide();
    });

    $("#Cancelar").click(function() {
        $("#insertar").hide();
        $("#tabla").show();
    });

    $("#frmInsertar").submit(function(e) {
        e.preventDefault();

        var rolId = $("#Rol_Id").val();
        var rolDescripcion = $("#Rol_Descripcion").val();
        var pantallasSeleccionadas = [];
        $("#pantallasPorRol .item").each(function() {
            pantallasSeleccionadas.push($(this).data("pantalla-id"));
        });

        var url = rolId ? '../Services/actualizar_pantalla_rol.php' : '../Services/insertar_rol.php';

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                Rol_Id: rolId,
                Rol_Descripcion: rolDescripcion,
                pantallasSeleccionadas: pantallasSeleccionadas.join(",")
            },
            success: function(response) {
                console.log(response);
                window.location.reload();
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    });

    $("#btnVolver").click(function() {
        $("#detalles").hide();
        $("#tabla").show();
    });

    $("#btnConfirmarEliminar").click(function() {
        const id = $('#modalEliminar').data('id');
        $('#eliminarUsuarioId').val(id);
        $('#eliminarUsuarioForm').submit();
    });
});

function allowDrop(event) {
    event.preventDefault();
}

function drag(event) {
    event.dataTransfer.setData("text", event.target.id);
}

function drop(event, targetId) {
    event.preventDefault();
    var data = event.dataTransfer.getData("text");
    var element = document.getElementById(data);
    document.getElementById(targetId).appendChild(element);
}

</script>


