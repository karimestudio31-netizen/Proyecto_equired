<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode(['ok'=>false]); exit(); }

$notif_id   = (int)($_POST['notificacion_id'] ?? 0);
$usuario_id = $_SESSION['usuario_id'];

$pdo->prepare("UPDATE notificaciones SET leida=1 WHERE id=? AND usuario_id=?")
    ->execute([$notif_id, $usuario_id]);

echo json_encode(['ok'=>true]);
?>