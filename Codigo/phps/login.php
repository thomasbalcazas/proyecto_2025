<?php
// Iniciar la sesión. Esto DEBE ser lo primero en el archivo.
session_start();

// Definir las credenciales correctas
$correct_username = 'admin';
$correct_password = 'Njoyadminadmin';

// Variable para mensajes de error
$error_message = '';

// Verificar si ya hay una sesión activa de administrador
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

// Comprobar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submitted_username = $_POST['username'] ?? '';
    $submitted_password = $_POST['password'] ?? '';

    // Validar las credenciales
    if ($submitted_username === $correct_username && $submitted_password === $correct_password) {
        // Credenciales correctas: guardar en la sesión y redirigir
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        // Credenciales incorrectas
        $error_message = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Administrador - NJOY</title>
    <style>
        /* CSS adaptado para una página de login profesional */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #1A1A2E;
            color: #E0E7FF;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            background-color: #16213E;
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #FFF;
            font-weight: 600;
        }
        .form-group { margin-bottom: 1.5rem; }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #A5B4FC;
        }
        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            background-color: #1A1A2E;
            border: 2px solid #312E81;
            border-radius: 8px;
            color: #E0E7FF;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25);
        }
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(90deg, #8B5CF6, #6366F1);
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }
        .error-message {
            color: #F87171; /* Un rojo claro */
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.5);
            padding: 0.8rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Acceso de Admin</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            <button type="submit" class="submit-btn">Entrar</button>
        </form>
    </div>
</body>
</html>