<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require "db.php";

// Obtener los datos enviados desde el front
$data = json_decode(file_get_contents("php://input"), true);
$nombre = $data["nombre"] ?? "";
$email = $data["email"] ?? "";
$password = $data["password"] ?? "";
$foto = $data["foto"] ?? "perfil.png"; // Foto por defecto si no se envía ninguna

// Validación básica
if (!$nombre || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Faltan datos"]);
    exit;
}

// Encriptar la contraseña
$hash = password_hash($password, PASSWORD_BCRYPT);

// Verificar si el usuario ya existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "El correo ya está registrado"]);
    exit;
}
$stmt->close();

// Insertar nuevo usuario con foto
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, foto) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre, $email, $hash, $foto);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Registro exitoso"]);
} else {
    echo json_encode(["success" => false, "message" => "Error en el registro"]);
}

$stmt->close();
$conn->close();
?>
