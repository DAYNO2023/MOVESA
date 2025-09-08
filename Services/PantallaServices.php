<?php
require_once '../config.php';

class PantallasController {
    // Listar Pantallas
    public function listarPantallas() {
        global $pdo;

        try {
            $sql = 'CALL SP_Pantallas_Listar()';
            $stmt = $pdo->prepare($sql);

            if ($stmt === false) {
                throw new Exception('Error al preparar la declaración: ' . implode(", ", $pdo->errorInfo()));
            }

            $stmt->execute();
            $result = $stmt->fetchAll();

            if ($result === false) {
                throw new Exception('Error al obtener resultados: ' . implode(", ", $stmt->errorInfo()));
            }

            return $result;

        } catch (Exception $e) {
            throw new Exception('Error al listar pantallas: ' . $e->getMessage());
        }
    }

    // Verificar si una pantalla existe
    public function verificarPantallaExiste($pantalla_id) {
        global $pdo;

        try {
            $sql = 'SELECT COUNT(*) FROM acce_tbpantallas WHERE Ptl_Id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pantalla_id]);
            $count = $stmt->fetchColumn();

            return $count > 0;
        } catch (Exception $e) {
            throw new Exception('Error al verificar si la pantalla existe: ' . $e->getMessage());
        }
    }

    // Verificar si un rol existe
    public function verificarRolExiste($rol_id) {
        global $pdo;

        try {
            $sql = 'SELECT COUNT(*) FROM acce_tbroles WHERE Rol_Id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$rol_id]);
            $count = $stmt->fetchColumn();

            return $count > 0;
        } catch (Exception $e) {
            throw new Exception('Error al verificar si el rol existe: ' . $e->getMessage());
        }
    }

    // Asignar pantalla a rol
    public function asignarPantallaARol($rol_id, $pantalla_id) {
        global $pdo;

        try {
            // Verificar si el rol y la pantalla existen
            if (!$this->verificarRolExiste($rol_id)) {
                throw new Exception("El rol con ID $rol_id no existe.");
            }
            if (!$this->verificarPantallaExiste($pantalla_id)) {
                throw new Exception("La pantalla con ID $pantalla_id no existe.");
            }

            $sql = 'CALL sp_PantallasPorRoles_Insertar(?, ?)';
            $stmt = $pdo->prepare($sql);

            if ($stmt === false) {
                throw new Exception('Error al preparar la declaración: ' . implode(", ", $pdo->errorInfo()));
            }

            $stmt->execute([$rol_id, $pantalla_id]);

        } catch (Exception $e) {
            throw new Exception('Error al asignar pantalla a rol: ' . $e->getMessage());
        }
    }

    // Listar pantallas por rol
    public function listarPantallasPorRol($rol_id) {
        global $pdo;

        try {
            $sql = 'CALL sp_PantallasPorRoles_Listar(?)';
            $stmt = $pdo->prepare($sql);

            if ($stmt === false) {
                throw new Exception('Error al preparar la declaración: ' . implode(", ", $pdo->errorInfo()));
            }

            $stmt->execute([$rol_id]);
            $result = $stmt->fetchAll();

            if ($result === false) {
                throw new Exception('Error al obtener resultados: ' . implode(", ", $stmt->errorInfo()));
            }

            return $result;

        } catch (Exception $e) {
            throw new Exception('Error al listar pantallas por rol: ' . $e->getMessage());
        }
    }

    // Eliminar pantalla de rol
    public function eliminarPantallaDeRol($rol_id, $pantalla_id) {
        global $pdo;

        try {
            $sql = 'CALL sp_PantallasPorRoles_Eliminar(?, ?)';
            $stmt = $pdo->prepare($sql);

            if ($stmt === false) {
                throw new Exception('Error al preparar la declaración: ' . implode(", ", $pdo->errorInfo()));
            }

            $stmt->execute([$rol_id, $pantalla_id]);

        } catch (Exception $e) {
            throw new Exception('Error al eliminar pantalla de rol: ' . $e->getMessage());
        }
    }
    
    // Listar pantallas disponibles (las que no están asignadas a un rol específico)
    public function listarPantallasDisponibles($rol_id) {
        global $pdo;

        try {
            $sql = 'CALL sp_PantallasDisponibles_Listar(?)';
            $stmt = $pdo->prepare($sql);

            if ($stmt === false) {
                throw new Exception('Error al preparar la declaración: ' . implode(", ", $pdo->errorInfo()));
            }

            $stmt->execute([$rol_id]);
            $result = $stmt->fetchAll();

            if ($result === false) {
                throw new Exception('Error al obtener resultados: ' . implode(", ", $stmt->errorInfo()));
            }

            return $result;

        } catch (Exception $e) {
            throw new Exception('Error al listar pantallas disponibles: ' . $e->getMessage());
        }
    }

    public function eliminarPantallasPorRol($rol_id) {
        global $pdo;
    
        try {
            $sql = 'CALL sp_PantallasPorRoles_EliminarPorRol(?)';
            $stmt = $pdo->prepare($sql);
    
            if ($stmt === false) {
                throw new Exception('Error al preparar la declaración: ' . implode(", ", $pdo->errorInfo()));
            }
    
            $stmt->execute([$rol_id]);
        } catch (Exception $e) {
            throw new Exception('Error al eliminar pantallas por rol: ' . $e->getMessage());
        }
    }
    
}
?>
