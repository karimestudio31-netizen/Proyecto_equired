<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode(['ok'=>false]); exit(); }

$servicio_id = (int)($_POST['servicio_id'] ?? 0);
$estrellas   = (int)($_POST['estrellas'] ?? 0);
$usuario_id  = $_SESSION['usuario_id'];

if(!$servicio_id || $estrellas < 1 || $estrellas > 5){ echo json_encode(['ok'=>false]); exit(); }

$stmt = $pdo->prepare("INSERT INTO calificaciones (servicio_id, usuario_id, estrellas) VALUES (?,?,?)
    ON DUPLICATE KEY UPDATE estrellas=?");
$stmt->execute([$servicio_id, $usuario_id, $estrellas, $estrellas]);

$avg = $pdo->prepare("SELECT ROUND(AVG(estrellas),1), COUNT(*) FROM calificaciones WHERE servicio_id=?");
$avg->execute([$servicio_id]);
[$promedio, $total] = $avg->fetch(PDO::FETCH_NUM);

echo json_encode(['ok'=>true, 'promedio'=>$promedio, 'total'=>$total]);
?>