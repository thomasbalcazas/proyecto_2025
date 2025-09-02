<?php
   
require_once ("_db.php");




if (isset($_POST['accion'])){ 
    switch ($_POST['accion']){
        //casos de registros
        case 'editar_registro':
            editar_registro();
            break; 

            case 'eliminar_registro';
            eliminar_registro();
    
            break;

            case 'acceso_user';
            acceso_user();
            break;


		}

	}

    function editar_registro() {
		$conexion=mysqli_connect("sql105.infinityfree.com","if0_39843490","Njoy2025","r_user");
		extract($_POST);
		$consulta="UPDATE usuario SET nombre = '$nombre', apellido = '$apellido' , correo = '$correo',
		password ='$password' WHERE id = '$id' ";

		mysqli_query($conexion, $consulta);


		header('Location: ../views/user.php');

}

function eliminar_registro() {
    $conexion=mysqli_connect("sql105.infinityfree.com","if0_39843490","Njoy2025","r_user");
    extract($_POST);
    $id= $_POST['id'];
    $consulta= "DELETE FROM usuario WHERE id= $id";

    mysqli_query($conexion, $consulta);


    header('Location: ../views/user.php');

}

function acceso_user() {
    $nombre=$_POST['nombre'];
    $password=$_POST['password'];
    session_start();
    $_SESSION['nombre']=$nombre;

    $conexion=mysqli_connect("sql105.infinityfree.com","if0_39843490","Njoy2025","r_user");
    $consulta= "SELECT * FROM usuario WHERE nombre='$nombre' AND password='$password'";
    $resultado=mysqli_query($conexion, $consulta);
    $filas=mysqli_num_rows($resultado);

    if($filas){

        header('Location: ../views/user.php');

    }else{

        header('Location: login.php');
        session_destroy();

    }

  
}






