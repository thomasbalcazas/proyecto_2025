<?php
// auth_user.php - Versión ajustada a tu tabla 'usuarios'

// Modo de depuración (lo dejaremos por si surge otro problema)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

$json_data = file_get_contents('php://input');
$data = json_decode($json_data);

if (!$data || !isset($data->email) || !isset($data->password)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit();
}

require_once 'db.php';

try {
    // --- CAMBIO 1: Se reemplazó 'password_hash' por 'password' ---
    $sql = "SELECT id, nombre, email, password FROM usuarios WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error SQL: " . $conn->error);
    }
    
    $stmt->bind_param("s", $data->email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // --- CAMBIO 2: Se reemplazó '$usuario['password_hash']' por '$usuario['password']' ---
        // Esta línea ahora verificará la contraseña de la columna 'password'.
        if (password_verify($data->password, $usuario['password'])) {
            // Login exitoso
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            $usuario_data_for_js = ['id' => $usuario['id'], 'nombre' => $usuario['nombre'], 'email' => $usuario['email']];
            echo json_encode(['success' => true, 'usuario' => $usuario_data_for_js]);
        } else {
            echo json_encode(['success' => false, 'message' => 'La contraseña es incorrecta.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit();
?>