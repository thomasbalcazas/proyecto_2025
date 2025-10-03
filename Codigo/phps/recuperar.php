<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Leer datos JSON enviados por fetch
$input = json_decode(file_get_contents("php://input"), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Correo inválido']);
    exit;
}

// Incluir la configuración de BD correcta
require_once 'db.php';

// Verificar la conexión
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la BD: ' . $conn->connect_error]);
    exit;
}

// Verificar que el email exista en la BD
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Correo no registrado']);
    exit;
}

$user = $result->fetch_assoc();
$id_usuario = $user['id'];

// Generar token y guardarlo en la BD
$token = bin2hex(random_bytes(16));
$stmt = $conn->prepare("UPDATE usuarios SET token_recuperacion = ? WHERE id = ?");
$stmt->bind_param("si", $token, $id_usuario);
$stmt->execute();

// Crear link de recuperación
$linkRecuperar = "http://njoy.free.nf/cambiarcontraseña.php?token=$token";

// Configuración SMTP
$mail = new PHPMailer(true);
try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'aywalovetacos000@gmail.com'; // Tu email
    $mail->Password   = 'rreg jooh hvln tzxe'; // ⚠️ CAMBIAR por contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Configuración del correo
    $mail->setFrom('aywalovetacos000@gmail.com', 'NJOY');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de contraseña - NJOY';
    $mail->Body    = "
        <h2>Recuperación de contraseña</h2>
        <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
        <a href='$linkRecuperar' style='color: #8B5CF6; text-decoration: none; font-weight: bold;'>Restablecer Contraseña</a>
        <p>Si no solicitaste este correo, ignóralo.</p>
        <hr>
        <small>Este enlace es válido por 24 horas.</small>
    ";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Correo enviado con instrucciones.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al enviar el correo: ' . $mail->ErrorInfo]);
}

$conn->close();
?>