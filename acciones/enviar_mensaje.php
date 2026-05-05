<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])){ echo json_encode(['ok'=>false]); exit(); }

$emisor_id  = $_SESSION['usuario_id'];
$receptor_id = (int)($_POST['receptor_id'] ?? 0);
$mensaje    = trim($_POST['mensaje'] ?? '');
$imagen     = null;

if(!$receptor_id){ echo json_encode(['ok'=>false]); exit(); }

// Subir imagen si existe
if(!empty($_FILES['imagen']['name'])){
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg','jpeg','png','gif','webp'];
    if(in_array($ext, $permitidos)){
        $carpeta = "../uploads/";
        if(!is_dir($carpeta)) mkdir($carpeta, 0755, true);
        $nombre = uniqid('msg_').'.'.$ext;
        if(move_uploaded_file($_FILES['imagen']['tmp_name'], $carpeta.$nombre)){
            $imagen = $nombre;
        }
    }
}

if(empty($mensaje) && !$imagen){ echo json_encode(['ok'=>false]); exit(); }

$stmt = $pdo->prepare("INSERT INTO mensajes (emisor_id, receptor_id, mensaje, imagen) VALUES (?,?,?,?)");
$stmt->execute([$emisor_id, $receptor_id, $mensaje, $imagen]);

$id = $pdo->lastInsertId();
$fecha = date('H:i');

echo json_encode([
    'ok'      => true,
    'id'      => $id,
    'mensaje' => htmlspecialchars($mensaje),
    'imagen'  => $imagen,
    'fecha'   => $fecha
]);
?>