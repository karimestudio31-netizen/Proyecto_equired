<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode([]); exit(); }

$mi_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT u.id, u.nombre, u.foto_perfil, u.rol,
        m.mensaje AS ultimo_mensaje,
        m.imagen AS ultima_imagen,
        DATE_FORMAT(m.fecha, '%H:%i') AS hora,
        SUM(CASE WHEN m.receptor_id=? AND m.leido=0 THEN 1 ELSE 0 END) AS no_leidos
    FROM mensajes m
    JOIN usuarios u ON u.id = IF(m.emisor_id=?, m.receptor_id, m.emisor_id)
    WHERE m.id IN (
        SELECT MAX(id) FROM mensajes
        WHERE emisor_id=? OR receptor_id=?
        GROUP BY LEAST(emisor_id, receptor_id), GREATEST(emisor_id, receptor_id)
    )
    AND (m.emisor_id=? OR m.receptor_id=?)
    GROUP BY u.id
    ORDER BY m.fecha DESC
");
$stmt->execute([$mi_id, $mi_id, $mi_id, $mi_id, $mi_id, $mi_id]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($chats);
?>