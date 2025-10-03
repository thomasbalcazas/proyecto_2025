USE Njoy;

CREATE TABLE usuario (
id_usuario NUMERIC PRIMARY KEY AUTO_INCREMENT,
Nombre_Apellido VARCHAR NOT NULL,
correo_electronico VARCHAR NOT NULL,
contrasena VARCHAR NOT NULL,
fecha_registro DATE NOT NULL,
rol VARCHAR NOT NULL);

CREATE TABLE Juego (
id_juego NUMERIC PRIMARY KEY NOT NULL,
titulo VARCHAR NOT NULL,
descripcion VARCHAR NULL,
precio NUMERIC NOT NULL,
fecha_lanzamiento DATE NOT NULL,
genero VARCHAR NULL,
plataforma  VARCHAR NULL);

CREATE TABLE resena (
id_resena NUMERIC PRIMARY KEY NOT NULL,
id_usuario NUMERIC NOT NULL,
id_juego NUMERIC NOT NULL,
calificacion NUMERIC NOT NULL,
comentario VARCHAR NOT NULL,
fecha_resena DATETIME NOT NULL,
FOREIGN KEY(id_usuario) REFERENCES usuario(Id_usuario ),
FOREIGN KEY(Id_usuario) REFERENCES Juego(id_juego));





