<?php
session_start();
if(!isset($_SESSION['usuario'])){ header("Location: login.php"); exit(); }
require_once("config/conexion.php");

$mi_id     = $_SESSION['usuario_id'];
$perfil_id = (int)($_GET['id'] ?? $mi_id);
$es_mio    = ($perfil_id === $mi_id);
$msg       = $_GET['msg'] ?? '';
$tab       = $_GET['tab'] ?? 'publicaciones';

// Info del perfil
$up = $pdo->prepare("SELECT * FROM usuarios WHERE id=?");
$up->execute([$perfil_id]); $perfil = $up->fetch();
if(!$perfil){ header("Location: home.php"); exit(); }

// Publicaciones del feed con likes y comentarios
$sp = $pdo->prepare("
    SELECT p.*,
        (SELECT COUNT(*) FROM likes_publicacion WHERE publicacion_id=p.id) AS total_likes,
        (SELECT COUNT(*) FROM comentarios_publicacion WHERE publicacion_id=p.id) AS total_comentarios,
        (SELECT COUNT(*) FROM likes_publicacion WHERE publicacion_id=p.id AND usuario_id=$mi_id) AS yo_di_like
    FROM publicaciones p WHERE p.usuario_id=? ORDER BY p.fecha DESC
");
$sp->execute([$perfil_id]); $pubs = $sp->fetchAll();

// Donaciones con likes y solicitudes
$sd = $pdo->prepare("
    SELECT d.*,
        (SELECT COUNT(*) FROM likes_donacion WHERE donacion_id=d.id) AS total_likes,
        (SELECT COUNT(*) FROM comentarios_donacion WHERE donacion_id=d.id) AS total_comentarios,
        (SELECT COUNT(*) FROM solicitudes_donacion WHERE donacion_id=d.id) AS total_solicitudes,
        (SELECT COUNT(*) FROM likes_donacion WHERE donacion_id=d.id AND usuario_id=$mi_id) AS yo_di_like
    FROM donaciones d WHERE d.usuario_id=? ORDER BY d.fecha DESC
");
$sd->execute([$perfil_id]); $dons = $sd->fetchAll();

// Total likes recibidos
$tl_q = $pdo->prepare("SELECT COALESCE(COUNT(*),0) FROM likes_publicacion lp JOIN publicaciones p ON lp.publicacion_id=p.id WHERE p.usuario_id=?");
$tl_q->execute([$perfil_id]); $total_likes = (int)$tl_q->fetchColumn();

// Info del usuario logueado (para comentarios)
$mi = $pdo->prepare("SELECT * FROM usuarios WHERE id=?"); $mi->execute([$mi_id]); $mi_info = $mi->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($perfil['nombre']) ?> - EquiRed</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .perfil-page { max-width:720px; margin:30px auto; padding:0 20px; }

        .perfil-banner { height:180px; border-radius:16px 16px 0 0; background:linear-gradient(135deg,#7b2ff7,#a855f7); }
        .perfil-card { background:white; border-radius:0 0 16px 16px; box-shadow:0 4px 20px rgba(0,0,0,0.08); padding:0 28px 24px; margin-bottom:24px; }
        .paw { position:relative; display:inline-block; margin-top:-50px; margin-bottom:12px; }
        .pa  { width:100px; height:100px; border-radius:50%; border:4px solid white; object-fit:cover; display:block; }
        .pap { width:100px; height:100px; border-radius:50%; border:4px solid white; background:linear-gradient(135deg,#7b2ff7,#a855f7); display:flex; align-items:center; justify-content:center; font-size:40px; color:white; font-weight:800; }
        .bcf { position:absolute; bottom:4px; right:4px; width:30px; height:30px; border-radius:50%; background:#7b2ff7; color:white; border:2px solid white; display:flex; align-items:center; justify-content:center; font-size:14px; cursor:pointer; }
        .pi  { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; }
        .pn  { font-size:22px; font-weight:900; color:#1a1a2e; }
        .pr  { font-size:14px; color:#a855f7; font-weight:700; text-transform:capitalize; margin-top:2px; }
        .pd  { font-size:14px; color:#666; margin-top:8px; line-height:1.6; max-width:500px; }
        .pdv { font-size:14px; color:#ccc; font-style:italic; margin-top:8px; }
        .ps  { display:flex; gap:24px; margin-top:14px; flex-wrap:wrap; }
        .pst { text-align:center; }
        .pst strong { display:block; font-size:20px; font-weight:900; color:#7b2ff7; }
        .pst span   { font-size:12px; color:#888; }
        .bep { padding:10px 20px; background:linear-gradient(135deg,#7b2ff7,#a855f7); color:white; border:none; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }

        .perfil-tabs { display:flex; background:white; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:20px; }
        .ptab { flex:1; padding:14px; text-align:center; font-weight:700; font-size:14px; color:#888; border:none; background:none; cursor:pointer; font-family:inherit; border-bottom:3px solid transparent; transition:all 0.2s; }
        .ptab.active { color:#7b2ff7; border-bottom-color:#7b2ff7; background:#faf5ff; }

        /* ── Publicaciones estilo home ── */
        .post-card { background:white; border-radius:16px; margin-bottom:16px; box-shadow:0 2px 12px rgba(0,0,0,0.06); overflow:visible; }
        .pch { display:flex; justify-content:space-between; align-items:flex-start; padding:16px 20px 10px; }
        .pcu { display:flex; align-items:center; gap:12px; cursor:pointer; }
        .pcu:hover .pun { color:#7b2ff7; }
        .pal { width:42px; height:42px; border-radius:50%; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#f97316,#fb923c); font-size:18px; color:white; font-weight:800; text-decoration:none; }
        .pal img { width:100%; height:100%; object-fit:cover; }
        .pun { font-size:15px; font-weight:800; color:#1a1a2e; transition:color 0.2s; }
        .put { font-size:12px; color:#aaa; }
        .pur { font-size:11px; color:#a855f7; font-weight:600; text-transform:capitalize; }

        .pmw { position:relative; }
        .pmb { background:none; border:none; font-size:22px; color:#aaa; cursor:pointer; padding:4px 8px; border-radius:8px; }
        .pmb:hover { background:#f4f4f8; }
        .pmdd { display:none; position:absolute; right:0; top:36px; background:white; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:50; min-width:170px; border:1px solid #f0f0f0; overflow:hidden; }
        .pmdd.open { display:block; }
        .pmi { display:flex; align-items:center; gap:8px; padding:12px 16px; font-size:14px; font-weight:600; color:#555; cursor:pointer; border:none; background:none; width:100%; font-family:inherit; transition:background 0.2s; text-align:left; }
        .pmi:hover { background:#f4f4f8; }
        .pmi.danger { color:#dc2626; }
        .pmi.danger:hover { background:#fee2e2; }

        .pcc { padding:0 20px 12px; font-size:15px; line-height:1.65; color:#333; }
        .pmedia { background:#f8f8f8; display:flex; align-items:center; justify-content:center; max-height:480px; overflow:hidden; border-top:1px solid #f0f0f0; border-bottom:1px solid #f0f0f0; }
        .pmedia img { max-width:100%; max-height:480px; width:auto; height:auto; object-fit:contain; display:block; }
        .pmedia video { width:100%; max-height:400px; display:block; background:#000; }
        .pcs { padding:10px 20px; display:flex; justify-content:space-between; font-size:13px; color:#aaa; border-top:1px solid #f0f0f0; border-bottom:1px solid #f0f0f0; }
        .pca { display:flex; padding:4px 10px; }
        .pab { flex:1; background:none; border:none; padding:10px; font-size:13px; font-weight:700; color:#666; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; border-radius:8px; font-family:inherit; transition:background 0.2s,color 0.2s; }
        .pab:hover { background:#f4f4f8; color:#7b2ff7; }
        .pab.liked { color:#ef4444; }

        /* Comentarios publicaciones */
        .cs { padding:0 20px 16px; border-top:1px solid #f0f0f0; display:none; }
        .cs.visible { display:block; }
        .ci { display:flex; gap:10px; margin-bottom:10px; padding-top:10px; }
        .ca { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#7b2ff7,#a855f7); display:flex; align-items:center; justify-content:center; font-size:13px; color:white; font-weight:700; flex-shrink:0; overflow:hidden; }
        .ca img { width:100%; height:100%; object-fit:cover; }
        .cb { background:#f4f4f8; border-radius:12px; padding:10px 14px; flex:1; }
        .cb strong { font-size:13px; font-weight:800; color:#1a1a2e; display:block; margin-bottom:2px; }
        .cb p { font-size:13px; color:#555; }
        .cf { display:flex; gap:8px; margin-top:10px; }
        .cf input { flex:1; padding:10px 14px; border:1.5px solid #e8e8e8; border-radius:10px; font-size:13px; font-family:inherit; outline:none; background:#f9f9f9; }
        .cf input:focus { border-color:#7b2ff7; background:white; }
        .bcs { padding:10px 16px; background:linear-gradient(135deg,#7b2ff7,#a855f7); color:white; border:none; border-radius:10px; font-weight:700; font-family:inherit; cursor:pointer; font-size:13px; }

        /* ── Donaciones estilo donar.php ── */
        .don-card { background:white; border-radius:16px; margin-bottom:18px; box-shadow:0 2px 12px rgba(0,0,0,0.06); overflow:visible; }
        .don-header { display:flex; justify-content:space-between; align-items:flex-start; padding:16px 20px 10px; }
        .don-user { display:flex; align-items:center; gap:12px; }
        .don-avatar { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#f97316,#fb923c); display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; color:white; font-weight:800; overflow:hidden; }
        .don-avatar img { width:100%; height:100%; object-fit:cover; }
        .don-user strong { display:block; font-size:15px; font-weight:800; color:#1a1a2e; }
        .don-user span { font-size:12px; color:#aaa; }
        .don-body { padding:0 20px 12px; }
        .don-titulo { font-size:17px; font-weight:800; color:#1a1a2e; margin-bottom:6px; }
        .don-desc { font-size:14px; color:#555; line-height:1.6; margin-bottom:8px; }
        .don-lugar { font-size:13px; color:#aaa; display:flex; align-items:center; gap:5px; }
        .don-media-wrap { background:#f8f8f8; display:flex; align-items:center; justify-content:center; overflow:hidden; max-height:480px; border-top:1px solid #f0f0f0; border-bottom:1px solid #f0f0f0; }
        .don-media { max-width:100%; max-height:480px; width:auto; height:auto; object-fit:contain; display:block; }
        .don-media-video { width:100%; max-height:400px; display:block; background:#000; }
        .don-stats { padding:10px 20px; display:flex; justify-content:space-between; font-size:13px; color:#aaa; border-top:1px solid #f0f0f0; border-bottom:1px solid #f0f0f0; }
        .don-actions { display:flex; padding:4px 10px; }
        .don-action-btn { flex:1; background:none; border:none; padding:10px; font-size:13px; font-weight:700; color:#666; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; border-radius:8px; font-family:inherit; transition:background 0.2s,color 0.2s; }
        .don-action-btn:hover { background:#f4f4f8; color:#7b2ff7; }
        .don-action-btn.liked { color:#ef4444; }
        .btn-solicitar-don { background:linear-gradient(135deg,#7b2ff7,#a855f7)!important; color:white!important; border-radius:8px!important; }
        .btn-solicitar-don:hover { opacity:0.9; color:white!important; }
        .btn-ver-solicitudes { background:#f3e8ff!important; color:#7b2ff7!important; border-radius:8px!important; border:2px solid #d8b4fe!important; }

        /* Comentarios donaciones */
        .don-comentarios { padding:0 20px 16px; border-top:1px solid #f0f0f0; display:none; }
        .don-comentarios.visible { display:block; }

        /* Menú 3 puntos donación */
        .don-menu-wrap { position:relative; }
        .don-menu-btn { background:none; border:none; font-size:22px; color:#aaa; cursor:pointer; padding:4px 8px; border-radius:8px; }
        .don-menu-btn:hover { background:#f4f4f8; }
        .don-menu-dropdown { display:none; position:absolute; right:0; top:36px; background:white; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:50; min-width:160px; overflow:hidden; border:1px solid #f0f0f0; }
        .don-menu-dropdown.open { display:block; }
        .don-menu-item { display:flex; align-items:center; gap:8px; padding:12px 16px; font-size:14px; font-weight:600; color:#555; cursor:pointer; border:none; background:none; width:100%; font-family:inherit; transition:background 0.2s; text-align:left; }
        .don-menu-item:hover { background:#f4f4f8; }
        .don-menu-item.danger { color:#dc2626; }
        .don-menu-item.danger:hover { background:#fee2e2; }

        /* Modales */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.active { display:flex; }
        .modal { background:white; border-radius:20px; padding:36px; width:100%; max-width:480px; max-height:90vh; overflow-y:auto; }
        .modal h3 { font-size:20px; font-weight:900; margin-bottom:20px; color:#1a1a2e; }
        .modal label { display:block; font-weight:700; font-size:14px; color:#333; margin-bottom:6px; }
        .modal input,.modal textarea { width:100%; padding:12px 14px; border:1.5px solid #e8e8e8; border-radius:10px; font-size:14px; font-family:inherit; margin-bottom:14px; outline:none; background:#f9f9f9; box-sizing:border-box; }
        .modal input:focus,.modal textarea:focus { border-color:#7b2ff7; background:white; }
        .modal textarea { height:100px; resize:none; }
        .modal-close { float:right; background:none; border:none; font-size:22px; cursor:pointer; color:#aaa; margin-top:-8px; }
        .modal-btns { display:flex; gap:10px; margin-top:8px; }
        .bmc { flex:1; padding:12px; background:#f4f4f8; color:#555; border:none; border-radius:10px; font-weight:700; font-family:inherit; cursor:pointer; }
        .bms { flex:2; padding:12px; background:linear-gradient(135deg,#7b2ff7,#a855f7); color:white; border:none; border-radius:10px; font-weight:800; font-family:inherit; cursor:pointer; }
        .bmd { flex:2; padding:12px; background:#dc2626; color:white; border:none; border-radius:10px; font-weight:800; font-family:inherit; cursor:pointer; }
        .fl { display:flex; align-items:center; gap:8px; padding:12px 14px; border:1.5px dashed #d8b4fe; border-radius:10px; cursor:pointer; font-size:14px; color:#7b2ff7; font-weight:700; margin-bottom:14px; background:#faf5ff; }
        .fl input { display:none; }
        .ap { width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:14px; display:none; }

        /* Toast */
        .toast { position:fixed; top:80px; right:24px; z-index:999; padding:14px 22px; border-radius:12px; font-size:15px; font-weight:700; box-shadow:0 4px 20px rgba(0,0,0,0.15); animation:slideIn 0.3s ease,fadeOut 0.4s ease 3s forwards; }
        .toast-exito { background:#d1fae5; color:#059669; border:1px solid #a7f3d0; }
        @keyframes slideIn { from{transform:translateX(120%);opacity:0} to{transform:translateX(0);opacity:1} }
        @keyframes fadeOut { from{opacity:1} to{opacity:0;visibility:hidden} }

        .vacio { text-align:center; padding:40px; color:#aaa; }
        .vacio .icon { font-size:40px; margin-bottom:10px; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
    </style>
</head>
<body>

<?php include("includes/navbar.php"); ?>

<?php if($msg==='actualizado'): ?><div class="toast toast-exito">✅ Perfil actualizado.</div>
<?php elseif($msg==='eliminado'): ?><div class="toast toast-exito">🗑️ Publicación eliminada.</div><?php endif; ?>

<div class="perfil-page">

    <div class="perfil-banner"></div>
    <div class="perfil-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px">
            <div class="paw">
                <?php if(!empty($perfil['foto_perfil'])): ?>
                    <img class="pa" src="uploads/<?= htmlspecialchars($perfil['foto_perfil']) ?>" alt="">
                <?php else: ?>
                    <div class="pap"><?= strtoupper(substr($perfil['nombre'],0,1)) ?></div>
                <?php endif; ?>
                <?php if($es_mio): ?>
                    <button class="bcf" onclick="document.getElementById('mEditar').classList.add('active')" title="Cambiar foto">📷</button>
                <?php endif; ?>
            </div>
            <?php if($es_mio): ?>
                <button class="bep" onclick="document.getElementById('mEditar').classList.add('active')">✏️ Editar perfil</button>
            <?php else: ?>
                <a href="chat.php?con=<?= $perfil_id ?>" class="bep">💬 Chatear</a>
            <?php endif; ?>
        </div>

        <div class="pi">
            <div>
                <div class="pn"><?= htmlspecialchars($perfil['nombre']) ?></div>
                <div class="pr"><?= htmlspecialchars($perfil['rol']) ?></div>
                <?php if(!empty($perfil['descripcion'])): ?>
                    <div class="pd"><?= nl2br(htmlspecialchars($perfil['descripcion'])) ?></div>
                <?php else: ?>
                    <div class="pdv"><?= $es_mio?'Agrega una descripción sobre ti...':'Sin descripción.' ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="ps">
            <div class="pst"><strong><?= count($pubs) ?></strong><span>Publicaciones</span></div>
            <div class="pst"><strong><?= count($dons) ?></strong><span>Donaciones</span></div>
            <div class="pst"><strong><?= $total_likes ?></strong><span>Me gusta recibidos</span></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="perfil-tabs">
        <button class="ptab <?= $tab==='publicaciones'?'active':'' ?>" onclick="cambiarTab('publicaciones')">
            📝 Publicaciones (<?= count($pubs) ?>)
        </button>
        <button class="ptab <?= $tab==='donaciones'?'active':'' ?>" onclick="cambiarTab('donaciones')">
            📦 Donaciones (<?= count($dons) ?>)
        </button>
    </div>

    <!-- ── Tab Publicaciones ── -->
    <div class="tab-content <?= $tab==='publicaciones'?'active':'' ?>" id="tab-publicaciones">
        <?php if(empty($pubs)): ?>
            <div class="vacio"><div class="icon">📭</div><p><?= $es_mio?'Aún no has publicado nada.':'Sin publicaciones aún.' ?></p></div>
        <?php else: foreach($pubs as $pub): ?>

        <div class="post-card" id="post-<?= $pub['id'] ?>">
            <div class="pch">
                <div class="pcu" onclick="window.location.href='perfil.php?id=<?= $perfil_id ?>'">
                    <a href="perfil.php?id=<?= $perfil_id ?>" class="pal" onclick="event.stopPropagation()">
                        <?php if(!empty($perfil['foto_perfil'])): ?>
                            <img src="uploads/<?= htmlspecialchars($perfil['foto_perfil']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($perfil['nombre'],0,1)) ?>
                        <?php endif; ?>
                    </a>
                    <div>
                        <div class="pun"><?= htmlspecialchars($perfil['nombre']) ?></div>
                        <div class="pur"><?= htmlspecialchars($perfil['rol']) ?></div>
                        <div class="put"><?= t2($pub['fecha']) ?></div>
                    </div>
                </div>
                <div class="pmw">
                    <button class="pmb" onclick="tPM(<?= $pub['id'] ?>)">⋮</button>
                    <div class="pmdd" id="pm<?= $pub['id'] ?>">
                        <?php if($pub['usuario_id'] == $mi_id): ?>
                            <button class="pmi danger" onclick="cEP(<?= $pub['id'] ?>)">🗑️ Eliminar</button>
                        <?php else: ?>
                            <button class="pmi" onclick="window.location.href='perfil.php?id=<?= $pub['usuario_id'] ?>'">👤 Ver perfil</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if(!empty($pub['contenido'])): ?>
                <div class="pcc"><?= nl2br(htmlspecialchars($pub['contenido'])) ?></div>
            <?php endif; ?>

            <?php if(!empty($pub['imagen'])):
                $ext = strtolower(pathinfo($pub['imagen'], PATHINFO_EXTENSION));
                $ev  = in_array($ext,['mp4','webm','ogg','mov']); ?>
                <?php if($ev): ?>
                    <video style="width:100%;max-height:400px;display:block;background:#000" controls>
                        <source src="uploads/<?= htmlspecialchars($pub['imagen']) ?>">
                    </video>
                <?php else: ?>
                    <div class="pmedia"><img src="uploads/<?= htmlspecialchars($pub['imagen']) ?>" alt=""></div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="pcs">
                <span id="pl<?= $pub['id'] ?>"><?= $pub['total_likes'] ?> me gusta</span>
                <span><?= $pub['total_comentarios'] ?> comentarios</span>
            </div>

            <div class="pca">
                <button class="pab <?= $pub['yo_di_like']?'liked':'' ?>"
                    id="plb<?= $pub['id'] ?>" onclick="tL(<?= $pub['id'] ?>,this)">
                    <?= $pub['yo_di_like']?'❤️':'♡' ?> Me gusta
                </button>
                <button class="pab" onclick="tC(<?= $pub['id'] ?>)">💬 Comentar</button>
            </div>

            <!-- Comentarios -->
            <div class="cs" id="pc<?= $pub['id'] ?>">
                <?php
                $sc = $pdo->prepare("SELECT c.*,u.nombre,u.foto_perfil FROM comentarios_publicacion c JOIN usuarios u ON c.usuario_id=u.id WHERE c.publicacion_id=? ORDER BY c.fecha ASC LIMIT 10");
                $sc->execute([$pub['id']]);
                foreach($sc->fetchAll() as $c): ?>
                <div class="ci">
                    <div class="ca">
                        <?php if(!empty($c['foto_perfil'])): ?>
                            <img src="uploads/<?= htmlspecialchars($c['foto_perfil']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($c['nombre'],0,1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="cb">
                        <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                        <p><?= htmlspecialchars($c['comentario']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <form class="cf" onsubmit="eC(event,<?= $pub['id'] ?>)">
                    <input type="text" id="pic<?= $pub['id'] ?>" placeholder="Escribe un comentario...">
                    <button type="submit" class="bcs">Enviar</button>
                </form>
            </div>
        </div>

        <?php endforeach; endif; ?>
    </div>

    <!-- ── Tab Donaciones ── -->
    <div class="tab-content <?= $tab==='donaciones'?'active':'' ?>" id="tab-donaciones">
        <?php if(empty($dons)): ?>
            <div class="vacio"><div class="icon">📦</div><p><?= $es_mio?'No has publicado donaciones aún.':'Sin donaciones aún.' ?></p></div>
        <?php else: foreach($dons as $don): ?>

        <div class="don-card" id="card-<?= $don['id'] ?>">
            <div class="don-header">
                <div class="don-user">
                    <a href="perfil.php?id=<?= $perfil_id ?>" class="don-avatar" style="text-decoration:none">
                        <?php if(!empty($perfil['foto_perfil'])): ?>
                            <img src="uploads/<?= htmlspecialchars($perfil['foto_perfil']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($perfil['nombre'],0,1)) ?>
                        <?php endif; ?>
                    </a>
                    <div>
                        <a href="perfil.php?id=<?= $perfil_id ?>" style="text-decoration:none;color:inherit">
                            <strong><?= htmlspecialchars($perfil['nombre']) ?></strong>
                        </a>
                        <span><?= t2($don['fecha']) ?></span>
                    </div>
                </div>
                <!-- Menú 3 puntos donación -->
                <div class="don-menu-wrap">
                    <button class="don-menu-btn" onclick="toggleDonMenu(<?= $don['id'] ?>)">⋮</button>
                    <div class="don-menu-dropdown" id="don-menu-<?= $don['id'] ?>">
                        <?php if($es_mio): ?>
                            <button class="don-menu-item danger" onclick="confirmarEliminarDon(<?= $don['id'] ?>,'<?= htmlspecialchars($don['titulo']??'esta donación',ENT_QUOTES) ?>')">🗑️ Eliminar</button>
                        <?php else: ?>
                            <button class="don-menu-item" onclick="cerrarDonMenu(<?= $don['id'] ?>)">🚩 Reportar</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="don-body">
                <?php if(!empty($don['titulo'])): ?><div class="don-titulo"><?= htmlspecialchars($don['titulo']) ?></div><?php endif; ?>
                <?php if(!empty($don['descripcion'])): ?><div class="don-desc"><?= nl2br(htmlspecialchars($don['descripcion'])) ?></div><?php endif; ?>
                <?php if(!empty($don['ciudad'])): ?><div class="don-lugar">📍 <?= htmlspecialchars($don['ciudad']) ?></div><?php endif; ?>
            </div>

            <?php if(!empty($don['imagen'])):
                $ext    = strtolower(pathinfo($don['imagen'], PATHINFO_EXTENSION));
                $esVideo = in_array($ext,['mp4','webm','ogg','mov']); ?>
                <?php if($esVideo): ?>
                    <video class="don-media-video" controls><source src="uploads/<?= htmlspecialchars($don['imagen']) ?>"></video>
                <?php else: ?>
                    <div class="don-media-wrap"><img class="don-media" src="uploads/<?= htmlspecialchars($don['imagen']) ?>" alt=""></div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="don-stats">
                <span id="don-likes-<?= $don['id'] ?>"><?= $don['total_likes'] ?> me gusta</span>
                <span><?= $don['total_comentarios'] ?> comentarios · <?= $don['total_solicitudes'] ?> solicitudes</span>
            </div>

            <div class="don-actions">
                <button class="don-action-btn <?= $don['yo_di_like']?'liked':'' ?>"
                    id="don-like-btn-<?= $don['id'] ?>"
                    onclick="toggleDonLike(<?= $don['id'] ?>,this)">
                    <?= $don['yo_di_like']?'❤️':'♡' ?> Me gusta
                </button>
                <button class="don-action-btn" onclick="toggleDonCom(<?= $don['id'] ?>)">💬 Comentar</button>
                <?php if($es_mio): ?>
                    <button class="don-action-btn btn-ver-solicitudes" onclick="verSolicitudesDon(<?= $don['id'] ?>)">
                        📋 Solicitudes (<?= $don['total_solicitudes'] ?>)
                    </button>
                <?php elseif($mi_id != $don['usuario_id']): ?>
                    <button class="don-action-btn btn-solicitar-don" onclick="abrirSolicitar(<?= $don['id'] ?>,'<?= htmlspecialchars($don['titulo']??'esta donación',ENT_QUOTES) ?>')">
                        Solicitar
                    </button>
                <?php endif; ?>
            </div>

            <!-- Comentarios donación -->
            <div class="don-comentarios" id="don-com-<?= $don['id'] ?>">
                <?php
                $scd = $pdo->prepare("SELECT c.*,u.nombre,u.foto_perfil FROM comentarios_donacion c JOIN usuarios u ON c.usuario_id=u.id WHERE c.donacion_id=? ORDER BY c.fecha ASC LIMIT 10");
                $scd->execute([$don['id']]);
                foreach($scd->fetchAll() as $c): ?>
                <div class="ci" style="padding-top:10px">
                    <div class="ca">
                        <?php if(!empty($c['foto_perfil'])): ?>
                            <img src="uploads/<?= htmlspecialchars($c['foto_perfil']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($c['nombre'],0,1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="cb">
                        <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                        <p><?= htmlspecialchars($c['comentario']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <form class="cf" onsubmit="enviarDonCom(event,<?= $don['id'] ?>)" style="margin-top:10px">
                    <input type="text" id="don-input-<?= $don['id'] ?>" placeholder="Escribe un comentario...">
                    <button type="submit" class="bcs">Enviar</button>
                </form>
            </div>
        </div>

        <?php endforeach; endif; ?>
    </div>

</div><!-- fin perfil-page -->

<!-- Modal solicitar donación -->
<div class="modal-overlay" id="modalSolicitarDon">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalSolicitarDon').classList.remove('active')">✕</button>
        <h3>✉️ Solicitar donación</h3>
        <p id="sol-don-titulo" style="color:#888;font-size:14px;margin-bottom:16px"></p>
        <form action="acciones/solicitar_donacion.php" method="POST">
            <input type="hidden" name="donacion_id" id="sol-don-id">
            <label>Mensaje</label>
            <textarea name="mensaje" placeholder="Cuéntale al donante por qué necesitas esta donación..." required></textarea>
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('modalSolicitarDon').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bms">Enviar solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal ver solicitudes de mi donación -->
<div class="modal-overlay" id="modalMisSolDon">
    <div class="modal" style="max-width:560px">
        <button class="modal-close" onclick="document.getElementById('modalMisSolDon').classList.remove('active')">✕</button>
        <h3>📋 Solicitudes de mi donación</h3>
        <p style="color:#888;font-size:13px;margin-bottom:16px">Estas personas están interesadas en tu donación.</p>
        <div id="listaSolDon"><div style="text-align:center;padding:20px;color:#aaa">Cargando...</div></div>
        <div class="modal-btns" style="margin-top:10px">
            <button class="bmc" onclick="document.getElementById('modalMisSolDon').classList.remove('active')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal confirmar eliminar donación -->
<div class="modal-overlay" id="mEliminarDon">
    <div class="modal">
        <h3>🗑️ Eliminar donación</h3>
        <p style="color:#555;margin-bottom:20px">¿Seguro que deseas eliminar <strong id="don-titulo-del"></strong>? Esta acción no se puede deshacer.</p>
        <form action="acciones/eliminar_donacion.php" method="POST">
            <input type="hidden" name="donacion_id" id="don-del-id">
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mEliminarDon').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bmd">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal editar perfil -->
<?php if($es_mio): ?>
<div class="modal-overlay" id="mEditar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('mEditar').classList.remove('active')">✕</button>
        <h3>✏️ Editar mi perfil</h3>
        <form action="acciones/actualizar_perfil.php" method="POST" enctype="multipart/form-data">
            <label>Foto de perfil</label>
            <label class="fl">📷 Seleccionar foto<input type="file" name="foto_perfil" accept="image/*" onchange="pAv(this)"></label>
            <img id="ap" class="ap" src="" alt="">
            <label>Nombre</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($perfil['nombre']) ?>" required>
            <label>Descripción</label>
            <textarea name="descripcion" placeholder="Ej: Soy diseñador apasionado por la inclusión..."><?= htmlspecialchars($perfil['descripcion']??'') ?></textarea>
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mEditar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bms">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal confirmar eliminar publicación -->
<div class="modal-overlay" id="mEliminar">
    <div class="modal">
        <h3>🗑️ Eliminar publicación</h3>
        <p style="color:#555;margin-bottom:20px">¿Estás seguro que quieres eliminar esta publicación?</p>
        <form action="acciones/eliminar_publicacion.php" method="POST">
            <input type="hidden" name="publicacion_id" id="del-pub-id">
            <input type="hidden" name="redirect" value="perfil.php">
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mEliminar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bmd">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<footer class="footer">© 2026 EquiRed. Conectando oportunidades, construyendo igualdad.</footer>

<script>
// Tabs
function cambiarTab(t){
    document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
    document.querySelectorAll('.ptab').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+t).classList.add('active');
    document.querySelector(`[onclick="cambiarTab('${t}')"]`).classList.add('active');
}

// Editar perfil
function pAv(i){ const p=document.getElementById('ap'); if(i.files&&i.files[0]){ p.src=URL.createObjectURL(i.files[0]); p.style.display='block'; } }

// Eliminar publicación
function cEP(id){
    document.getElementById('del-pub-id').value=id;
    document.querySelectorAll('.pmdd').forEach(d=>d.classList.remove('open'));
    document.getElementById('mEliminar').classList.add('active');
}

// Menú 3 puntos publicación
function tPM(id){
    const m=document.getElementById('pm'+id);
    document.querySelectorAll('.pmdd').forEach(d=>{if(d.id!=='pm'+id)d.classList.remove('open');});
    m.classList.toggle('open');
}
document.addEventListener('click',e=>{
    if(!e.target.closest('.pmw')&&!e.target.closest('.don-menu-wrap')){
        document.querySelectorAll('.pmdd,.don-menu-dropdown').forEach(d=>d.classList.remove('open'));
    }
});

// Like publicación
function tL(id,btn){
    fetch('acciones/like_publicacion.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'publicacion_id='+id})
    .then(r=>r.json()).then(d=>{
        btn.classList.toggle('liked',d.liked);
        btn.innerHTML=(d.liked?'❤️':'♡')+' Me gusta';
        document.getElementById('pl'+id).textContent=d.total_likes+' me gusta';
    });
}

// Comentar publicación
function tC(id){ document.getElementById('pc'+id).classList.toggle('visible'); }
function eC(e,id){
    e.preventDefault();
    const inp=document.getElementById('pic'+id); const t=inp.value.trim(); if(!t) return;
    fetch('acciones/comentar_publicacion.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'publicacion_id='+id+'&comentario='+encodeURIComponent(t)})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const s=document.getElementById('pc'+id); const f=s.querySelector('.cf');
            const div=document.createElement('div'); div.className='ci';
            div.innerHTML=`<div class="ca">${d.inicial}</div><div class="cb"><strong>${d.nombre}</strong><p>${t}</p></div>`;
            s.insertBefore(div,f); inp.value='';
        }
    });
}

// Menú 3 puntos donación
function toggleDonMenu(id){
    const m=document.getElementById('don-menu-'+id);
    document.querySelectorAll('.don-menu-dropdown').forEach(d=>{if(d.id!=='don-menu-'+id)d.classList.remove('open');});
    m.classList.toggle('open');
}
function cerrarDonMenu(id){ document.getElementById('don-menu-'+id).classList.remove('open'); }

// Eliminar donación
function confirmarEliminarDon(id,titulo){
    document.getElementById('don-del-id').value=id;
    document.getElementById('don-titulo-del').textContent='"'+titulo+'"';
    cerrarDonMenu(id);
    document.getElementById('mEliminarDon').classList.add('active');
}

// Like donación
function toggleDonLike(id,btn){
    fetch('acciones/like_donacion.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'donacion_id='+id})
    .then(r=>r.json()).then(d=>{
        btn.classList.toggle('liked',d.liked);
        btn.innerHTML=(d.liked?'❤️':'♡')+' Me gusta';
        document.getElementById('don-likes-'+id).textContent=d.total_likes+' me gusta';
    });
}

// Comentar donación
function toggleDonCom(id){ document.getElementById('don-com-'+id).classList.toggle('visible'); }
function enviarDonCom(e,id){
    e.preventDefault();
    const input=document.getElementById('don-input-'+id); const texto=input.value.trim(); if(!texto) return;
    fetch('acciones/comentar_donacion.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'donacion_id='+id+'&comentario='+encodeURIComponent(texto)})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const sec=document.getElementById('don-com-'+id); const form=sec.querySelector('.cf');
            const div=document.createElement('div'); div.className='ci'; div.style.paddingTop='10px';
            div.innerHTML=`<div class="ca">${d.inicial}</div><div class="cb"><strong>${d.nombre}</strong><p>${texto}</p></div>`;
            sec.insertBefore(div,form); input.value='';
        }
    });
}

// Solicitar donación
function abrirSolicitar(id,titulo){
    document.getElementById('sol-don-id').value=id;
    document.getElementById('sol-don-titulo').textContent='📦 '+titulo;
    document.getElementById('modalSolicitarDon').classList.add('active');
}

// Ver solicitudes de mi donación
function verSolicitudesDon(donacionId){
    document.getElementById('modalMisSolDon').classList.add('active');
    document.getElementById('listaSolDon').innerHTML='<div style="text-align:center;padding:20px;color:#aaa">Cargando...</div>';
    fetch('acciones/obtener_solicitudes_donacion.php?donacion_id='+donacionId)
    .then(r=>r.json()).then(data=>{
        const cont=document.getElementById('listaSolDon');
        if(data.length===0){cont.innerHTML='<div style="text-align:center;padding:30px;color:#aaa"><div style="font-size:36px;margin-bottom:10px">📭</div><p>Nadie ha solicitado esta donación aún.</p></div>';return;}
        cont.innerHTML=data.map(s=>`
            <div style="background:#f9f9f9;border-radius:12px;padding:16px;margin-bottom:12px;border-left:4px solid #7b2ff7">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                    <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#7b2ff7,#a855f7);display:flex;align-items:center;justify-content:center;font-size:16px;color:white;font-weight:800;overflow:hidden;flex-shrink:0">
                        ${s.foto_perfil?`<img src="uploads/${s.foto_perfil}" style="width:100%;height:100%;object-fit:cover">`:s.nombre.charAt(0).toUpperCase()}
                    </div>
                    <div style="flex:1">
                        <div style="font-size:15px;font-weight:800;color:#1a1a2e">${s.nombre}</div>
                        <div style="font-size:12px;color:#a855f7;font-weight:700;text-transform:capitalize">${s.rol}</div>
                    </div>
                    <div style="font-size:11px;color:#aaa">${new Date(s.fecha).toLocaleDateString('es-CO')}</div>
                </div>
                ${s.mensaje?`<div style="font-size:14px;color:#555;background:white;padding:10px 14px;border-radius:10px;margin-bottom:10px;line-height:1.6">💬 ${s.mensaje}</div>`:''}
                <div style="display:flex;gap:8px">
                    <a href="perfil.php?id=${s.solicitante_id}" style="flex:1;padding:8px;background:#f3e8ff;color:#7b2ff7;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;text-align:center;text-decoration:none;display:block">👤 Ver perfil</a>
                    <a href="chat.php?con=${s.solicitante_id}" style="flex:1;padding:8px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;text-align:center;text-decoration:none;display:block">💬 Chatear</a>
                </div>
            </div>
        `).join('');
    });
}

setTimeout(()=>{ const t=document.querySelector('.toast'); if(t) t.style.display='none'; },3500);
</script>

<?php function t2($f){ $d=time()-strtotime($f); if($d<3600) return round($d/60)." min"; if($d<86400) return "Hace ".round($d/3600)."h"; return date('d/m/Y',strtotime($f)); } ?>
</body>
</html>