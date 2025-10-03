<?php
// actualizar_nombre.php
session_start();
header('Content-Type: application/json');

// 1. Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'No has iniciado sesión.']);
    exit();
}

// 2. Verificar que se ha enviado un nombre y no está vacío
if (!isset($_POST['nombre']) || empty(trim($_POST['nombre']))) {
    echo json_encode(['status' => 'error', 'msg' => 'El nombre no puede estar vacío.']);
    exit();
}

$nuevo_nombre = trim($_POST['nombre']);
$usuario_id = $_SESSION['usuario_id'];

require 'db.php';

// 4. Preparar y ejecutar la consulta para evitar inyección SQL
$stmt = $conn->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
if ($stmt === false) {
     echo json_encode(['status' => 'error', 'msg' => 'Error al preparar la consulta.']);
     exit();
}

$stmt->bind_param("si", $nuevo_nombre, $usuario_id);

// 5. Enviar respuesta
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'ok', 'msg' => 'Nombre actualizado con éxito.', 'nuevoNombre' => $nuevo_nombre]);
    } else {
        // Esto puede pasar si el nombre nuevo es igual al antiguo
        echo json_encode(['status' => 'ok', 'msg' => 'El nombre no ha cambiado.', 'nuevoNombre' => $nuevo_nombre]);
    }
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Error al actualizar el nombre en la base de datos.']);
}

$stmt->close();
$conn->close();
?>