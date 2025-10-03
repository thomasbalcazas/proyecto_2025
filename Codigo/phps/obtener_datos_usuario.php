<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json')
;
require_once __DIR__ . "/db.php";

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        "status" => "error",
        "msg" => "Usuario no logueado"
    ]);
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

try {
    $stmt = $conn->prepare("SELECT nombre, email, foto FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Si el usuario no tiene foto personalizada, usar una por defecto
        $ruta_foto = !empty($usuario['foto']) ? $usuario['foto'] : "njoyimages/user.png";

        echo json_encode([
            "status" => "ok",
            "nombre" => $usuario['nombre'],
            "email" => $usuario['email'],
            "foto" => $ruta_foto
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "msg" => "Usuario no encontrado"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "msg" => "Error en el servidor: " . $e->getMessage()
    ]);
}
