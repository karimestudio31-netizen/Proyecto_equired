<?php
session_start();
require_once("../config/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesional') {
    echo json_encode([]); exit();
}

$prof_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT sc.*, s.nombre AS servicio_nombre, s.especialidad,
        sc.usuario_id,
        u2.foto_perfil,
        DATE_FORMAT(h.dia_hora, '%W %d de %M %Y, %h:%i %p') AS dia_hora
    FROM solicitudes_cita sc
    JOIN servicios s ON sc.servicio_id = s.id
    JOIN horarios h ON sc.horario_id = h.id
    LEFT JOIN usuarios u2 ON sc.usuario_id = u2.id
    WHERE s.profesional_id = ?
    ORDER BY sc.fecha DESC
");
$stmt->execute([$prof_id]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traducir fechas al español
$dias  = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
$meses = ['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril','May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto','September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre'];

foreach($solicitudes as &$s){
    foreach($dias  as $en=>$es) $s['dia_hora'] = str_replace($en, $es, $s['dia_hora']);
    foreach($meses as $en=>$es) $s['dia_hora'] = str_replace($en, $es, $s['dia_hora']);
}

echo json_encode($solicitudes);
?>