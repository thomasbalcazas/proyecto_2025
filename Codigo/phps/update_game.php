<?php
// update_game.php

// Iniciar la sesión y proteger el script
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Incluir la conexión a la BD
require_once 'db.php';

// Habilitar errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Asegurarse de que la petición es de tipo POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header('Location: admin.php');
    exit();
}

try {
    // --- 1. RECOGER Y VALIDAR DATOS BÁSICOS ---
    if (!isset($_POST['juego_id']) || !is_numeric($_POST['juego_id'])) {
        throw new Exception("ID de juego no válido.");
    }
    $juego_id = (int)$_POST['juego_id'];

    $titulo = $_POST['titulo'] ?? 'Sin Título';
    $pagina_url = $_POST['pagina_url'] ?? "game.php?id=$juego_id";
    $tags = $_POST['tags'] ?? '';
    $secciones_seleccionadas = $_POST['secciones'] ?? [];
    
    $precio_final = 0.00;
    $descuento = 0;
    if (!isset($_POST['es_free'])) {
        $precio_final = (float)($_POST['precio'] ?? 0.00);
        $descuento = (int)($_POST['descuento'] ?? 0);
    }

    $descripcion_larga = $_POST['descripcion_larga'] ?? '';
    $video_youtube_id = $_POST['video_youtube_id'] ?? '';
    $caracteristicas = str_replace(["\r\n", "\r", "\n"], '|', $_POST['caracteristicas'] ?? '');
    $req_minimos = str_replace(["\r\n", "\r", "\n"], '|', $_POST['req_minimos'] ?? '');
    $req_recomendados = str_replace(["\r\n", "\r", "\n"], '|', $_POST['req_recomendados'] ?? '');
    $desarrollador = $_POST['desarrollador'] ?? '';
    $editor = $_POST['editor'] ?? '';
    $fecha_lanzamiento = $_POST['fecha_lanzamiento'] ?? '';

    // --- 2. GESTIONAR IMAGEN DE PORTADA ---
    $imagen_url = $_POST['imagen_actual']; // Por defecto, mantenemos la imagen antigua
    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {
        $target_dir_cover = "njoyimages/";
        if (!is_writable($target_dir_cover)) throw new Exception("Error de permisos en la carpeta '{$target_dir_cover}'.");
        $nombre_archivo = time() . '_' . basename($_FILES["imagen"]["name"]);
        $target_file = $target_dir_cover . $nombre_archivo;
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
            // Si la subida es exitosa, actualizamos la ruta y borramos la antigua si es diferente
            if (file_exists($imagen_url) && $imagen_url !== $target_file) {
                unlink($imagen_url);
            }
            $imagen_url = $target_file; 
        } else { throw new Exception("Error al mover la nueva imagen de portada."); }
    }

    // --- 3. GESTIONAR GALERÍA DE IMÁGENES ---
    // a) Obtener galería actual
    $stmt_galeria = $conn->prepare("SELECT imagenes_galeria FROM juegos WHERE id = ?");
    $stmt_galeria->bind_param("i", $juego_id);
    $stmt_galeria->execute();
    $rutas_actuales = explode('|', $stmt_galeria->get_result()->fetch_assoc()['imagenes_galeria'] ?? '');
    $stmt_galeria->close();

    // b) Procesar eliminaciones
    $imagenes_a_borrar = $_POST['delete_images'] ?? [];
    foreach ($imagenes_a_borrar as $path_a_borrar) {
        if (in_array($path_a_borrar, $rutas_actuales) && file_exists($path_a_borrar)) {
            unlink($path_a_borrar);
        }
    }
    $rutas_conservadas = array_diff($rutas_actuales, $imagenes_a_borrar);

    // c) Procesar nuevas subidas con renombrado
    $rutas_nuevas = [];
    if (isset($_FILES['imagenes_galeria']) && !empty($_FILES['imagenes_galeria']['name'][0])) {
        $target_dir_gallery = "njoyimages/gallery/";
        if (!file_exists($target_dir_gallery)) mkdir($target_dir_gallery, 0775, true);
        if (!is_writable($target_dir_gallery)) throw new Exception("Error de permisos en la carpeta '{$target_dir_gallery}'.");
        
        $nombres_personalizados = $_POST['gallery_image_names'] ?? [];
        $total_files = count($_FILES['imagenes_galeria']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['imagenes_galeria']['error'][$i] === 0) {
                $custom_name = $nombres_personalizados[$i] ?? pathinfo($_FILES['imagenes_galeria']['name'][$i], PATHINFO_FILENAME);
                $original_extension = pathinfo($_FILES['imagenes_galeria']['name'][$i], PATHINFO_EXTENSION);
                $sanitized_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $custom_name);
                $final_filename = time() . '_' . $i . '_' . $sanitized_name . '.' . $original_extension;
                $target_file = $target_dir_gallery . $final_filename;

                if (move_uploaded_file($_FILES['imagenes_galeria']['tmp_name'][$i], $target_file)) {
                    $rutas_nuevas[] = $target_file;
                }
            }
        }
    }
    // d) Combinar y guardar
    $rutas_finales_array = array_merge($rutas_conservadas, $rutas_nuevas);
    $imagenes_galeria_string = implode('|', $rutas_finales_array);

    // --- 4. ACTUALIZAR LA BASE DE DATOS (CON TRANSACCIÓN) ---
    $conn->begin_transaction();

    // PASO 4.1: Actualizar la tabla `juegos`
    $stmt_update = $conn->prepare(
        "UPDATE juegos SET 
            titulo = ?, imagen_url = ?, precio_final = ?, descuento_porcentaje = ?, tags = ?, pagina_url = ?,
            descripcion_larga = ?, caracteristicas = ?, video_youtube_id = ?, imagenes_galeria = ?,
            req_minimos = ?, req_recomendados = ?, desarrollador = ?, editor = ?, fecha_lanzamiento = ?
        WHERE id = ?"
    );
    $stmt_update->bind_param("ssdisssssssssssi", 
        $titulo, $imagen_url, $precio_final, $descuento, $tags, $pagina_url,
        $descripcion_larga, $caracteristicas, $video_youtube_id, $imagenes_galeria_string,
        $req_minimos, $req_recomendados, $desarrollador, $editor, $fecha_lanzamiento,
        $juego_id
    );
    if (!$stmt_update->execute()) { throw new Exception("Error al actualizar el juego: " . $stmt_update->error); }
    $stmt_update->close();

    // PASO 4.2: Actualizar las secciones (borrar todo e insertar lo nuevo)
    $stmt_delete_secciones = $conn->prepare("DELETE FROM juego_secciones WHERE juego_id = ?");
    $stmt_delete_secciones->bind_param("i", $juego_id);
    $stmt_delete_secciones->execute();
    $stmt_delete_secciones->close();
    
    if (!empty($secciones_seleccionadas)) {
        $stmt_insert_secciones = $conn->prepare("INSERT INTO juego_secciones (juego_id, seccion_id) VALUES (?, ?)");
        foreach ($secciones_seleccionadas as $seccion_id) {
            $seccion_id_int = (int)$seccion_id;
            $stmt_insert_secciones->bind_param("ii", $juego_id, $seccion_id_int);
            $stmt_insert_secciones->execute();
        }
        $stmt_insert_secciones->close();
    }
    
    // Si todo fue bien, confirmar la transacción
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    header("Location: admin.php?status=success&message=" . urlencode("Juego '{$titulo}' actualizado con éxito."));
    exit;

} catch (Exception $e) {
    // Si algo falla, revertir la transacción
    if ($conn->ping()) { $conn->rollback(); }
    // Redirigir con un mensaje de error detallado
    header("Location: admin.php?status=error&message=" . urlencode($e->getMessage()));
    exit;
} finally {
    if (isset($conn)) { $conn->close(); }
}
?>