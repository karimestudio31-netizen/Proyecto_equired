<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode(['ok'=>false]); exit(); }

$solicitud_id = (int)($_POST['solicitud_id'] ?? 0);
$motivo       = trim($_POST['motivo'] ?? '');
$usuario_id   = $_SESSION['usuario_id'];
$rol          = $_SESSION['rol'];

if(!$solicitud_id){ echo json_encode(['ok'=>false]); exit(); }

if($rol !== 'profesional') {
    // Beneficiario cancela su propia solicitud (pendiente o aceptada)
    $check = $pdo->prepare("
        SELECT sc.id, sc.horario_id, sc.estado,
            s.profesional_id, s.nombre AS servicio_nombre,
            u.nombre AS nombre_solicitante
        FROM solicitudes_cita sc
        JOIN servicios s ON sc.servicio_id = s.id
        JOIN usuarios u ON sc.usuario_id = u.id
        WHERE sc.id=? AND sc.usuario_id=?
    ");
    $check->execute([$solicitud_id, $usuario_id]);
    $sol = $check->fetch();
    if(!$sol){ echo json_encode(['ok'=>false]); exit(); }

    // Liberar horario
    $pdo->prepare("UPDATE horarios SET disponible=1 WHERE id=?")->execute([$sol['horario_id']]);
    $pdo->prepare("DELETE FROM solicitudes_cita WHERE id=?")->execute([$solicitud_id]);

    // Si estaba aceptada, notificar al profesional
    if($sol['estado'] === 'aceptada') {
        $motivo_texto = !empty($motivo) ? " Motivo: \"$motivo\"" : '';
        $mensaje = "⚠️ {$sol['nombre_solicitante']} canceló una cita aceptada para el servicio \"{$sol['servicio_nombre']}\".{$motivo_texto} El horario ya está disponible nuevamente.";
        $pdo->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (?,?)")
            ->execute([$sol['profesional_id'], $mensaje]);
    }

    echo json_encode(['ok'=>true]);
    exit();
}

// Profesional cancela cita aceptada
$check = $pdo->prepare("
    SELECT sc.id, sc.horario_id FROM solicitudes_cita sc
    JOIN servicios s ON sc.servicio_id = s.id
    WHERE sc.id=? AND s.profesional_id=?
");
$check->execute([$solicitud_id, $usuario_id]);
$sol = $check->fetch();
if(!$sol){ echo json_encode(['ok'=>false]); exit(); }

$pdo->prepare("UPDATE horarios SET disponible=1 WHERE id=?")->execute([$sol['horario_id']]);
$pdo->prepare("UPDATE solicitudes_cita SET estado='rechazada' WHERE id=?")->execute([$solicitud_id]);
echo json_encode(['ok'=>true]);
?>