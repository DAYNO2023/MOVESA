<?php
require_once '../Services/UsuarioService.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $controller = new UsuarioController();

    try {
        $usuario = $controller->obtenerUsuarioPorID($id);
        echo json_encode($usuario);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'ID de usuario no especificado.']);
}
?>
