<?php
// game.php
session_start();
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: index.html');
    exit();
}
require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { die("Juego no encontrado."); }
$juego_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM juegos WHERE id = ?");
$stmt->bind_param("i", $juego_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) { die("Juego no encontrado."); }
$juego = $result->fetch_assoc();
$stmt->close();
$conn->close();

function getAbsolutePath($path) {
    $path = trim($path);
    if (!empty($path) && strpos($path, '/') !== 0 && strpos($path, 'http') !== 0) {
        return '/' . $path;
    }
    return $path;
}

$tags_array = !empty($juego['tags']) ? explode(',', $juego['tags']) : [];
$galeria_array = !empty($juego['imagenes_galeria']) ? explode('|', $juego['imagenes_galeria']) : [];
$caracteristicas_array = !empty($juego['caracteristicas']) ? explode('|', $juego['caracteristicas']) : [];
$req_min_array = !empty($juego['req_minimos']) ? explode('|', $juego['req_minimos']) : [];
$req_rec_array = !empty($juego['req_recomendados']) ? explode('|', $juego['req_recomendados']) : [];
$main_media_image = $galeria_array[0] ?? $juego['imagen_url'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Allerta+Stencil&display=swap" rel="stylesheet">
  <title>NJOY - <?php echo htmlspecialchars($juego['titulo']); ?></title>
  <style>
/* EN PANTALLAINICIO.PHP, DENTRO DE <STYLE> */

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
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
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

    /* === BURBUJAS FLOTANTES === */
    .floating-shapes { 
      position: fixed; 
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%; 
      pointer-events: none; 
      z-index: 1; 
      overflow: hidden; 
    }
    .shape { 
      position: absolute; 
      background: rgba(139, 92, 246, 0.1); 
      border-radius: 50%; 
      animation: float 25s linear infinite; 
      backdrop-filter: blur(1px); 
    }
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
    
    header, nav { 
      background: rgba(30, 30, 47, 0.85); 
      backdrop-filter: blur(15px); 
      position: fixed; 
      left: 0; 
      right: 0; 
      border-bottom: 2px solid rgba(139, 92, 246, 0.2); 
      z-index: 1001; 
      transition: all 0.3s ease; 
    }
    header { 
      padding: 15px 5%; 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      top: 0; 
      height: 86px; 
      box-shadow: 0 5px 20px rgba(0,0,0,0.2); 
    }
    nav { 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      gap: 40px; 
      padding: 15px 5%; 
      top: 86px; 
      height: 60px; 
      z-index: 1000; 
    }
    .header-left { display: flex; align-items: center; gap: 80px; }
    .logo img { 
      height: 45px; 
      transition: transform 0.3s ease; 
      filter: drop-shadow(0 4px 8px rgba(139, 92, 246, 0.4)); 
      cursor: pointer; 
    }
    .logo:hover img { transform: scale(1.05) rotate(5deg); }
    
    nav a { 
      text-decoration: none; 
      color: #E5E7EB; 
      font-weight: 600; 
      font-size: 1rem; 
      padding: 10px 5px; 
      position: relative; 
      transition: color 0.3s ease; 
    }
    nav a:hover { color: #FFFFFF; }
    nav a::after { 
      content: ''; 
      position: absolute; 
      width: 0%; 
      height: 3px; 
      border-radius: 2px; 
      background: linear-gradient(90deg, #8B5CF6, #6366F1); 
      bottom: -5px; 
      left: 50%; 
      transform: translateX(-50%); 
      transition: width 0.4s cubic-bezier(0.25, 1, 0.5, 1); 
    }
    nav a:hover::after { width: 100%; }
    
    .usuario { 
      cursor: pointer; 
      display: flex; 
      align-items: center; 
      gap: 12px; 
      position: relative; 
      z-index: 2; 
    }
    .nombre-usuario { font-weight: 600; font-size: 1rem; transition: color 0.3s ease; }
    .usuario:hover .nombre-usuario { color: #e0e0e0; }
    .usuario img { 
      width: 50px; 
      height: 50px; 
      border-radius: 50%; 
      border: 2.5px solid rgba(139, 92, 246, 0.8); 
      object-fit: cover; 
      transition: all 0.3s ease; 
      box-shadow: 0 0 15px rgba(139, 92, 246, 0.75); 
    }
    .usuario:hover img { 
      transform: scale(1.1); 
      border-color: #8B5CF6; 
      box-shadow: 0 0 25px rgba(139, 92, 246, 1); 
    }
    
    .usuario-menu { 
      display: none; 
      position: absolute; 
      top: 65px; 
      right: 0; 
      background: rgba(30, 30, 47, 0.95); 
      backdrop-filter: blur(20px); 
      border-radius: 15px; 
      overflow: hidden; 
      min-width: 220px; 
      box-shadow: 0 20px 40px rgba(0,0,0,0.3); 
      border: 1px solid rgba(139, 92, 246, 0.3); 
      z-index: 1000; 
      animation: menuSlideDown 0.3s ease-out; 
      transition: background 0.5s ease; 
    }
    @keyframes menuSlideDown { 
      from { opacity: 0; transform: translateY(-10px); } 
      to { opacity: 1; transform: translateY(0); } 
    }
    .usuario-menu a { 
      display: flex; 
      align-items: center; 
      gap: 12px; 
      padding: 15px; 
      text-decoration: none; 
      color: white; 
      font-weight: 500; 
      font-size: 14px; 
      transition: all 0.3s ease; 
    }
    .usuario-menu a:hover { background: rgba(139, 92, 246, 0.2); }
    
    main { 
      padding-top: 160px; 
      max-width: 1400px; 
      margin: 0 auto; 
      padding-left: 5%; 
      padding-right: 5%; 
      position: relative;
      z-index: 2;
    }
    
    .breadcrumbs { 
      margin-bottom: 20px; 
      font-size: 0.9rem;
      color: #9ca3af; 
    }
    .breadcrumbs a { 
      color: #c4b5fd; 
      text-decoration: none; 
      transition: color 0.3s ease; 
    }
    
    .hero-section { 
      background: linear-gradient(135deg, #1e1b4b 0%, #3b0764 100%); 
      border-radius: 8px; 
      overflow: hidden; 
      margin-bottom: 20px; 
      position: relative; 
      border: 1px solid rgba(139, 92, 246, 0.3); 
      box-shadow: 0 10px 40px rgba(139, 92, 246, 0.2); 
    }
    .hero-background { 
      width: 100%; 
      height: 400px; 
      object-fit: cover; 
      opacity: 0.3; 
    }
    .hero-content { 
      position: absolute; 
      top: 0; 
      left: 0; 
      right: 0; 
      bottom: 0; 
      padding: 40px; 
      display: flex; 
      align-items: flex-end; 
      background: linear-gradient(to bottom, transparent 0%, rgba(26, 11, 46, 0.9) 100%); 
    }
    .hero-info h1 { 
      /* Usa clamp() para un tamaño de fuente fluido */
      font-size: clamp(2rem, 6vw, 3rem); 
      font-weight: 300; 
      margin-bottom: 10px; 
      color: #fff; 
      text-shadow: 0 0 20px rgba(139, 92, 246, 0.6); 
    }
    .hero-tags { 
      display: flex; 
      gap: 8px; 
      flex-wrap: wrap; 
    }
    .hero-tags .tag { 
      background: rgba(139, 92, 246, 0.3); 
      padding: 6px 14px; 
      border-radius: 5px; 
      font-size: 12px; 
      color: #e0d4ff; 
      border: 1px solid rgba(139, 92, 246, 0.5); 
    }
    
    .media-section { margin-bottom: 30px; }
    .main-media { 
      background: #000; 
      border-radius: 8px; 
      overflow: hidden; 
      position: relative; 
      border: 1px solid rgba(139, 92, 246, 0.3); 
      box-shadow: 0 10px 40px rgba(139, 92, 246, 0.2); 
      aspect-ratio: 16 / 9; 
      margin-bottom: 15px; 
    }
    .main-media img, .main-media iframe { 
      width: 100%; 
      height: 100%; 
      object-fit: cover; 
      display: block; 
      border: none; 
      position: absolute; 
      top: 0; 
      left: 0; 
    }
    
    .thumbnail-carousel { position: relative; padding: 0 40px; }
    .thumbnail-strip { 
      display: flex; 
      gap: 10px; 
      overflow-x: auto; 
      scroll-behavior: smooth; 
      -ms-overflow-style: none; 
      scrollbar-width: none; 
    }
    .thumbnail-strip::-webkit-scrollbar { display: none; }
    .thumb { 
      flex-shrink: 0; 
      width: 160px; 
      border-radius: 4px; 
      overflow: hidden; 
      cursor: pointer; 
      border: 2px solid transparent; 
      transition: all 0.3s ease; 
      opacity: 0.6; 
      background: #000; 
      position: relative; 
    }
    .thumb img { 
      width: 100%; 
      display: block; 
      aspect-ratio: 16 / 9; 
      object-fit: cover; 
    }
    .thumb:hover, .thumb.active { 
      border-color: #8B5CF6; 
      opacity: 1; 
    }
    .thumb .play-icon { 
      position: absolute; 
      top: 50%; 
      left: 50%; 
      transform: translate(-50%, -50%); 
      width: 30px; 
      height: 30px; 
      background-color: rgba(0,0,0,0.6); 
      border-radius: 50%; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      color: white; 
      font-size: 14px; 
      pointer-events: none; 
    }
    
    .carousel-arrow { 
      position: absolute; 
      top: 50%; 
      transform: translateY(-50%); 
      background: rgba(30, 30, 47, 0.8); 
      border: 1px solid rgba(139, 92, 246, 0.3); 
      color: white; 
      width: 35px; 
      height: 35px; 
      border-radius: 50%; 
      cursor: pointer; 
      z-index: 10; 
      font-size: 20px; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      transition: all 0.3s ease; 
    }
    .carousel-arrow:hover { background-color: #8B5CF6; }
    .carousel-arrow.prev { left: 0; }
    .carousel-arrow.next { right: 0; }
    
    .content-grid { 
      display: grid; 
      grid-template-columns: 2fr 1fr; 
      gap: 20px; 
      margin-bottom: 40px; 
    }
    
    .description-section { 
      background: linear-gradient(135deg, rgba(30, 27, 75, 0.6) 0%, rgba(59, 7, 100, 0.4) 100%); 
      padding: 30px; 
      border-radius: 8px; 
      border: 1px solid rgba(139, 92, 246, 0.2); 
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3); 
    }
    .description-section h2 { 
      font-size: 1rem; 
      text-transform: uppercase; 
      color: #e0d4ff; 
      margin-bottom: 15px; 
      font-weight: 600; 
      letter-spacing: 2px; 
      border-bottom: 2px solid rgba(139, 92, 246, 0.4); 
      padding-bottom: 10px; 
    }
    .description-section p { 
      line-height: 1.8; 
      margin-bottom: 15px; 
      color: #d1d5db; 
    }
    
    .features-list { list-style: none; padding: 0; }
    .features-list li { 
      padding: 8px 0; 
      padding-left: 25px; 
      position: relative; 
      color: #d1d5db; 
    }
    .features-list li:before { 
      content: "✓"; 
      position: absolute; 
      left: 0; 
      color: #a78bfa; 
      font-weight: bold; 
      font-size: 18px; 
    }
    
    .sidebar-section { 
      display: flex; 
      flex-direction: column; 
      gap: 20px; 
    }
    
    .game-cover-box img { 
      width: 100%; 
      border-radius: 8px; 
      box-shadow: 0 5px 30px rgba(139, 92, 246, 0.3); 
      border: 1px solid rgba(139, 92, 246, 0.3); 
    }
    
    .purchase-box { 
      background: linear-gradient(135deg, rgba(30, 27, 75, 0.8) 0%, rgba(59, 7, 100, 0.6) 100%); 
      padding: 20px; 
      border-radius: 8px; 
      border: 1px solid rgba(139, 92, 246, 0.4); 
      box-shadow: 0 5px 25px rgba(139, 92, 246, 0.2); 
    }
    .purchase-box h3 { 
      font-size: 1.1rem; 
      margin-bottom: 15px; 
      color: #fff; 
      font-weight: 500; 
    }
    
    .price-section { 
      background: rgba(0, 0, 0, 0.4); 
      padding: 15px; 
      border-radius: 5px; 
      margin-bottom: 15px; 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      border: 1px solid rgba(139, 92, 246, 0.2); 
    }
    .discount-badge { 
      background: linear-gradient(135deg, #ec4899, #8B5CF6); 
      color: #fff; 
      padding: 8px 14px; 
      font-size: 1.5rem; 
      font-weight: 700; 
      border-radius: 5px; 
      box-shadow: 0 4px 15px rgba(236, 72, 153, 0.4); 
    }
    .price-values { text-align: right; }
    .original-price { 
      text-decoration: line-through; 
      color: #9ca3af; 
      font-size: 0.9rem;
    }
    .current-price { 
      font-size: 1.6rem; 
      color: #e0d4ff; 
      font-weight: 600; 
      text-shadow: 0 0 10px rgba(139, 92, 246, 0.5); 
    }
    
    .purchase-button { 
      background: linear-gradient(135deg, #8B5CF6, #6366F1); 
      border: none; 
      color: white; 
      padding: 14px; 
      width: 100%; 
      border-radius: 5px; 
      cursor: pointer; 
      font-size: 1rem; 
      font-weight: 600; 
      transition: all 0.3s ease; 
      text-decoration: none; 
      display: block; 
      text-align: center; 
      box-shadow: 0 5px 20px rgba(139, 92, 246, 0.4); 
    }
    .purchase-button:hover { 
      background: linear-gradient(135deg, #a78bfa, #818cf8); 
      transform: translateY(-3px); 
      box-shadow: 0 8px 25px rgba(139, 92, 246, 0.6); 
    }
    
    .game-info { 
      background: rgba(30, 27, 75, 0.5); 
      padding: 20px; 
      border-radius: 8px; 
      font-size: 0.9rem;
      border: 1px solid rgba(139, 92, 246, 0.2); 
    }
    .info-row { 
      display: flex; 
      padding: 8px 0; 
      border-bottom: 1px solid rgba(139, 92, 246, 0.1); 
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { 
      color: #a78bfa; 
      flex: 0 0 120px; 
      font-weight: 600; 
    }
    .info-value { color: #d1d5db; }
    .info-value a { 
      color: #c4b5fd; 
      text-decoration: none; 
      transition: color 0.3s ease; 
    }
    .info-value a:hover { color: #fff; }
    
    .tags-section { 
      background: rgba(30, 27, 75, 0.5); 
      padding: 20px; 
      border-radius: 8px; 
      border: 1px solid rgba(139, 92, 246, 0.2); 
    }
    .tags-section h3 { 
      font-size: 1rem; 
      margin-bottom: 12px; 
      color: #e0d4ff; 
      font-weight: 500; 
    }
    .tags-container { 
      display: flex; 
      flex-wrap: wrap; 
      gap: 8px; 
    }
    .tag-link { 
      background: rgba(139, 92, 246, 0.2); 
      padding: 6px 14px; 
      border-radius: 20px; 
      font-size: 0.8rem; 
      color: #c4b5fd; 
      text-decoration: none; 
      transition: all 0.3s ease; 
      border: 1px solid rgba(139, 92, 246, 0.3); 
    }
    .tag-link:hover { 
      background: rgba(139, 92, 246, 0.4); 
      color: #fff; 
      border-color: #8B5CF6; 
      transform: translateY(-2px); 
    }
    
    .requirements-section { 
      background: linear-gradient(135deg, rgba(30, 27, 75, 0.6) 0%, rgba(59, 7, 100, 0.4) 100%); 
      padding: 30px; 
      border-radius: 8px; 
      margin-top: 20px; 
      border: 1px solid rgba(139, 92, 246, 0.2); 
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3); 
    }
    .requirements-section h2 { 
      font-size: 1rem; 
      text-transform: uppercase; 
      color: #e0d4ff; 
      margin-bottom: 20px; 
      font-weight: 600; 
      letter-spacing: 2px; 
      border-bottom: 2px solid rgba(139, 92, 246, 0.4); 
      padding-bottom: 10px; 
    }
    .requirements-grid { 
      display: grid; 
      /* Se mantiene en 2 columnas por defecto */
      grid-template-columns: 1fr 1fr; 
      gap: 30px; 
    }
    .req-column h3 { 
      font-size: 0.9rem; 
      color: #c4b5fd; 
      margin-bottom: 15px; 
      font-weight: 600; 
    }
    .req-column ul { list-style: none; padding: 0; }
    .req-column li { 
      padding: 5px 0; 
      font-size: 0.85rem; 
      color: #d1d5db; 
    }
    .req-column strong { color: #a78bfa; }
    
    footer { 
      background: rgba(30, 30, 47, 0.9); 
      backdrop-filter: blur(20px); 
      padding: 40px 20px; 
      text-align: center; 
      color: #E5E7EB; 
      font-size: 14px; 
      border-top: 1px solid rgba(139, 92, 246, 0.2); 
      position: relative; 
      z-index: 2; 
      margin-top: 2rem; 
      transition: background 0.5s ease, color 0.5s ease; 
    }

    /* === MODALES (sin cambios) === */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(10px); justify-content: center; align-items: center; z-index: 2000; }
    .modal-content { background: rgba(30, 30, 47, 0.95); backdrop-filter: blur(20px); padding: 30px; border-radius: 20px; max-width: 450px; width: 90%; color: white; text-align: center; position: relative; border: 1px solid rgba(139, 92, 246, 0.3); animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); transition: background 0.5s ease, color 0.5s ease; }
    @keyframes modalSlideIn { from { transform: translateY(-50px) scale(0.9); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
    .modal-content h2 { margin-bottom: 20px; font-size: 1.5rem; background: linear-gradient(45deg, #FFFFFF, #E0E7FF); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: #ffffff50; }
    .modal-content label { display: block; margin: 15px 0 8px; text-align: left; font-weight: 500; }
    .modal-content input, .modal-content select { width: 100%; padding: 12px; border: 1px solid rgba(139, 92, 246, 0.3); background: rgba(255, 255, 255, 0.1); border-radius: 10px; margin-bottom: 15px; color: white; transition: all 0.3s ease; }
    .modal-content input:focus, .modal-content select:focus { outline: none; border-color: #8B5CF6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2); }
    .modal-content button, .modal-content .modal-button { padding: 12px 24px; border: none; border-radius: 25px; cursor: pointer; margin: 10px 5px; font-weight: 600; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: linear-gradient(75deg, #8b5cf691, #210b632b); color: #FFFFFF; width: auto; display: inline-block; }
    .modal-content button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(139, 92, 246, 0.4); }
    .modal-button-secondary { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); }
    .modal-button-secondary:hover { background: rgba(139, 92, 246, 0.2); border-color: #8B5CF6; }
    .cerrar { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #9CA3AF; transition: all 0.3s ease; }
    .cerrar:hover { color: #fff; }
    .foto-perfil-config-container { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
    #configFotoPreview { width: 70px; height: 70px; border-radius: 50%; border: 4px solid rgba(139, 92, 246, 0.5); object-fit: cover; }
    .boton-subir-foto { background: rgba(255, 255, 255, 0.1); padding: 10px 15px; border-radius: 8px; border: 1px solid rgba(139, 92, 246,0.3); cursor: pointer; transition: all 0.3s ease; font-weight: 500; }
    .boton-subir-foto:hover { background: rgba(139, 92, 246, 0.2); border-color: #8B5CF6; }
    .password-wrapper { position: relative; }
    .password-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; }
    .theme-switch-wrapper { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
    .theme-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
    .theme-switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #8B5CF6; }
    input:focus + .slider { box-shadow: 0 0 1px #8B5CF6; }
    input:checked + .slider:before { transform: translateX(26px); }

    /* === MODO CLARO (sin cambios) === */
    body.light-mode { background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 25%, #f5f3ff 50%, #c4b5fd 75%, #a5b4fc 100%); color: #111827; }
    body.light-mode header, body.light-mode nav { background: rgba(255, 255, 255, 0.85); border-bottom-color: rgba(0, 0, 0, 0.1); }
    body.light-mode nav a, body.light-mode .nombre-usuario { color: #374151; }
    body.light-mode nav a:hover { color: #111827; }
    body.light-mode .breadcrumbs { color: #6b7280; }
    body.light-mode .breadcrumbs a { color: #6d28d9; }
    body.light-mode .usuario-menu { background: rgba(255, 255, 255, 0.95); border-color: rgba(0, 0, 0, 0.1); }
    body.light-mode .usuario-menu a { color: #1f2937; }
    body.light-mode .usuario-menu a:hover { background: rgba(139, 92, 246, 0.1); }
    body.light-mode .hero-section, body.light-mode .description-section, body.light-mode .purchase-box, body.light-mode .game-info, body.light-mode .tags-section, body.light-mode .requirements-section { background: #70007738; border-color: #e5e7eb; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    body.light-mode .hero-info h1 { color: #111827; text-shadow: 0 2px 10px rgba(109, 40, 217, 0.3); }
    body.light-mode .hero-tags .tag { background: rgba(139, 92, 246, 0.1); color: #6d28d9; border-color: rgba(139, 92, 246, 0.3); }
    body.light-mode .description-section h2, body.light-mode .requirements-section h2 { color: #111827; border-bottom-color: rgba(139, 92, 246, 0.3); }
    body.light-mode .description-section p, body.light-mode .features-list li, body.light-mode .info-value, body.light-mode .req-column li { color: #374151; }
    body.light-mode .info-label, body.light-mode .req-column strong, body.light-mode .features-list li:before { color: #6d28d9; }
    body.light-mode .purchase-box h3, body.light-mode .req-column h3, body.light-mode .tags-section h3 { color: #111827; }
    body.light-mode .price-section { background: #f3f4f6; border-color: #e5e7eb; }
    body.light-mode .current-price { color: #6d28d9; text-shadow: none; }
    body.light-mode .tag-link { background: rgba(139, 92, 246, 0.1); color: #6d28d9; border-color: rgba(139, 92, 246, 0.3); }
    body.light-mode .tag-link:hover { background: rgba(139, 92, 246, 0.2); color: #5b21b6; }
    body.light-mode footer { background: rgba(255, 255, 255, 0.9); color: #4b5563; border-top-color: rgba(0, 0, 0, 0.1); }
    body.light-mode .modal-content { background: rgba(255, 255, 255, 0.98); color: #111827; border-color: rgba(0, 0, 0, 0.1); }
    body.light-mode .modal-content h2 { background: linear-gradient(45deg, #374151, #111827); background-clip: text; -webkit-background-clip: text; }
    body.light-mode .modal-content input, body.light-mode .modal-content select { background: #f3f4f6; border-color: #d1d5db; color: #111827; }
    body.light-mode .modal-content input:focus, body.light-mode .modal-content select:focus { border-color: #8B5CF6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2); }
    body.light-mode .cerrar { color: #6b7280; }
    body.light-mode .cerrar:hover { color: #111827; }
    body.light-mode .boton-subir-foto { background: #f3f4f6; border-color: #d1d5db; }
    body.light-mode .carousel-arrow { background: rgba(255, 255, 255, 0.8); border-color: #d1d5db; color: #111827; }

    /* === RESPONSIVE MODIFICADO === */
    @media (max-width: 992px) { 
        /* En lugar de cambiar a 1 columna, hacemos que las columnas sean más flexibles */
        .content-grid {
            /* El contenido principal toma más espacio, pero la barra lateral no desaparece */
            grid-template-columns: minmax(0, 2.5fr) minmax(0, 1fr);
        }
        .carousel-arrow { 
            /* Ocultamos las flechas en pantallas más pequeñas para favorecer el control táctil */
            display: none; 
        }
        .thumbnail-carousel { padding: 0 20px; }
    }

    @media (max-width: 820px) {
        /* Este es el nuevo punto de quiebre donde todo pasa a una sola columna */
        .content-grid, .requirements-grid { 
            grid-template-columns: 1fr; 
        } 
    }

    @media (max-width: 767px) { 
      header { height: 70px; } 
      nav { top: 70px; justify-content: flex-start; overflow-x: auto; } 
      main { padding-top: 132px; } 
      .nombre-usuario { 
          /* Mantenemos visible el nombre de usuario */
          display: block; 
      }
    }

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
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <header>
        <div class="header-left">
            <div class="logo">
                <a href="Pantallainicio.php"><img src="/NJOYSINFONDO.jpeg" alt="njoy" id="logoImg" /></a>
            </div>
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
        <a href="Pantallainicio.php#recomendados-para-ti">Recomendados</a>
        <a href="Pantallainicio.php#ofertas">Ofertas</a>
        <a href="Pantallainicio.php#populares">Populares</a>
        <a href="Pantallainicio.php#free-to-play">Gratis</a>
        <a href="Pantallainicio.php#novedades">Novedades</a>
    </nav>
    
    <main>
        <div class="breadcrumbs">
            <a href="Pantallainicio.php">Inicio</a> › 
            <span><?php echo htmlspecialchars($juego['titulo']); ?></span>
        </div>

        <div class="hero-section">
            <img class="hero-background" src="<?php echo htmlspecialchars(getAbsolutePath($main_media_image)); ?>" alt="Hero">
            <div class="hero-content">
                <div class="hero-info">
                    <h1><?php echo htmlspecialchars($juego['titulo']); ?></h1>
                    <div class="hero-tags">
                        <?php foreach (array_slice($tags_array, 0, 3) as $tag): if(!empty(trim($tag))): ?>
                            <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="media-section">
            <div class="main-media" id="main-media-player">
                <?php if (!empty($juego['video_youtube_id'])): ?>
                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($juego['video_youtube_id']); ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                <?php else: ?>
                    <img src="<?php echo htmlspecialchars(getAbsolutePath($main_media_image)); ?>" alt="Gameplay">
                <?php endif; ?>
            </div>

            <div class="thumbnail-carousel">
                <button class="carousel-arrow prev" id="thumb-prev">‹</button>
                <div class="thumbnail-strip" id="thumbnail-strip">
                    <?php if (!empty($juego['video_youtube_id'])): ?>
                    <div class="thumb active" data-type="video" data-id="<?php echo htmlspecialchars($juego['video_youtube_id']); ?>">
                        <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($juego['video_youtube_id']); ?>/0.jpg" alt="Video Thumbnail">
                        <div class="play-icon">▶</div>
                    </div>
                    <?php endif; ?>
                    <?php foreach ($galeria_array as $index => $img_path): if(!empty(trim($img_path))): ?>
                    <div class="thumb <?php if(empty($juego['video_youtube_id']) && $index == 0) echo 'active'; ?>" data-type="image" data-src="<?php echo htmlspecialchars(getAbsolutePath(trim($img_path))); ?>">
                        <img src="<?php echo htmlspecialchars(getAbsolutePath(trim($img_path))); ?>" alt="thumbnail">
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <button class="carousel-arrow next" id="thumb-next">›</button>
            </div>
        </div>
        
        <div class="content-grid">
            <div>
                <div class="description-section">
                    <h2>Acerca de este juego</h2>
                    <p><?php echo nl2br(htmlspecialchars($juego['descripcion_larga'])); ?></p>
                    
                    <?php if(!empty(trim($juego['caracteristicas']))): ?>
                    <h2 style="margin-top: 30px;">Características principales</h2>
                    <ul class="features-list">
                        <?php foreach ($caracteristicas_array as $caracteristica): if(!empty(trim($caracteristica))): ?>
                            <li><?php echo htmlspecialchars(trim($caracteristica)); ?></li>
                        <?php endif; endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <?php if(!empty(trim($juego['req_minimos']))): ?>
                <div class="requirements-section">
                  <h2>Requisitos del sistema</h2>
                  <div class="requirements-grid">
                    <div class="req-column">
                      <h3>MÍNIMO:</h3>
                      <ul>
                        <?php foreach ($req_min_array as $req): if(!empty(trim($req))): ?>
                            <li><?php echo htmlspecialchars(trim($req)); ?></li>
                        <?php endif; endforeach; ?>
                      </ul>
                    </div>
                    <?php if(!empty(trim($juego['req_recomendados']))): ?>
                    <div class="req-column">
                      <h3>RECOMENDADO:</h3>
                      <ul>
                        <?php foreach ($req_rec_array as $req): if(!empty(trim($req))): ?>
                            <li><?php echo htmlspecialchars(trim($req)); ?></li>
                        <?php endif; endforeach; ?>
                      </ul>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="sidebar-section">
                <div class="game-cover-box">
                    <img src="<?php echo htmlspecialchars(getAbsolutePath($juego['imagen_url'])); ?>" alt="Carátula de <?php echo htmlspecialchars($juego['titulo']); ?>">
                </div>

                <div class="purchase-box">
                  <h3>Comprar <?php echo htmlspecialchars($juego['titulo']); ?></h3>
                  <div class="price-section">
                    <?php
                    if ($juego['descuento_porcentaje'] > 0) {
                        $precio_original = $juego['precio_final'] / (1 - ($juego['descuento_porcentaje'] / 100));
                        echo '<div class="discount-badge">-' . $juego['descuento_porcentaje'] . '%</div>';
                        echo '<div class="price-values"><div class="original-price">$' . number_format($precio_original, 2) . '</div><div class="current-price">$' . number_format($juego['precio_final'], 2) . ' USD</div></div>';
                    } elseif ($juego['precio_final'] == 0 && $juego['descuento_porcentaje'] == 0) {
                        echo '<div class="current-price" style="text-align:center; width:100%;">Free to Play</div>';
                    } else {
                        echo '<div class="current-price" style="text-align:right; width:100%;">$' . number_format($juego['precio_final'], 2) . ' USD</div>';
                    }
                    ?>
                  </div>
                  <?php if($juego['precio_final'] > 0 || $juego['descuento_porcentaje'] > 0): ?>
                    <button class="purchase-button">Añadir al carrito</button>
                  <?php else: ?>
                    <button class="purchase-button" style="background: linear-gradient(135deg, #10B981, #059669);">Jugar Gratis</button>
                  <?php endif; ?>
                </div>

                <div class="game-info">
                  <div class="info-row"><div class="info-label">Desarrollador:</div><div class="info-value"><a href="#"><?php echo htmlspecialchars($juego['desarrollador']); ?></a></div></div>
                  <div class="info-row"><div class="info-label">Editor:</div><div class="info-value"><a href="#"><?php echo htmlspecialchars($juego['editor']); ?></a></div></div>
                  <div class="info-row"><div class="info-label">Lanzamiento:</div><div class="info-value"><?php echo htmlspecialchars($juego['fecha_lanzamiento']); ?></div></div>
                </div>

                <?php if(!empty($tags_array[0])): ?>
                <div class="tags-section">
                  <h3>Etiquetas populares:</h3>
                  <div class="tags-container">
                    <?php foreach ($tags_array as $tag): if(!empty(trim($tag))): ?>
                    <a href="#" class="tag-link"><?php echo htmlspecialchars(trim($tag)); ?></a>
                    <?php endif; endforeach; ?>
                  </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <footer>
        <p>© 2025 NJOY - Tu tienda de videojuegos. Todos los derechos reservados.</p>
    </footer>

    <!-- MODALES -->
    <div class="modal" id="modalLogout">
        <div class="modal-content">
          <span class="cerrar" data-close="modalLogout">&times;</span>
          <h2>🚪 Cerrar sesión</h2>
          <p>¿Estás seguro de que deseas cerrar sesión?</p>
          <button id="confirmarLogout" style="background: #ef4444;">Sí, cerrar sesión</button>
          <button class="modal-button-secondary" data-close="modalLogout">Cancelar</button>
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
            <img src="/njoyimages/user.jpeg" alt="Vista previa" id="configFotoPreview">
            <input type="file" id="configInputFoto" accept="image/*" style="display: none;">
            <label for="configInputFoto" class="boton-subir-foto">Elegir archivo</label>
          </div>
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
          <h2>🔒 Cambiar Contraseña</h2>
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
    document.addEventListener('DOMContentLoaded', function() {
        const mainMediaContainer = document.getElementById('main-media-player');
        const thumbs = document.querySelectorAll('.thumb');
        const usuarioBtn = document.getElementById("usuarioBtn");
        const usuarioMenu = document.getElementById("usuarioMenu");
        const fotoPerfil = document.getElementById("foto");
        const nombreUsuarioSpan = document.getElementById("nombreUsuario");
        const logoImg = document.getElementById("logoImg");
        const configInputFoto = document.getElementById('configInputFoto');
        const configFotoPreview = document.getElementById('configFotoPreview');
        let nombreUsuarioActual = '';

        function getAbsolutePath(path) {
            if (path && !path.startsWith('/') && !path.startsWith('http')) { return '/' + path; }
            return path;
        }

        // === GALERÍA DE MEDIOS ===
        if(mainMediaContainer && thumbs.length > 0) {
            thumbs.forEach(thumb => {
              thumb.addEventListener('click', () => {
                thumbs.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                const type = thumb.dataset.type;
                if (type === 'video') {
                    const videoId = thumb.dataset.id;
                    mainMediaContainer.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0" allowfullscreen></iframe>`;
                } else if (type === 'image') {
                    const imgSrc = thumb.dataset.src;
                    mainMediaContainer.innerHTML = `<img src="${imgSrc}" alt="Gameplay Screenshot">`;
                }
              });
            });
        }

        // === CARRUSEL DE THUMBNAILS ===
        const thumbStrip = document.getElementById('thumbnail-strip');
        const prevBtn = document.getElementById('thumb-prev');
        const nextBtn = document.getElementById('thumb-next');
        if(thumbStrip) {
            const scrollAmount = thumbStrip.clientWidth * 0.8;
            prevBtn.addEventListener('click', () => { thumbStrip.scrollBy({ left: -scrollAmount, behavior: 'smooth' }); });
            nextBtn.addEventListener('click', () => { thumbStrip.scrollBy({ left: scrollAmount, behavior: 'smooth' }); });
        }

        // === MENÚ DE USUARIO ===
        if (usuarioBtn) {
            usuarioBtn.addEventListener("click", (e) => { 
                e.stopPropagation(); 
                usuarioMenu.style.display = usuarioMenu.style.display === "block" ? "none" : "block"; 
            });
        }
        document.addEventListener("click", () => { 
            if (usuarioMenu && usuarioMenu.style.display === "block") { 
                usuarioMenu.style.display = "none"; 
            } 
        });

        // === CARGAR DATOS DE USUARIO ===
        fetch('obtener_datos_usuario.php?v=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.status === "ok") {
                    nombreUsuarioActual = data.nombre;
                    const fotoUrl = getAbsolutePath(data.foto) + "?t=" + new Date().getTime();
                    fotoPerfil.src = fotoUrl;
                    configFotoPreview.src = fotoUrl;
                    nombreUsuarioSpan.textContent = data.nombre;
                    document.getElementById('configNombreInput').value = data.nombre;
                }
            });

        // === ABRIR MODALES ===
        document.querySelectorAll(".usuario-menu a").forEach(link => {
            const href = link.getAttribute("href");
            if (href === "#configuracion") { 
                link.addEventListener("click", (e) => { 
                    e.preventDefault(); 
                    document.getElementById('modalConfig').style.display = 'flex';
                    usuarioMenu.style.display = 'none';
                }); 
            }
            else if (href === "logout.php") { 
                link.addEventListener("click", (e) => { 
                    e.preventDefault(); 
                    document.getElementById('modalLogout').style.display = 'flex'; 
                    usuarioMenu.style.display = 'none';
                }); 
            }
        });

        // === CERRAR MODALES ===
        document.querySelectorAll(".cerrar, [data-close]").forEach(el => { 
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

        // === CERRAR SESIÓN ===
// CÓDIGO NUEVO Y CORRECTO
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

        // === PREVISUALIZAR FOTO ===
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

        // === GUARDAR CONFIGURACIÓN ===
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
                  nombreUsuarioSpan.textContent = data.nuevoNombre;
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
                  fotoPerfil.src = data.ruta + cacheBuster;
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
              }).catch(error => { alert('Hubo un error: ' + error.message); });
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

          if (!antiguaPass || !nuevaPass || !repetirPass) { 
            mensajeError.textContent = 'Todos los campos son obligatorios.'; 
            mensajeError.style.display = 'block'; 
            return; 
          }
          if (nuevaPass !== repetirPass) { 
            mensajeError.textContent = 'Las nuevas contraseñas no coinciden.'; 
            mensajeError.style.display = 'block'; 
            return; 
          }
          if (nuevaPass.length < 8) { 
            mensajeError.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.'; 
            mensajeError.style.display = 'block'; 
            return; 
          }
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
          if(themeToggle) themeToggle.checked = (theme === 'light');
          if(themeLabel) themeLabel.textContent = (theme === 'light') ? 'Modo Claro' : 'Modo Oscuro';
          if(logoImg) logoImg.src = getAbsolutePath((theme === 'light') ? 'NJOY_LOGO_CLARO.jpeg' : 'NJOYSINFONDO.jpeg');
        }

        if(themeToggle) {
            themeToggle.addEventListener('change', () => {
              const newTheme = themeToggle.checked ? 'light' : 'dark';
              localStorage.setItem('theme', newTheme);
              setTheme(newTheme);
            });
        }
        
        const savedTheme = localStorage.getItem('theme') || 'dark';
        setTheme(savedTheme);

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

        // === DRAG TO SCROLL PARA THUMBNAILS ===
        if(thumbStrip) {
          let isDown = false;
          let startX, scrollLeft;
          
          thumbStrip.addEventListener('mousedown', (e) => {
            isDown = true;
            thumbStrip.style.cursor = 'grabbing';
            startX = e.pageX - thumbStrip.offsetLeft;
            scrollLeft = thumbStrip.scrollLeft;
          });
          
          thumbStrip.addEventListener('mouseleave', () => { 
            isDown = false; 
            thumbStrip.style.cursor = 'grab'; 
          });
          
          thumbStrip.addEventListener('mouseup', () => { 
            isDown = false; 
            thumbStrip.style.cursor = 'grab'; 
          });
          
          thumbStrip.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - thumbStrip.offsetLeft;
            const walk = (x - startX) * 2;
            thumbStrip.scrollLeft = scrollLeft - walk;
          });
        }

        // === REGISTRO DE CLICS PARA JUEGOS POPULARES ===
        const juegoId = <?php echo $juego_id; ?>;
        if(juegoId) {
            const formData = new FormData();
            formData.append('juego_id', juegoId);
            navigator.sendBeacon('registrar_clic.php', formData);
        }
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