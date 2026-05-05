<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode([]); exit(); }

$servicio_id = (int)($_GET['servicio_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM horarios WHERE servicio_id=? AND disponible=1 ORDER BY dia_hora ASC");
$stmt->execute([$servicio_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>