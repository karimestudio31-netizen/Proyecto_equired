<?php
session_start();
if(!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'empresa'){
    header("Location: ../login.php"); exit();
}
require_once("../config/conexion.php");

$empleo_id  = (int)($_POST['empleo_id'] ?? 0);
$usuario_id = $_SESSION['usuario_id'];

// Verificar que el empleo pertenece a esta empresa
$check = $pdo->prepare("SELECT id FROM empleos WHERE id=? AND empresa_id=?");
$check->execute([$empleo_id, $usuario_id]);

if($check->fetch()){
    // Eliminar postulaciones primero (por si no hay CASCADE)
    $pdo->prepare("DELETE FROM postulaciones WHERE empleo_id=?")->execute([$empleo_id]);
    $pdo->prepare("DELETE FROM empleos WHERE id=?")->execute([$empleo_id]);
    header("Location: ../empleos.php?msg=empleo_eliminado");
} else {
    header("Location: ../empleos.php?msg=error");
}
exit();
?>