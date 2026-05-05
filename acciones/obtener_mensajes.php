<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode([]); exit(); }

$mi_id      = $_SESSION['usuario_id'];
$receptor_id = (int)($_GET['receptor_id'] ?? 0);
$ultimo_id  = (int)($_GET['ultimo_id'] ?? 0);

if(!$receptor_id){ echo json_encode([]); exit(); }

// Marcar como leídos
$pdo->prepare("UPDATE mensajes SET leido=1 WHERE emisor_id=? AND receptor_id=? AND leido=0")
    ->execute([$receptor_id, $mi_id]);

// Obtener mensajes nuevos
$stmt = $pdo->prepare("
    SELECT m.*, 
        DATE_FORMAT(m.fecha, '%H:%i') AS hora,
        DATE_FORMAT(m.fecha, '%d/%m/%Y') AS dia
    FROM mensajes m
    WHERE ((m.emisor_id=? AND m.receptor_id=?) OR (m.emisor_id=? AND m.receptor_id=?))
    AND m.id > ?
    ORDER BY m.fecha ASC
");
$stmt->execute([$mi_id, $receptor_id, $receptor_id, $mi_id, $ultimo_id]);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($mensajes);
?>