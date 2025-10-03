<?php
// delete_game.php

session_start();
// Proteger el script: solo los administradores pueden borrar
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

// Validar que se recibió un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin.php?status=error&message=' . urlencode('ID de juego no válido.'));
    exit;
}
$juego_id = (int)$_GET['id'];

try {
    // Iniciar una transacción para asegurar que todo se complete o nada
    $conn->begin_transaction();

    // 1. Obtener las rutas de las imágenes ANTES de borrar el juego de la BD
    $stmt_get_images = $conn->prepare("SELECT titulo, imagen_url, imagenes_galeria FROM juegos WHERE id = ?");
    $stmt_get_images->bind_param("i", $juego_id);
    $stmt_get_images->execute();
    $result = $stmt_get_images->get_result();
    if ($result->num_rows !== 1) {
        throw new Exception("Juego no encontrado.");
    }
    $juego = $result->fetch_assoc();
    $stmt_get_images->close();

    // 2. Eliminar los archivos de imagen del servidor
    // a) Eliminar la imagen de portada
    if (!empty($juego['imagen_url']) && file_exists($juego['imagen_url'])) {
        unlink($juego['imagen_url']);
    }
    // b) Eliminar las imágenes de la galería
    if (!empty($juego['imagenes_galeria'])) {
        $galeria_paths = explode('|', $juego['imagenes_galeria']);
        foreach ($galeria_paths as $path) {
            if (!empty($path) && file_exists($path)) {
                unlink($path);
            }
        }
    }

    // 3. Eliminar el juego de la base de datos
    // Gracias a "ON DELETE CASCADE", las entradas en `juego_secciones` se borrarán automáticamente.
    $stmt_delete = $conn->prepare("DELETE FROM juegos WHERE id = ?");
    $stmt_delete->bind_param("i", $juego_id);
    $stmt_delete->execute();
    
    if ($stmt_delete->affected_rows === 0) {
        throw new Exception("No se pudo eliminar el juego de la base de datos.");
    }
    $stmt_delete->close();
    
    // Si todo fue bien, confirmar la transacción
    $conn->commit();
    
    // Redirigir de vuelta al panel de admin con un mensaje de éxito
    header('Location: admin.php?status=deleted&game_title=' . urlencode($juego['titulo']));
    exit;

} catch (Exception $e) {
    // Si algo falla, revertir la transacción
    if ($conn->ping()) {
        $conn->rollback();
    }
    // Redirigir con un mensaje de error
    header('Location: admin.php?status=error&message=' . urlencode($e->getMessage()));
    exit;
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>