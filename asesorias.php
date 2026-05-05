<?php
session_start();
if(!isset($_SESSION['usuario'])){ header("Location: login.php"); exit(); }
require_once("config/conexion.php");

$tab        = $_GET['tab'] ?? 'psicologica';
$buscar     = trim($_GET['buscar'] ?? '');
$rol        = $_SESSION['rol'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? null;
$msg        = $_GET['msg'] ?? '';

// Servicios psicológicos
$whereExtra = '';
$params = ['psicologica'];
if($buscar !== '') {
    $whereExtra = "AND (s.nombre LIKE ? OR s.especialidad LIKE ? OR u.nombre LIKE ?)";
    $params[] = "%$buscar%"; $params[] = "%$buscar%"; $params[] = "%$buscar%";
}
$stmt = $pdo->prepare("
    SELECT s.*, u.nombre AS prof_nombre, u.foto_perfil, u.id AS prof_usuario_id,
        COUNT(DISTINCT h.id) AS total_horarios,
        (SELECT COUNT(*) FROM horarios WHERE servicio_id=s.id AND disponible=1) AS horarios_disponibles,
        ROUND(AVG(c.estrellas),1) AS promedio_estrellas,
        COUNT(DISTINCT c.id) AS total_calificaciones
    FROM servicios s
    JOIN usuarios u ON s.profesional_id = u.id
    LEFT JOIN horarios h ON h.servicio_id = s.id
    LEFT JOIN calificaciones c ON c.servicio_id = s.id
    WHERE s.tipo = ? $whereExtra
    GROUP BY s.id ORDER BY s.fecha DESC
");
$stmt->execute($params);
$servicios_psi = $stmt->fetchAll();

// Servicios jurídicos
$paramsJ = ['juridica'];
$whereJ  = '';
if($buscar !== '') {
    $whereJ = "AND (s.nombre LIKE ? OR s.especialidad LIKE ? OR u.nombre LIKE ?)";
    $paramsJ[] = "%$buscar%"; $paramsJ[] = "%$buscar%"; $paramsJ[] = "%$buscar%";
}
$stmtJ = $pdo->prepare("
    SELECT s.*, u.nombre AS prof_nombre, u.foto_perfil, u.id AS prof_usuario_id,
        COUNT(DISTINCT h.id) AS total_horarios,
        (SELECT COUNT(*) FROM horarios WHERE servicio_id=s.id AND disponible=1) AS horarios_disponibles,
        ROUND(AVG(c.estrellas),1) AS promedio_estrellas,
        COUNT(DISTINCT c.id) AS total_calificaciones
    FROM servicios s
    JOIN usuarios u ON s.profesional_id = u.id
    LEFT JOIN horarios h ON h.servicio_id = s.id
    LEFT JOIN calificaciones c ON c.servicio_id = s.id
    WHERE s.tipo = ? $whereJ
    GROUP BY s.id ORDER BY s.fecha DESC
");
$stmtJ->execute($paramsJ);
$servicios_jur = $stmtJ->fetchAll();

// Historial citas
$mis_solicitudes = [];
if($usuario_id && $rol !== 'profesional') {
    $ms = $pdo->prepare("
        SELECT sc.id, sc.estado, sc.fecha, sc.mensaje,
            s.nombre AS servicio_nombre, s.especialidad, s.id AS serv_id,
            u.nombre AS prof_nombre, u.foto_perfil, u.id AS prof_usuario_id,
            DATE_FORMAT(h.dia_hora, '%d/%m/%Y %h:%i %p') AS dia_hora_fmt,
            h.id AS horario_id_actual
        FROM solicitudes_cita sc
        JOIN servicios s ON sc.servicio_id = s.id
        JOIN usuarios u ON s.profesional_id = u.id
        JOIN horarios h ON sc.horario_id = h.id
        WHERE sc.usuario_id = ?
        ORDER BY sc.fecha DESC
    ");
    $ms->execute([$usuario_id]);
    $mis_solicitudes = $ms->fetchAll();
}

// Pendientes profesional
$pendientes2 = 0;
if($rol === 'profesional' && $usuario_id) {
    $sp2 = $pdo->prepare("SELECT COUNT(*) FROM solicitudes_cita sc JOIN servicios s ON sc.servicio_id=s.id WHERE s.profesional_id=? AND sc.estado='pendiente'");
    $sp2->execute([$usuario_id]);
    $pendientes2 = (int)$sp2->fetchColumn();
}

// Notificaciones no leídas
$notifs_list = [];
if($rol === 'profesional' && $usuario_id) {
    $notifs = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id=? AND leida=0 ORDER BY fecha DESC");
    $notifs->execute([$usuario_id]);
    $notifs_list = $notifs->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asesorías - EquiRed</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .asesorias-page{max-width:1100px;margin:0 auto;padding:40px 20px}
        .asesorias-hero{text-align:center;margin-bottom:32px}
        .asesorias-hero h1{font-size:36px;font-weight:900;color:#1a1a2e}
        .asesorias-hero h1 span{color:#7b2ff7}
        .asesorias-hero p{color:#777;font-size:15px;margin-top:10px;max-width:560px;margin-inline:auto;line-height:1.6}

        .toast{position:fixed;top:80px;right:24px;z-index:999;padding:14px 22px;border-radius:12px;font-size:15px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,0.15);animation:slideIn 0.3s ease,fadeOut 0.4s ease 3.5s forwards}
        .toast-exito{background:#d1fae5;color:#059669;border:1px solid #a7f3d0}
        .toast-warning{background:#fef3c7;color:#d97706;border:1px solid #fde68a}
        .toast-error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
        @keyframes slideIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes fadeOut{from{opacity:1}to{opacity:0;visibility:hidden}}

        .search-bar-as{background:white;border-radius:14px;padding:14px 18px;box-shadow:0 2px 12px rgba(0,0,0,0.06);margin-bottom:24px;display:flex;gap:10px;align-items:center}
        .search-wrap-as{flex:1;position:relative}
        .search-wrap-as .sicon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa}
        .search-wrap-as input{width:100%;padding:10px 14px 10px 38px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f9f9f9}
        .search-wrap-as input:focus{border-color:#7b2ff7;background:white}
        .btn-buscar-as{padding:10px 20px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-weight:700;font-family:inherit;cursor:pointer;font-size:14px}
        .limpiar-as{color:#7b2ff7;font-weight:700;font-size:14px;text-decoration:none}

        .top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px}
        .top-bar-btns{display:flex;gap:10px;flex-wrap:wrap}
        .btn-publicar-servicio{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;padding:11px 22px;border-radius:10px;font-weight:800;font-size:14px;border:none;cursor:pointer;font-family:inherit}
        .btn-mis-solicitudes{display:inline-flex;align-items:center;gap:8px;background:#f3e8ff;color:#7b2ff7;padding:11px 22px;border-radius:10px;font-weight:800;font-size:14px;border:2px solid #d8b4fe;cursor:pointer;font-family:inherit;position:relative}
        .btn-historial{display:inline-flex;align-items:center;gap:8px;background:#f3e8ff;color:#7b2ff7;padding:11px 22px;border-radius:10px;font-weight:800;font-size:14px;border:2px solid #d8b4fe;cursor:pointer;font-family:inherit}
        .badge-count{position:absolute;top:-8px;right:-8px;background:#ef4444;color:white;border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center}

        .tabs-row{display:flex;justify-content:center;margin-bottom:36px}
        .tabs-wrap{background:#f0e8ff;border-radius:50px;padding:5px;display:inline-flex;gap:4px}
        .tab-btn{padding:10px 24px;border-radius:50px;border:none;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;background:transparent;color:#888;transition:all 0.2s;display:flex;align-items:center;gap:6px}
        .tab-btn.active{background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white}

        .seccion-titulo{margin-bottom:20px}
        .seccion-titulo h2{font-size:22px;font-weight:900;color:#1a1a2e}
        .seccion-titulo p{font-size:14px;color:#888;margin-top:4px}

        .profesionales-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:30px}
        @media(max-width:768px){.profesionales-grid{grid-template-columns:1fr}}

        .prof-card{background:white;border-radius:16px;padding:26px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);text-align:center;border:1.5px solid #f0f0f0;transition:border-color 0.2s,box-shadow 0.2s}
        .prof-card:hover{border-color:#7b2ff7;box-shadow:0 4px 20px rgba(123,47,247,0.1)}
        .prof-avatar{width:70px;height:70px;background:#f3e8ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;margin:0 auto 14px;overflow:hidden;cursor:pointer;transition:transform 0.2s;text-decoration:none}
        .prof-avatar:hover{transform:scale(1.05)}
        .prof-avatar img{width:100%;height:100%;object-fit:cover}
        .prof-nombre{font-size:17px;font-weight:900;color:#1a1a2e;margin-bottom:4px;cursor:pointer}
        .prof-nombre:hover{color:#7b2ff7}
        .prof-especialidad{font-size:13px;font-weight:700;color:#7b2ff7;margin-bottom:10px}
        .prof-desc{font-size:12px;color:#888;line-height:1.5;margin-bottom:10px}
        .prof-disponible{display:inline-block;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700;margin-bottom:10px}
        .disponible{background:#d1fae5;color:#059669}
        .proximamente{background:#fee2e2;color:#dc2626}
        .prof-rating{font-size:13px;color:#555;margin-bottom:8px}
        .estrellas-interactivas{display:flex;justify-content:center;gap:4px;margin-bottom:6px}
        .estrella-btn{background:none;border:none;font-size:22px;cursor:pointer;color:#d1d5db;transition:color 0.15s;padding:2px;line-height:1}
        .estrella-btn:hover,.estrella-btn.activa{color:#f59e0b}
        .rating-label{font-size:11px;color:#aaa;margin-bottom:8px;display:block}
        .horarios-disponibles{font-size:12px;color:#888;margin-bottom:8px}
        .horarios-disponibles span{color:#7b2ff7;font-weight:700}
        .btn-cita{width:100%;padding:11px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-size:14px;font-weight:800;font-family:inherit;cursor:pointer;transition:opacity 0.2s;margin-top:4px}
        .btn-cita:hover{opacity:0.9}
        .btn-cita:disabled{background:#e8e8e8;color:#aaa;cursor:not-allowed}
        .btn-cita-outline{width:100%;padding:11px;background:transparent;color:#7b2ff7;border:2px solid #7b2ff7;border-radius:10px;font-size:14px;font-weight:800;font-family:inherit;cursor:pointer;margin-top:6px}
        .btn-cita-outline:hover{background:#f3e8ff}
        .btn-eliminar-serv{flex:1;padding:11px;background:#fee2e2;color:#dc2626;border:none;border-radius:10px;font-size:13px;font-weight:800;font-family:inherit;cursor:pointer}
        .btn-eliminar-serv:hover{background:#fecaca}
        .btn-agregar-horarios{flex:1;padding:11px;background:#f3e8ff;color:#7b2ff7;border:2px solid #d8b4fe;border-radius:10px;font-size:13px;font-weight:800;font-family:inherit;cursor:pointer}
        .btn-agregar-horarios:hover{background:#e9d5ff}

        .sin-res{text-align:center;padding:40px;color:#aaa;grid-column:1/-1}
        .sin-res .icon{font-size:40px;margin-bottom:10px}

        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:200;align-items:center;justify-content:center;padding:20px}
        .modal-overlay.active{display:flex}
        .modal{background:white;border-radius:20px;padding:36px;width:100%;max-width:540px;max-height:92vh;overflow-y:auto}
        .modal h3{font-size:20px;font-weight:900;margin-bottom:6px;color:#1a1a2e}
        .modal-subtitle{font-size:13px;color:#888;margin-bottom:20px}
        .modal-close{float:right;background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;margin-top:-8px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-weight:700;font-size:13px;color:#333;margin-bottom:6px}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f9f9f9;color:#333;box-sizing:border-box}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#7b2ff7;background:white}
        .form-group textarea{height:90px;resize:none}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .modal-btns{display:flex;gap:10px;margin-top:16px}
        .btn-modal-cancel{flex:1;padding:12px;background:#f4f4f8;color:#555;border:none;border-radius:10px;font-weight:700;font-family:inherit;cursor:pointer}
        .btn-modal-send{flex:2;padding:12px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-weight:800;font-family:inherit;cursor:pointer}
        .btn-modal-danger{flex:2;padding:12px;background:#dc2626;color:white;border:none;border-radius:10px;font-weight:800;font-family:inherit;cursor:pointer}

        .horarios-list{display:flex;flex-direction:column;gap:8px;margin-bottom:14px}
        .horario-item{display:flex;gap:8px;align-items:center}
        .horario-item input[type="datetime-local"]{flex:1;padding:10px 12px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:13px;font-family:inherit;outline:none;background:#f9f9f9}
        .horario-item input:focus{border-color:#7b2ff7;background:white}
        .btn-remove-horario{background:#fee2e2;color:#dc2626;border:none;border-radius:8px;width:32px;height:32px;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .btn-add-horario{display:flex;align-items:center;gap:6px;background:#f3e8ff;color:#7b2ff7;border:none;border-radius:10px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;margin-bottom:14px}
        .horario-option{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;margin-bottom:8px;cursor:pointer;transition:border-color 0.2s,background 0.2s}
        .horario-option:has(input:checked){border-color:#7b2ff7;background:#faf5ff}
        .horario-option input[type="radio"]{accent-color:#7b2ff7;width:16px;height:16px;flex-shrink:0}
        .horario-option span{font-size:14px;font-weight:600;color:#333}

        .solicitud-item{background:#f9f9f9;border-radius:12px;padding:16px;margin-bottom:12px;border-left:4px solid #7b2ff7}
        .solicitud-item.aceptada{border-left-color:#10b981}
        .solicitud-item.rechazada{border-left-color:#ef4444}
        .solicitud-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
        .solicitud-usuario{display:flex;align-items:center;gap:10px}
        .sol-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#7b2ff7,#a855f7);display:flex;align-items:center;justify-content:center;font-size:16px;color:white;font-weight:800;overflow:hidden;flex-shrink:0;text-decoration:none;transition:transform 0.2s}
        .sol-avatar:hover{transform:scale(1.05)}
        .sol-avatar img{width:100%;height:100%;object-fit:cover}
        .solicitud-nombre{font-size:15px;font-weight:800;color:#7b2ff7;cursor:pointer}
        .solicitud-nombre:hover{text-decoration:underline}
        .solicitud-estado{padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700;display:inline-block;white-space:nowrap}
        .estado-pendiente{background:#fef3c7;color:#d97706}
        .estado-aceptada{background:#d1fae5;color:#059669}
        .estado-rechazada{background:#fee2e2;color:#dc2626}
        .solicitud-info{font-size:13px;color:#666;line-height:1.6}
        .solicitud-horario{font-size:13px;color:#7b2ff7;font-weight:700;margin-top:4px}
        .solicitud-acciones{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
        .btn-aceptar{flex:1;padding:9px;background:#d1fae5;color:#059669;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit}
        .btn-rechazar{flex:1;padding:9px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit}
        .btn-cancelar-sol{flex:1;padding:9px;background:#f4f4f8;color:#555;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit}
        .btn-chat-sol{flex:1;padding:9px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit;text-decoration:none;text-align:center;display:block}
        .sin-solicitudes{text-align:center;padding:30px;color:#aaa}

        .historial-item{background:#f9f9f9;border-radius:12px;padding:16px;margin-bottom:12px;border-left:4px solid #7b2ff7;display:flex;gap:14px;align-items:flex-start}
        .historial-item.aceptada{border-left-color:#10b981}
        .historial-item.rechazada{border-left-color:#ef4444}
        .historial-av{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#7b2ff7,#a855f7);display:flex;align-items:center;justify-content:center;font-size:18px;color:white;font-weight:800;overflow:hidden;flex-shrink:0;cursor:pointer;text-decoration:none}
        .historial-av img{width:100%;height:100%;object-fit:cover}
        .historial-info{flex:1}
        .historial-prof{font-size:15px;font-weight:800;color:#1a1a2e}
        .historial-serv{font-size:13px;color:#7b2ff7;font-weight:700}
        .historial-horario{font-size:13px;color:#555;margin-top:4px}
        .historial-fecha{font-size:12px;color:#aaa;margin-top:2px}
        .historial-acciones{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
        .btn-cambiar-horario{padding:8px 14px;background:#f3e8ff;color:#7b2ff7;border:2px solid #d8b4fe;border-radius:8px;font-weight:700;font-size:12px;cursor:pointer;font-family:inherit}
        .btn-cancelar-cita{padding:8px 14px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;font-weight:700;font-size:12px;cursor:pointer;font-family:inherit}

        .notif-box{position:fixed;bottom:24px;right:24px;z-index:999;max-width:360px}
        .notif-item{background:white;border-radius:14px;padding:16px 20px;margin-top:10px;box-shadow:0 4px 20px rgba(0,0,0,0.15);border-left:4px solid #f59e0b;display:flex;gap:12px;align-items:flex-start;animation:slideIn 0.3s ease}
        .notif-texto{flex:1;font-size:13px;color:#555;line-height:1.6}
        .notif-cerrar{background:none;border:none;font-size:18px;cursor:pointer;color:#aaa;flex-shrink:0}

        .hidden{display:none}
    </style>
</head>
<body>

<?php include("includes/navbar.php"); ?>

<?php if($msg==='servicio_publicado'): ?><div class="toast toast-exito">✅ ¡Servicio publicado!</div>
<?php elseif($msg==='cita_solicitada'): ?><div class="toast toast-exito">✅ ¡Solicitud enviada!</div>
<?php elseif($msg==='ya_solicitado'): ?><div class="toast toast-warning">⚠️ Ya tienes una solicitud pendiente.</div>
<?php elseif($msg==='error'): ?><div class="toast toast-error">❌ Ocurrió un error.</div><?php endif; ?>

<div class="asesorias-page">

    <div class="asesorias-hero">
        <h1>Asesorías <span>Profesionales</span></h1>
        <p>Encuentra apoyo con nuestros especialistas. Terapeutas y abogados comprometidos con la inclusión.</p>
    </div>

    <form method="GET" action="asesorias.php">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="search-bar-as">
            <div class="search-wrap-as">
                <span class="sicon">🔍</span>
                <input type="text" name="buscar" placeholder="Buscar por nombre o especialidad..." value="<?= htmlspecialchars($buscar) ?>">
            </div>
            <button type="submit" class="btn-buscar-as">Buscar</button>
            <?php if($buscar): ?><a href="asesorias.php?tab=<?= $tab ?>" class="limpiar-as">✕ Limpiar</a><?php endif; ?>
        </div>
    </form>

    <div class="top-bar">
        <div></div>
        <div class="top-bar-btns">
            <?php if($rol === 'profesional'): ?>
                <button class="btn-mis-solicitudes" onclick="abrirMisSolicitudes()">
                    📋 Mis solicitudes
                    <?php if($pendientes2 > 0): ?><span class="badge-count"><?= $pendientes2 ?></span><?php endif; ?>
                </button>
                <button class="btn-publicar-servicio" onclick="document.getElementById('modalPublicar').classList.add('active')">➕ Publicar servicio</button>
            <?php else: ?>
                <button class="btn-historial" onclick="abrirHistorial()">📅 Mis citas</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="tabs-row">
        <div class="tabs-wrap">
            <button class="tab-btn <?= $tab==='psicologica'?'active':'' ?>" onclick="cambiarTab('psicologica')">🧠 Asesoría Psicológica</button>
            <button class="tab-btn <?= $tab==='juridica'?'active':'' ?>"   onclick="cambiarTab('juridica')">⚖️ Asesoría Jurídica</button>
        </div>
    </div>

    <div id="tab-psicologica" class="<?= $tab==='psicologica'?'':'hidden' ?>">
        <div class="seccion-titulo">
            <h2>Psicólogos disponibles</h2>
            <p>Todos nuestros profesionales están certificados y tienen experiencia en casos de discriminación e inclusión.</p>
        </div>
        <div class="profesionales-grid">
            <?php if(!empty($servicios_psi)):
                foreach($servicios_psi as $s) renderServicio($s,$rol,$usuario_id,$pdo);
            else: ?>
                <div class="sin-res"><div class="icon"><?= $buscar?'🔍':'📭' ?></div><p><?= $buscar?'Sin resultados para "'.$buscar.'"':'No hay psicólogos registrados aún.' ?></p></div>
            <?php endif; ?>
        </div>
    </div>

    <div id="tab-juridica" class="<?= $tab==='juridica'?'':'hidden' ?>">
        <div class="seccion-titulo">
            <h2>Abogados disponibles</h2>
            <p>Todos nuestros profesionales están certificados y tienen experiencia en casos de discriminación e inclusión.</p>
        </div>
        <div class="profesionales-grid">
            <?php if(!empty($servicios_jur)):
                foreach($servicios_jur as $s) renderServicio($s,$rol,$usuario_id,$pdo);
            else: ?>
                <div class="sin-res"><div class="icon"><?= $buscar?'🔍':'📭' ?></div><p><?= $buscar?'Sin resultados para "'.$buscar.'"':'No hay abogados registrados aún.' ?></p></div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Modal publicar servicio -->
<?php if($rol==='profesional'): ?>
<div class="modal-overlay" id="modalPublicar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalPublicar').classList.remove('active')">✕</button>
        <h3>🩺 Publicar mi servicio</h3>
        <p class="modal-subtitle">Completa la información y agrega tus horarios disponibles.</p>
        <form action="acciones/publicar_servicio.php" method="POST">
            <div class="form-group"><label>Nombre / Título profesional</label><input type="text" name="nombre" value="<?= htmlspecialchars($_SESSION['usuario']) ?>" required></div>
            <div class="form-group"><label>Especialidad</label><input type="text" name="especialidad" placeholder="Ej: Psicología Clínica" required></div>
            <div class="form-group"><label>Tipo de asesoría</label>
                <select name="tipo" required>
                    <option value="psicologica">🧠 Asesoría Psicológica</option>
                    <option value="juridica">⚖️ Asesoría Jurídica</option>
                </select>
            </div>
            <div class="form-group"><label>Descripción</label><textarea name="descripcion" placeholder="Cuéntales sobre tu experiencia..."></textarea></div>
            <div class="form-group">
                <label>📅 Horarios disponibles</label>
                <div class="horarios-list" id="horariosList">
                    <div class="horario-item">
                        <input type="datetime-local" name="horarios[]" required>
                        <button type="button" class="btn-remove-horario" onclick="quitarHorario(this)">✕</button>
                    </div>
                </div>
                <button type="button" class="btn-add-horario" onclick="agregarHorario()">➕ Agregar otro horario</button>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-modal-cancel" onclick="document.getElementById('modalPublicar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-modal-send">Publicar servicio</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal mis solicitudes -->
<div class="modal-overlay" id="modalMisSolicitudes">
    <div class="modal" style="max-width:660px">
        <button class="modal-close" onclick="document.getElementById('modalMisSolicitudes').classList.remove('active')">✕</button>
        <h3>📋 Mis solicitudes de cita</h3>
        <p class="modal-subtitle">Gestiona las citas de tus pacientes o clientes.</p>
        <div id="listaSolicitudes"><div style="text-align:center;padding:20px;color:#aaa">Cargando...</div></div>
    </div>
</div>

<!-- Modal agregar horarios -->
<div class="modal-overlay" id="modalAgregarHorarios">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalAgregarHorarios').classList.remove('active')">✕</button>
        <h3>📅 Agregar horarios</h3>
        <p class="modal-subtitle">Agrega nuevos horarios disponibles a tu servicio.</p>
        <input type="hidden" id="ah-servicio-id">
        <div class="form-group">
            <label>Nuevos horarios disponibles</label>
            <div class="horarios-list" id="horariosAgregar">
                <div class="horario-item">
                    <input type="datetime-local" name="nuevos_horarios[]" required>
                    <button type="button" class="btn-remove-horario" onclick="quitarHorarioAgregar(this)">✕</button>
                </div>
            </div>
            <button type="button" class="btn-add-horario" onclick="agregarHorarioNuevo()">➕ Agregar otro</button>
        </div>
        <div class="modal-btns">
            <button type="button" class="btn-modal-cancel" onclick="document.getElementById('modalAgregarHorarios').classList.remove('active')">Cancelar</button>
            <button type="button" class="btn-modal-send" onclick="guardarNuevosHorarios()">Guardar horarios</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal solicitar cita -->
<div class="modal-overlay" id="modalSolicitar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalSolicitar').classList.remove('active')">✕</button>
        <h3>📅 Solicitar cita</h3>
        <p class="modal-subtitle" id="solicitar-prof-nombre"></p>
        <form action="acciones/solicitar_cita.php" method="POST">
            <input type="hidden" name="servicio_id" id="solicitar-servicio-id">
            <div class="form-group"><label>Selecciona un horario</label><div id="horariosDisponibles"></div></div>
            <div class="form-row">
                <div class="form-group"><label>Nombre completo</label><input type="text" name="nombre_solicitante" value="<?= htmlspecialchars($_SESSION['usuario']??'') ?>" required></div>
                <div class="form-group"><label>Celular</label><input type="tel" name="celular" placeholder="300 000 0000" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Cédula</label><input type="text" name="cedula" placeholder="123456789" required></div>
                <div class="form-group"><label>Edad</label><input type="number" name="edad" placeholder="25" min="5" max="120" required></div>
            </div>
            <div class="form-group"><label>Mensaje (opcional)</label><textarea name="mensaje" placeholder="Cuéntale el motivo de tu consulta..."></textarea></div>
            <div class="modal-btns">
                <button type="button" class="btn-modal-cancel" onclick="document.getElementById('modalSolicitar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-modal-send">Enviar solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal cambiar horario -->
<div class="modal-overlay" id="modalCambiarHorario">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalCambiarHorario').classList.remove('active')">✕</button>
        <h3>🔄 Cambiar horario</h3>
        <p class="modal-subtitle">Selecciona un nuevo horario disponible.</p>
        <input type="hidden" id="ch-solicitud-id">
        <input type="hidden" id="ch-servicio-id">
        <div class="form-group"><label>Horarios disponibles</label><div id="horariosNuevos">Cargando...</div></div>
        <div class="modal-btns">
            <button type="button" class="btn-modal-cancel" onclick="document.getElementById('modalCambiarHorario').classList.remove('active')">Cancelar</button>
            <button type="button" class="btn-modal-send" onclick="confirmarCambioHorario()">Confirmar cambio</button>
        </div>
    </div>
</div>

<!-- Modal cancelar cita -->
<div class="modal-overlay" id="modalCancelar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalCancelar').classList.remove('active')">✕</button>
        <h3>❌ Cancelar cita</h3>
        <p class="modal-subtitle">Cuéntanos por qué deseas cancelar esta cita.</p>
        <input type="hidden" id="cancelar-solicitud-id">
        <div class="form-group">
            <label>Motivo de cancelación</label>
            <textarea id="cancelar-motivo" placeholder="Ej: Tengo un compromiso a esa hora..."></textarea>
        </div>
        <div class="modal-btns">
            <button type="button" class="btn-modal-cancel" onclick="document.getElementById('modalCancelar').classList.remove('active')">Volver</button>
            <button type="button" class="btn-modal-danger" onclick="confirmarCancelacion()">Sí, cancelar cita</button>
        </div>
    </div>
</div>

<!-- Modal historial citas -->
<?php if($rol !== 'profesional'): ?>
<div class="modal-overlay" id="modalHistorial">
    <div class="modal" style="max-width:620px">
        <button class="modal-close" onclick="document.getElementById('modalHistorial').classList.remove('active')">✕</button>
        <h3>📅 Historial de mis citas</h3>
        <p class="modal-subtitle">Todas tus solicitudes, pasadas y actuales.</p>
        <div id="listaHistorial">
        <?php if(empty($mis_solicitudes)): ?>
            <div class="sin-solicitudes"><div style="font-size:36px;margin-bottom:10px">📭</div><p>No has solicitado citas aún.</p></div>
        <?php else: foreach($mis_solicitudes as $sc): ?>
            <div class="historial-item <?= $sc['estado'] ?>" id="hist-<?= $sc['id'] ?>">
                <a href="perfil.php?id=<?= $sc['prof_usuario_id'] ?>" class="historial-av">
                    <?php if(!empty($sc['foto_perfil'])): ?>
                        <img src="uploads/<?= htmlspecialchars($sc['foto_perfil']) ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($sc['prof_nombre'],0,1)) ?>
                    <?php endif; ?>
                </a>
                <div class="historial-info">
                    <div class="historial-prof"><?= htmlspecialchars($sc['prof_nombre']) ?></div>
                    <div class="historial-serv"><?= htmlspecialchars($sc['servicio_nombre']) ?> — <?= htmlspecialchars($sc['especialidad']) ?></div>
                    <div class="historial-horario">📅 <?= $sc['dia_hora_fmt'] ?></div>
                    <div class="historial-fecha">Solicitado: <?= date('d/m/Y', strtotime($sc['fecha'])) ?></div>
                    <span class="solicitud-estado estado-<?= $sc['estado'] ?>" id="hist-estado-<?= $sc['id'] ?>">
                        <?= $sc['estado']==='pendiente'?'⏳ Pendiente':($sc['estado']==='aceptada'?'✅ Aceptada':'❌ Rechazada') ?>
                    </span>
                    <?php if($sc['estado']==='pendiente'): ?>
                    <div class="historial-acciones" id="hist-acc-<?= $sc['id'] ?>">
                        <button class="btn-cambiar-horario" onclick="abrirCambiarHorario(<?= $sc['id'] ?>,<?= $sc['serv_id'] ?>)">🔄 Cambiar horario</button>
                        <button class="btn-cancelar-cita" onclick="abrirCancelar(<?= $sc['id'] ?>)">❌ Cancelar cita</button>
                    </div>
                    <?php elseif($sc['estado']==='aceptada'): ?>
                    <div class="historial-acciones">
                        <a href="chat.php?con=<?= $sc['prof_usuario_id'] ?>" style="padding:8px 14px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border-radius:8px;font-weight:700;font-size:12px;text-decoration:none">💬 Chatear</a>
                        <button class="btn-cancelar-cita" onclick="abrirCancelar(<?= $sc['id'] ?>)">❌ Cancelar cita</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
        </div>
        <div class="modal-btns" style="margin-top:10px">
            <button class="btn-modal-cancel" onclick="document.getElementById('modalHistorial').classList.remove('active')">Cerrar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Notificaciones profesional -->
<?php if(!empty($notifs_list)): ?>
<div class="notif-box" id="notifBox">
    <?php foreach($notifs_list as $n): ?>
    <div class="notif-item" id="notif-<?= $n['id'] ?>">
        <div class="notif-texto"><?= htmlspecialchars($n['mensaje']) ?></div>
        <button class="notif-cerrar" onclick="marcarLeida(<?= $n['id'] ?>)">✕</button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<footer class="footer">© 2026 EquiRed. Conectando oportunidades, construyendo igualdad.</footer>

<script>
function cambiarTab(tab){
    document.getElementById('tab-psicologica').classList.toggle('hidden',tab!=='psicologica');
    document.getElementById('tab-juridica').classList.toggle('hidden',tab!=='juridica');
    document.querySelectorAll('.tab-btn').forEach((b,i)=>b.classList.toggle('active',(i===0&&tab==='psicologica')||(i===1&&tab==='juridica')));
}

function agregarHorario(){
    const list=document.getElementById('horariosList');
    const div=document.createElement('div'); div.className='horario-item';
    div.innerHTML='<input type="datetime-local" name="horarios[]" required><button type="button" class="btn-remove-horario" onclick="quitarHorario(this)">✕</button>';
    list.appendChild(div);
}
function quitarHorario(btn){const list=document.getElementById('horariosList');if(list.children.length>1)btn.parentElement.remove();}

function abrirSolicitar(servicioId,profNombre,horarios){
    document.getElementById('solicitar-servicio-id').value=servicioId;
    document.getElementById('solicitar-prof-nombre').textContent='🩺 '+profNombre;
    const cont=document.getElementById('horariosDisponibles'); cont.innerHTML='';
    if(horarios.length===0){cont.innerHTML='<p style="color:#aaa;font-size:13px">No hay horarios disponibles.</p>';return;}
    horarios.forEach(h=>{
        const fecha=new Date(h.dia_hora).toLocaleString('es-CO',{weekday:'long',year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'});
        cont.innerHTML+=`<label class="horario-option"><input type="radio" name="horario_id" value="${h.id}" required><span>📅 ${fecha}</span></label>`;
    });
    document.getElementById('modalSolicitar').classList.add('active');
}

// ══ Mis solicitudes profesional ══
function abrirMisSolicitudes(){
    document.getElementById('modalMisSolicitudes').classList.add('active');
    fetch('acciones/obtener_solicitudes.php').then(r=>r.json()).then(data=>{
        const cont=document.getElementById('listaSolicitudes');
        if(data.length===0){
            cont.innerHTML='<div class="sin-solicitudes"><div style="font-size:36px;margin-bottom:10px">📭</div><p>No tienes solicitudes aún.</p></div>';
            return;
        }
        cont.innerHTML=data.map(s=>`
            <div class="solicitud-item ${s.estado}" id="sol-${s.id}">
                <div class="solicitud-header">
                    <div class="solicitud-usuario">
                        <a href="perfil.php?id=${s.usuario_id}" class="sol-avatar">
                            ${s.foto_perfil
                                ? `<img src="uploads/${s.foto_perfil}" alt="">`
                                : s.nombre_solicitante.charAt(0).toUpperCase()
                            }
                        </a>
                        <div class="solicitud-nombre" onclick="window.location.href='perfil.php?id=${s.usuario_id}'">
                            ${s.nombre_solicitante}
                        </div>
                    </div>
                    <span class="solicitud-estado estado-${s.estado}" id="sol-estado-${s.id}">
                        ${s.estado==='pendiente'?'⏳ Pendiente':s.estado==='aceptada'?'✅ Aceptada':'❌ Rechazada'}
                    </span>
                </div>
                <div class="solicitud-info">
                    📞 ${s.celular} &nbsp;·&nbsp; 🪪 ${s.cedula} &nbsp;·&nbsp; 🎂 ${s.edad} años<br>
                    📋 <strong>${s.servicio_nombre}</strong> — ${s.especialidad}
                    ${s.mensaje ? '<br>💬 ' + s.mensaje : ''}
                </div>
                <div class="solicitud-horario">📅 ${s.dia_hora}</div>
                <div class="solicitud-acciones" id="sol-acc-${s.id}">
                    ${s.estado==='pendiente' ? `
                        <button class="btn-aceptar"  onclick="responderSolicitud(${s.id},'aceptada')">✅ Aceptar</button>
                        <button class="btn-rechazar" onclick="responderSolicitud(${s.id},'rechazada')">❌ Rechazar</button>
                    ` : ''}
                    ${s.estado==='aceptada' ? `
                        <button class="btn-cancelar-sol" onclick="cancelarSolicitudProf(${s.id})">🚫 Cancelar</button>
                    ` : ''}
                    <a href="chat.php?con=${s.usuario_id}" class="btn-chat-sol">💬 Chatear</a>
                </div>
            </div>
        `).join('');
    });
}

function responderSolicitud(id,estado){
    fetch('acciones/responder_solicitud.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`solicitud_id=${id}&estado=${estado}`})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            document.getElementById('sol-'+id).className='solicitud-item '+estado;
            document.getElementById('sol-estado-'+id).className='solicitud-estado estado-'+estado;
            document.getElementById('sol-estado-'+id).textContent=estado==='aceptada'?'✅ Aceptada':'❌ Rechazada';
            const acc=document.getElementById('sol-acc-'+id);
            const chatBtn=acc.querySelector('a.btn-chat-sol');
            acc.innerHTML=estado==='aceptada'?`<button class="btn-cancelar-sol" onclick="cancelarSolicitudProf(${id})">🚫 Cancelar</button>`:'';
            if(chatBtn) acc.appendChild(chatBtn);
        }
    });
}

function cancelarSolicitudProf(id){
    if(!confirm('¿Seguro que deseas cancelar esta cita?'))return;
    fetch('acciones/cancelar_solicitud.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`solicitud_id=${id}`})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            document.getElementById('sol-'+id).className='solicitud-item rechazada';
            document.getElementById('sol-estado-'+id).className='solicitud-estado estado-rechazada';
            document.getElementById('sol-estado-'+id).textContent='❌ Rechazada';
            document.getElementById('sol-acc-'+id).innerHTML='';
        }
    });
}

function abrirHistorial(){document.getElementById('modalHistorial').classList.add('active');}

function abrirCancelar(id){
    document.getElementById('modalHistorial').classList.remove('active');
    document.getElementById('cancelar-solicitud-id').value=id;
    document.getElementById('cancelar-motivo').value='';
    document.getElementById('modalCancelar').classList.add('active');
}

function confirmarCancelacion(){
    const id=document.getElementById('cancelar-solicitud-id').value;
    const motivo=document.getElementById('cancelar-motivo').value.trim();
    if(!motivo){alert('Por favor escribe el motivo de cancelación');return;}
    fetch('acciones/cancelar_solicitud.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`solicitud_id=${id}&motivo=${encodeURIComponent(motivo)}`})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            document.getElementById('modalCancelar').classList.remove('active');
            const item=document.getElementById('hist-'+id);
            if(item) item.remove();
        }
    });
}

function abrirCambiarHorario(solicitudId,servicioId){
    document.getElementById('modalHistorial').classList.remove('active');
    document.getElementById('ch-solicitud-id').value=solicitudId;
    document.getElementById('ch-servicio-id').value=servicioId;
    const cont=document.getElementById('horariosNuevos'); cont.innerHTML='Cargando...';
    document.getElementById('modalCambiarHorario').classList.add('active');
    fetch(`acciones/obtener_horarios_disponibles.php?servicio_id=${servicioId}`)
    .then(r=>r.json()).then(data=>{
        if(data.length===0){cont.innerHTML='<p style="color:#aaa;font-size:13px">No hay otros horarios disponibles.</p>';return;}
        cont.innerHTML=data.map(h=>{
            const fecha=new Date(h.dia_hora).toLocaleString('es-CO',{weekday:'long',year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'});
            return `<label class="horario-option"><input type="radio" name="nuevo_horario" value="${h.id}" required><span>📅 ${fecha}</span></label>`;
        }).join('');
    });
}

function confirmarCambioHorario(){
    const solicitudId=document.getElementById('ch-solicitud-id').value;
    const sel=document.querySelector('input[name="nuevo_horario"]:checked');
    if(!sel){alert('Selecciona un horario');return;}
    fetch('acciones/cambiar_horario.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`solicitud_id=${solicitudId}&nuevo_horario_id=${sel.value}`})
    .then(r=>r.json()).then(d=>{if(d.ok){document.getElementById('modalCambiarHorario').classList.remove('active');alert('✅ Horario cambiado');location.reload();}});
}

// Agregar horarios a servicio
function abrirAgregarHorarios(servicioId){
    document.getElementById('ah-servicio-id').value=servicioId;
    document.getElementById('horariosAgregar').innerHTML=`
        <div class="horario-item">
            <input type="datetime-local" name="nuevos_horarios[]" required>
            <button type="button" class="btn-remove-horario" onclick="quitarHorarioAgregar(this)">✕</button>
        </div>`;
    document.getElementById('modalAgregarHorarios').classList.add('active');
}
function agregarHorarioNuevo(){
    const list=document.getElementById('horariosAgregar');
    const div=document.createElement('div'); div.className='horario-item';
    div.innerHTML='<input type="datetime-local" name="nuevos_horarios[]" required><button type="button" class="btn-remove-horario" onclick="quitarHorarioAgregar(this)">✕</button>';
    list.appendChild(div);
}
function quitarHorarioAgregar(btn){
    const list=document.getElementById('horariosAgregar');
    if(list.children.length>1) btn.parentElement.remove();
}
function guardarNuevosHorarios(){
    const servicioId=document.getElementById('ah-servicio-id').value;
    const inputs=document.querySelectorAll('#horariosAgregar input[type="datetime-local"]');
    const horarios=Array.from(inputs).map(i=>i.value).filter(v=>v!=='');
    if(horarios.length===0){alert('Agrega al menos un horario');return;}
    fetch('acciones/agregar_horarios.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`servicio_id=${servicioId}&horarios=${encodeURIComponent(JSON.stringify(horarios))}`})
    .then(r=>r.json()).then(d=>{
        if(d.ok){document.getElementById('modalAgregarHorarios').classList.remove('active');alert('✅ Horarios agregados');location.reload();}
        else alert('Error al guardar los horarios');
    });
}

function eliminarServicio(servicioId){
    if(!confirm('¿Seguro que deseas eliminar este servicio?'))return;
    fetch('acciones/eliminar_servicio.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`servicio_id=${servicioId}`})
    .then(r=>r.json()).then(d=>{if(d.ok)document.getElementById('card-serv-'+servicioId).remove();else alert('No se pudo eliminar.');});
}

function marcarLeida(id){
    fetch('acciones/marcar_notificacion.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`notificacion_id=${id}`})
    .then(r=>r.json()).then(d=>{if(d.ok)document.getElementById('notif-'+id).remove();});
}

function hoverEstrella(sid,n){for(let i=1;i<=5;i++){const e=document.getElementById(`est-${sid}-${i}`);if(e)e.textContent=i<=n?'★':'☆';}}
function resetEstrellas(sid,actual){for(let i=1;i<=5;i++){const e=document.getElementById(`est-${sid}-${i}`);if(e)e.textContent=i<=actual?'★':'☆';}}
function calificar(sid,estrellas){
    fetch('acciones/calificar_servicio.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`servicio_id=${sid}&estrellas=${estrellas}`})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const label=document.getElementById(`rating-label-${sid}`);
            if(label)label.textContent=`⭐ ${d.promedio} (${d.total} calificaciones)`;
            for(let i=1;i<=5;i++){const e=document.getElementById(`est-${sid}-${i}`);if(e){e.textContent=i<=estrellas?'★':'☆';e.classList.toggle('activa',i<=estrellas);}}
        }
    });
}

setTimeout(()=>{const t=document.querySelector('.toast');if(t)t.style.display='none';},3500);
</script>

<?php
function renderServicio($s,$rol,$usuario_id,$pdo){
    $disponible=$s['horarios_disponibles']>0;
    $foto=!empty($s['foto_perfil'])?"uploads/{$s['foto_perfil']}":null;
    $prof_url="perfil.php?id={$s['prof_usuario_id']}";
    $promedio=$s['promedio_estrellas']??0;
    $total_cal=$s['total_calificaciones']??0;
    $horarios=[];
    if($disponible){
        $h=$pdo->prepare("SELECT * FROM horarios WHERE servicio_id=? AND disponible=1 ORDER BY dia_hora ASC");
        $h->execute([$s['id']]); $horarios=$h->fetchAll();
    }
    $horariosJson=json_encode($horarios);
    $es_mio=($rol==='profesional'&&$s['profesional_id']==$usuario_id);
    ?>
    <div class="prof-card" id="card-serv-<?= $s['id'] ?>">
        <a href="<?= $prof_url ?>" class="prof-avatar">
            <?php if($foto): ?><img src="<?= htmlspecialchars($foto) ?>" alt=""><?php else: ?>🩺<?php endif; ?>
        </a>
        <div class="prof-nombre" onclick="window.location.href='<?= $prof_url ?>'"><?= htmlspecialchars($s['nombre']) ?></div>
        <div class="prof-especialidad"><?= htmlspecialchars($s['especialidad']) ?></div>
        <span class="prof-disponible <?= $disponible?'disponible':'proximamente' ?>"><?= $disponible?'Disponible':'Sin horarios' ?></span>
        <?php if(!empty($s['descripcion'])): ?>
            <div class="prof-desc"><?= htmlspecialchars(substr($s['descripcion'],0,80)) ?>...</div>
        <?php endif; ?>
        <div class="prof-rating">
            <span id="rating-label-<?= $s['id'] ?>"><?= $total_cal>0?"⭐ {$promedio} ({$total_cal} calificaciones)":'Sin calificaciones aún' ?></span>
        </div>
        <?php if($rol!=='profesional'): ?>
        <div class="estrellas-interactivas">
            <?php for($i=1;$i<=5;$i++): ?>
            <button class="estrella-btn <?= $i<=$promedio?'activa':'' ?>" id="est-<?= $s['id'] ?>-<?= $i ?>"
                onmouseover="hoverEstrella(<?= $s['id'] ?>,<?= $i ?>)"
                onmouseout="resetEstrellas(<?= $s['id'] ?>,<?= round($promedio) ?>)"
                onclick="calificar(<?= $s['id'] ?>,<?= $i ?>)"><?= $i<=$promedio?'★':'☆' ?></button>
            <?php endfor; ?>
        </div>
        <span class="rating-label">Toca para calificar</span>
        <?php endif; ?>
        <div class="horarios-disponibles"><span><?= $s['horarios_disponibles']??0 ?></span> horarios disponibles</div>
        <?php if($disponible&&$rol!=='profesional'&&$usuario_id): ?>
            <button class="btn-cita" onclick='abrirSolicitar(<?= $s["id"] ?>,"<?= htmlspecialchars($s["nombre"],ENT_QUOTES) ?>",<?= $horariosJson ?>)'>Solicitar cita</button>
        <?php elseif($es_mio): ?>
            <button class="btn-cita-outline" onclick="abrirMisSolicitudes()">Ver mis solicitudes</button>
            <div style="display:flex;gap:8px;margin-top:8px">
                <button class="btn-agregar-horarios" onclick="abrirAgregarHorarios(<?= $s['id'] ?>)">➕ Horarios</button>
                <button class="btn-eliminar-serv" onclick="eliminarServicio(<?= $s['id'] ?>)">🗑️ Eliminar</button>
            </div>
        <?php else: ?>
            <button class="btn-cita" disabled>Sin horarios</button>
        <?php endif; ?>
    </div>
    <?php
}
?>
</body>
</html>