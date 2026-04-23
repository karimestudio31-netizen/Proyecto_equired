<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])||$_SESSION['rol']!=='empresa'){ echo json_encode([]); exit(); }

$empleo_id  = (int)($_GET['empleo_id']??0);
$empresa_id = $_SESSION['usuario_id'];

// Verificar que el empleo pertenece a esta empresa
$check=$pdo->prepare("SELECT id FROM empleos WHERE id=? AND empresa_id=?");
$check->execute([$empleo_id,$empresa_id]);
if(!$check->fetch()){ echo json_encode([]); exit(); }

$stmt=$pdo->prepare("
    SELECT p.id, p.hoja_vida, p.estado, p.fecha,
        u.nombre, u.email
    FROM postulaciones p
    JOIN usuarios u ON p.usuario_id=u.id
    WHERE p.empleo_id=?
    ORDER BY p.fecha DESC
");
$stmt->execute([$empleo_id]);
$posts=$stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatear fecha
foreach($posts as &$p){
    $p['fecha']=date('d/m/Y H:i',strtotime($p['fecha']));
}

echo json_encode($posts);
?>