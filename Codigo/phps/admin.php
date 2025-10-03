<?php
// admin.php

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - NJOY</title>
    <style>
        /* CSS completo para el panel de administración */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background-color: #1A1A2E; color: #E0E7FF; line-height: 1.6; padding: 2rem; }
        .admin-container { max-width: 800px; margin: 0 auto; }
        h1 { color: #FFFFFF; text-align: center; margin-bottom: 2rem; font-weight: 600; letter-spacing: 1px; }
        h2 { color: #FFFFFF; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(139, 92, 246, 0.3); font-weight: 500; }
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
        .submit-btn { width: 100%; padding: 1rem; background: linear-gradient(90deg, #8B5CF6, #6366F1); color: #FFFFFF; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; letter-spacing: 0.5px; text-transform: uppercase; }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3); }
        .manage-section { margin-top: 4rem; }
        .games-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; background-color: #16213E; border-radius: 8px; overflow: hidden; border: 1px solid rgba(139, 92, 246, 0.2); }
        .games-table th, .games-table td { padding: 0.8rem 1.2rem; text-align: left; border-bottom: 1px solid #312E81; }
        .games-table thead { background-color: rgba(30, 30, 47, 0.85); }
        .games-table th { color: #A5B4FC; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .games-table td { color: #E0E7FF; vertical-align: middle; }
        .games-table tr:last-child td { border-bottom: none; }
        .games-table tr:hover { background-color: #1a2744; }
        .actions-cell { display: flex; gap: 0.5rem; }
        .action-btn { display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.8rem; transition: all 0.2s ease; }
        .edit-btn { background-color: #8B5CF6; color: white; }
        .edit-btn:hover { background-color: #7c4dff; transform: scale(1.05); }
        .delete-btn { background-color: #EF4444; color: white; }
        .delete-btn:hover { background-color: #dc2626; transform: scale(1.05); }
        .notification { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; text-align: center; }
        .notification.success { background-color: #10B981; color: white; }
        .notification.error { background-color: #EF4444; color: white; }
        .form-help-text { display: block; margin-top: 0.5rem; font-size: 0.8rem; color: #9CA3AF; line-height: 1.4; }
        .form-help-text code { background-color: #1A1A2E; padding: 2px 5px; border-radius: 4px; color: #E0E7FF; font-family: monospace; }
        .form-help-text strong { color: #a78bfa; }
        .gallery-management { border: 2px solid #312E81; border-radius: 8px; padding: 1rem; background-color: #1A1A2E; }
        .file-upload-button { display: inline-block; width: 100%; padding: 0.8rem 1rem; background-color: #1A1A2E; border: 2px solid #312E81; border-radius: 8px; color: #E0E7FF; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; text-align: left; }
        .file-upload-button:hover { border-color: #8B5CF6; }
        .gallery-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .preview-item { display: flex; flex-direction: column; gap: 0.5rem; }
        .preview-image { width: 100%; height: auto; aspect-ratio: 16/9; object-fit: cover; border-radius: 4px; }
        .preview-name-input { font-size: 0.8rem; padding: 0.4rem; width: 100%; border: 1px solid #312E81; background-color: #16213E; color: #E0E7FF; border-radius: 4px; }
        @media (max-width: 600px) { body { padding: 1rem; } .form-container { padding: 1.5rem; } .form-row { flex-direction: column; gap: 0; } }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="admin_logout.php" style="float: right; color: #A5B4FC; text-decoration: none;">Cerrar Sesión</a>
        <h1>Panel de Administración de NJOY</h1>

        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'deleted') { echo "<div class='notification success'>" . htmlspecialchars($_GET['game_title'] ?? 'El juego') . " ha sido eliminado.</div>"; }
            elseif ($_GET['status'] == 'error') { echo "<div class='notification error'>Error: " . htmlspecialchars($_GET['message'] ?? 'Error inesperado.') . "</div>"; }
            elseif ($_GET['status'] == 'success') { echo "<div class='notification success'>" . htmlspecialchars($_GET['message'] ?? 'Operación completada.') . "</div>"; }
        }
        ?>
        
        <div class="form-container">
            <h2>Añadir Nuevo Juego</h2>
            <form action="guardar_juego.php" method="post" enctype="multipart/form-data">
                <!-- FORMULARIO DE AÑADIR JUEGO (completo) -->
                <h2>Datos Principales</h2>
                <div class="form-group"><label for="titulo">Título del Juego</label><input type="text" id="titulo" name="titulo" class="form-input" required></div>
                <div class="form-group"><label for="imagen">Imagen de Portada (para la tarjeta)</label><input type="file" id="imagen" name="imagen" class="form-input" required></div>
                <div class="form-group-checkbox"><input type="checkbox" id="es_free" name="es_free"><label for="es_free">Marcar si es Free to Play</label></div>
                <div class="form-row"><div class="form-group"><label for="precio">Precio Final (USD)</label><input type="number" id="precio" name="precio" step="0.01" class="form-input" required></div><div class="form-group"><label for="descuento">Descuento (%)</label><input type="number" id="descuento" name="descuento" class="form-input" value="0"></div></div>
                <div class="form-group"><label for="tags">Tags (separados por coma)</label><input type="text" id="tags" name="tags" class="form-input" placeholder="Ej: ROL, Aventura"></div>
                
                <h2>Detalles de la Página del Juego</h2>
                <div class="form-group"><label for="descripcion_larga">Descripción Larga</label><textarea id="descripcion_larga" name="descripcion_larga" class="form-input" rows="6"></textarea></div>
                <div class="form-group">
                    <label for="video_youtube_id">ID del Video de YouTube</label>
                    <input type="text" id="video_youtube_id" name="video_youtube_id" class="form-input" placeholder="Ej: QdBZY2fkU-0">
                    <small class="form-help-text">Copia solo el código de 11 caracteres de la URL. Ej: <code>youtube.com/watch?v=<strong>QdBZY2fkU-0</strong></code></small>
                </div>
                
                <div class="form-group">
                    <label>Imágenes de Galería</label>
                    <div class="gallery-management">
                        <button type="button" id="add_gallery_btn" class="file-upload-button">Seleccionar archivos para añadir...</button>
                        <input type="file" id="gallery_file_input" name="imagenes_galeria[]" multiple style="display: none;">
                        <small class="form-help-text">Puedes hacer clic en el botón varias veces para añadir más imágenes a la selección.</small>
                        <div id="gallery_preview_container" class="gallery-preview"></div>
                    </div>
                </div>

                <div class="form-group"><label for="caracteristicas">Características Principales (una por línea)</label><textarea id="caracteristicas" name="caracteristicas" class="form-input" rows="5" placeholder="Mundo abierto épico&#10;Modo online masivo"></textarea></div>
                <div class="form-row"><div class="form-group"><label for="req_minimos">Requisitos Mínimos (una por línea)</label><textarea id="req_minimos" name="req_minimos" class="form-input" rows="6" placeholder="SO: Windows 10&#10;Procesador: Intel Core i5"></textarea></div><div class="form-group"><label for="req_recomendados">Requisitos Recomendados (una por línea)</label><textarea id="req_recomendados" name="req_recomendados" class="form-input" rows="6" placeholder="SO: Windows 11&#10;Procesador: Intel Core i7"></textarea></div></div>
                <div class="form-row"><div class="form-group"><label for="desarrollador">Desarrollador</label><input type="text" id="desarrollador" name="desarrollador" class="form-input"></div><div class="form-group"><label for="editor">Editor</label><input type="text" id="editor" name="editor" class="form-input"></div></div>
                <div class="form-group"><label for="fecha_lanzamiento">Fecha de Lanzamiento</label><input type="text" id="fecha_lanzamiento" name="fecha_lanzamiento" class="form-input" placeholder="Ej: 14 ABR 2015"></div>
                
                <div class="form-group">
                    <label>Asignar a Secciones</label>
                    <div class="checkbox-group">
                        <?php
                        $sql_secciones = "SELECT id, nombre_visible FROM secciones ORDER BY nombre_visible ASC";
                        $result_secciones = $conn->query($sql_secciones);
                        if ($result_secciones && $result_secciones->num_rows > 0) {
                            while($seccion = $result_secciones->fetch_assoc()) {
                                $seccion_id = htmlspecialchars($seccion['id']);
                                $nombre_seccion = htmlspecialchars($seccion['nombre_visible']);
                                echo "<div class='checkbox-item'><input type='checkbox' name='secciones[]' value='{$seccion_id}' id='seccion_add_{$seccion_id}'><label for='seccion_add_{$seccion_id}'>{$nombre_seccion}</label></div>";
                            }
                        }
                        ?>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Guardar Nuevo Juego</button>
            </form>
        </div>

        <div class="manage-section">
            <h2>Modificar Juegos Existentes</h2>
            <table class="games-table">
                <thead><tr><th>ID</th><th>Título</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php
                    $sql_juegos = "SELECT id, titulo FROM juegos ORDER BY id DESC";
                    $result_juegos = $conn->query($sql_juegos);
                    if ($result_juegos && $result_juegos->num_rows > 0) {
                        while($juego = $result_juegos->fetch_assoc()) {
                            echo "<tr><td>" . $juego['id'] . "</td><td>" . htmlspecialchars($juego['titulo']) . "</td>";
                            echo '<td class="actions-cell"><a href="edit_game.php?id=' . $juego['id'] . '" class="action-btn edit-btn">Modificar</a><a href="delete_game.php?id=' . $juego['id'] . '" class="action-btn delete-btn" onclick="return confirm(\'¿Estás SEGURO de que quieres eliminar este juego? Esta acción no se puede deshacer y borrará todas sus imágenes.\');">Eliminar</a></td></tr>';
                        }
                    } else { echo '<tr><td colspan="3">No hay juegos en la base de datos.</td></tr>'; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica para el checkbox "Free to Play"
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
                    precioInput.value = '';
                    precioInput.required = true;
                }
            });

            // Lógica para la galería acumulativa en "Añadir Juego"
            const addButton = document.getElementById('add_gallery_btn');
            const fileInput = document.getElementById('gallery_file_input');
            const previewContainer = document.getElementById('gallery_preview_container');
            let fileStore = new DataTransfer();

            addButton.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', (event) => {
                for (let i = 0; i < event.target.files.length; i++) {
                    fileStore.items.add(event.target.files[i]);
                }
                fileInput.files = fileStore.files;
                updatePreview();
            });

            function updatePreview() {
                previewContainer.innerHTML = '';
                Array.from(fileStore.files).forEach((file, index) => {
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
                        const removeButton = document.createElement('button');
                        removeButton.type = 'button';
                        removeButton.textContent = 'Quitar';
                        removeButton.style.cssText = "font-size:0.7rem; padding:2px 5px; margin-top: 4px; background-color:#EF4444; color:white; border:none; border-radius:4px; cursor:pointer;";
                        removeButton.onclick = function() {
                            fileStore.items.remove(index);
                            fileInput.files = fileStore.files;
                            updatePreview();
                        };
                        previewItem.appendChild(img);
                        previewItem.appendChild(nameInput);
                        previewItem.appendChild(removeButton);
                        previewContainer.appendChild(previewItem);
                    }
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>