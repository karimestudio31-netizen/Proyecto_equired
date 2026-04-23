<?php
session_start();
if(!isset($_SESSION['usuario'])){ header("Location: login.php"); exit(); }
require_once("config/conexion.php");

$rol        = $_SESSION['rol'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? null;
$msg        = $_GET['msg'] ?? '';

// Stats
$total_empleos  = $pdo->query("SELECT COUNT(*) FROM empleos")->fetchColumn() ?: 6;
$total_empresas = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='empresa'")->fetchColumn();

// Mis postulaciones
$mis_post = []; $total_mis_post = 0;
if($usuario_id && $rol !== 'empresa') {
    $sp = $pdo->prepare("SELECT p.*,e.titulo,e.descripcion,u.nombre AS empresa_nombre FROM postulaciones p JOIN empleos e ON p.empleo_id=e.id JOIN usuarios u ON e.empresa_id=u.id WHERE p.usuario_id=? ORDER BY p.fecha DESC");
    $sp->execute([$usuario_id]); $mis_post=$sp->fetchAll(); $total_mis_post=count($mis_post);
}

// Vacantes de la empresa con postulaciones
$mis_vacantes = [];
if($rol === 'empresa' && $usuario_id) {
    $sv = $pdo->prepare("SELECT e.*, COUNT(p.id) AS total_post FROM empleos e LEFT JOIN postulaciones p ON p.empleo_id=e.id WHERE e.empresa_id=? GROUP BY e.id ORDER BY e.fecha DESC");
    $sv->execute([$usuario_id]); $mis_vacantes=$sv->fetchAll();
}

// Empleos BD
$empleos = $pdo->query("SELECT e.*,u.nombre AS empresa_nombre FROM empleos e JOIN usuarios u ON e.empresa_id=u.id ORDER BY e.fecha DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleos - EquiRed</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .empleos-page{max-width:1000px;margin:0 auto;padding:40px 20px}
        .empleos-hero{text-align:center;margin-bottom:36px}
        .empleos-hero h1{font-size:36px;font-weight:900;color:#1a1a2e}
        .empleos-hero h1 span{color:#7b2ff7}
        .empleos-hero p{color:#777;font-size:15px;margin-top:10px;max-width:580px;margin-inline:auto;line-height:1.6}
        .toast{position:fixed;top:80px;right:24px;z-index:999;padding:14px 22px;border-radius:12px;font-size:15px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,0.15);animation:slideIn 0.3s ease,fadeOut 0.4s ease 3s forwards}
        .toast-exito{background:#d1fae5;color:#059669;border:1px solid #a7f3d0}
        .toast-warning{background:#fef3c7;color:#d97706;border:1px solid #fde68a}
        @keyframes slideIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes fadeOut{from{opacity:1}to{opacity:0;visibility:hidden}}
        .search-bar{display:flex;gap:12px;margin-bottom:28px;background:white;padding:14px 18px;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,0.06)}
        .siw{flex:1;position:relative}.siw input{width:100%;padding:10px 14px 10px 38px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f9f9f9}
        .siw input:focus{border-color:#7b2ff7;background:white}.sic{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa}
        .fsel{padding:10px 16px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f9f9f9;color:#555;min-width:180px;cursor:pointer}
        .stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
        .stat-card{background:white;border-radius:14px;padding:20px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,0.06)}
        .stat-card.clickable{cursor:pointer;border:1.5px solid #f0f0f0;transition:box-shadow 0.2s,transform 0.2s,border-color 0.2s}
        .stat-card.clickable:hover{box-shadow:0 6px 20px rgba(123,47,247,0.15);transform:translateY(-2px);border-color:#7b2ff7}
        .stat-num{font-size:32px;font-weight:900;color:#7b2ff7}.stat-label{font-size:13px;color:#888;margin-top:4px}
        .stat-hint{font-size:11px;color:#a855f7;margin-top:4px;font-weight:600}
        .empleos-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:40px}
        .empleo-card{background:white;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;flex-direction:column;gap:12px;border:1.5px solid #f0f0f0;transition:border-color 0.2s,box-shadow 0.2s}
        .empleo-card:hover{border-color:#7b2ff7;box-shadow:0 4px 20px rgba(123,47,247,0.1)}
        .eh{display:flex;justify-content:space-between;align-items:flex-start}
        .el{display:flex;gap:12px;align-items:flex-start}
        .ei{width:46px;height:46px;border-radius:12px;background:#f3e8ff;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
        .et{font-size:16px;font-weight:800;color:#1a1a2e;margin-bottom:2px}
        .ee{font-size:13px;font-weight:700;color:#7b2ff7}
        .badge-tipo{font-size:11px;font-weight:700;padding:4px 10px;border-radius:50px;background:#f3e8ff;color:#7b2ff7;white-space:nowrap;flex-shrink:0}
        .em{display:flex;flex-direction:column;gap:5px}
        .em span{font-size:13px;color:#888;display:flex;align-items:center;gap:6px}
        .ed{font-size:14px;color:#555;line-height:1.6}
        .btn-postular{width:100%;padding:11px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-size:14px;font-weight:800;font-family:inherit;cursor:pointer;transition:opacity 0.2s}
        .btn-postular:hover{opacity:0.9}
        .btn-pub-emp{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;padding:11px 24px;border-radius:10px;font-weight:800;font-size:14px;border:none;cursor:pointer;font-family:inherit;margin-bottom:28px;transition:opacity 0.2s}
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;padding:20px}
        .modal-overlay.active{display:flex}
        .modal{background:white;border-radius:20px;padding:36px;width:100%;max-width:560px;max-height:92vh;overflow-y:auto}
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
        .fl{display:flex;align-items:center;gap:8px;padding:12px 14px;border:1.5px dashed #d8b4fe;border-radius:10px;cursor:pointer;font-size:14px;color:#7b2ff7;font-weight:700;margin-bottom:14px;background:#faf5ff}
        .fl input{display:none}
        .pi{background:#fafafa;border-radius:12px;padding:14px 16px;margin-bottom:10px;border-left:4px solid #7b2ff7;display:flex;justify-content:space-between;align-items:center}
        .pi.aceptado{border-left-color:#10b981}.pi.rechazado{border-left-color:#ef4444}
        .pi-info strong{display:block;font-size:14px;font-weight:800;color:#1a1a2e}
        .pi-info span{font-size:12px;color:#888}
        .eb{padding:4px 12px;border-radius:50px;font-size:12px;font-weight:800}
        .ep{background:#fef3c7;color:#d97706}.ea{background:#d1fae5;color:#059669}.er{background:#fee2e2;color:#dc2626}
        .vacante-item{background:#fafafa;border-radius:12px;padding:16px;margin-bottom:12px;border:1.5px solid #f0f0f0;transition:border-color 0.2s}
        .vacante-item:hover{border-color:#7b2ff7}
        .vi-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
        .vi-titulo{font-size:15px;font-weight:800;color:#1a1a2e}
        .vi-tipo{font-size:11px;font-weight:700;padding:4px 10px;border-radius:50px;background:#f3e8ff;color:#7b2ff7}
        .vi-meta{font-size:13px;color:#888}
        .btn-ver-hv{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px;border:none;cursor:pointer;font-family:inherit;margin-top:8px}
        .hv-item{background:white;border-radius:12px;padding:16px;margin-bottom:10px;border:1.5px solid #e8e8e8}
        .hv-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
        .hv-nombre{font-size:14px;font-weight:800;color:#1a1a2e}
        .hv-info{font-size:13px;color:#666;margin-bottom:8px}
        .hv-link{display:inline-flex;align-items:center;gap:4px;color:#7b2ff7;font-weight:700;font-size:13px;text-decoration:none}
        .hv-acciones{display:flex;gap:8px;margin-top:10px}
        .ba{flex:1;padding:8px;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit}
        .ba-acep{background:#d1fae5;color:#059669}
        .ba-rech{background:#fee2e2;color:#dc2626}
        .ns{text-align:center;padding:30px;color:#aaa}
        /* Empresa card en modal */
        .empresa-item{display:flex;align-items:center;gap:14px;padding:14px;border-radius:12px;border:1.5px solid #f0f0f0;margin-bottom:10px;cursor:pointer;transition:border-color 0.2s,box-shadow 0.2s}
        .empresa-item:hover{border-color:#7b2ff7;box-shadow:0 4px 12px rgba(123,47,247,0.1)}
        .empresa-avatar{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#7b2ff7,#a855f7);display:flex;align-items:center;justify-content:center;font-size:20px;color:white;font-weight:800;overflow:hidden;flex-shrink:0}
        .empresa-avatar img{width:100%;height:100%;object-fit:cover}
        .empresa-nombre{font-size:15px;font-weight:800;color:#1a1a2e}
        .empresa-desc{font-size:13px;color:#888}
    </style>
</head>
<body>
<?php include("includes/navbar.php"); ?>

<?php if($msg==='exito'): ?><div class="toast toast-exito">✅ ¡Postulación enviada!</div>
<?php elseif($msg==='ya_postulado'): ?><div class="toast toast-warning">⚠️ Ya te postulaste a este empleo.</div>
<?php elseif($msg==='empleo_publicado'): ?><div class="toast toast-exito">✅ ¡Empleo publicado!</div><?php endif; ?>

<div class="empleos-page">

    <div class="empleos-hero">
        <h1>Empleos <span>Inclusivos</span></h1>
        <p>Lista extensa de oportunidades laborales en empresas comprometidas con la diversidad y la inclusión.</p>
    </div>

    <?php if($rol==='empresa'): ?>
    <div style="text-align:right;margin-bottom:10px">
        <button class="btn-pub-emp" onclick="document.getElementById('mEmpleo').classList.add('active')">➕ Publicar empleo</button>
    </div>
    <?php endif; ?>

    <div class="search-bar">
        <div class="siw"><span class="sic">🔍</span>
            <input type="text" id="buscar" placeholder="Buscar por puesto o empresa..." oninput="filtrar()">
        </div>
        <select class="fsel" id="filtroTipo" onchange="filtrar()">
            <option value="">Todos los empleos</option>
            <option value="Tiempo completo">Tiempo completo</option>
            <option value="Media jornada">Media jornada</option>
            <option value="Freelance">Freelance</option>
        </select>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card"><div class="stat-num"><?= $total_empleos ?></div><div class="stat-label">Empleos disponibles</div></div>

        <!-- Empresas clickeable -->
        <div class="stat-card clickable" onclick="document.getElementById('mEmpresas').classList.add('active')" title="Ver empresas">
            <div class="stat-num"><?= $total_empresas>0?$total_empresas:'0' ?></div>
            <div class="stat-label">Empresas registradas</div>
            <div class="stat-hint">👆 Ver empresas</div>
        </div>

        <?php if($rol==='empresa' && $usuario_id): ?>
            <div class="stat-card clickable" onclick="document.getElementById('mVacantes').classList.add('active')" title="Ver mis vacantes">
                <div class="stat-num"><?= count($mis_vacantes) ?></div>
                <div class="stat-label">Mis vacantes publicadas</div>
                <div class="stat-hint">👆 Ver postulantes</div>
            </div>
        <?php elseif(isset($_SESSION['usuario']) && $rol!=='empresa'): ?>
            <div class="stat-card clickable" onclick="document.getElementById('mPostulaciones').classList.add('active')" title="Ver mis postulaciones">
                <div class="stat-num"><?= $total_mis_post ?></div>
                <div class="stat-label">Mis postulaciones</div>
                <div class="stat-hint">👆 Ver detalle</div>
            </div>
        <?php else: ?>
            <div class="stat-card"><div class="stat-num">500+</div><div class="stat-label">Postulaciones exitosas</div></div>
        <?php endif; ?>
    </div>

    <!-- Grid empleos -->
    <div class="empleos-grid" id="egrid">
        <?php
        $iconos=['💼','💻','🎧','📋','🎨','📞','👥','🔧'];
        if(empty($empleos)):
            $ejs=[
                ['🎧','Operador(a) Telefónico(a)','TechCall Solutions','Bogotá, Colombia','$2,500,000 - $3,200,000/mes','Hace 2 días','Tiempo completo','Buscamos operadores telefónicos. Ofrecemos capacitación completa.'],
                ['💻','Desarrollador(a) Web','Digital Inclusion','Medellín (Remoto)','$4,000,000 - $6,000,000/mes','Hace 5 días','Tiempo completo','Únete a nuestro equipo diverso de desarrollo.'],
                ['📋','Auxiliar Administrativo(a)','Empresa Inclusiva SA','Cali, Colombia','$1,800,000 - $2,200,000/mes','Hace 3 días','Media jornada','Empresa comprometida con la inclusión busca auxiliar.'],
                ['🎨','Diseñador(a) Gráfico(a)','Creative Minds','Barranquilla','$3,000,000 - $4,500,000/mes','Hace 1 día','Freelance','Estudio creativo busca diseñadores con portafolio.'],
                ['👥','Asistente RRHH','EquiHR Consulting','Cartagena','$2,800,000 - $3,800,000/mes','Hace 4 días','Tiempo completo','Consultora especializada en diversidad busca asistente.'],
                ['📞','Atención al Cliente','ServiPlus','Bucaramanga','$2,200,000 - $2,800,000/mes','Hace 1 día','Tiempo completo','Empresa líder busca personas con ganas de aprender.'],
            ];
            foreach($ejs as $e): ?>
            <div class="empleo-card" data-titulo="<?= $e[1] ?>" data-empresa="<?= $e[2] ?>" data-tipo="<?= $e[6] ?>">
                <div class="eh"><div class="el"><div class="ei"><?= $e[0] ?></div><div><div class="et"><?= $e[1] ?></div><div class="ee"><?= $e[2] ?></div></div></div><span class="badge-tipo"><?= $e[6] ?></span></div>
                <div class="em"><span>📍 <?= $e[3] ?></span><span>💲 <?= $e[4] ?></span><span>🕐 <?= $e[5] ?></span></div>
                <div class="ed"><?= $e[7] ?></div>
                <?php if(isset($_SESSION['usuario']) && $rol!=='empresa'): ?>
                    <button class="btn-postular" onclick="abrirPostular(0,'<?= htmlspecialchars($e[1],ENT_QUOTES) ?>')">Postularme</button>
                <?php endif; ?>
            </div>
            <?php endforeach;
        else:
            $i=0; foreach($empleos as $emp):
            $tipo_real = !empty($emp['tipo']) ? $emp['tipo'] : 'Tiempo completo'; ?>
            <div class="empleo-card" data-titulo="<?= htmlspecialchars($emp['titulo']) ?>" data-empresa="<?= htmlspecialchars($emp['empresa_nombre']) ?>" data-tipo="<?= $tipo_real ?>">
                <div class="eh"><div class="el"><div class="ei"><?= $iconos[$i%count($iconos)] ?></div><div><div class="et"><?= htmlspecialchars($emp['titulo']) ?></div><div class="ee"><?= htmlspecialchars($emp['empresa_nombre']) ?></div></div></div><span class="badge-tipo"><?= $tipo_real ?></span></div>
                <div class="em">
                    <?php if(!empty($emp['ciudad'])): ?><span>📍 <?= htmlspecialchars($emp['ciudad']) ?></span><?php endif; ?>
                    <?php if(!empty($emp['salario'])): ?><span>💲 <?= htmlspecialchars($emp['salario']) ?></span><?php endif; ?>
                    <span>🕐 Hace <?= t($emp['fecha']) ?></span>
                </div>
                <div class="ed"><?= htmlspecialchars(substr($emp['descripcion'],0,120)) ?>...</div>
                <?php if(isset($_SESSION['usuario']) && $rol!=='empresa'): ?>
                    <button class="btn-postular" onclick="abrirPostular(<?= $emp['id'] ?>,'<?= htmlspecialchars($emp['titulo'],ENT_QUOTES) ?>')">Postularme</button>
                <?php endif; ?>
            </div>
            <?php $i++; endforeach;
        endif; ?>
    </div>
</div>

<!-- ══ Modal publicar empleo (empresa) ══ -->
<?php if($rol==='empresa'): ?>
<div class="modal-overlay" id="mEmpleo">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('mEmpleo').classList.remove('active')">✕</button>
        <h3>💼 Publicar empleo</h3>
        <form action="acciones/publicar_empleo.php" method="POST">
            <div class="fg"><label>Título del puesto</label><input type="text" name="titulo" placeholder="Ej: Desarrollador Web" required></div>
            <div class="fr">
                <div class="fg"><label>Tipo de empleo</label>
                    <select name="tipo" required>
                        <option value="Tiempo completo">Tiempo completo</option>
                        <option value="Media jornada">Media jornada</option>
                        <option value="Freelance">Freelance</option>
                    </select>
                </div>
                <div class="fg"><label>Ciudad</label><input type="text" name="ciudad" placeholder="Bogotá, Colombia"></div>
            </div>
            <div class="fg"><label>Salario</label><input type="text" name="salario" placeholder="$2,000,000 - $3,000,000/mes"></div>
            <div class="fg"><label>Descripción</label><textarea name="descripcion" placeholder="Describe el empleo y requisitos..." required></textarea></div>
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mEmpleo').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bms">Publicar empleo</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ Modal vacantes + postulantes (empresa) ══ -->
<div class="modal-overlay" id="mVacantes">
    <div class="modal" style="max-width:620px">
        <button class="modal-close" onclick="document.getElementById('mVacantes').classList.remove('active')">✕</button>
        <h3>📋 Mis vacantes publicadas</h3>
        <p class="modal-sub">Haz click en "Ver hojas de vida" para gestionar postulantes.</p>
        <?php if(empty($mis_vacantes)): ?>
            <div class="ns"><div style="font-size:36px;margin-bottom:10px">📭</div><p>No has publicado vacantes aún.</p></div>
        <?php else: foreach($mis_vacantes as $v): ?>
        <div class="vacante-item">
            <div class="vi-header">
                <div class="vi-titulo"><?= htmlspecialchars($v['titulo']) ?></div>
                <span class="vi-tipo"><?= htmlspecialchars($v['tipo'] ?? 'Tiempo completo') ?></span>
            </div>
            <div class="vi-meta">
                <?php if(!empty($v['ciudad'])): ?>📍 <?= htmlspecialchars($v['ciudad']) ?> &nbsp;·&nbsp;<?php endif; ?>
                👥 <?= $v['total_post'] ?> postulante(s)
            </div>
            <button class="btn-ver-hv" onclick="verHojaVida(<?= $v['id'] ?>, '<?= htmlspecialchars($v['titulo'],ENT_QUOTES) ?>')">
                📄 Ver hojas de vida (<?= $v['total_post'] ?>)
            </button>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- ══ Modal hojas de vida de una vacante ══ -->
<div class="modal-overlay" id="mHV">
    <div class="modal" style="max-width:620px">
        <button class="modal-close" onclick="document.getElementById('mHV').classList.remove('active')">✕</button>
        <h3 id="mHV-titulo">Hojas de vida</h3>
        <div id="mHV-contenido"><div style="text-align:center;padding:20px;color:#aaa">Cargando...</div></div>
    </div>
</div>
<?php endif; ?>

<!-- ══ Modal mis postulaciones (beneficiario/profesional) ══ -->
<?php if(isset($_SESSION['usuario']) && $rol!=='empresa'): ?>
<div class="modal-overlay" id="mPostulaciones">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('mPostulaciones').classList.remove('active')">✕</button>
        <h3>📋 Mis postulaciones (<?= $total_mis_post ?>)</h3>
        <?php if(empty($mis_post)): ?>
            <div class="ns"><div style="font-size:36px;margin-bottom:10px">📭</div><p>Aún no te has postulado.</p></div>
        <?php else: foreach($mis_post as $mp): ?>
        <div class="pi <?= $mp['estado'] ?>">
            <div class="pi-info">
                <strong><?= htmlspecialchars($mp['titulo']) ?></strong>
                <span><?= htmlspecialchars($mp['empresa_nombre']) ?> · <?= date('d/m/Y',strtotime($mp['fecha'])) ?></span>
            </div>
            <span class="eb <?= $mp['estado']==='aceptado'?'ea':($mp['estado']==='rechazado'?'er':'ep') ?>">
                <?= $mp['estado']==='aceptado'?'✅ Aceptado':($mp['estado']==='rechazado'?'❌ Rechazado':'⏳ Pendiente') ?>
            </span>
        </div>
        <?php endforeach; endif; ?>
        <div class="modal-btns" style="margin-top:16px">
            <button class="bmc" onclick="document.getElementById('mPostulaciones').classList.remove('active')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ══ Modal postular con PDF ══ -->
<div class="modal-overlay" id="mPostular">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('mPostular').classList.remove('active')">✕</button>
        <h3>📄 Postularme a este empleo</h3>
        <p class="modal-sub" id="mPostular-titulo"></p>
        <form action="acciones/postular.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="empleo_id" id="mPostular-id">
            <input type="hidden" name="empleo_titulo" id="mPostular-titulo-h">
            <div class="fg">
                <label>📎 Adjunta tu hoja de vida (PDF)</label>
                <label class="fl">📄 Seleccionar PDF<input type="file" name="hoja_vida" accept=".pdf" required></label>
            </div>
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mPostular').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bms">Enviar postulación</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ══ Modal empresas registradas ══ -->
<div class="modal-overlay" id="mEmpresas">
    <div class="modal" style="max-width:560px">
        <button class="modal-close" onclick="document.getElementById('mEmpresas').classList.remove('active')">✕</button>
        <h3>🏢 Empresas registradas</h3>
        <p class="modal-sub">Haz click en una empresa para ver su perfil.</p>
        <?php
        $empresas_lista = $pdo->query("SELECT id, nombre, descripcion, foto_perfil FROM usuarios WHERE rol='empresa' ORDER BY nombre ASC")->fetchAll();
        if(empty($empresas_lista)): ?>
            <div class="ns"><div style="font-size:36px;margin-bottom:10px">📭</div><p>No hay empresas registradas aún.</p></div>
        <?php else: foreach($empresas_lista as $emp): ?>
            <div class="empresa-item" onclick="window.location.href='perfil.php?id=<?= $emp['id'] ?>'">
                <div class="empresa-avatar">
                    <?php if(!empty($emp['foto_perfil'])): ?>
                        <img src="uploads/<?= htmlspecialchars($emp['foto_perfil']) ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($emp['nombre'],0,1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="empresa-nombre"><?= htmlspecialchars($emp['nombre']) ?></div>
                    <div class="empresa-desc"><?= !empty($emp['descripcion']) ? htmlspecialchars(substr($emp['descripcion'],0,60)).'...' : 'Sin descripción aún' ?></div>
                </div>
                <span style="margin-left:auto;color:#7b2ff7;font-size:18px">›</span>
            </div>
        <?php endforeach; endif; ?>
        <div class="modal-btns" style="margin-top:10px">
            <button class="bmc" onclick="document.getElementById('mEmpresas').classList.remove('active')">Cerrar</button>
        </div>
    </div>
</div>

<footer class="footer">© 2026 EquiRed. Conectando oportunidades, construyendo igualdad.</footer>

<script>
function filtrar(){
    const b=document.getElementById('buscar').value.toLowerCase();
    const t=document.getElementById('filtroTipo').value.toLowerCase();
    document.querySelectorAll('.empleo-card').forEach(c=>{
        const ob=c.dataset.titulo.toLowerCase().includes(b)||c.dataset.empresa.toLowerCase().includes(b);
        const ot=t===''||c.dataset.tipo.toLowerCase()===t;
        c.style.display=(ob&&ot)?'':'none';
    });
}

function abrirPostular(id, titulo){
    document.getElementById('mPostular-id').value=id;
    document.getElementById('mPostular-titulo-h').value=titulo;
    document.getElementById('mPostular-titulo').textContent='💼 '+titulo;
    document.getElementById('mPostular').classList.add('active');
}

function verHojaVida(empleoId, titulo){
    document.getElementById('mHV-titulo').textContent='📄 Hojas de vida — '+titulo;
    document.getElementById('mHV-contenido').innerHTML='<div style="text-align:center;padding:20px;color:#aaa">Cargando...</div>';
    document.getElementById('mVacantes').classList.remove('active');
    document.getElementById('mHV').classList.add('active');
    fetch('acciones/obtener_hojas_vida.php?empleo_id='+empleoId)
    .then(r=>r.json()).then(data=>{
        const c=document.getElementById('mHV-contenido');
        if(data.length===0){
            c.innerHTML='<div class="ns"><div style="font-size:36px;margin-bottom:10px">📭</div><p>Aún no hay postulantes.</p></div>';
            return;
        }
        c.innerHTML=data.map(p=>`
            <div class="hv-item" id="hv-${p.id}">
                <div class="hv-header">
                    <div class="hv-nombre">👤 ${p.nombre}</div>
                    <span class="eb ${p.estado==='aceptado'?'ea':p.estado==='rechazado'?'er':'ep'}" id="hv-estado-${p.id}">
                        ${p.estado==='aceptado'?'✅ Aceptado':p.estado==='rechazado'?'❌ Rechazado':'⏳ Pendiente'}
                    </span>
                </div>
                <div class="hv-info">📧 ${p.email} &nbsp;·&nbsp; 📅 ${p.fecha}</div>
                ${p.hoja_vida
                    ? `<a class="hv-link" href="uploads/${p.hoja_vida}" target="_blank">📄 Ver hoja de vida (PDF)</a>`
                    : '<span style="color:#aaa;font-size:13px">Sin PDF adjunto</span>'
                }
                ${p.estado==='pendiente' ? `
                <div class="hv-acciones">
                    <button class="ba ba-acep" onclick="responderPost(${p.id},'aceptado')">✅ Aceptar</button>
                    <button class="ba ba-rech" onclick="responderPost(${p.id},'rechazado')">❌ Rechazar</button>
                </div>` : ''}
            </div>
        `).join('');
    });
}

function responderPost(id, estado){
    fetch('acciones/responder_postulaciones.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`postulacion_id=${id}&estado=${estado}`
    })
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const badge=document.getElementById('hv-estado-'+id);
            badge.className='eb '+(estado==='aceptado'?'ea':'er');
            badge.textContent=estado==='aceptado'?'✅ Aceptado':'❌ Rechazado';
            const item=document.getElementById('hv-'+id);
            const acc=item.querySelector('.hv-acciones');
            if(acc) acc.remove();
        }
    });
}

setTimeout(()=>{const t=document.querySelector('.toast');if(t)t.style.display='none';},3500);
</script>

<?php function t($f){$d=time()-strtotime($f);if($d<60)return"ahora";if($d<3600)return round($d/60)."min";if($d<86400)return round($d/3600)."h";return round($d/86400)." días";} ?>
</body>
</html>