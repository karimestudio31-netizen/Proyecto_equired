<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesional'){
    echo json_encode(['ok'=>false]); exit();
}

$servicio_id = (int)($_POST['servicio_id'] ?? 0);
$usuario_id  = $_SESSION['usuario_id'];
$horarios    = json_decode(urldecode($_POST['horarios'] ?? '[]'), true);

if(!$servicio_id || empty($horarios)){ echo json_encode(['ok'=>false]); exit(); }

// Verificar que el servicio pertenece al profesional
$check = $pdo->prepare("SELECT id FROM servicios WHERE id=? AND profesional_id=?");
$check->execute([$servicio_id, $usuario_id]);
if(!$check->fetch()){ echo json_encode(['ok'=>false]); exit(); }

foreach($horarios as $h){
    $h = trim($h);
    if(!empty($h)){
        $pdo->prepare("INSERT INTO horarios (servicio_id, dia_hora, disponible) VALUES (?,?,1)")
            ->execute([$servicio_id, $h]);
    }
}

echo json_encode(['ok'=>true]);
?>