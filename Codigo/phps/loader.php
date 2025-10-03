<!-- loader.php -->
<style>
    /* Estilos de la Pantalla de Carga */
    #loader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        background-color: #1E1B4B;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
        opacity: 1;
        visibility: visible;
    }

    .loader-logo img {
        width: 80px;
        height: auto;
        animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.7; }
        100% { transform: scale(1); opacity: 1; }
    }

    #loader.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
</style>

<div id="loader">
    <div class="loader-logo">
        <img src="/NJOYSINFONDO.jpeg" alt="Cargando NJOY...">
    </div>
</div>