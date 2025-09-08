<?php
require_once '../Services/RolesService.php';
require_once '../Services/PantallaServices.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $controller = new RolesController();
    $pantallaController = new PantallasController();

    try {
        $rol = $controller->obtenerRolesPorID($id);
        $pantallasAsignadas = $pantallaController->listarPantallasPorRol($id);
        $pantallasDisponibles = $pantallaController->listarPantallas();

        // Eliminar las pantallas asignadas de las disponibles
        $pantallasDisponibles = array_filter($pantallasDisponibles, function($pantalla) use ($pantallasAsignadas) {
            foreach ($pantallasAsignadas as $asignada) {
                if ($pantalla['Ptl_Id'] === $asignada['Ptl_Id']) {
                    return false;
                }
            }
            return true;
        });

        echo json_encode([
            'rol' => $rol,
            'pantallasDisponibles' => array_values($pantallasDisponibles), // Asegurar que los índices sean correctos
            'pantallasAsignadas' => $pantallasAsignadas
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'ID de usuario no especificado.']);
}
?>
