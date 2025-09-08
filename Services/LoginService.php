
<?php
require_once __DIR__ . '/../Models/LoginModel.php';
require_once __DIR__ . '/../config.php';

class LoginService {
    /**
     * Intenta autenticar usando el procedimiento almacenado dbo.usp_Usuarios_GetByUsername
     * @return LoginModel|null
     */
    public static function login($email, $password) {
        try {
            $pdo = getDbConnection();
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }

            // 1) Intentar el procedimiento que existe en tu SQL Server: usp_Usuarios_Login
            try {
                $stmt = $pdo->prepare("EXEC dbo.usp_Usuarios_Login @Username = :username, @PasswordHash = :pw");
                $stmt->bindValue(':username', $email, PDO::PARAM_STR);
                $stmt->bindValue(':pw', $password, PDO::PARAM_STR); // se envía tal cual al SP según tu ejemplo
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    @file_put_contents($logDir . '/login_debug.log', date('c') . " - usp_Usuarios_Login matched for " . $email . PHP_EOL, FILE_APPEND | LOCK_EX);
                    return new LoginModel((int)$row['UsuarioId'], $row['NombreCompleto'], $row['Es_Admin']);
                }
            } catch (PDOException $e) {
                // Si falla, lo registramos pero seguimos intentando con GetByUsername
                @file_put_contents($logDir . '/login_debug.log', date('c') . " - usp_Usuarios_Login error: " . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            // 2) Fallback: intentar obtener por username y verificar hashes en PHP
            try {
                $stmt = $pdo->prepare("EXEC dbo.usp_Usuarios_GetByUsername @Username = :username");
                $stmt->bindValue(':username', $email, PDO::PARAM_STR);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $dex) {
                @file_put_contents($logDir . '/login_debug.log', date('c') . " - GetByUsername error: " . $dex->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
                // no hay más fallbacks; relanzar para que el catch exterior lo registre
                throw $dex;
            }

            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }

            if (!$row) {
                @file_put_contents($logDir . '/login_debug.log', date('c') . " - User not found: " . $email . PHP_EOL, FILE_APPEND | LOCK_EX);
                return null;
            }

            $storedHash = $row['PasswordHash'] ?? null;

            if (!$storedHash) {
                @file_put_contents($logDir . '/login_debug.log', date('c') . " - Empty PasswordHash for UsuarioId=" . ($row['UsuarioId'] ?? 'unknown') . PHP_EOL, FILE_APPEND | LOCK_EX);
                return null;
            }

            // Verificación: si el hash fue creado con password_hash()
            if (password_verify($password, $storedHash)) {
                @file_put_contents($logDir . '/login_debug.log', date('c') . " - password_verify OK for UsuarioId=" . $row['UsuarioId'] . PHP_EOL, FILE_APPEND | LOCK_EX);
                return new LoginModel((int)$row['UsuarioId'], $row['NombreCompleto'], $row['Es_Admin']);
            }

            // Fallback: comparar SHA256 (por compatibilidad con sistemas que guardan hash simple)
            if (hash('sha256', $password) === $storedHash) {
                @file_put_contents($logDir . '/login_debug.log', date('c') . " - SHA256 match for UsuarioId=" . $row['UsuarioId'] . PHP_EOL, FILE_APPEND | LOCK_EX);
                return new LoginModel((int)$row['UsuarioId'], $row['NombreCompleto'], $row['Es_Admin']);
            }

            // Fallback adicional: algunas instalaciones guardan la contraseña en texto plano.
            // Si coincide, migramos a password_hash() automáticamente y aceptamos el login.
            if ($password === $storedHash) {
                @file_put_contents($logDir . '/login_debug.log', date('c') . " - Plaintext match for UsuarioId=" . $row['UsuarioId'] . PHP_EOL, FILE_APPEND | LOCK_EX);
                try {
                    // Migrar al hash seguro
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE dbo.Usuarios SET PasswordHash = :h WHERE UsuarioId = :id");
                    $update->bindValue(':h', $newHash, PDO::PARAM_STR);
                    $update->bindValue(':id', $row['UsuarioId'], PDO::PARAM_INT);
                    $update->execute();

                    // Registrar la migración (opcional)
                    $logDir = __DIR__ . '/../logs';
                    if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
                    @file_put_contents($logDir . '/password_migrations.log', date('c') . " - Migrated UsuarioId=" . $row['UsuarioId'] . PHP_EOL, FILE_APPEND | LOCK_EX);
                } catch (Exception $e) {
                    // Si falla la migración no bloqueamos el login; lo registramos
                    @file_put_contents(__DIR__ . '/../logs/login_errors.log', date('c') . ' - Migration error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
                }

                return new LoginModel((int)$row['UsuarioId'], $row['NombreCompleto'], $row['Es_Admin']);
            }

            return null;
        } catch (Exception $e) {
            // Registrar en logs y devolver null (no mostrar error al usuario)
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logDir . '/login_errors.log', date('c') . ' - ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
            return null;
        }
    }
}
?>