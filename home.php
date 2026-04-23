<?php
session_start();
if(!isset($_SESSION['usuario'])){ header("Location: login.php"); exit(); }
require_once("config/conexion.php");

$usuario_id = $_SESSION['usuario_id'];
$buscar     = trim($_GET['buscar'] ?? '');
$likeCol    = "(SELECT COUNT(*) FROM likes_publicacion WHERE publicacion_id=p.id AND usuario_id=$usuario_id) AS yo_di_like";
$where = ''; $params = [];
if($buscar !== ''){ $where="WHERE (p.contenido LIKE ? OR u.nombre LIKE ?)"; $params=["%$buscar%","%$buscar%"]; }

$sql = "SELECT p.*, u.nombre, u.foto_perfil, u.rol,
    (SELECT COUNT(*) FROM likes_publicacion l WHERE l.publicacion_id=p.id) AS total_likes,
    (SELECT COUNT(*) FROM comentarios_publicacion c WHERE c.publicacion_id=p.id) AS total_comentarios,
    $likeCol
    FROM publicaciones p JOIN usuarios u ON p.usuario_id=u.id
    $where ORDER BY p.fecha DESC LIMIT 30";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$publicaciones = $stmt->fetchAll();

$mi = $pdo->prepare("SELECT * FROM usuarios WHERE id=?"); $mi->execute([$usuario_id]); $mi_info = $mi->fetch();
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - EquiRed</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .feed-container { max-width:680px; margin:30px auto; padding:0 20px; }

        /* Buscador */
        .feed-search { background:white; border-radius:14px; padding:12px 16px; box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:18px; display:flex; gap:10px; align-items:center; }
        .fsw { flex:1; position:relative; }
        .fsw .si { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#aaa; }
        .fsw input { width:100%; padding:10px 14px 10px 36px; border:1.5px solid #e8e8e8; border-radius:10px; font-size:14px; font-family:inherit; outline:none; background:#f9f9f9; }
        .fsw input:focus { border-color:#7b2ff7; background:white; }
        .btn-search { padding:10px 18px; background:linear-gradient(135deg,#7b2ff7,#a855f7); color:white; border:none; border-radius:10px; font-weight:700; font-family:inherit; cursor:pointer; font-size:14px; }
        .limpiar-link { color:#7b2ff7; font-weight:700; font-size:13px; text-decoration:none; }

        /* Caja publicar */
        .post-box { background:white; padding:18px; border-radius:16px; margin-bottom:18px; box-shadow:0 2px 12px rgba(0,0,0,0.06); display:flex; gap:14px; align-items:flex-start; }
        .post-box-av { width:42px; height:42px; border-radius:50%; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#7b2ff7,#a855f7); font-size:18px; color:white; font-weight:800; }
        .post-box-av img { width:100%; height:100%; object-fit:cover; }
        .post-box-r { flex:1; }
        .post-box-r textarea { width:100%; border:1.5px solid #e8e8e8; border-radius:10px; padding:12px 14px; font-family:inherit; font-size:14px; resize:none; height:70px; outline:none; color:#333; background:#f9f9f9; transition:border-color 0.2s; }
        .post-box-r textarea:focus { border-color:#7b2ff7; background:white; }
        .mpw { display:none; background:#f8f8f8; border-radius:10px; margin-top:10px; overflow:hidden; position:relative; }
        .mpw.v { display:block; }
        .mpw img, .mpw video { width:100%; max-height:200px; object-fit:contain; display:block; }
        .brm { position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.5); color:white; border:none; border-radius:50%; width:24px; height:24px; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
        .pba { display:flex; align-items:center; justify-content:space-between; margin-top:10px; }
        .pbm { display:flex; gap:12px; }
        .bma { background:none; border:none; color:#888; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:5px; font-family:inherit; padding:6px 10px; border-radius:8px; transition:background 0.2s; }
        .bma:hover { background:#f3e8ff; color:#7b2ff7; }
        .btn-pub { background:linear-gradient(135deg,#7b2ff7,#a855f7); color:white; border:none; padding:9px 22px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; font-family:inherit; }

        /* Post cards */
        .post-card { background:white; border-radius:16px; margin-bottom:16px; box-shadow:0 2px 12px rgba(0,0,0,0.06); overflow:visible; }
        .pch { display:flex; justify-content:space-between; align-items:flex-start; padding:16px 20px 10px; }
        .pcu { display:flex; align-items:center; gap:12px; cursor:pointer; }
        .pcu:hover .pun { color:#7b2ff7; }
        .pal { width:42px; height:42px; border-radius:50%; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#f97316,#fb923c); font-size:18px; color:white; font-weight:800; text-decoration:none; }
        .pal img { width:100%; height:100%; object-fit:cover; }
        .pun { font-size:15px; font-weight:800; color:#1a1a2e; transition:color 0.2s; }
        .put { font-size:12px; color:#aaa; }
        .pur { font-size:11px; color:#a855f7; font-weight:600; text-transform:capitalize; }

        /* Menú 3 puntos */
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
        .pab.shared { color:#7b2ff7; }

        /* Comentarios */
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

        /* Toast */
        .toast { position:fixed; top:80px; right:24px; z-index:999; padding:14px 22px; border-radius:12px; font-size:15px; font-weight:700; box-shadow:0 4px 20px rgba(0,0,0,0.15); animation:slideIn 0.3s ease,fadeOut 0.4s ease 3s forwards; }
        .toast-exito { background:#d1fae5; color:#059669; border:1px solid #a7f3d0; }
        @keyframes slideIn { from{transform:translateX(120%);opacity:0} to{transform:translateX(0);opacity:1} }
        @keyframes fadeOut { from{opacity:1} to{opacity:0;visibility:hidden} }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.active { display:flex; }
        .modal { background:white; border-radius:20px; padding:36px; width:100%; max-width:440px; }
        .modal h3 { font-size:20px; font-weight:900; margin-bottom:16px; color:#1a1a2e; }
        .modal p { color:#555; font-size:14px; line-height:1.6; margin-bottom:20px; }
        .modal-btns { display:flex; gap:10px; }
        .bmc { flex:1; padding:12px; background:#f4f4f8; color:#555; border:none; border-radius:10px; font-weight:700; font-family:inherit; cursor:pointer; }
        .bmd { flex:2; padding:12px; background:#dc2626; color:white; border:none; border-radius:10px; font-weight:800; font-family:inherit; cursor:pointer; }

        .sr { text-align:center; padding:40px; color:#aaa; }
        .sr .icon { font-size:40px; margin-bottom:12px; }
    </style>
</head>
<body>

<?php include("includes/navbar.php"); ?>

<?php if($msg==='publicado'): ?><div class="toast toast-exito">✅ ¡Publicación creada!</div>
<?php elseif($msg==='eliminado'): ?><div class="toast toast-exito">🗑️ Publicación eliminada.</div><?php endif; ?>

<div class="feed-container">

   <!-- Banner primero -->
    <div class="home-banner" style="margin-bottom:18px;">
        <h2>Construyendo Igualdad Juntos</h2>
        <p>Buscamos construir una sociedad más justa donde todas las personas tengan las mismas oportunidades. Cada mensaje es una invitación a creer en un futuro más inclusivo.</p>
    </div>

    <!-- Buscador -->
    <form method="GET" action="home.php">
        <div class="feed-search">
            <div class="fsw">
                <span class="si">🔍</span>
                <input type="text" name="buscar" placeholder="Buscar publicaciones o usuarios..." value="<?= htmlspecialchars($buscar) ?>">
            </div>
            <button type="submit" class="btn-search">Buscar</button>
            <?php if($buscar): ?><a href="home.php" class="limpiar-link">✕</a><?php endif; ?>
        </div>
    </form>

    <!-- Caja publicar -->
    <div class="post-box">
        <div class="post-box-av">
            <?php if(!empty($mi_info['foto_perfil'])): ?>
                <img src="uploads/<?= htmlspecialchars($mi_info['foto_perfil']) ?>" alt="">
            <?php else: ?>
                <?= strtoupper(substr($mi_info['nombre'],0,1)) ?>
            <?php endif; ?>
        </div>
        <div class="post-box-r">
            <form action="acciones/publicar.php" method="POST" enctype="multipart/form-data">
                <textarea name="contenido" placeholder="¿Qué quieres compartir hoy?"></textarea>
                <div class="mpw" id="mpw">
                    <img id="ipv" src="" alt="" style="display:none">
                    <video id="vpv" controls style="display:none"></video>
                    <button type="button" class="brm" onclick="qM()">✕</button>
                </div>
                <input type="file" name="media" id="mi" accept="image/*,video/*" style="display:none" onchange="pM(this)">
                <div class="pba">
                    <div class="pbm">
                        <button type="button" class="bma" onclick="document.getElementById('mi').click()">📷 Foto</button>
                        <button type="button" class="bma" onclick="document.getElementById('mi').click()">🎥 Video</button>
                    </div>
                    <button type="submit" class="btn-pub">Publicar</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Publicaciones -->
    <?php if(empty($publicaciones)): ?>
        <div class="sr">
            <div class="icon"><?= $buscar?'🔍':'📝' ?></div>
            <p><?= $buscar?'Sin resultados para "'.htmlspecialchars($buscar).'"':'¡Sé el primero en publicar!' ?></p>
        </div>
    <?php else: foreach($publicaciones as $pub): ?>

    <div class="post-card" id="post-<?= $pub['id'] ?>">
        <div class="pch">
            <!-- Avatar + nombre → perfil -->
            <div class="pcu" onclick="window.location.href='perfil.php?id=<?= $pub['usuario_id'] ?>'">
                <a href="perfil.php?id=<?= $pub['usuario_id'] ?>" class="pal" onclick="event.stopPropagation()">
                    <?php if(!empty($pub['foto_perfil'])): ?>
                        <img src="uploads/<?= htmlspecialchars($pub['foto_perfil']) ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($pub['nombre'],0,1)) ?>
                    <?php endif; ?>
                </a>
                <div>
                    <div class="pun"><?= htmlspecialchars($pub['nombre']) ?></div>
                    <div class="pur"><?= htmlspecialchars($pub['rol']) ?></div>
                    <div class="put"><?= t($pub['fecha']) ?></div>
                </div>
            </div>

            <!-- Menú 3 puntos — TODOS los roles pueden eliminar sus propias publicaciones -->
            <div class="pmw">
                <button class="pmb" onclick="tPM(<?= $pub['id'] ?>)">⋮</button>
                <div class="pmdd" id="pm<?= $pub['id'] ?>">
                    <?php if($pub['usuario_id'] == $usuario_id): ?>
                        <button class="pmi danger" onclick="cEP(<?= $pub['id'] ?>)">🗑️ Eliminar</button>
                    <?php else: ?>
                        <button class="pmi" onclick="window.location.href='perfil.php?id=<?= $pub['usuario_id'] ?>'">👤 Ver perfil</button>
                        <button class="pmi" onclick="clPM(<?= $pub['id'] ?>)">🚩 Reportar</button>
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
                <div class="pmedia">
                    <img src="uploads/<?= htmlspecialchars($pub['imagen']) ?>" alt="">
                </div>
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
            <button class="pab" onclick="sP(<?= $pub['id'] ?>,this)">↗ Compartir</button>
        </div>

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

<!-- Modal confirmar eliminar -->
<div class="modal-overlay" id="mEP">
    <div class="modal">
        <h3>🗑️ Eliminar publicación</h3>
        <p>¿Estás seguro que quieres eliminar esta publicación? Esta acción no se puede deshacer.</p>
        <form action="acciones/eliminar_publicacion.php" method="POST">
            <input type="hidden" name="publicacion_id" id="epid">
            <input type="hidden" name="redirect" value="home.php">
            <div class="modal-btns">
                <button type="button" class="bmc" onclick="document.getElementById('mEP').classList.remove('active')">Cancelar</button>
                <button type="submit" class="bmd">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">© 2026 EquiRed. Conectando oportunidades, construyendo igualdad.</footer>

<script>
// Preview media
function pM(i){
    const w=document.getElementById('mpw'),img=document.getElementById('ipv'),v=document.getElementById('vpv');
    img.style.display='none'; v.style.display='none';
    if(i.files&&i.files[0]){
        const u=URL.createObjectURL(i.files[0]);
        w.classList.add('v');
        if(i.files[0].type.startsWith('video/')){v.src=u;v.style.display='block';}
        else{img.src=u;img.style.display='block';}
    }
}
function qM(){ document.getElementById('mi').value=''; document.getElementById('mpw').classList.remove('v'); }

// Menú 3 puntos
function tPM(id){
    const m=document.getElementById('pm'+id);
    document.querySelectorAll('.pmdd').forEach(d=>{ if(d.id!=='pm'+id) d.classList.remove('open'); });
    m.classList.toggle('open');
}
function clPM(id){ document.getElementById('pm'+id).classList.remove('open'); }
document.addEventListener('click',e=>{ if(!e.target.closest('.pmw')) document.querySelectorAll('.pmdd').forEach(d=>d.classList.remove('open')); });

// Confirmar eliminar
function cEP(id){
    document.getElementById('epid').value=id;
    clPM(id);
    document.getElementById('mEP').classList.add('active');
}

// Like AJAX
function tL(id,btn){
    fetch('acciones/like_publicacion.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'publicacion_id='+id})
    .then(r=>r.json()).then(d=>{
        btn.classList.toggle('liked',d.liked);
        btn.innerHTML=(d.liked?'❤️':'♡')+' Me gusta';
        document.getElementById('pl'+id).textContent=d.total_likes+' me gusta';
    });
}

// Comentarios
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

// Compartir
function sP(id,btn){
    navigator.clipboard.writeText(window.location.origin+window.location.pathname+'#post-'+id).then(()=>{
        btn.classList.add('shared'); btn.innerHTML='✅ Copiado';
        setTimeout(()=>{ btn.classList.remove('shared'); btn.innerHTML='↗ Compartir'; },2000);
    });
}

setTimeout(()=>{ const t=document.querySelector('.toast'); if(t) t.style.display='none'; },3500);
</script>

<?php function t($f){ $d=time()-strtotime($f); if($d<60) return "ahora"; if($d<3600) return round($d/60)." min"; if($d<86400) return "Hace ".round($d/3600)."h"; return "Hace ".round($d/86400)." días"; } ?>
</body>
</html>