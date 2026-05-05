<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode([]); exit(); }

$donacion_id = (int)($_GET['donacion_id'] ?? 0);
$usuario_id  = $_SESSION['usuario_id'];

// Verificar que la donación pertenece al usuario
$check = $pdo->prepare("SELECT id FROM donaciones WHERE id=? AND usuario_id=?");
$check->execute([$donacion_id, $usuario_id]);
if(!$check->fetch()){ echo json_encode([]); exit(); }

$stmt = $pdo->prepare("
    SELECT sd.*, u.nombre, u.foto_perfil, u.rol
    FROM solicitudes_donacion sd
    JOIN usuarios u ON sd.solicitante_id = u.id
    WHERE sd.donacion_id = ?
    ORDER BY sd.fecha DESC
");
$stmt->execute([$donacion_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>