<?php
session_start();

// Prevenir caché
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

// Verificar si hay una sesión activa
if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'ok', 'message' => 'Sesión activa']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No hay sesión activa']);
}
exit;
?>