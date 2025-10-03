<?php
// limpiar_fotos.php - Ejecutar manualmente cuando sea necesario
session_start();
include("db.php");

// Solo permitir a administradores o ejecutar manualmente
if (!isset($_SESSION["usuario_id"])) {
    die("Acceso denegado");
}

$uploadDir = "uploads/";
$fotosEliminadas = 0;
$errores = [];

if (is_dir($uploadDir)) {
    // Obtener todas las fotos referenciadas en la BD
    $stmt = $conn->prepare("SELECT foto FROM usuarios WHERE foto IS NOT NULL AND foto != ''");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $fotosEnUso = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['foto']) {
            $fotosEnUso[] = basename($row['foto']);
        }
    }
    
    // Escanear directorio uploads
    $archivos = scandir($uploadDir);
    
    foreach ($archivos as $archivo) {
        if ($archivo === '.' || $archivo === '..' || $archivo === 'default.png') {
            continue;
        }
        
        $rutaCompleta = $uploadDir . $archivo;
        
        // Si el archivo no está en uso en la BD, eliminarlo
        if (!in_array($archivo, $fotosEnUso) && is_file($rutaCompleta)) {
            if (unlink($rutaCompleta)) {
                $fotosEliminadas++;
                error_log("Foto huérfana eliminada: " . $rutaCompleta);
            } else {
                $errores[] = "No se pudo eliminar: " . $archivo;
            }
        }
    }
    
    echo json_encode([
        "status" => "ok",
        "fotos_eliminadas" => $fotosEliminadas,
        "errores" => $errores,
        "mensaje" => "Limpieza completada. $fotosEliminadas archivos eliminados."
    ]);
} else {
    echo json_encode(["status" => "error", "msg" => "Directorio uploads no encontrado"]);
}
?>