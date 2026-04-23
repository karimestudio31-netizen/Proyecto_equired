<?php
session_start();
require_once("config/conexion.php");

$tab        = $_GET['tab'] ?? 'psicologica';
$rol        = $_SESSION['rol'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? null;
$msg        = $_GET['msg'] ?? '';

// Servicios reales de BD
$stmt = $pdo->prepare("
    SELECT s.*, u.nombre AS prof_nombre, u.foto_perfil,
        COUNT(h.id) AS total_horarios,
        SUM(CASE WHEN h.disponible=1 THEN 1 ELSE 0 END) AS horarios_disponibles
    FROM servicios s
    JOIN usuarios u ON s.profesional_id = u.id
    LEFT JOIN horarios h ON h.servicio_id = s.id
    WHERE s.tipo = ?
    GROUP BY s.id
    ORDER BY s.fecha DESC
");
$stmt->execute([$tab]);
$servicios_bd = $stmt->fetchAll();

// Pendientes del profesional (para badge)
$pendientes = 0;
if($rol === 'profesional' && $usuario_id) {
    $sp = $pdo->prepare("SELECT COUNT(*) FROM solicitudes_cita sc JOIN servicios s ON sc.servicio_id=s.id WHERE s.profesional_id=? AND sc.estado='pendiente'");
    $sp->execute([$usuario_id]);
    $pendientes = (int)$sp->fetchColumn();
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
        .asesorias-page{max-width:1000px;margin:0 auto;padding:40px 20px}
        .asesorias-hero{text-align:center;margin-bottom:32px}
        .asesorias-hero h1{font-size:36px;font-weight:900;color:#1a1a2e}
        .asesorias-hero h1 span{color:#7b2ff7}
        .asesorias-hero p{color:#777;font-size:15px;margin-top:10px;max-width:560px;margin-inline:auto;line-height:1.6}

        /* Toast */
        .toast{position:fixed;top:80px;right:24px;z-index:999;padding:14px 22px;border-radius:12px;font-size:15px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,0.15);animation:slideIn 0.3s ease,fadeOut 0.4s ease 3.5s forwards}
        .toast-exito{background:#d1fae5;color:#059669;border:1px solid #a7f3d0}
        .toast-warning{background:#fef3c7;color:#d97706}
        .toast-error{background:#fee2e2;color:#dc2626}
        @keyframes slideIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes fadeOut{from{opacity:1}to{opacity:0;visibility:hidden}}

        /* Top bar */
        .top-bar{display:flex;justify-content:flex-end;gap:10px;margin-bottom:28px;flex-wrap:wrap}
        .btn-publicar-servicio{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;padding:11px 22px;border-radius:10px;font-weight:800;font-size:14px;border:none;cursor:pointer;font-family:inherit;transition:opacity 0.2s}
        .btn-publicar-servicio:hover{opacity:0.9}
        .btn-mis-solicitudes{display:inline-flex;align-items:center;gap:8px;background:#f3e8ff;color:#7b2ff7;padding:11px 22px;border-radius:10px;font-weight:800;font-size:14px;border:2px solid #d8b4fe;cursor:pointer;font-family:inherit;transition:background 0.2s;position:relative}
        .btn-mis-solicitudes:hover{background:#e9d5ff}
        .badge-count{position:absolute;top:-8px;right:-8px;background:#ef4444;color:white;border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center}

        /* Tabs */
        .tabs-row{display:flex;justify-content:center;margin-bottom:36px}
        .tabs-wrap{background:#f0e8ff;border-radius:50px;padding:5px;display:inline-flex;gap:4px}
        .tab-btn{padding:10px 24px;border-radius:50px;border:none;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;background:transparent;color:#888;transition:all 0.2s;display:flex;align-items:center;gap:6px}
        .tab-btn.active{background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white}

        .seccion-titulo{margin-bottom:20px}
        .seccion-titulo h2{font-size:22px;font-weight:900;color:#1a1a2e}
        .seccion-titulo p{font-size:14px;color:#888;margin-top:4px}

        /* Grid profesionales */
        .profesionales-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:30px}
        .prof-card{background:white;border-radius:16px;padding:26px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);text-align:center;border:1.5px solid #f0f0f0;transition:border-color 0.2s,box-shadow 0.2s;position:relative}
        .prof-card:hover{border-color:#7b2ff7;box-shadow:0 4px 20px rgba(123,47,247,0.1)}
        .prof-avatar{width:70px;height:70px;background:#f3e8ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;margin:0 auto 14px;overflow:hidden}
        .prof-avatar img{width:100%;height:100%;object-fit:cover}
        .prof-nombre{font-size:17px;font-weight:900;color:#1a1a2e;margin-bottom:4px}
        .prof-especialidad{font-size:13px;font-weight:700;color:#7b2ff7;margin-bottom:10px}
        .prof-desc{font-size:12px;color:#888;line-height:1.5;margin-bottom:10px}
        .prof-disponible{display:inline-block;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700;margin-bottom:10px}
        .disponible{background:#d1fae5;color:#059669}
        .proximamente{background:#fee2e2;color:#dc2626}
        .prof-rating{font-size:13px;color:#555;margin-bottom:12px}
        .prof-tags{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-bottom:16px}
        .prof-tag{padding:4px 10px;background:#f4f4f8;color:#555;border-radius:50px;font-size:11px;font-weight:600}
        .horarios-disponibles{font-size:12px;color:#888;margin-bottom:8px}
        .horarios-disponibles span{color:#7b2ff7;font-weight:700}

        .btn-cita{width:100%;padding:11px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-size:14px;font-weight:800;font-family:inherit;cursor:pointer;transition:opacity 0.2s}
        .btn-cita:hover{opacity:0.9}
        .btn-cita:disabled{background:#e8e8e8;color:#aaa;cursor:not-allowed}
        .btn-cita-outline{width:100%;padding:11px;background:transparent;color:#7b2ff7;border:2px solid #7b2ff7;border-radius:10px;font-size:14px;font-weight:800;font-family:inherit;cursor:pointer;margin-top:8px;transition:background 0.2s}
        .btn-cita-outline:hover{background:#f3e8ff}

        /* Botón eliminar en tarjeta de servicio */
        .btn-del-servicio{position:absolute;top:12px;right:12px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;padding:5px 10px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:background 0.2s}
        .btn-del-servicio:hover{background:#fecaca}

        /* Modales */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:200;align-items:center;justify-content:center;padding:20px}
        .modal-overlay.active{display:flex}
        .modal{background:white;border-radius:20px;padding:36px;width:100%;max-width:540px;max-height:92vh;overflow-y:auto}
        .modal h3{font-size:20px;font-weight:900;margin-bottom:6px;color:#1a1a2e}
        .modal-sub{font-size:13px;color:#888;margin-bottom:20px}
        .modal-close{float:right;background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;margin-top:-8px}
        .fg{margin-bottom:16px}
        .fg label{display:block;font-weight:700;font-size:13px;color:#333;margin-bottom:6px}
        .fg input,.fg select,.fg textarea{width:100%;padding:11px 14px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f9f9f9}
        .fg input:focus,.fg select:focus,.fg textarea:focus{border-color:#7b2ff7;background:white}
        .fg textarea{height:80px;resize:none}
        .fr{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .modal-btns{display:flex;gap:10px;margin-top:14px}
        .bmc{flex:1;padding:12px;background:#f4f4f8;color:#555;border:none;border-radius:10px;font-weight:700;font-family:inherit;cursor:pointer}
        .bms{flex:2;padding:12px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-weight:800;font-family:inherit;cursor:pointer}
        .bmd{flex:2;padding:12px;background:#dc2626;color:white;border:none;border-radius:10px;font-weight:800;font-family:inherit;cursor:pointer}
        .horarios-list{display:flex;flex-direction:column;gap:8px;margin-bottom:14px}
        .horario-item{display:flex;gap:8px;align-items:center}
        .horario-item input[type="datetime-local"]{flex:1;padding:10px 12px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:13px;font-family:inherit;outline:none;background:#f9f9f9}
        .horario-item input:focus{border-color:#7b2ff7;background:white}
        .btn-remove-horario{background:#fee2e2;color:#dc2626;border:none;border-radius:8px;width:32px;height:32px;font-size:16px;cursor:pointer;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .btn-add-horario{display:flex;align-items:center;gap:6px;background:#f3e8ff;color:#7b2ff7;border:none;border-radius:10px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;margin-bottom:14px}
        .horario-option{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;margin-bottom:8px;cursor:pointer;transition:border-color 0.2s,background 0.2s}
        .horario-option:has(input:checked){border-color:#7b2ff7;background:#faf5ff}
        .horario-option input[type="radio"]{accent-color:#7b2ff7;width:16px;height:16px;flex-shrink:0}
        .horario-option span{font-size:14px;font-weight:600;color:#333}
        .solicitud-item{background:#f9f9f9;border-radius:12px;padding:16px;margin-bottom:12px;border-left:4px solid #7b2ff7}
        .solicitud-item.aceptada{border-left-color:#10b981}
        .solicitud-item.rechazada{border-left-color:#ef4444}
        .solicitud-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px}
        .solicitud-nombre{font-size:15px;font-weight:800;color:#1a1a2e}
        .solicitud-estado{padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700}
        .solicitud-info{font-size:13px;color:#666;line-height:1.6}
        .solicitud-horario{font-size:13px;color:#7b2ff7;font-weight:700;margin-top:4px}
        .solicitud-acciones{display:flex;gap:8px;margin-top:10px}
        .btn-aceptar{flex:1;padding:8px;background:#d1fae5;color:#059669;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit}
        .btn-rechazar{flex:1;padding:8px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit}
        .estado-pendiente{background:#fef3c7;color:#d97706}
        .estado-aceptada{background:#d1fae5;color:#059669}
        .estado-rechazada{background:#fee2e2;color:#dc2626}
        .ns{text-align:center;padding:30px;color:#aaa}
        .hidden{display:none}
    </style>
</head>
<body>

<?php include("includes/navbar.php"); ?>

<!-- Toasts -->
<?php if($msg==='servicio_publicado'): ?><div class="toast toast-exito">✅ ¡Servicio publicado exitosamente!</div>
<?php elseif($msg==='servicio_eliminado'): ?><div class="toast toast-exito">🗑️ Servicio eliminado.</div>
<?php elseif($msg==='cita_solicitada'): ?><div class="toast toast-exito">✅ ¡Solicitud enviada al profesional!</div>
<?php elseif($msg==='ya_solicitado'): ?><div class="toast toast-warning">⚠️ Ya tienes una solicitud pendiente.</div>
<?php elseif($msg==='error'): ?><div class="toast toast-error">❌ Ocurrió un error.</div><?php endif; ?>

<div class="asesorias-page">

    <div class="asesorias-hero">
        <h1>Asesorías <span>Profesionales</span></h1>
        <p>Encuentra apoyo con nuestros especialistas. Terapeutas y abogados comprometidos brindando asesoría profesional orientada a personas en situación vulnerable.</p>
    </div>

    <!-- Botones solo para profesionales -->
    <?php if($rol === 'profesional'): ?>
    <div class="top-bar">
        <button class="btn-mis-solicitudes" onclick="abrirMisSolicitudes()">
            📋 Mis solicitudes
            <?php if($pendientes > 0): ?><span class="badge-count"><?= $pendientes ?></span><?php endif; ?>
        </button>
        <button class="btn-publicar-servicio" onclick="document.getElementById('mPublicar').classList.add('active')">
            ➕ Publicar servicio
        </button>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs-row">
        <div class="tabs-wrap">
            <button class="tab-btn <?= $tab==='psicologica'?'active':'' ?>" onclick="cambiarTab('psicologica')">🧠 Asesoría Psicológica</button>
            <button class="tab-btn <?= $tab==='juridica'?'active':'' ?>" onclick="cambiarTab('juridica')">⚖️ Asesoría Jurídica</button>
        </div>
    </div>

    <!-- ── Tab Psicológica ── -->
    <div id="tab-psicologica" class="<?= $tab==='psicologica'?'':'hidden' ?>">
        <div class="seccion-titulo">
            <h2>Psicólogos disponibles</h2>
            <p>Todos nuestros profesionales están certificados y tienen experiencia en casos de discriminación e inclusión.</p>
        </div>
        <div class="profesionales-grid">

            <!-- Servicios reales de BD tipo psicológica -->
            <?php
            $stmt2 = $pdo->prepare("SELECT s.*, u.nombre AS prof_nombre, u.foto_perfil,
                COUNT(h.id) AS total_horarios,
                SUM(CASE WHEN h.disponible=1 THEN 1 ELSE 0 END) AS horarios_disponibles
                FROM servicios s JOIN usuarios u ON s.profesional_id=u.id
                LEFT JOIN horarios h ON h.servicio_id=s.id
                WHERE s.tipo='psicologica' GROUP BY s.id ORDER BY s.fecha DESC");
            $stmt2->execute();
            foreach($stmt2->fetchAll() as $s): renderServicio($s, $rol, $usuario_id, $pdo); endforeach;
            ?>

            <!-- Ejemplos fijos -->
            <?php
            $psi=[
              
                
            ];
            foreach($psi as $p): ?>
            <div class="prof-card">
                <div class="prof-avatar"><?= $p[0] ?></div>
                <div class="prof-nombre"><?= $p[1] ?></div>
                <div class="prof-especialidad"><?= $p[2] ?></div>
                <span class="prof-disponible <?= $p[3] ?>"><?= $p[3]==='disponible'?'Disponible':'Próximamente' ?></span>
                <div class="prof-rating">⭐ <?= $p[4] ?> (<?= $p[5] ?> reseñas)</div>
                <div class="prof-tags"><?php foreach($p[6] as $t): ?><span class="prof-tag"><?= $t ?></span><?php endforeach; ?></div>
                <?php if($p[3]==='disponible' && $rol!=='profesional'): ?>
                    <?php if(isset($_SESSION['usuario'])): ?>
                        <button class="btn-cita" onclick="abrirSolicitarEjemplo('<?= $p[1] ?>','<?= $p[2] ?>')">Solicitar cita</button>
                    <?php else: ?>
                        <a href="login.php" class="btn-cita" style="display:block;text-decoration:none">Solicitar cita</a>
                    <?php endif; ?>
                <?php elseif($p[3]==='proximamente'): ?>
                    <button class="btn-cita" disabled>No disponible</button>
                <?php elseif($rol==='profesional'): ?>
                    <button class="btn-cita" disabled style="background:#f3e8ff;color:#7b2ff7">Colega</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Tab Jurídica ── -->
    <div id="tab-juridica" class="<?= $tab==='juridica'?'':'hidden' ?>">
        <div class="seccion-titulo">
            <h2>Abogados disponibles</h2>
            <p>Todos nuestros profesionales están certificados y tienen experiencia en casos de discriminación e inclusión.</p>
        </div>
        <div class="profesionales-grid">

            <!-- Servicios reales BD tipo jurídica -->
            <?php
            $stmt3 = $pdo->prepare("SELECT s.*, u.nombre AS prof_nombre, u.foto_perfil,
                COUNT(h.id) AS total_horarios,
                SUM(CASE WHEN h.disponible=1 THEN 1 ELSE 0 END) AS horarios_disponibles
                FROM servicios s JOIN usuarios u ON s.profesional_id=u.id
                LEFT JOIN horarios h ON h.servicio_id=s.id
                WHERE s.tipo='juridica' GROUP BY s.id ORDER BY s.fecha DESC");
            $stmt3->execute();
            foreach($stmt3->fetchAll() as $s): renderServicio($s, $rol, $usuario_id, $pdo); endforeach;
            ?>

            <!-- Ejemplos fijos -->
            <?php
            $jur=[
                ];
                
            foreach($jur as $a): ?>
            <div class="prof-card">
                <div class="prof-avatar"><?= $a[0] ?></div>
                <div class="prof-nombre"><?= $a[1] ?></div>
                <div class="prof-especialidad"><?= $a[2] ?></div>
                <span class="prof-disponible disponible">Disponible</span>
                <div class="prof-rating">⭐ <?= $a[4] ?> (<?= $a[5] ?> reseñas)</div>
                <div class="prof-tags"><?php foreach($a[6] as $t): ?><span class="prof-tag"><?= $t ?></span><?php endforeach; ?></div>
                <?php if($rol!=='profesional'): ?>
                    <?php if(isset($_SESSION['usuario'])): ?>
                        <button class="btn-cita" onclick="abrirSolicitarEjemplo('<?= $a[1] ?>','<?= $a[2] ?>')">Solicitar cita</button>
                    <?php else: ?>
                        <a href="login.php" class="btn-cita" style="display:block;text-decoration:none">Solicitar cita</a>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn-cita" disabled style="background:#f3e8ff;color:#7b2ff7">Colega</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div><!-- fin asesorias-page -->

<!-- ── Modal publicar servicio (profesional) ── -->
<?php if($rol==='profesional'): ?>
<div class="modal-overlay" id="mPublicar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('mPublicar').classList.remove('active')">✕</button>
        <h3>🩺 Publicar mi servicio</h3>
        <p class="modal-sub">Completa la información y agrega tus horarios disponibles para citas.</p>
        <form action="acciones/publicar_servicio.php" method="POST">
            <div class="fg"><label>Nombre / Título profesional</label><input type="text" name="nombre" placeholder="Ej: Dra. María González" value="<?= htmlspecialchars($_SESSION['usuario']) ?>" required></div>
            <div class="fg"><label>Especialidad</label><input type="text" name="especialidad" placeholder="Ej: Psicología Clínica" required></div>
            <div class="fg"><label>Tipo de asesoría</label>
                <select name="tipo" required>
                    <option value="psicologica">🧠 Asesoría Psicológica</option>
                    <option value="juridica">⚖️ Asesoría Jurídica</option>
                </select>
            </div>
            <div class="fg"><label>Descripción</label><textarea name="descripcion" placeholder="Cuéntales sobre tu experiencia..."></textarea></div>
            <div class="fg">
                <label>📅 Horarios disponibles para citas</label>
                <div class="horarios-list" id="horariosList">
                    <div class="horario-item">
                        <input type="datetime-local" name="horarios[]" required>
                        <button type="button" class="btn-remove-horario" onclick="qH(this)">✕</button>
                    </div>
                </div>
                <button type="button" class="btn-add-horario" onclick="aH()">➕ Agregar horario</button>
            </div>
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mPublicar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bms">Publicar servicio</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal mis solicitudes -->
<div class="modal-overlay" id="mSolicitudes">
    <div class="modal" style="max-width:600px">
        <button class="modal-close" onclick="document.getElementById('mSolicitudes').classList.remove('active')">✕</button>
        <h3>📋 Mis solicitudes de cita</h3>
        <p class="modal-sub">Gestiona las citas solicitadas.</p>
        <div id="listaSolicitudes"><div style="text-align:center;padding:20px;color:#aaa">Cargando...</div></div>
    </div>
</div>

<!-- Modal confirmar eliminar servicio -->
<div class="modal-overlay" id="mEliminarServicio">
    <div class="modal" style="max-width:440px">
        <h3>🗑️ Eliminar servicio</h3>
        <p style="color:#555;font-size:14px;line-height:1.6;margin:12px 0 20px">¿Estás seguro que quieres eliminar este servicio? Se eliminarán también todos los horarios y solicitudes asociadas.</p>
        <form action="acciones/eliminar_servicio.php" method="POST">
            <input type="hidden" name="servicio_id" id="del-serv-id">
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mEliminarServicio').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bmd">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<!-- Modal solicitar cita (servicio real BD) -->
<div class="modal-overlay" id="mSolicitar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('mSolicitar').classList.remove('active')">✕</button>
        <h3>📅 Solicitar cita</h3>
        <p class="modal-sub" id="sol-prof-nombre"></p>
        <form action="acciones/solicitar_cita.php" method="POST">
            <input type="hidden" name="servicio_id" id="sol-serv-id">
            <div class="fg"><label>Selecciona un horario disponible</label><div id="horariosDisponibles"></div></div>
            <div class="fr">
                <div class="fg"><label>Nombre completo</label><input type="text" name="nombre_solicitante" value="<?= htmlspecialchars($_SESSION['usuario']??'') ?>" required></div>
                <div class="fg"><label>Celular</label><input type="tel" name="celular" placeholder="300 000 0000" required></div>
            </div>
            <div class="fr">
                <div class="fg"><label>Cédula</label><input type="text" name="cedula" placeholder="123456789" required></div>
                <div class="fg"><label>Edad</label><input type="number" name="edad" placeholder="25" min="5" max="120" required></div>
            </div>
            <div class="fg"><label>Mensaje (opcional)</label><textarea name="mensaje" placeholder="Motivo de la consulta..."></textarea></div>
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mSolicitar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bms">Enviar solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal solicitar ejemplo -->
<div class="modal-overlay" id="mSolicitarEj">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('mSolicitarEj').classList.remove('active')">✕</button>
        <h3>📅 Solicitar cita</h3>
        <p class="modal-sub" id="ej-prof-nombre"></p>
        <form onsubmit="enviarEjemplo(event)">
            <div class="fr">
                <div class="fg"><label>Nombre completo</label><input type="text" value="<?= htmlspecialchars($_SESSION['usuario']??'') ?>" required></div>
                <div class="fg"><label>Celular</label><input type="tel" placeholder="300 000 0000" required></div>
            </div>
            <div class="fr">
                <div class="fg"><label>Cédula</label><input type="text" placeholder="123456789" required></div>
                <div class="fg"><label>Edad</label><input type="number" placeholder="25" min="5" max="120" required></div>
            </div>
            <div class="fg"><label>Horario preferido</label>
                <select required>
                    <option value="">Selecciona un horario</option>
                    <option>Lunes 9:00 AM</option><option>Martes 2:00 PM</option>
                    <option>Miércoles 10:00 AM</option><option>Jueves 4:00 PM</option>
                    <option>Viernes 11:00 AM</option>
                </select>
            </div>
            <div class="fg"><label>Mensaje (opcional)</label><textarea placeholder="Motivo de la consulta..."></textarea></div>
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mSolicitarEj').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bms">Enviar solicitud</button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">© 2026 EquiRed. Conectando oportunidades, construyendo igualdad.</footer>
<style>.hidden{display:none}</style>

<script>
function cambiarTab(tab){
    document.getElementById('tab-psicologica').classList.toggle('hidden',tab!=='psicologica');
    document.getElementById('tab-juridica').classList.toggle('hidden',tab!=='juridica');
    document.querySelectorAll('.tab-btn').forEach((b,i)=>b.classList.toggle('active',(i===0&&tab==='psicologica')||(i===1&&tab==='juridica')));
}

// Horarios publicar
function aH(){
    const l=document.getElementById('horariosList');
    const d=document.createElement('div'); d.className='horario-item';
    d.innerHTML='<input type="datetime-local" name="horarios[]" required><button type="button" class="btn-remove-horario" onclick="qH(this)">✕</button>';
    l.appendChild(d);
}
function qH(btn){ const l=document.getElementById('horariosList'); if(l.children.length>1) btn.parentElement.remove(); }

// Eliminar servicio
function confirmarEliminarServicio(id){
    document.getElementById('del-serv-id').value=id;
    document.getElementById('mEliminarServicio').classList.add('active');
}

// Solicitar cita (BD)
function abrirSolicitar(id, nombre, horarios){
    document.getElementById('sol-serv-id').value=id;
    document.getElementById('sol-prof-nombre').textContent='🩺 '+nombre;
    const cont=document.getElementById('horariosDisponibles');
    cont.innerHTML='';
    if(!horarios||horarios.length===0){
        cont.innerHTML='<p style="color:#aaa;font-size:13px">Sin horarios disponibles.</p>'; return;
    }
    horarios.forEach(h=>{
        const f=new Date(h.dia_hora).toLocaleString('es-CO',{weekday:'long',year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'});
        cont.innerHTML+=`<label class="horario-option"><input type="radio" name="horario_id" value="${h.id}" required><span>📅 ${f}</span></label>`;
    });
    document.getElementById('mSolicitar').classList.add('active');
}

// Solicitar ejemplo
function abrirSolicitarEjemplo(nombre,esp){
    document.getElementById('ej-prof-nombre').textContent='🩺 '+nombre+' — '+esp;
    document.getElementById('mSolicitarEj').classList.add('active');
}
function enviarEjemplo(e){
    e.preventDefault();
    document.getElementById('mSolicitarEj').classList.remove('active');
    const t=document.createElement('div');
    t.className='toast toast-exito';
    t.textContent='✅ ¡Solicitud de cita enviada!';
    t.style.cssText='position:fixed;top:80px;right:24px;z-index:999;padding:14px 22px;border-radius:12px;font-size:15px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,0.15);background:#d1fae5;color:#059669;border:1px solid #a7f3d0';
    document.body.appendChild(t); setTimeout(()=>t.remove(),3500);
}

// Mis solicitudes AJAX
function abrirMisSolicitudes(){
    document.getElementById('mSolicitudes').classList.add('active');
    fetch('acciones/obtener_solicitudes.php').then(r=>r.json()).then(data=>{
        const c=document.getElementById('listaSolicitudes');
        if(data.length===0){ c.innerHTML='<div class="ns"><div style="font-size:36px;margin-bottom:10px">📭</div><p>No tienes solicitudes aún.</p></div>'; return; }
        c.innerHTML=data.map(s=>`
            <div class="solicitud-item ${s.estado}" id="sol-${s.id}">
                <div class="solicitud-header">
                    <div class="solicitud-nombre">👤 ${s.nombre_solicitante}</div>
                    <span class="solicitud-estado estado-${s.estado}" id="sol-est-${s.id}">${s.estado==='pendiente'?'⏳ Pendiente':s.estado==='aceptada'?'✅ Aceptada':'❌ Rechazada'}</span>
                </div>
                <div class="solicitud-info">📞 ${s.celular} · 🪪 ${s.cedula} · 🎂 ${s.edad} años<br>📋 <strong>${s.servicio_nombre}</strong> — ${s.especialidad}${s.mensaje?'<br>💬 '+s.mensaje:''}</div>
                <div class="solicitud-horario">📅 ${s.dia_hora}</div>
                ${s.estado==='pendiente'?`<div class="solicitud-acciones"><button class="btn-aceptar" onclick="responder(${s.id},'aceptada')">✅ Aceptar</button><button class="btn-rechazar" onclick="responder(${s.id},'rechazada')">❌ Rechazar</button></div>`:''}
            </div>`).join('');
    });
}
function responder(id,estado){
    fetch('acciones/responder_solicitud.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`solicitud_id=${id}&estado=${estado}`})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const item=document.getElementById('sol-'+id);
            item.className='solicitud-item '+estado;
            const b=document.getElementById('sol-est-'+id);
            b.className='solicitud-estado estado-'+estado;
            b.textContent=estado==='aceptada'?'✅ Aceptada':'❌ Rechazada';
            const acc=item.querySelector('.solicitud-acciones'); if(acc) acc.remove();
        }
    });
}

setTimeout(()=>{ const t=document.querySelector('.toast'); if(t) t.style.display='none'; },3500);
</script>

<?php
function renderServicio($s, $rol, $usuario_id, $pdo) {
    $disponible  = (int)($s['horarios_disponibles'] ?? 0) > 0;
    $es_mio      = ($rol === 'profesional' && $usuario_id == $s['profesional_id']);
    $foto        = !empty($s['foto_perfil']) ? "uploads/".htmlspecialchars($s['foto_perfil']) : null;

    $horarios = [];
    if($disponible) {
        $h = $pdo->prepare("SELECT * FROM horarios WHERE servicio_id=? AND disponible=1 ORDER BY dia_hora ASC");
        $h->execute([$s['id']]);
        $horarios = $h->fetchAll();
    }
    $horariosJson = json_encode($horarios, JSON_HEX_APOS | JSON_HEX_QUOT);
    $nombre_esc   = htmlspecialchars($s['nombre'], ENT_QUOTES);
    ?>
    <div class="prof-card">
        <?php if($es_mio): ?>
            <!-- Botón eliminar solo para el dueño del servicio -->
            <button class="btn-del-servicio" onclick="confirmarEliminarServicio(<?= $s['id'] ?>)">🗑️ Eliminar</button>
        <?php endif; ?>

        <div class="prof-avatar">
            <?php if($foto): ?><img src="<?= $foto ?>" alt=""><?php else: ?>🩺<?php endif; ?>
        </div>
        <div class="prof-nombre"><?= htmlspecialchars($s['nombre']) ?></div>
        <div class="prof-especialidad"><?= htmlspecialchars($s['especialidad']) ?></div>
        <span class="prof-disponible <?= $disponible?'disponible':'proximamente' ?>">
            <?= $disponible?'Disponible':'Sin horarios' ?>
        </span>
        <?php if(!empty($s['descripcion'])): ?>
            <div class="prof-desc"><?= htmlspecialchars(substr($s['descripcion'],0,90)) ?>...</div>
        <?php endif; ?>
        <div class="horarios-disponibles"><span><?= (int)($s['horarios_disponibles']??0) ?></span> horarios disponibles</div>

        <?php if($es_mio): ?>
            <button class="btn-cita-outline" onclick="abrirMisSolicitudes()">Ver solicitudes</button>
        <?php elseif($disponible && $rol!=='profesional'): ?>
            <?php if($usuario_id): ?>
                <button class="btn-cita" onclick='abrirSolicitar(<?= $s["id"] ?>, "<?= $nombre_esc ?>", <?= $horariosJson ?>)'>Solicitar cita</button>
            <?php else: ?>
                <a href="login.php" class="btn-cita" style="display:block;text-decoration:none">Solicitar cita</a>
            <?php endif; ?>
        <?php elseif($rol==='profesional'): ?>
            <button class="btn-cita" disabled style="background:#f3e8ff;color:#7b2ff7">Colega</button>
        <?php else: ?>
            <button class="btn-cita" disabled>Sin horarios</button>
        <?php endif; ?>
    </div>
    <?php
}
?>
</body>
</html>