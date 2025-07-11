
CREATE TABLE usuario (
Id_usuario  NUMERIC PRIMARY KEY NOT NULL,
Nombre_Apellido VARCHAR NOT NULL,
correo_electrónico  VARCHAR NOT NULL,
contraseña  VARCHAR NOT NULL,
fecha_registro DATE NOT NULL,
rol VARCHAR NOT NULL);

CREATE TABLE Juego (
id_juego NUMERIC PRIMARY KEY NOT NULL,
titulo VARCHAR NOT NULL,
descripción  VARCHAR NOT NULL,
precio  NUMERIC NOT NULL,
fecha_lanzamiento DATE NOT NULL,
Id_JuegoExtra INT NOT NULL,
FOREIGN KEY(Id_JuegoExtra) REFERENCES JuegoExtra(id_juegoextra));

CREATE TABLE reseña (
id_reseña NUMERIC PRIMARY KEY NOT NULL,
id_usuario NUMERIC NOT NULL,
Id_usuario NUMERIC NOT NULL,
calificación  NUMERIC NOT NULL,
comentario  VARCHAR NOT NULL,
fecha_reseña DATETIME NOT NULL,
FOREIGN KEY(id_usuario) REFERENCES usuario(Id_usuario ),
FOREIGN KEY(Id_usuario) REFERENCES Juego(id_juego));

CREATE TABLE JuegoExtra (
id_juegoextra INT PRIMARY KEY AUTOINCREMENT NOT NULL,
genero VARCHAR NOT NULL,
plataforma VARCHAR NOT NULL);

