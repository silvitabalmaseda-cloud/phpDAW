<?php
$servidor = "localhost";
$usuario = "root";
$clave = "";
$basedatos = "DAW";
$puerto = "3306";
try {
    $dsn = "mysql:host=$servidor;dbname=$basedatos;charset=utf8mb4";
    $conexion = new PDO($dsn, $usuario, $clave);

    // Para mostrar errores de MySQL
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("❌ Error de conexión a la BD: " . $e->getMessage());
}
?>
