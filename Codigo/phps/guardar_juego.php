<?php
// guardar_juego.php

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
    // --- 1. RECOGER DATOS DEL FORMULARIO ---
    $titulo = $_POST['titulo'] ?? 'Sin Título';
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

    // --- 2. SUBIR IMAGEN DE PORTADA ---
    $imagen_url = ''; 
    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {
        $target_dir_cover = "njoyimages/";
        if (!is_writable($target_dir_cover)) throw new Exception("Error de permisos en la carpeta '{$target_dir_cover}'.");
        $nombre_archivo = time() . '_' . basename($_FILES["imagen"]["name"]);
        $target_file = $target_dir_cover . $nombre_archivo;
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
            $imagen_url = $target_file; 
        } else { throw new Exception("Error al mover la imagen de portada."); }
    } else {
        throw new Exception("Error: La imagen de portada es obligatoria.");
    }
    
    // --- 3. SUBIR IMÁGENES DE GALERÍA (LÓGICA MÚLTIPLE CON RENOMBRADO) ---
    $rutas_galeria = [];
    if (isset($_FILES['imagenes_galeria']) && !empty($_FILES['imagenes_galeria']['name'][0])) {
        $target_dir_gallery = "njoyimages/gallery/";
        if (!file_exists($target_dir_gallery)) { mkdir($target_dir_gallery, 0775, true); }
        if (!is_writable($target_dir_gallery)) { throw new Exception("Error de permisos en la carpeta '{$target_dir_gallery}'."); }

        $nombres_personalizados = $_POST['gallery_image_names'] ?? [];
        $total_files = count($_FILES['imagenes_galeria']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['imagenes_galeria']['error'][$i] === 0) {
                // Obtener el nombre personalizado o usar el original como fallback
                $custom_name = $nombres_personalizados[$i] ?? pathinfo($_FILES['imagenes_galeria']['name'][$i], PATHINFO_FILENAME);
                $original_extension = pathinfo($_FILES['imagenes_galeria']['name'][$i], PATHINFO_EXTENSION);
                
                // Limpiar el nombre de caracteres peligrosos
                $sanitized_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $custom_name);
                
                // Crear un nombre de archivo final único
                $final_filename = time() . '_' . $i . '_' . $sanitized_name . '.' . $original_extension;
                $target_file_galeria = $target_dir_gallery . $final_filename;

                if (move_uploaded_file($_FILES['imagenes_galeria']['tmp_name'][$i], $target_file_galeria)) {
                    $rutas_galeria[] = $target_file_galeria;
                }
            }
        }
    }
    $imagenes_galeria_string = implode('|', $rutas_galeria);

    // --- 4. INSERTAR EN LA BASE DE DATOS (CON TRANSACCIÓN) ---
    $conn->begin_transaction();

    // PASO 4.1: Insertar el juego en la tabla `juegos` con una URL temporal
    $stmt_juego = $conn->prepare(
        "INSERT INTO juegos (titulo, pagina_url, imagen_url, precio_final, descuento_porcentaje, tags, descripcion_larga, caracteristicas, video_youtube_id, imagenes_galeria, req_minimos, req_recomendados, desarrollador, editor, fecha_lanzamiento) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $pagina_url_placeholder = 'temp'; // URL temporal
    $stmt_juego->bind_param("sssdsssssssssss", 
        $titulo, $pagina_url_placeholder, $imagen_url, $precio_final, $descuento, $tags,
        $descripcion_larga, $caracteristicas, $video_youtube_id, $imagenes_galeria_string,
        $req_minimos, $req_recomendados, $desarrollador, $editor, $fecha_lanzamiento
    );
    if (!$stmt_juego->execute()) {
        throw new Exception("Error al guardar el juego: " . $stmt_juego->error);
    }
    
    $juego_id = $conn->insert_id;
    $stmt_juego->close();

    // PASO 4.2: Actualizar la `pagina_url` con el ID real que acabamos de obtener
    $pagina_url_final = "game.php?id=" . $juego_id;
    $stmt_url = $conn->prepare("UPDATE juegos SET pagina_url = ? WHERE id = ?");
    $stmt_url->bind_param("si", $pagina_url_final, $juego_id);
    $stmt_url->execute();
    $stmt_url->close();

    // PASO 4.3: Insertar las relaciones en la tabla `juego_secciones`
    if (!empty($secciones_seleccionadas)) {
        $stmt_secciones = $conn->prepare("INSERT INTO juego_secciones (juego_id, seccion_id) VALUES (?, ?)");
        foreach ($secciones_seleccionadas as $seccion_id) {
            $seccion_id_int = (int)$seccion_id;
            $stmt_secciones->bind_param("ii", $juego_id, $seccion_id_int);
            $stmt_secciones->execute();
        }
        $stmt_secciones->close();
    }
    
    // Si todo fue bien, confirmar la transacción
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    header("Location: admin.php?status=success&message=" . urlencode("Juego '{$titulo}' guardado con éxito."));
    exit;

} catch (Exception $e) {
    // Si algo falla, revertir la transacción para no dejar datos a medias
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    // Redirigir con un mensaje de error detallado
    header("Location: admin.php?status=error&message=" . urlencode($e->getMessage()));
    exit;
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>