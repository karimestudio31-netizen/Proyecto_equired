<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode(['ok'=>false]); exit(); }

$solicitud_id    = (int)($_POST['solicitud_id'] ?? 0);
$nuevo_horario_id = (int)($_POST['nuevo_horario_id'] ?? 0);
$usuario_id      = $_SESSION['usuario_id'];

if(!$solicitud_id || !$nuevo_horario_id){ echo json_encode(['ok'=>false]); exit(); }

// Verificar que la solicitud pertenece al usuario y está pendiente
$check = $pdo->prepare("SELECT id, horario_id FROM solicitudes_cita WHERE id=? AND usuario_id=? AND estado='pendiente'");
$check->execute([$solicitud_id, $usuario_id]);
$sol = $check->fetch();
if(!$sol){ echo json_encode(['ok'=>false]); exit(); }

// Liberar horario anterior
$pdo->prepare("UPDATE horarios SET disponible=1 WHERE id=?")->execute([$sol['horario_id']]);

// Asignar nuevo horario
$pdo->prepare("UPDATE solicitudes_cita SET horario_id=? WHERE id=?")->execute([$nuevo_horario_id, $solicitud_id]);
$pdo->prepare("UPDATE horarios SET disponible=0 WHERE id=?")->execute([$nuevo_horario_id]);

echo json_encode(['ok'=>true]);
?>