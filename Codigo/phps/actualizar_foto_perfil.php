<?php
session_start();
header('Content-Type: application/json');
include("db.php");
if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["status" => "error", "msg" => "Acceso denegado. No hay sesión activa."]);
    exit;
}

$idUsuario = $_SESSION["usuario_id"];
if (!isset($_FILES["foto"]) || $_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "msg" => "No se recibió el archivo o hubo un error en la subida."]);
    exit;
}
$file = $_FILES["foto"];

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
$extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    echo json_encode(["status" => "error", "msg" => "Tipo de archivo no permitido (solo JPG, PNG, GIF)."]);
    exit;
}

if ($file["size"] > 5 * 1024 * 1024) { // 5MB
    echo json_encode(["status" => "error", "msg" => "El archivo es demasiado grande (máximo 5MB)."]);
    exit;
}
$uploadDirAbsoluta = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
$uploadDirWeb = '/uploads/';
if (!is_dir($uploadDirAbsoluta)) {
    mkdir($uploadDirAbsoluta, 0755, true);
}

$fileName = "user_" . $idUsuario . "_" . time() . "." . $extension;
$rutaCompletaAbsoluta = $uploadDirAbsoluta . $fileName;
$rutaCompletaWeb = $uploadDirWeb . $fileName;


if (move_uploaded_file($file["tmp_name"], $rutaCompletaAbsoluta)) {
    $stmt = $conn->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
    $stmt->bind_param("si", $rutaCompletaWeb, $idUsuario);
    
    if ($stmt->execute()) {
        if (isset($_SESSION['usuario'])) {
            $_SESSION['usuario']['foto'] = $rutaCompletaWeb;
        }
        echo json_encode([
            "status" => "ok", 
            "ruta" => $rutaCompletaWeb,
            "mensaje" => "Foto de perfil actualizada correctamente."
        ]);
    } else {
        unlink($rutaCompletaAbsoluta);
        echo json_encode(["status" => "error", "msg" => "Error al actualizar la base de datos."]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "msg" => "Error crítico al guardar el archivo en el servidor."]);
}

$conn->close();
?>