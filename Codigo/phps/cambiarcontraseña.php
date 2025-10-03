<?php
session_start();
require_once 'db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Token no válido o expirado.';
} else {
    $stmt = $conn->prepare("SELECT id, email FROM usuarios WHERE token_recuperacion = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = 'Token no válido o expirado.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    if (empty($nueva_password) || empty($confirmar_password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($nueva_password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva_password !== $confirmar_password) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL WHERE token_recuperacion = ?");
        $stmt->bind_param("ss", $password_hash, $token);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = 'Contraseña actualizada exitosamente. Ahora puedes iniciar sesión.';
            $token = '';
        } else {
            $error = 'Error al actualizar la contraseña. Inténtalo de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NJOY - Cambiar Contraseña</title>
    <style>
        /* ------------------- Estilos generales ------------------- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #8B5CF6 0%, #3B0764 25%, #1E1B4B 50%, #312E81 75%, #6366F1 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        @keyframes gradientShift { 
            0% { background-position: 0% 50%; } 
            50% { background-position: 100% 50%; } 
            100% { background-position: 0% 50%; } 
        }
        /* ------------------- Formulario ------------------- */
        .container {
            width: 100%;
            max-width: 450px;
        }
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 2.5rem;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #8B5CF6, #6366F1);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        .form-card h2 {
            text-align: center;
            color: #4B5563;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            letter-spacing: 2px;
        }
        .form-description {
            text-align: center;
            color: #6B7280;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        /* ------------------- Inputs ------------------- */
        .input-group { 
            position: relative; 
            margin-bottom: 1.5rem; 
        }
        .input-icon { 
            position: absolute; 
            left: 1.2rem; 
            top: 50%; 
            transform: translateY(-50%); 
            font-size: 1.2rem; 
            color: #6B7280;
        }
        input {
            width: 100%;
            padding: 1.2rem 1rem 1.2rem 3.5rem;
            border-radius: 1.5rem;
            border: 2px solid #E5E7EB;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }
        input:focus { 
            border-color: #8B5CF6; 
            outline: none; 
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        /* ------------------- Botones y enlaces ------------------- */
        .submit-btn {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #8B5CF6 0%, #6366F1 100%);
            color: white;
            border: none;
            border-radius: 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin: 2rem 0 1.5rem;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
        }
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .back-to-login {
            text-align: center;
            margin-top: 2rem;
        }
        .back-to-login a {
            color: #8B5CF6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .back-to-login a:hover {
            color: #7C3AED;
        }
        /* ------------------- Mensajes ------------------- */
        .message {
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #DC2626;
        }
        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #059669;
        }

        /* ------------------- Validación visual ------------------- */
        .password-requirements {
            font-size: 0.8rem;
            color: #6B7280;
            margin-top: 0.5rem;
            padding-left: 3.5rem;
        }
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.2rem 0;
        }
        .requirement.valid { color: #059669; }
        .requirement.invalid { color: #DC2626; }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <div class="avatar">🔐</div>
            <h2>NUEVA CONTRASEÑA</h2>
            <?php if ($error): ?>
                <div class="message error-message">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="message success-message">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
                <div class="back-to-login">
                    <a href="index.html">🚀 Ir al inicio de sesión</a>
                </div>
            <?php elseif (empty($error)): ?>
                <p class="form-description">
                    Ingresa tu nueva contraseña para completar la recuperación:
                </p>
                <form method="POST" id="cambiarPasswordForm">
                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="nueva_password" 
                               name="nueva_password" 
                               placeholder="Nueva contraseña..." 
                               required 
                               minlength="6">
                        <div class="password-requirements">
                            <div class="requirement" id="req-length">
                                <span>•</span> Mínimo 6 caracteres
                            </div>
                        </div>
                    </div>
                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="confirmar_password" 
                               name="confirmar_password" 
                               placeholder="Confirmar contraseña..." 
                               required>
                        <div id="match-indicator" style="font-size: 0.8rem; margin-top: 0.5rem; padding-left: 3.5rem;"></div>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        🔄 CAMBIAR CONTRASEÑA
                    </button>
                </form>
            <?php else: ?>
                <div class="back-to-login">
                    <a href="RECUPERACION_FINAL(1).html">🔙 Solicitar nuevo token</a>
                </div>
            <?php endif; ?>
            <div class="back-to-login">
                <a href="index.html">← Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
    <script>
        const nuevaPassword = document.getElementById('nueva_password');
        const confirmarPassword = document.getElementById('confirmar_password');
        const submitBtn = document.getElementById('submitBtn');
        const reqLength = document.getElementById('req-length');
        const matchIndicator = document.getElementById('match-indicator');

        function validatePassword() {
            const password = nuevaPassword.value;
            const confirm = confirmarPassword.value;

            if (password.length >= 6) {
                reqLength.classList.add('valid');
                reqLength.classList.remove('invalid');
                reqLength.innerHTML = '<span>✅</span> Mínimo 6 caracteres';
            } else {
                reqLength.classList.add('invalid');
                reqLength.classList.remove('valid');
                reqLength.innerHTML = '<span>❌</span> Mínimo 6 caracteres';
            }
            if (confirm && password !== confirm) {
                matchIndicator.innerHTML = '<span style="color: #DC2626;">❌ Las contraseñas no coinciden</span>';
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
            } else if (confirm && password === confirm && password.length >= 6) {
                matchIndicator.innerHTML = '<span style="color: #059669;">✅ Las contraseñas coinciden</span>';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            } else {
                matchIndicator.innerHTML = '';
                submitBtn.disabled = password.length < 6;
                submitBtn.style.opacity = password.length >= 6 ? '1' : '0.6';
            }
        }
        nuevaPassword.addEventListener('input', validatePassword);
        confirmarPassword.addEventListener('input', validatePassword);
        document.getElementById('cambiarPasswordForm')?.addEventListener('submit', function() {
            submitBtn.innerHTML = '⏳ CAMBIANDO...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>