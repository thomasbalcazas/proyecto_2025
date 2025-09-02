<form action="registro.php" method="POST">
    <div class="form-row">
        <div class="input-group">
            <span class="input-icon">👤</span>
            <input type="text" name="nombre" placeholder="Nombre..." required>
        </div>
        <div class="input-group">
            <span class="input-icon">👤</span>
            <input type="text" name="apellido" placeholder="Apellido..." required>
        </div>
    </div>
    
    <div class="input-group full-width">
        <span class="input-icon">✉️</span>
        <input type="email" name="correo" placeholder="Correo Electrónico..." required>
    </div>
    
    <div class="input-group full-width">
        <span class="input-icon">🔒</span>
        <input type="password" name="contrasena" placeholder="Contraseña..." required>
    </div>
    
    <button type="submit" class="signup-btn"> Registrate </button>
</form>