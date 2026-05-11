<?php
$host     = "sql308.infinityfree.com";
$dbname   = "if0_41893925_proyecto_requerido";
$usuario  = "if0_41893925";
$password = "T8w9uRjvGIsv3";
$port     = "3306";

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


