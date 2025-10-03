<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'No has iniciado sesión.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Método no permitido.']);
    exit();
}
if (!isset($_POST['antiguaPass'], $_POST['nuevaPass'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Faltan datos para procesar la solicitud.']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$antiguaPass = $_POST['antiguaPass'];
$nuevaPass = $_POST['nuevaPass'];

if (strlen($nuevaPass) < 8) {
    echo json_encode(['status' => 'error', 'msg' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
    exit();
}

require 'db.php';

$stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id); 
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Usuario no encontrado en la base de datos.']);
    $stmt->close();
    $conn->close();
    exit();
}

$usuario = $result->fetch_assoc();
$hashActual = $usuario['password'];
$stmt->close();

if (password_verify($antiguaPass, $hashActual)) {
    $nuevoHash = password_hash($nuevaPass, PASSWORD_DEFAULT);
    $stmt_update = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt_update->bind_param("si", $nuevoHash, $usuario_id);
    if ($stmt_update->execute()) {
        echo json_encode(['status' => 'ok', 'msg' => 'Contraseña actualizada con éxito.']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Error al actualizar la contraseña en la base de datos.']);
    }
    $stmt_update->close();
} else {
    echo json_encode(['status' => 'error', 'msg' => 'La contraseña antigua es incorrecta.']);
}
$conn->close();
?>