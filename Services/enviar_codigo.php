<?php
require '../vendor/autoload.php'; // Verifica esta ruta
require '../config.php';
require '../funciones.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $userData = usuariosEnviarCorreo($usuario);

    if ($userData) {
        $userId = $userData['Usu_Id'];
        $email = $userData['correo'];
        $randomNumber = rand(100000, 999999);

        agregarCodigoVerificacion($randomNumber, $userId);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yordinsanchez466@gmail.com';
            $mail->Password = 'wwuv tukc rzve mwsv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('yordinsanchez466@gmail.com', 'Yordin Sanchez');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Codigo de Restablecimiento de Contrasena';
            $mail->Body    = "Su código es: <b>$randomNumber</b>";

            $mail->send();
            header("Location: ../Views/Pages/verificar_codigo.php");
        } catch (Exception $e) {
            echo "Error al enviar el correo: {$mail->ErrorInfo}";
        }
    } else {
        echo 'El usuario no existe.';
    }
}
?>
