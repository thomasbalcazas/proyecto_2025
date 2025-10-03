<?php
// edit_game.php

// Iniciar la sesión y proteger la página
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Incluir la conexión a la base de datos
require_once 'db.php';

// Validar que se ha recibido un ID numérico por la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Se requiere un ID de juego válido.");
}
$juego_id = (int)$_GET['id'];

// 1. OBTENER LOS DATOS PRINCIPALES DEL JUEGO
$stmt = $conn->prepare("SELECT * FROM juegos WHERE id = ?");
$stmt->bind_param("i", $juego_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    die("Error: Juego con ID {$juego_id} no encontrado.");
}
$juego = $result->fetch_assoc();
$stmt->close();

// 2. OBTENER LAS SECCIONES A LAS QUE YA PERTENECE EL JUEGO
$secciones_actuales = [];
$stmt_secciones = $conn->prepare("SELECT seccion_id FROM juego_secciones WHERE juego_id = ?");
$stmt_secciones->bind_param("i", $juego_id);
$stmt_secciones->execute();
$result_secciones = $stmt_secciones->get_result();
while ($row = $result_secciones->fetch_assoc()) {
    $secciones_actuales[] = $row['seccion_id'];
}
$stmt_secciones->close();

// Variable para saber si el juego es Free to Play
$is_free = ($juego['precio_final'] == 0 && $juego['descuento_porcentaje'] == 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Juego - NJOY</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background-color: #1A1A2E; color: #E0E7FF; line-height: 1.6; padding: 2rem; }
        .admin-container { max-width: 800px; margin: 0 auto; }
        h1, h2 { color: #FFFFFF; margin-bottom: 1.5rem; font-weight: 600; }
        h1 { text-align: center; }
        h2 { padding-bottom: 0.5rem; border-bottom: 1px solid rgba(139, 92, 246, 0.3); }
        .form-container { background-color: #16213E; padding: 2.5rem; border-radius: 12px; border: 1px solid rgba(139, 92, 246, 0.2); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); }
        .form-group { margin-bottom: 1.5rem; }
        .form-row { display: flex; gap: 1.5rem; }
        .form-row .form-group { flex: 1; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; color: #A5B4FC; }
        .form-input, textarea.form-input { width: 100%; padding: 0.8rem 1rem; background-color: #1A1A2E; border: 2px solid #312E81; border-radius: 8px; color: #E0E7FF; font-size: 1rem; transition: all 0.3s ease; font-family: inherit; }
        textarea.form-input { resize: vertical; height: auto; }
        .form-input:disabled { background-color: #2a2a4a; color: #888; cursor: not-allowed; }
        .form-input::placeholder { color: #6366F1; opacity: 0.6; }
        .form-input:focus:not(:disabled) { outline: none; border-color: #8B5CF6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25); }
        input[type="file"].form-input { padding: 0; }
        input[type="file"]::file-selector-button { background-color: #312E81; color: #E0E7FF; border: none; padding: 0.8rem 1rem; border-right: 2px solid #312E81; margin-right: 1rem; cursor: pointer; transition: background-color 0.3s ease; }
        input[type="file"]::file-selector-button:hover { background-color: #6366F1; }
        .checkbox-group { border: 2px solid #312E81; border-radius: 8px; padding: 1rem; background-color: #1A1A2E; }
        .checkbox-item { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
        .checkbox-item:last-child { margin-bottom: 0; }
        .checkbox-item input[type="checkbox"] { width: 1.2em; height: 1.2em; accent-color: #8B5CF6; cursor: pointer; }
        .checkbox-item label { margin-bottom: 0; font-weight: 500; color: #E0E7FF; cursor: pointer; }
        .form-group-checkbox { display: flex; align-items: center; gap: 0.75rem; background-color: #1A1A2E; padding: 0.8rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; cursor: pointer; border: 2px solid #312E81; transition: all 0.3s ease; }
        .form-group-checkbox:hover { border-color: #8B5CF6; }
        .form-group-checkbox input[type="checkbox"] { width: 1.2em; height: 1.2em; accent-color: #8B5CF6; cursor: pointer; }
        .form-group-checkbox label { margin-bottom: 0; font-weight: 500; color: #E0E7FF; cursor: pointer; }
        .submit-btn { width: 100%; padding: 1rem; background: linear-gradient(90deg, #6366F1, #8B5CF6); color: #FFFFFF; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; text-transform: uppercase; }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3); }
        .current-image { max-width: 100px; display: block; margin-bottom: 10px; border-radius: 4px; border: 2px solid #312E81; }
        .back-link { display: inline-block; margin-bottom: 2rem; color: #A5B4FC; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
        .form-help-text { display: block; margin-top: 0.5rem; font-size: 0.8rem; color: #9CA3AF; line-height: 1.4; }
        .form-help-text code { background-color: #1A1A2E; padding: 2px 5px; border-radius: 4px; color: #E0E7FF; font-family: monospace; }
        .form-help-text strong { color: #a78bfa; }
        
        .gallery-management { border: 2px solid #312E81; border-radius: 8px; padding: 1rem; background-color: #1A1A2E; }
        .current-gallery, .gallery-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .gallery-item { position: relative; border: 2px solid #312E81; border-radius: 4px; overflow: hidden; }
        .gallery-item img { width: 100%; height: auto; display: block; aspect-ratio: 16/9; object-fit: cover; }
        .delete-checkbox { position: absolute; top: 5px; right: 5px; background-color: rgba(0,0,0,0.6); border-radius: 3px; padding: 2px; display: flex; align-items: center; }
        .delete-checkbox input { width: 18px; height: 18px; accent-color: #ec4899; cursor: pointer; }
        
        .preview-item { display: flex; flex-direction: column; gap: 0.5rem; }
        .preview-image { width: 100%; height: auto; aspect-ratio: 16/9; object-fit: cover; border-radius: 4px; }
        .preview-name-input { font-size: 0.8rem; padding: 0.4rem; width: 100%; border: 1px solid #312E81; background-color: #16213E; color: #E0E7FF; border-radius: 4px; }

        @media (max-width: 600px) { body { padding: 1rem; } .form-container { padding: 1.5rem; } .form-row { flex-direction: column; gap: 0; } }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="admin.php" class="back-link">← Volver al listado</a>
        <h1>Modificar Juego</h1>
        
        <div class="form-container">
            <h2>Editando: "<?php echo htmlspecialchars($juego['titulo']); ?>"</h2>
            <form action="update_game.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="juego_id" value="<?php echo $juego['id']; ?>">

                <div class="form-group"><label for="titulo">Título del Juego</label><input type="text" id="titulo" name="titulo" class="form-input" value="<?php echo htmlspecialchars($juego['titulo']); ?>" required></div>
                <div class="form-group"><label for="pagina_url">URL de la Página del Juego</label><input type="text" id="pagina_url" name="pagina_url" class="form-input" value="<?php echo htmlspecialchars($juego['pagina_url'] ?? 'game.php?id=' . $juego['id']); ?>" required></div>
                <div class="form-group"><label for="imagen">Cambiar Imagen de Portada (opcional)</label><img src="<?php echo htmlspecialchars($juego['imagen_url']); ?>" alt="Imagen actual" class="current-image"><input type="file" id="imagen" name="imagen" class="form-input"><input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($juego['imagen_url']); ?>"></div>
                <div class="form-group-checkbox"><input type="checkbox" id="es_free" name="es_free" <?php if ($is_free) echo 'checked'; ?>><label for="es_free">Marcar si es Free to Play</label></div>
                <div class="form-row"><div class="form-group"><label for="precio">Precio Final (USD)</label><input type="number" id="precio" name="precio" step="0.01" class="form-input" value="<?php echo htmlspecialchars($juego['precio_final']); ?>" <?php if ($is_free) echo 'disabled'; ?> required></div><div class="form-group"><label for="descuento">Descuento (%)</label><input type="number" id="descuento" name="descuento" class="form-input" value="<?php echo htmlspecialchars($juego['descuento_porcentaje']); ?>" <?php if ($is_free) echo 'disabled'; ?>></div></div>
                <div class="form-group"><label for="tags">Tags (separados por coma)</label><input type="text" id="tags" name="tags" class="form-input" value="<?php echo htmlspecialchars($juego['tags']); ?>" placeholder="Ej: ROL, Aventura"></div>
                
                <h2>Detalles de la Página del Juego</h2>
                <div class="form-group"><label for="descripcion_larga">Descripción Larga</label><textarea id="descripcion_larga" name="descripcion_larga" class="form-input" rows="6"><?php echo htmlspecialchars($juego['descripcion_larga']); ?></textarea></div>
                <div class="form-group">
                    <label for="video_youtube_id">ID del Video de YouTube</label>
                    <input type="text" id="video_youtube_id" name="video_youtube_id" class="form-input" value="<?php echo htmlspecialchars($juego['video_youtube_id']); ?>" placeholder="Ej: QdBZY2fkU-0">
                    <small class="form-help-text">Copia solo el código de 11 caracteres de la URL. Ej: <code>youtube.com/watch?v=<strong>QdBZY2fkU-0</strong></code></small>
                </div>

                <div class="form-group">
                    <label>Gestionar Galería de Imágenes</label>
                    <div class="gallery-management">
                        <label>Galería Actual (marcar para eliminar)</label>
                        <div class="current-gallery">
                            <?php
                            $galeria_actual = !empty($juego['imagenes_galeria']) ? explode('|', $juego['imagenes_galeria']) : [];
                            if (!empty($galeria_actual[0])):
                                foreach ($galeria_actual as $imagen_path):
                            ?>
                                <div class="gallery-item">
                                    <img src="<?php echo htmlspecialchars($imagen_path); ?>" alt="Imagen de galería">
                                    <div class="delete-checkbox"><input type="checkbox" name="delete_images[]" value="<?php echo htmlspecialchars($imagen_path); ?>" title="Marcar para eliminar"></div>
                                </div>
                            <?php
                                endforeach;
                            else:
                                echo "<p style='color:#9CA3AF; font-size:0.9rem;'>No hay imágenes en la galería.</p>";
                            endif;
                            ?>
                        </div>
                        <label for="new_gallery_images">Añadir nuevas imágenes (con vista previa y renombrado)</label>
                        <input type="file" id="new_gallery_images" name="imagenes_galeria[]" class="form-input" multiple>
                        <div id="gallery_preview_container" class="gallery-preview" style="margin-top: 1rem;"></div>
                    </div>
                </div>

                <div class="form-group"><label for="caracteristicas">Características Principales (una por línea)</label><textarea id="caracteristicas" name="caracteristicas" class="form-input" rows="5"><?php echo str_replace('|', "\n", htmlspecialchars($juego['caracteristicas'])); ?></textarea></div>
                <div class="form-row"><div class="form-group"><label for="req_minimos">Requisitos Mínimos (una por línea)</label><textarea id="req_minimos" name="req_minimos" class="form-input" rows="6"><?php echo str_replace('|', "\n", htmlspecialchars($juego['req_minimos'])); ?></textarea></div><div class="form-group"><label for="req_recomendados">Requisitos Recomendados (una por línea)</label><textarea id="req_recomendados" name="req_recomendados" class="form-input" rows="6"><?php echo str_replace('|', "\n", htmlspecialchars($juego['req_recomendados'])); ?></textarea></div></div>
                <div class="form-row"><div class="form-group"><label for="desarrollador">Desarrollador</label><input type="text" id="desarrollador" name="desarrollador" class="form-input" value="<?php echo htmlspecialchars($juego['desarrollador']); ?>"></div><div class="form-group"><label for="editor">Editor</label><input type="text" id="editor" name="editor" class="form-input" value="<?php echo htmlspecialchars($juego['editor']); ?>"></div></div>
                <div class="form-group"><label for="fecha_lanzamiento">Fecha de Lanzamiento</label><input type="text" id="fecha_lanzamiento" name="fecha_lanzamiento" class="form-input" value="<?php echo htmlspecialchars($juego['fecha_lanzamiento']); ?>"></div>
                
                <div class="form-group">
                    <label>Asignar a Secciones</label>
                    <div class="checkbox-group">
                        <?php
                        $sql_secciones_totales = "SELECT id, nombre_visible FROM secciones ORDER BY nombre_visible ASC";
                        $result_secciones_totales = $conn->query($sql_secciones_totales);
                        if ($result_secciones_totales && $result_secciones_totales->num_rows > 0) {
                            while($seccion = $result_secciones_totales->fetch_assoc()) {
                                $seccion_id = htmlspecialchars($seccion['id']);
                                $nombre_seccion = htmlspecialchars($seccion['nombre_visible']);
                                $checked = in_array($seccion['id'], $secciones_actuales) ? 'checked' : '';
                                echo "<div class='checkbox-item'><input type='checkbox' name='secciones[]' value='{$seccion_id}' id='seccion_{$seccion_id}' {$checked}><label for='seccion_{$seccion_id}'>{$nombre_seccion}</label></div>";
                            }
                        }
                        ?>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Actualizar Juego</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const freeCheckbox = document.getElementById('es_free');
            const precioInput = document.getElementById('precio');
            const descuentoInput = document.getElementById('descuento');

            freeCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                precioInput.disabled = isChecked;
                descuentoInput.disabled = isChecked;
                if (isChecked) {
                    precioInput.value = '0.00';
                    precioInput.required = false;
                    descuentoInput.value = '0';
                } else {
                    precioInput.required = true;
                }
            });

            const imageInput = document.getElementById('new_gallery_images');
            const previewContainer = document.getElementById('gallery_preview_container');

            imageInput.addEventListener('change', function(event) {
                previewContainer.innerHTML = '';
                if (event.target.files) {
                    Array.from(event.target.files).forEach(file => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'preview-item';
                            const img = document.createElement('img');
                            img.className = 'preview-image';
                            img.src = e.target.result;
                            const nameInput = document.createElement('input');
                            nameInput.type = 'text';
                            nameInput.className = 'preview-name-input';
                            nameInput.name = 'gallery_image_names[]';
                            nameInput.value = file.name.split('.').slice(0, -1).join('.');
                            previewItem.appendChild(img);
                            previewItem.appendChild(nameInput);
                            previewContainer.appendChild(previewItem);
                        }
                        reader.readAsDataURL(file);
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>