<?php
// Controller para manejar CRUD de usuarios vía AJAX usando procedimientos almacenados
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function jsonResponse($ok, $data = null, $msg = '') {
    echo json_encode(['ok' => $ok, 'data' => $data, 'msg' => $msg]);
    exit;
}

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    jsonResponse(false, null, 'No se pudo conectar a la base de datos');
}

$action = $_REQUEST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("EXEC dbo.usp_Usuarios_GetAll");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, $rows);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(false, null, 'Id inválido');
            $stmt = $pdo->prepare("EXEC dbo.usp_Usuarios_GetById @UsuarioId = :id, @IncludePassword = 0");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            jsonResponse(true, $row ?: null);
            break;

        case 'create':
            // campos esperados: username, password, nombre, es_admin
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $nombre = trim($_POST['nombre'] ?? '');
            $es_admin = isset($_POST['es_admin']) ? (int)$_POST['es_admin'] : 0;

            if ($username === '' || $password === '') jsonResponse(false, null, 'Usuario y contraseña requeridos');

            // Almacenar contraseña tal cual (plain text) según solicitud del cliente
            $passwordToStore = $password;

            $stmt = $pdo->prepare("EXEC dbo.usp_Usuarios_Insert @Username = :u, @PasswordHash = :h, @NombreCompleto = :n, @Es_Admin = :a");
            $stmt->bindValue(':u', $username, PDO::PARAM_STR);
            $stmt->bindValue(':h', $passwordToStore, PDO::PARAM_STR);
            $stmt->bindValue(':n', $nombre, PDO::PARAM_STR);
            $stmt->bindValue(':a', $es_admin, PDO::PARAM_INT);
            $stmt->execute();
            $new = $stmt->fetch(PDO::FETCH_ASSOC);
            jsonResponse(true, $new ?: null, 'Usuario creado');
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? null; // si viene vacío no se cambia
            $nombre = trim($_POST['nombre'] ?? '');
            $es_admin = isset($_POST['es_admin']) ? (int)$_POST['es_admin'] : 0;

            if ($id <= 0 || $username === '') jsonResponse(false, null, 'Datos inválidos');

            // Si se envía password, guardarla tal cual; si no, recuperar la contraseña actual
            if ($password !== null && $password !== '') {
                $passwordToStore = $password;
            } else {
                // obtener contraseña actual
                $stmt = $pdo->prepare("EXEC dbo.usp_Usuarios_GetById @UsuarioId = :id, @IncludePassword = 1");
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                $passwordToStore = $r['PasswordHash'] ?? '';
            }

            $stmt = $pdo->prepare("EXEC dbo.usp_Usuarios_Update @UsuarioId = :id, @Username = :u, @PasswordHash = :h, @NombreCompleto = :n, @Es_Admin = :a");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':u', $username, PDO::PARAM_STR);
            $stmt->bindValue(':h', $passwordToStore, PDO::PARAM_STR);
            $stmt->bindValue(':n', $nombre, PDO::PARAM_STR);
            $stmt->bindValue(':a', $es_admin, PDO::PARAM_INT);
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            jsonResponse(true, $res ?: null, 'Usuario actualizado');
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) jsonResponse(false, null, 'Id inválido');
            $stmt = $pdo->prepare("EXEC dbo.usp_Usuarios_Delete @UsuarioId = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            jsonResponse(true, null, 'Usuario eliminado');
            break;

        default:
            jsonResponse(false, null, 'Acción no soportada');
    }
} catch (PDOException $ex) {
    jsonResponse(false, null, $ex->getMessage());
}

?>