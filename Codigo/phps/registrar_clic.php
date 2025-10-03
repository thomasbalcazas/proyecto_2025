<?php
// registrar_clic.php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['juego_id'])) {
    $juego_id = (int)$_POST['juego_id'];

    if ($juego_id > 0) {
        $sql = "UPDATE juegos SET click_count = click_count + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $juego_id);
        $stmt->execute();
        $stmt->close();
    }
}
$conn->close();
// No es necesario devolver una respuesta, la enviamos y nos olvidamos.
?>