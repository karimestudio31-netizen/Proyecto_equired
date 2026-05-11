<?php
$host     = getenv('MYSQLHOST')     ?: "localhost";
$dbname   = getenv('MYSQLDATABASE') ?: "proyecto_equired";
$usuario  = getenv('MYSQLUSER')     ?: "root";
$password = getenv('MYSQLPASSWORD') ?: "";
$port     = getenv('MYSQLPORT')     ?: "3306";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $usuario,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>


