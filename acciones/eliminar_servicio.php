<?php
session_start();
if(!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'profesional'){
    header("Location: ../login.php"); exit();
}
require_once("../config/conexion.php");

$servicio_id = (int)($_POST['servicio_id'] ?? 0);
$usuario_id  = $_SESSION['usuario_id'];

// Verificar que el servicio pertenece al profesional
$check = $pdo->prepare("SELECT id FROM servicios WHERE id=? AND profesional_id=?");
$check->execute([$servicio_id, $usuario_id]);

if($check->fetch()){
    // Eliminar horarios y solicitudes (CASCADE lo hace automático)
    $pdo->prepare("DELETE FROM servicios WHERE id=?")->execute([$servicio_id]);
    header("Location: ../asesorias.php?msg=servicio_eliminado");
} else {
    header("Location: ../asesorias.php?msg=error");
}
exit();
?>