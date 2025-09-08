<?php
require '../config.php';
require '../funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];
    $nuevaContrasena = $_POST['password'];
    $fechaModificacion = date('Y-m-d');

    $userData = mostrarCodigoVerificacion($codigo);

    if ($userData) {
        $resultado = usuarioReestablecer($codigo, $nuevaContrasena, $fechaModificacion);
        if ($resultado) {
            // Redirigir a la página de inicio de sesión después de actualizar la contraseña
            header('Location: ../index.php');
            exit; // Asegurarse de que el script se detenga después de la redirección
        } else {
            echo 'Error al actualizar la contraseña.';
        }
    } else {
        echo 'Código de verificación inválido.';
    }
}
?>
