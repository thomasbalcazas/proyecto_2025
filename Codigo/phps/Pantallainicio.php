<?php
// Iniciar la sesión
session_start();

// 1. Verificar si el usuario tiene una sesión activa
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: index.html');
    exit();
}

// 2. Incluir el archivo de conexión a la base de datos
require_once 'db.php';

// 3. Prevenir caché del navegador
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/**
 * Función que genera el bloque HTML para una tarjeta de juego.
 * @param array $juego Un array asociativo con los datos del juego desde la BD.
 */
function generarJuegoHTML($juego) {
    $id = htmlspecialchars($juego['id']);
    $titulo = htmlspecialchars($juego['titulo']);
    $imagen_url = htmlspecialchars($juego['imagen_url']);
    $pagina_url = !empty($juego['pagina_url']) ? htmlspecialchars($juego['pagina_url']) : '#';
    $descripcion_extra = !empty($juego['descripcion_extra']) ? htmlspecialchars($juego['descripcion_extra']) : '';
    $precio_final = (float)$juego['precio_final'];
    $descuento = (int)$juego['descuento_porcentaje'];

    $html_precio = '';
    if ($precio_final == 0 && $descuento == 0) {
        $html_precio = '<p class="precio">Free to Play</p>';
    } else if ($descuento > 0) {
        $precio_original = $precio_final / (1 - ($descuento / 100));
        $html_precio = 
            '<span class="descuento">-' . $descuento . '%</span>' .
            '<div class="precio-bloque-descuento">' .
                '<span class="precio-original">$' . number_format($precio_original, 2) . '</span>' .
                '<p class="precio">$' . number_format($precio_final, 2) . ' USD</p>' .
            '</div>';
    } else {
        $html_precio = '<p class="precio">$' . number_format($precio_final, 2) . ' USD</p>';
    }

    echo '
    <div class="juego" data-juego-id="' . $id . '">
      <a href="' . $pagina_url . '">
        <img src="' . $imagen_url . '" alt="' . $titulo . '">
        <div class="info-content">
          <p class="titulo">' . $titulo . '</p>';
    
    if (!empty($descripcion_extra)) {
        echo '<p class="extra">' . $descripcion_extra . '</p>';
    }
    
    echo '
        </div>
        <div class="precio-container">' . $html_precio . '</div>
      </a>
    </div>';
}

// --- CONSULTAS PARA CADA SECCIÓN ---
// "TU TIENDA" -> RECOMENDADO PARA TI (Placeholder: juegos al azar)
$sql_recomendados = "SELECT * FROM juegos ORDER BY RAND() LIMIT 10";
$result_recomendados = $conn->query($sql_recomendados);

// "NOVEDADES" (Los últimos añadidos a la BD)
$sql_novedades = "SELECT * FROM juegos ORDER BY id DESC LIMIT 16";
$result_novedades = $conn->query($sql_novedades);

// "OFERTAS" (Juegos con descuento > 0)
$sql_ofertas = "SELECT * FROM juegos WHERE descuento_porcentaje > 0 ORDER BY id DESC LIMIT 10";
$result_ofertas = $conn->query($sql_ofertas);

// "PRÓXIMAMENTE" (Juegos asignados a la sección 'proximamente')
$sql_proximamente = "SELECT j.* FROM juegos j JOIN juego_secciones js ON j.id = js.juego_id JOIN secciones s ON js.seccion_id = s.id WHERE s.slug = 'proximamente' ORDER BY j.id ASC LIMIT 10";
$result_proximamente = $conn->query($sql_proximamente);

// "TÍTULOS POPULARES" (Juegos con más clics)
$sql_populares = "SELECT * FROM juegos WHERE click_count > 0 ORDER BY click_count DESC LIMIT 10";
$result_populares = $conn->query($sql_populares);

$sql_f2p = "SELECT * FROM juegos WHERE precio_final = 0 AND descuento_porcentaje = 0 ORDER BY id DESC LIMIT 10";
$result_f2p = $conn->query($sql_f2p);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Allerta+Stencil&display=swap" rel="stylesheet">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NJOY - Tu tienda de videojuegos</title>
<style>
/* Control de Animaciones */
body:not(.animations-active) .banner h1,
body:not(.animations-active) .banner p {
    opacity: 0; /* Mantenlos ocultos */
}
body.animations-active .banner h1 {
    /* Define la animación aquí, pero sin los retrasos (delay) iniciales si los tenías */
    animation: fadeInUp 1s cubic-bezier(0.2, 0.8, 0.2, 1) 0.5s both,
               premiumFloat 8s ease-in-out 1.5s infinite alternate; /* Añadimos delay a la segunda animación */
}
body.animations-active .banner p {
    animation: fadeInUp 1s ease-out 1s both;
}
/* === BASE Y FONDO === */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
    /* Establece una base de tamaño de fuente fluida */
    font-size: clamp(14px, 1.5vw, 16px);
}

body {
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  background: linear-gradient(135deg, #8B5CF6 0%, #3B0764 25%, #1E1B4B 50%, #312E81 75%, #6366F1 100%);
  background-size: 400% 400%;
  animation: gradientShift 20s ease infinite;
  color: white;
  min-height: 100vh;
  position: relative;
  overflow-x: hidden;
  transition: background 0.5s ease, color 0.5s ease;
}

@keyframes gradientShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.floating-shapes { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; overflow: hidden; }
.shape { position: absolute; background: rgba(139, 92, 246, 0.1); border-radius: 50%; animation: float 25s linear infinite; backdrop-filter: blur(1px); }
.shape:nth-child(1) { width: 100px; height: 100px; left: 10%; animation-duration: 30s; }
.shape:nth-child(2) { width: 150px; height: 150px; left: 30%; animation-duration: 25s; }
.shape:nth-child(3) { width: 80px; height: 80px; left: 60%; animation-duration: 35s; }
.shape:nth-child(4) { width: 120px; height: 120px; left: 80%; animation-duration: 28s; }

@keyframes float {
  0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { transform: translateY(-200px) rotate(360deg); opacity: 0; }
}

/* === HEADER Y NAV === */
header, nav {
  background: rgba(30, 30, 47, 0.85);
  backdrop-filter: blur(15px);
  position: fixed;
  left: 0;
  right: 0;
  border-bottom: 2px solid rgba(139, 92, 246, 0.2);
  z-index: 101;
  transition: all 0.3s ease;
}

header { padding: 15px 5%; display: flex; align-items: center; justify-content: space-between; top: 0; height: 86px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
nav { display: flex; align-items: center; justify-content: center; gap: 40px; padding: 15px 5%; top: 86px; height: 60px; z-index: 100; }
main { padding-top: 86px; }
.banner { padding-top: 60px; }
.header-left { display: flex; align-items: center; gap: 80px; }
.logo img { height: 45px; transition: transform 0.3s ease; filter: drop-shadow(0 4px 8px rgba(139, 92, 246, 0.4)); cursor: pointer; }
.logo:hover img { transform: scale(1.05) rotate(5deg); }
.logo:active { transform: scale(0.90); }
.menu { display: flex; gap: 30px; }
.menu a { text-decoration: none; color: white; font-size: 14px; font-weight: 600; padding: 8px 16px; border-radius: 20px; transition: all 0.3s ease; }
.menu a:hover { background: rgba(139, 92, 246, 0.15); }

nav a { text-decoration: none; color: #E5E7EB; font-weight: 600; font-size: 1rem; padding: 10px 5px; position: relative; transition: color 0.3s ease; background: none; border: none; cursor: pointer; }
nav a:hover { color: #FFFFFF; }
nav a::after { content: ''; position: absolute; width: 0%; height: 3px; border-radius: 2px; background: linear-gradient(90deg, #8B5CF6, #6366F1); bottom: -5px; left: 50%; transform: translateX(-50%); transition: width 0.4s cubic-bezier(0.25, 1, 0.5, 1); }
nav a:hover::after { width: 100%; }

/* === BANNER === */
.banner { width: 100%; height: 80vh; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)), url("njoyimages/fondo.jpeg") no-repeat center center/cover; display: flex; align-items: center; justify-content: center; text-align: center; padding-top: 146px; }
.banner-content { z-index: 2; }

@keyframes fadeInUp { from { opacity: 0; transform: translate3d(0, 40px, 0); filter: blur(5px); } to { opacity: 1; transform: translate3d(0, 0, 0); filter: blur(0); } }
@keyframes premiumFloat { from { transform: translateY(0px) scale(1); } to { transform: translateY(-12px) scale(1.01); } }

.banner h1 { 
    position: relative; 
    font-family: "Allerta Stencil", sans-serif; 
    font-weight: 400; 
    font-style: normal; 
    /* Usa clamp() para un tamaño de fuente fluido */
    font-size: clamp(2.5rem, 8vw, 5.5rem); 
    margin-bottom: 1rem; 
    animation: fadeInUp 1s cubic-bezier(0.2, 0.8, 0.2, 1) 1.8s both, premiumFloat 8s ease-in-out infinite alternate; 
    will-change: transform; 
    background: linear-gradient(45deg, #FFFFFF, #FFFFFF); 
    background-clip: text; 
    -webkit-background-clip: text; 
    -webkit-text-fill-color: #ffffff50; 
    text-shadow: 0 0 1px #fff, 0 0 15px #000, 0 0 70px #ed00fff0, 0 0 100px #9000fd, 0 0 150px #b700ff, 0 0 150px #210b63; 
    backface-visibility: hidden; 
    transform-style: preserve-3d; 
}
.banner p { 
    /* Usa clamp() para un tamaño de fuente fluido */
    font-size: clamp(0.9rem, 2.5vw, 1.2rem); 
    max-width: 650px; 
    margin: 0 auto; 
    animation: fadeInUp 1s ease-out 2.4s both; 
    color: #FFFFFF; 
    text-shadow: 0 0 2.5px #ffffff, 0 0 10px #00000087, 0 0 45px #ed00fff0, 0 0 70px #9000fd36, 0 0 75px #210b63; 
}

/* === SECCIONES Y TÍTULOS === */
main { max-width: 1400px; margin: 0 auto; padding: 0 5%; }
.section { padding: 30px 0; position: relative; z-index: 2; text-align: left; }
.section-title { 
    /* Usa clamp() para un tamaño de fuente fluido */
    font-size: clamp(1.5rem, 4vw, 2.2rem); 
    font-weight: 700; margin-bottom: 2.5rem; position: relative; padding-bottom: 10px; display: block; max-width: 1400px; margin-left: auto; margin-right: auto; padding-left: 5%; padding-right: 5%; 
}
.section-title::after { content: ''; position: absolute; bottom: 0; left: 5%; width: calc(100% - 10%); height: 4px; background: linear-gradient(90deg, #8B5CF6, #6366F1); border-radius: 2px; }

/* === CATEGORÍAS === */
.categorias-grid { display: flex; gap: 20px; margin: 70px 0; padding: 5px 5%; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
.categorias-grid::-webkit-scrollbar { display: none; }
.categoria { flex: 1; min-width: 150px; flex-shrink: 0; background: rgba(30, 30, 47, 0.7); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 1rem; text-align: center; cursor: pointer; transition: transform 0.3s ease, background 0.3s ease, box-shadow 0.3s ease; overflow: hidden; scroll-snap-align: start; }
.categoria button { background: none; border: none; color: white; font-weight: 700; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; width: 100%; height: 100%; padding: 1.2rem 1rem; transition: color 0.3s ease; white-space: nowrap; }
.categoria:hover { transform: translateY(-8px); background: rgba(139, 92, 246, 0.2); box-shadow: 0 10px 20px rgba(0,0,0,0.4); }

/* === CARRUSELES Y JUEGOS === */
.carrusel-wrapper { 
    position: relative; 
}
.carrusel-automatico { overflow: hidden; }
.carrusel-automatico .destacados-grid { display: flex; gap: 25px; padding-bottom: 1.5rem; overflow-x: visible; animation: scrollC 80s linear infinite; width: max-content; touch-action: none; margin-top: 15px; }
.carrusel-automatico .destacados-grid:hover { animation-play-state: paused; }

@keyframes scrollC { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

.carrusel-container { 
    /* AÑADIMOS PADDING para empujar las tarjetas hacia adentro */
    padding: 0 65px;
    overflow-x: auto; 
    overflow-y: hidden; 
    scroll-snap-type: x mandatory; 
    -webkit-overflow-scrolling: touch; 
    scroll-behavior: smooth; 
    scrollbar-width: none; 
    cursor: grab; 
    /* Esto evita que las tarjetas se vean en el área del padding al hacer scroll */
    clip-path: inset(0); 
}
.carrusel-container:active { cursor: grabbing; }
.carrusel-container::-webkit-scrollbar { display: none; }
.carrusel-container .destacados-grid { display: flex; gap: 25px; padding-bottom: 1.5rem; margin-top: 15px; width: max-content; touch-action: pan-x; }

.juego { 
    width: clamp(220px, 20vw, 280px); 
    flex-shrink: 0; 
    background: #1E1E2F; 
    border-radius: 1rem; 
    overflow: hidden; 
    cursor: pointer; 
    transition: all 0.4s ease; 
    border: 1px solid rgba(139, 92, 246, 0.2); 
    display: flex; 
    flex-direction: column; 
    scroll-snap-align: start; 
}
.juego > a { text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%; }
.juego:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 15px rgba(139, 92, 246, 0.4); border-color: #8B5CF6; }
.juego img { width: 100%; height: auto; aspect-ratio: 16 / 9; object-fit: cover; display: block; }

.info-content { padding: 1rem; flex-grow: 1; }
.info-content .titulo { font-size: 1.1rem; font-weight: 600; margin-bottom: 8px; line-height: 1.4; }
.info-content .extra { font-size: 0.8rem; margin-top: 6px; opacity: 0.7; }

/* === PRECIOS === */
.precio-container { padding: 0.75rem 1rem; margin-top: auto; background: rgba(18, 18, 31, 0.9); display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; flex-wrap: wrap; transition: background 0.5s ease; }
.precio-bloque-descuento { display: flex; flex-direction: column; align-items: flex-end; }
.precio-original { font-size: 0.8rem; color: #9CA3AF; text-decoration: line-through; opacity: 0.8; }
.precio { font-weight: 700; font-size: 1rem; }
.descuento { background: rgba(239, 68, 68, 0.8); padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: white; align-self: center; }

.carrusel-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(30, 30, 47, 0.8); backdrop-filter: blur(5px); border: 1px solid rgba(139, 92, 246, 0.3); color: white; font-size: 20px; border-radius: 50%; cursor: pointer; transition: all 0.4s ease; z-index: 30; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; }
.carrusel-btn:hover { background: #8B5CF6; transform: translateY(-50%) scale(1.1); box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4); }
.carrusel-btn.prev { 
    /* Posicionamos el botón 10px desde el borde izquierdo */
    left: 10px; 
}
.carrusel-btn.next { 
    /* Posicionamos el botón 10px desde el borde derecho */
    right: 10px; 
}

/* === USUARIO === */
.usuario { cursor: pointer; display: flex; align-items: center; gap: 12px; position: relative; z-index: 2; }
.nombre-usuario { font-weight: 600; font-size: 1rem; transition: color 0.3s ease; }
.usuario:hover .nombre-usuario { color: #e0e0e0; }
.usuario img { width: 50px; height: 50px; border-radius: 50%; border: 2.5px solid rgba(139, 92, 246, 0.8); object-fit: cover; transition: all 0.3s ease; box-shadow: 0 0 15px rgba(139, 92, 246, 0.75); }
.usuario:hover img { transform: scale(1.1); border-color: #8B5CF6; box-shadow: 0 0 25px rgba(139, 92, 246, 1); }
.usuario-menu { display: none; position: absolute; top: 65px; right: 0; background: rgba(30, 30, 47, 0.95); backdrop-filter: blur(20px); border-radius: 15px; overflow: hidden; min-width: 220px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); border: 1px solid rgba(139, 92, 246, 0.3); z-index: 1000; animation: menuSlideDown 0.3s ease-out; transition: background 0.5s ease; }
@keyframes menuSlideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.usuario-menu a { display: flex; align-items: center; gap: 12px; padding: 15px; text-decoration: none; color: white; font-weight: 500; font-size: 14px; transition: all 0.3s ease; }
.usuario-menu a:hover { background: rgba(139, 92, 246, 0.2); }

/* === FOOTER, MODALES, ETC. === */
.explorar-botones { display: flex; justify-content: flex-start; gap: 20px; flex-wrap: wrap; }
.explorar-botones a { text-decoration: none; background: rgba(139, 92, 246, 0.15); padding: 15px 30px; border-radius: 25px; color: white; font-weight: 600; border: 1px solid rgba(139, 92, 246, 0.3); transition: all 0.4s ease; }
.explorar-botones a:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(139, 92, 246, 0.4); border-color: #8B5CF6; background-color: rgba(139, 92, 246, 0.3); }
footer { background: rgba(30, 30, 47, 0.9); backdrop-filter: blur(20px); padding: 40px 20px; text-align: center; color: #E5E7EB; font-size: 14px; border-top: 1px solid rgba(139, 92, 246, 0.2); position: relative; z-index: 2; margin-top: 2rem; transition: background 0.5s ease, color 0.5s ease; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(10px); justify-content: center; align-items: center; z-index: 2000; }
.modal-content { background: rgba(30, 30, 47, 0.95); backdrop-filter: blur(20px); padding: 30px; border-radius: 20px; max-width: 450px; width: 90%; color: white; text-align: center; position: relative; border: 1px solid rgba(139, 92, 246, 0.3); animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); transition: background 0.5s ease, color 0.5s ease; }
@keyframes modalSlideIn { from { transform: translateY(-50px) scale(0.9); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
.modal-content h2 { margin-bottom: 20px; font-size: 1.5rem; background: linear-gradient(45deg, #FFFFFF, #E0E7FF); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color:#ffffff50; }
.modal-content label { display: block; margin: 15px 0 8px; text-align: left; font-weight: 500; }
.modal-content input, .modal-content select { width: 100%; padding: 12px; border: 1px solid rgba(139, 92, 246, 0.3); background: rgba(255, 255, 255, 0.1); border-radius: 10px; margin-bottom: 15px; color: white; transition: all 0.3s ease; }
.modal-content input:focus, .modal-content select:focus { outline: none; border-color: #8B5CF6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2); }
.modal-content button, .modal-content .modal-button { padding: 12px 24px; border: none; border-radius: 25px; cursor: pointer; margin: 10px 5px; font-weight: 600; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: linear-gradient(75deg, #8b5cf691, #210b632b); color: #000000c9; width: auto; display: inline-block; }
.cerrar { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #9CA3AF; transition: all 0.3s ease; }
.cerrar:hover { color: #fff; }
.foto-perfil-config-container { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
#configFotoPreview { width: 70px; height: 70px; border-radius: 50%; border: 4px solid rgba(139, 92, 246, 0.5); object-fit: cover; }
.boton-subir-foto { background: rgba(255, 255, 255, 0.1); padding: 10px 15px; border-radius: 8px; border: 1px solid rgba(139, 92, 246, 0.3); cursor: pointer; transition: all 0.3s ease; font-weight: 500; }
.boton-subir-foto:hover { background: rgba(139, 92, 246, 0.2); border-color: #8B5CF6; }
.password-wrapper { position: relative; }
.password-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; }
.modal-button-secondary { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); }
.modal-button-secondary:hover { background: rgba(139, 92, 246, 0.2); border-color: #8B5CF6; }

/* === THEME SWITCH === */
.theme-switch-wrapper { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.theme-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
.theme-switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: #8B5CF6; }
input:focus + .slider { box-shadow: 0 0 1px #8B5CF6; }
input:checked + .slider:before { transform: translateX(26px); }

/* === MODO CLARO === */
body.light-mode {
    background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 25%, #f5f3ff 50%, #c4b5fd 75%, #a5b4fc 100%);
    color: #111827;
}
body.light-mode .banner p { color: #FFFFFF; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4); }
body.light-mode header,
body.light-mode nav { background: rgba(255, 255, 255, 0.85); border-bottom-color: rgba(0, 0, 0, 0.1); }
body.light-mode .menu a,
body.light-mode nav a,
body.light-mode .nombre-usuario { color: #374151; }
body.light-mode nav a:hover { color: #111827; }
body.light-mode .section-title { color: #111827; }
body.light-mode .categoria { background: rgba(255, 255, 255, 0.7); border-color: rgba(0, 0, 0, 0.1); }
body.light-mode .categoria button { color: #374151; }
body.light-mode .categoria:hover { background: rgba(233, 213, 255, 0.8); }
body.light-mode .juego { background: #ffffff; border-color: rgba(0, 0, 0, 0.1); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
body.light-mode .juego:hover { border-color: #a78bfa; }
body.light-mode .precio-container { background: #f3f4f6; }
body.light-mode .usuario-menu { background: rgba(255, 255, 255, 0.95); border-color: rgba(0, 0, 0, 0.1); }
body.light-mode .usuario-menu a { color: #1f2937; }
body.light-mode .usuario-menu a:hover { background: rgba(139, 92, 246, 0.1); }
body.light-mode footer { background: rgba(255, 255, 255, 0.9); color: #4b5563; border-top-color: rgba(0, 0, 0, 0.1); }
body.light-mode .modal-content { background: rgba(255, 255, 255, 0.98); color: #111827; border-color: rgba(0, 0, 0, 0.1); }
body.light-mode .modal-content h2 { background: linear-gradient(45deg, #374151, #111827); background-clip: text; -webkit-background-clip: text; }
body.light-mode .modal-content input,
body.light-mode .modal-content select { background: #f3f4f6; border-color: #d1d5db; color: #111827; }
body.light-mode .modal-content input:focus,
body.light-mode .modal-content select:focus { border-color: #8B5CF6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2); }
body.light-mode .cerrar { color: #6b7280; }
body.light-mode .cerrar:hover { color: #111827; }
body.light-mode .boton-subir-foto { background: #f3f4f6; border-color: #d1d5db; }
body.light-mode .carrusel-btn { background: rgba(255, 255, 255, 0.8); border-color: #d1d5db; color: #111827; }

/* === RESPONSIVE MODIFICADO === */
/* Se mantienen los botones del carrusel visibles en tablet para mejor UX */
@media (max-width: 1024px) {
  .carrusel-wrapper { padding: 0; } /* Se quita el padding para que no se vea cortado */
}

/* En móvil, se hacen ajustes menores sin romper la estructura */
@media (max-width: 768px) {
    nav {
        /* Permite el scroll horizontal de la barra de navegación si no cabe */
        justify-content: flex-start;
        overflow-x: auto;
        scrollbar-width: none; /* Oculta la barra de scroll */
    }
    nav::-webkit-scrollbar {
        display: none; /* Oculta la barra de scroll en Chrome/Safari */
    }
    .nombre-usuario {
        /* Se mantiene el nombre de usuario visible si no es muy largo */
        display: block;
    }
    .carrusel-btn {
        /* Se ocultan los botones en móvil para no estorbar el touch */
        display: none;
    }
}

/* Se elimina el breakpoint de 480px para mayor consistencia, los estilos fluidos se encargan */

@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
</head>
<body>
    <?php include 'loader.php'; ?>
  <div class="floating-shapes">
    <div class="shape"></div><div class="shape"></div><div class="shape"></div><div class="shape"></div>
  </div>

  <header>
    <div class="header-left">
      <div class="logo"><img src="NJOYSINFONDO.jpeg" alt="njoy" id="logoImg" /></div>
      <div class="menu"></div>
    </div>
    <div class="usuario" id="usuarioBtn">
      <img src="" alt="Perfil" id="foto">
      <span id="nombreUsuario" class="nombre-usuario"></span>
      <div class="usuario-menu" id="usuarioMenu">
        <a href="#configuracion">⚙️ Configuración</a>
        <a href="logout.php">🚪 Cerrar sesión</a>
      </div>
    </div>
  </header>

  <nav>
    <a href="#recomendados-para-ti">Recomendados</a>
    <a href="#ofertas">Ofertas</a>
    <a href="#populares">Populares</a>
    <a href="#free-to-play">Gratis</a> 
    <a href="#novedades">Novedades</a>
</nav>

  <div class="banner">
    <div class="banner-content">
      <h1>NJOY GAMING</h1>
      <p>Descubre, juega y conecta en la mejor plataforma de videojuegos</p>
    </div>
  </div>

  <main>
    <section class="section" id="recomendados-para-ti">
      <h2 class="section-title">Recomendado para Ti</h2>
      <div class="carrusel-wrapper">
        <div class="carrusel-container" id="carrusel-recomendados">
          <div class="destacados-grid">
            <?php
            if ($result_recomendados->num_rows > 0) {
                while($juego = $result_recomendados->fetch_assoc()) { generarJuegoHTML($juego); }
            } else { echo "<p>No hay recomendaciones disponibles en este momento.</p>"; }
            ?>
          </div>
        </div>
        <button class="carrusel-btn prev" onclick="moverCarrusel('carrusel-recomendados', -1)">←</button>
        <button class="carrusel-btn next" onclick="moverCarrusel('carrusel-recomendados', 1)">→</button>
      </div>
    </section>

    <section class="section" id="ofertas">
        <h2 class="section-title">Ofertas Especiales</h2>
        <div class="carrusel-wrapper">
            <div class="carrusel-container" id="carrusel-ofertas">
                <div class="destacados-grid">
                    <?php
                    if ($result_ofertas->num_rows > 0) {
                        while($juego = $result_ofertas->fetch_assoc()) { generarJuegoHTML($juego); }
                    } else { echo "<p>No hay ofertas disponibles en este momento.</p>"; }
                    ?>
                </div>
            </div>
            <button class="carrusel-btn prev" onclick="moverCarrusel('carrusel-ofertas', -1)">←</button>
            <button class="carrusel-btn next" onclick="moverCarrusel('carrusel-ofertas', 1)">→</button>
        </div>
    </section>

    <section class="section" id="populares">
        <h2 class="section-title">Títulos Populares</h2>
        <div class="carrusel-wrapper">
            <div class="carrusel-container" id="carrusel-populares">
                <div class="destacados-grid">
                    <?php
                    if ($result_populares->num_rows > 0) {
                        while($juego = $result_populares->fetch_assoc()) { generarJuegoHTML($juego); }
                    } else { echo "<p>Aún no hay títulos populares. ¡Sé el primero en descubrir uno!</p>"; }
                    ?>
                </div>
            </div>
            <button class="carrusel-btn prev" onclick="moverCarrusel('carrusel-populares', -1)">←</button>
            <button class="carrusel-btn next" onclick="moverCarrusel('carrusel-populares', 1)">→</button>
        </div>
    </section>

    <section class="section" id="novedades">
      <h2 class="section-title">Novedades</h2>
      <div class="carrusel-automatico">
        <div class="destacados-grid">
          <?php
            if ($result_novedades->num_rows > 0) {
                $novedades_juegos = $result_novedades->fetch_all(MYSQLI_ASSOC);
                $juegos_duplicados = array_merge($novedades_juegos, $novedades_juegos);
                foreach ($juegos_duplicados as $juego) { generarJuegoHTML($juego); }
            } else { echo "<p>No hay novedades disponibles.</p>"; }
          ?>
        </div>
      </div>
    </section>
	<section class="section" id="free-to-play">
        <h2 class="section-title">Juega Gratis</h2>
        <div class="carrusel-wrapper">
            <div class="carrusel-container" id="carrusel-f2p">
                <div class="destacados-grid">
                    <?php
                    if ($result_f2p->num_rows > 0) {
                        while($juego = $result_f2p->fetch_assoc()) {
                            generarJuegoHTML($juego); // Usamos la misma función mágica
                        }
                    } else {
                        echo "<p>No hay juegos gratuitos disponibles en este momento.</p>";
                    }
                    ?>
                </div>
            </div>
            <button class="carrusel-btn prev" onclick="moverCarrusel('carrusel-f2p', -1)">←</button>
            <button class="carrusel-btn next" onclick="moverCarrusel('carrusel-f2p', 1)">→</button>
        </div>
    </section>
    <section class="section" id="proximamente">
      <h2 class="section-title">Próximamente</h2>
      <div class="carrusel-wrapper">
        <div class="carrusel-container" id="carrusel-proximamente">
          <div class="destacados-grid">
            <?php
            if ($result_proximamente->num_rows > 0) {
                while($juego = $result_proximamente->fetch_assoc()) { generarJuegoHTML($juego); }
            } else { echo "<p>No hay anuncios de próximos juegos.</p>"; }
            ?>
          </div>
        </div>
        <button class="carrusel-btn prev" onclick="moverCarrusel('carrusel-proximamente', -1)">←</button>
        <button class="carrusel-btn next" onclick="moverCarrusel('carrusel-proximamente', 1)">→</button>
      </div>
    </section>

    <section class="section">
      <h2 class="section-title">Explorar NJOY</h2>
      <div class="explorar-botones">
        <a href="#">Novedades</a>
        <a href="#">Ofertas</a>
        <a href="#">Juegos gratuitos</a>
        <a href="#">Por etiquetas de usuario</a>
      </div>
    </section>
  </main>

  <footer>
    <p id="footer-text">© 2025 NJOY - Tu tienda de videojuegos. Todos los derechos reservados.</p>
  </footer>

  <!-- MODALES -->
  <div class="modal" id="modalLogout">
    <div class="modal-content">
      <span class="cerrar" data-close="modalLogout">&times;</span>
      <h2>🚪 Cerrar sesión</h2>
      <p>¿Estás seguro de que deseas cerrar sesión?</p>
      <button id="confirmarLogout" style="background: #ef4444;">Sí, cerrar sesión</button>
      <button id="cancelarLogout" class="modal-button-secondary">Cancelar</button>
    </div>
  </div>

  <div class="modal" id="modalConfig">
    <div class="modal-content">
      <span class="cerrar" data-close="modalConfig">&times;</span>
      <h2>⚙️ Configuración</h2>
      <label for="configNombreInput">Nombre de usuario:</label>
      <input type="text" id="configNombreInput" placeholder="Tu nombre de usuario">
      <label>Foto de perfil:</label>
      <div class="foto-perfil-config-container">
        <img src="njoyimages/user.jpeg" alt="Vista previa" id="configFotoPreview">
        <input type="file" id="configInputFoto" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">
        <label for="configInputFoto" class="boton-subir-foto">Elegir archivo</label>
      </div>
      <label>Idioma:</label>
      <select id="idiomaConfig"><option value="es">Español</option><option value="en">Inglés</option></select>
      <label>Modo de visualización:</label>
      <div class="theme-switch-wrapper">
        <label class="theme-switch" for="theme-toggle"><input type="checkbox" id="theme-toggle" /><span class="slider"></span></label>
        <span id="theme-label-text">Modo Oscuro</span>
      </div>
      <label>Contraseña:</label>
      <button id="abrirModalPassBtn" class="modal-button modal-button-secondary" style="width: 100%; margin-bottom: 20px;">Cambiar contraseña</button>
      <button id="guardarConfig">Guardar cambios</button>
    </div>
  </div>

  <div class="modal" id="modalCambiarPass">
    <div class="modal-content">
      <span class="cerrar" data-close="modalCambiarPass">&times;</span>
      <h2>🔑 Cambiar Contraseña</h2>
      <label for="antiguaPass">Antigua contraseña:</label>
      <div class="password-wrapper"><input type="password" id="antiguaPass" placeholder="Tu contraseña actual" required><span class="password-toggle">👁️</span></div>
      <label for="nuevaPass">Nueva contraseña:</label>
      <div class="password-wrapper"><input type="password" id="nuevaPass" placeholder="Mínimo 8 caracteres" required><span class="password-toggle">👁️</span></div>
      <label for="repetirPass">Repetir nueva contraseña:</label>
      <div class="password-wrapper"><input type="password" id="repetirPass" placeholder="Confirma la nueva contraseña" required><span class="password-toggle">👁️</span></div>
      <small id="mensajeErrorPass" style="color:#ef4444; display:none; text-align: left; margin-top: -10px; margin-bottom: 10px;"></small>
      <button id="guardarNuevaPass">Confirmar cambio</button>
      <button class="modal-button-secondary" data-close="modalCambiarPass">Cancelar</button>
    </div>
  </div>
<script>
    // === VARIABLES GLOBALES ===
    const fotoPerfilHeader = document.querySelector("#usuarioBtn img");
    const usuarioBtn = document.getElementById("usuarioBtn");
    const usuarioMenu = document.getElementById("usuarioMenu");
    const logoImg = document.getElementById("logoImg");
    
    // === MANEJO DE MENÚ DE USUARIO ===
    usuarioBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      usuarioMenu.style.display = usuarioMenu.style.display === "block" ? "none" : "block";
    });

    document.addEventListener("click", () => {
      if (usuarioMenu.style.display === "block") {
        usuarioMenu.style.display = "none";
      }
    });

    // === ABRIR MODALES ===
    const modales = {
      "#configuracion": "modalConfig"
    };

    document.querySelectorAll(".usuario-menu a").forEach(link => {
      link.addEventListener("click", (e) => {
        const href = link.getAttribute("href");
        if (modales[href]) {
          e.preventDefault();
          const idModal = modales[href];
          const modalElement = document.getElementById(idModal);
          if (idModal === 'modalConfig') {
            document.getElementById('configFotoPreview').src = fotoPerfilHeader.src;
          }
          modalElement.style.display = "flex";
          usuarioMenu.style.display = "none";
        } else if (href === "logout.php") {
          e.preventDefault();
          document.getElementById('modalLogout').style.display = 'flex';
          usuarioMenu.style.display = 'none';
        }
      });
    });

    // === CERRAR MODALES ===
    document.querySelectorAll(".cerrar, [data-close], #cancelarLogout").forEach(el => {
      el.addEventListener("click", () => {
        const modalId = el.getAttribute('data-close') || el.closest('.modal').id;
        document.getElementById(modalId).style.display = "none";
      });
    });

    document.querySelectorAll(".modal").forEach(modal => {
      modal.addEventListener("click", e => {
        if(e.target === modal) {
          modal.style.display = "none";
        }
      });
    });
    
    // === CERRAR SESIÓN (LOGOUT) CON CONFIRMACIÓN ===
document.getElementById("confirmarLogout").addEventListener("click", () => {
    // Deshabilitar el botón para evitar doble clic
    document.getElementById("confirmarLogout").disabled = true;

    // Usar fetch para llamar al script de logout en segundo plano
    fetch('logout.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            // Cuando recibimos la confirmación "ok"...
            if (data.status === 'ok') {
                // ...entonces redirigimos al usuario al inicio de sesión.
                window.location.replace('index.html');
            } else {
                // Si algo saliera mal, informamos y redirigimos de todas formas.
                alert("Hubo un problema al cerrar la sesión. Redirigiendo de todas formas.");
                window.location.replace('index.html');
            }
        })
        .catch(error => {
            // Si hay un error de conexión, también redirigimos.
            console.error('Error al intentar cerrar sesión:', error);
            alert("Error de conexión al cerrar sesión. Redirigiendo...");
            window.location.replace('index.html');
        });
});

    // === MODAL DE CONTRASEÑA ===
    document.getElementById('abrirModalPassBtn').addEventListener('click', () => {
      document.getElementById('modalConfig').style.display = 'none';
      document.getElementById('modalCambiarPass').style.display = 'flex';
    });

    // === TOGGLE PASSWORD VISIBILITY ===
    document.querySelectorAll('.password-toggle').forEach(eye => {
      eye.addEventListener('click', function() {
        const input = this.previousElementSibling;
        input.type = (input.type === 'password') ? 'text' : 'password';
        this.textContent = (input.type === 'password') ? '👁️' : '🙈';
      });
    });

    // === FUNCIÓN MOVER CARRUSEL ===
    function moverCarrusel(idCarrusel, direccion) {
      const container = document.getElementById(idCarrusel);
      if (!container) return;
      const card = container.querySelector('.juego');
      if (!card) return;
      const cardWidth = card.offsetWidth;
      const gap = parseInt(window.getComputedStyle(container.querySelector('.destacados-grid')).gap) || 25;
      const scrollAmount = (cardWidth + gap) * 2;
      container.scrollBy({ left: direccion * scrollAmount, behavior: 'smooth' });
    }

    // === CARGAR DATOS DE USUARIO ===
    let nombreUsuarioActual = '';
    document.addEventListener("DOMContentLoaded", () => {
      fetch('obtener_datos_usuario.php?v=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
          if (data.status === "ok") {
            nombreUsuarioActual = data.nombre;
            fotoPerfilHeader.src = data.foto + "?t=" + new Date().getTime();
            document.getElementById('configFotoPreview').src = data.foto + "?t=" + new Date().getTime();
            document.getElementById("nombreUsuario").textContent = data.nombre;
            document.getElementById('configNombreInput').value = data.nombre;
          } else {
            console.warn("Error cargando usuario:", data.msg);
            window.location.replace('index.html');
          }
        })
        .catch(error => {
          console.error("Error en fetch:", error);
          window.location.replace('index.html');
        });
    });
    
    // === PREVISUALIZAR FOTO DE PERFIL AL SELECCIONARLA ===
    const configInputFoto = document.getElementById('configInputFoto');
    const configFotoPreview = document.getElementById('configFotoPreview');

    configInputFoto.addEventListener('change', () => {
      const file = configInputFoto.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          configFotoPreview.src = e.target.result;
        }
        reader.readAsDataURL(file);
      }
    });

    // === LÓGICA COMPLETA PARA GUARDAR CONFIGURACIÓN ===
    document.getElementById('guardarConfig').addEventListener('click', () => {
      const fotoFile = configInputFoto.files[0];
      const nuevoNombre = document.getElementById('configNombreInput').value.trim();
      let promesas = [];
      
      if (nuevoNombre && nuevoNombre !== nombreUsuarioActual) {
        const formDataNombre = new FormData();
        formDataNombre.append('nombre', nuevoNombre);
        const promesaNombre = fetch('actualizar_nombre.php', { method: 'POST', body: formDataNombre })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'ok') {
              document.getElementById('nombreUsuario').textContent = data.nuevoNombre;
              nombreUsuarioActual = data.nuevoNombre;
              return 'Nombre actualizado.';
            } else { throw new Error(data.msg || 'Error al actualizar el nombre.'); }
          });
        promesas.push(promesaNombre);
      }

      if (fotoFile) {
        const formDataFoto = new FormData();
        formDataFoto.append('foto', fotoFile);
        const promesaFoto = fetch('actualizar_foto_perfil.php', { method: 'POST', body: formDataFoto })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'ok') {
              const cacheBuster = '?t=' + new Date().getTime();
              fotoPerfilHeader.src = data.ruta + cacheBuster;
              return 'Foto actualizada.';
            } else { throw new Error(data.msg || 'Error al subir la foto.'); }
          });
        promesas.push(promesaFoto);
      }
      
      if (promesas.length > 0) {
        Promise.all(promesas)
          .then(mensajes => {
            alert('Configuración guardada: ' + mensajes.join(' '));
            document.getElementById('modalConfig').style.display = 'none';
          })
          .catch(error => { alert('Hubo un error: ' + error.message); });
      } else {
        document.getElementById('modalConfig').style.display = 'none';
      }
    });

    // === CAMBIAR CONTRASEÑA ===
    document.getElementById('guardarNuevaPass').addEventListener('click', () => {
      const antiguaPassInput = document.getElementById('antiguaPass');
      const nuevaPassInput = document.getElementById('nuevaPass');
      const repetirPassInput = document.getElementById('repetirPass');
      const mensajeError = document.getElementById('mensajeErrorPass');
      const antiguaPass = antiguaPassInput.value;
      const nuevaPass = nuevaPassInput.value;
      const repetirPass = repetirPassInput.value;

      if (!antiguaPass || !nuevaPass || !repetirPass) { mensajeError.textContent = 'Todos los campos son obligatorios.'; mensajeError.style.display = 'block'; return; }
      if (nuevaPass !== repetirPass) { mensajeError.textContent = 'Las nuevas contraseñas no coinciden.'; mensajeError.style.display = 'block'; return; }
      if (nuevaPass.length < 8) { mensajeError.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.'; mensajeError.style.display = 'block'; return; }
      mensajeError.style.display = 'none';

      const formData = new FormData();
      formData.append('antiguaPass', antiguaPass);
      formData.append('nuevaPass', nuevaPass);

      fetch('cambiar_password.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'ok') {
            alert(data.msg);
            document.getElementById('modalCambiarPass').style.display = 'none';
            [antiguaPassInput, nuevaPassInput, repetirPassInput].forEach(input => input.value = '');
          } else {
            mensajeError.textContent = data.msg;
            mensajeError.style.display = 'block';
          }
        })
        .catch(error => {
          console.error('Error en fetch:', error);
          mensajeError.textContent = 'Error de conexión. Inténtalo de nuevo.';
          mensajeError.style.display = 'block';
        });
    });

    // === TEMA CLARO/OSCURO ===
    const themeToggle = document.getElementById('theme-toggle');
    const themeLabel = document.getElementById('theme-label-text');

    function setTheme(theme) {
      document.body.classList.toggle('light-mode', theme === 'light');
      themeToggle.checked = (theme === 'light');
      themeLabel.textContent = (theme === 'light') ? 'Modo Claro' : 'Modo Oscuro';
      logoImg.src = (theme === 'light') ? 'NJOY_LOGO_CLARO.jpeg' : 'NJOYSINFONDO.jpeg';
    }

    themeToggle.addEventListener('change', () => {
      const newTheme = themeToggle.checked ? 'light' : 'dark';
      localStorage.setItem('theme', newTheme);
      setTheme(newTheme);
    });

    const savedTheme = localStorage.getItem('theme') || 'dark';
    setTheme(savedTheme);

    // === EFECTO CONFETI (RESTAURADO) ===
    let logoClickCount = 0;
    let isConfettiActive = false;

    function createConfettiStream() {
        if (isConfettiActive) return;
        isConfettiActive = true;
        const gamingEmojis = ['🎮', '🕹️', '👾', '🎯', '🏆', '⚡', '💎', '🌟', '🔥', '💀'];
        const totalDuration = 2000;
        const startTime = Date.now();

        const streamInterval = setInterval(() => {
            for (let i = 0; i < 12; i++) {
                const emoji = document.createElement('div');
                emoji.style.position = 'fixed';
                emoji.style.fontSize = '18px';
                emoji.textContent = gamingEmojis[Math.floor(Math.random() * gamingEmojis.length)];
                emoji.style.left = (Math.random() * window.innerWidth) + 'px';
                emoji.style.bottom = '10px';
                emoji.style.pointerEvents = 'none';
                emoji.style.zIndex = '9999';
                emoji.style.opacity = '1';
                emoji.style.userSelect = 'none';
                document.body.appendChild(emoji);

                const anim = emoji.animate([
                    { transform: 'translateY(0px) rotate(0deg) scale(1)', opacity: 1 },
                    { transform: `translateY(-${70 + Math.random() * 50}vh) rotate(${Math.random() * 720}deg) scale(${0.2 + Math.random() * 0.8})`, opacity: 0 }
                ], {
                    duration: 2500 + Math.random() * 1500,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                });
                anim.onfinish = () => emoji.remove();
            }

            if (Date.now() - startTime >= totalDuration) {
                clearInterval(streamInterval);
                isConfettiActive = false;
                logoClickCount = 0;
            }
        }, 80);
    }

    document.querySelector('.logo').addEventListener('click', (e) => {
        e.preventDefault();
        logoClickCount++;
        if (logoClickCount >= 5) {
            createConfettiStream();
        }
    });

    // === OCULTAR NAVBAR AL HACER SCROLL ===
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
      const nav = document.querySelector('nav');
      const currentScroll = window.scrollY || document.documentElement.scrollTop;
      if (currentScroll > lastScrollTop && currentScroll > 100) {
        nav.style.transform = 'translateY(-100%)';
        nav.style.opacity = '0';
      } else if (currentScroll < lastScrollTop) {
        nav.style.transform = 'translateY(0)';
        nav.style.opacity = '1';
      }
      lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    });

    // === SMOOTH SCROLL PARA NAVEGACIÓN ===
    document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          const offsetTop = target.offsetTop - 86;
          window.scrollTo({ top: offsetTop, behavior: 'smooth' });
        }
      });
    });

    // === DRAG TO SCROLL PARA CARRUSELES ===
    document.addEventListener('DOMContentLoaded', function() {
      const carruseles = document.querySelectorAll('.carrusel-container, .categorias-grid');
      carruseles.forEach(carrusel => {
        let isDown = false;
        let startX, scrollLeft;
        carrusel.addEventListener('mousedown', (e) => {
          isDown = true;
          carrusel.style.cursor = 'grabbing';
          startX = e.pageX - carrusel.offsetLeft;
          scrollLeft = carrusel.scrollLeft;
        });
        carrusel.addEventListener('mouseleave', () => { isDown = false; carrusel.style.cursor = 'grab'; });
        carrusel.addEventListener('mouseup', () => { isDown = false; carrusel.style.cursor = 'grab'; });
        carrusel.addEventListener('mousemove', (e) => {
          if (!isDown) return;
          e.preventDefault();
          const x = e.pageX - carrusel.offsetLeft;
          const walk = (x - startX) * 2;
          carrusel.scrollLeft = scrollLeft - walk;
        });
      });
    });

    // === REGISTRO DE CLICS PARA JUEGOS POPULARES ===
    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('click', function(e) {
            const juegoCard = e.target.closest('.juego');
            if (juegoCard && juegoCard.dataset.juegoId) {
                const juegoId = juegoCard.dataset.juegoId;
                const formData = new FormData();
                formData.append('juego_id', juegoId);
                navigator.sendBeacon('registrar_clic.php', formData);
            }
        });
    });
    window.addEventListener('load', function() {
    const loader = document.getElementById('loader');
    const body = document.body;

    // Ocultar la pantalla de carga
    if (loader) {
        loader.classList.add('hidden');
    }

    // Activar las animaciones en el resto de la página
    body.classList.add('animations-active');
});

// El resto de tu JavaScript específico de la página va aquí
document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA UNIVERSAL (HEADER, MODALES, TEMA) ---
    const usuarioBtn = document.getElementById("usuarioBtn");
    // ... (Y todo el resto de tu JavaScript que ya tenías)
    
});
</script>
</body>
</html>
<?php
// Cerramos la conexión a la base de datos al final del script
if (isset($conn)) {
    $conn->close();
}
?>