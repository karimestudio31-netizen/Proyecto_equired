<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'empresa'){
    echo json_encode(['ok'=>false]); exit();
}

$empleo_id  = (int)($_POST['empleo_id'] ?? 0);
$usuario_id = $_SESSION['usuario_id'];

$check = $pdo->prepare("SELECT id FROM empleos WHERE id=? AND empresa_id=?");
$check->execute([$empleo_id, $usuario_id]);
if(!$check->fetch()){ echo json_encode(['ok'=>false]); exit(); }

$pdo->prepare("DELETE FROM empleos WHERE id=?")->execute([$empleo_id]);
echo json_encode(['ok'=>true]);
?>