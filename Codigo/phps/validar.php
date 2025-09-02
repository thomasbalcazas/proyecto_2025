<?php
$conexion = mysqli_connect("sql105.infinityfree.com", "if0_39843490_NJOY", "Njoy2025", "if0_39843490_NJOY");

if(isset($_POST['registrar'])){
    if(!empty($_POST['nombre']) && !empty($_POST['apellido']) && !empty($_POST['correo']) && !empty($_POST['password'])){

        $nombre   = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $correo   = trim($_POST['correo']);
        $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);

        // Usamos consulta preparada para evitar SQL Injection
        $stmt = $conexion->prepare("INSERT INTO usuario (nombre, apellido, correo, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $apellido, $correo, $password);

        if($stmt->execute()){
            header('Location: ../views/user.php');
            exit;
        } else {
            echo "Error al registrar usuario: " . $stmt->error;
        }

        $stmt->close();
        $conexion->close();
    }
}
?>


?>