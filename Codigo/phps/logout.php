<?php
// logout.php

// Iniciar la sesión para poder acceder a ella
session_start();

// Destruir todas las variables de la sesión
$_SESSION = array();

// Finalmente, destruir la sesión del servidor
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Enviar una confirmación en formato JSON
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
exit;
?>