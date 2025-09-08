
<?php
session_start();
require_once __DIR__ . '/../Services/LoginService.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Metodo no permitido.';
    header('Location: ../index.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error_message'] = 'Ingrese usuario y contraseña.';
    header('Location: ../index.php');
    exit();
}

$usuario = LoginService::login($email, $password);

if ($usuario) {
    // Regenerar id de sesión para prevenir fixation
    session_regenerate_id(true);

    // Guardar datos mínimos en sesión como array (evita problemas de serialización de objetos)
    if (is_object($usuario)) {
        $_SESSION['usuario'] = [
            'id' => $usuario->id ?? null,
            'nombre' => $usuario->nombre ?? null,
            'rol' => $usuario->rol ?? null,
        ];
    } elseif (is_array($usuario)) {
        $_SESSION['usuario'] = $usuario;
    } else {
        // fallback
        $_SESSION['usuario'] = ['nombre' => $email];
    }

    header('Location: ../services/Template.Service.php');
    exit();
} else {
    $_SESSION['error_message'] = "Usuario o contraseña incorrectos.";
    header('Location: ../index.php');
    exit();
}
?>