<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])||$_SESSION['rol']!=='empresa'){ echo json_encode(['ok'=>false]); exit(); }

$post_id    = (int)($_POST['postulacion_id']??0);
$estado     = $_POST['estado']??'';
$empresa_id = $_SESSION['usuario_id'];

if(!in_array($estado,['aceptado','rechazado','seleccionado_1','seleccionado_2','proceso_finalizado'])||!$post_id){ echo json_encode(['ok'=>false]); exit(); }

// Verificar que la postulación es de una vacante de esta empresa
$check=$pdo->prepare("SELECT p.id FROM postulaciones p JOIN empleos e ON p.empleo_id=e.id WHERE p.id=? AND e.empresa_id=?");
$check->execute([$post_id,$empresa_id]);
if(!$check->fetch()){ echo json_encode(['ok'=>false]); exit(); }

$pdo->prepare("UPDATE postulaciones SET estado=? WHERE id=?")->execute([$estado,$post_id]);
echo json_encode(['ok'=>true,'estado'=>$estado]);
?>