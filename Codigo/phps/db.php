 <?php
$host = "sql105.infinityfree.com" ;
$user = "if0_39843490" ;
$password = "Njoy2025" ;
$database = "if0_39843490_NJOY"


$conexion =mysqli_connect( $host , $user , $password , $database ); 
if (! $conexion ) {
   echo "No se realizo la conexion a la base de datos, el error fue:"
       mysqli_connect_error();
