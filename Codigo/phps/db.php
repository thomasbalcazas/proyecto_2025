<?php
$host = "sql208.infinityfree.com";
$user = "if0_39450468";
$pass = "Njoy2025";
$db   = "if0_39450468_njoy";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Error en la conexión: " . $conn->connect_error]));
}
?>
