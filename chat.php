<?php
session_start();
if(!isset($_SESSION['usuario'])){ header("Location: login.php"); exit(); }
require_once("config/conexion.php");

$mi_id      = $_SESSION['usuario_id'];
$receptor_id = (int)($_GET['con'] ?? 0);
$receptor   = null;

if($receptor_id){
    $r = $pdo->prepare("SELECT * FROM usuarios WHERE id=?");
    $r->execute([$receptor_id]);
    $receptor = $r->fetch();
}

// Cargar mensajes iniciales
$mensajes_iniciales = [];
if($receptor_id){
    // Marcar como leídos
    $pdo->prepare("UPDATE mensajes SET leido=1 WHERE emisor_id=? AND receptor_id=? AND leido=0")
        ->execute([$receptor_id, $mi_id]);

    $stmt = $pdo->prepare("
        SELECT m.*,
            DATE_FORMAT(m.fecha, '%H:%i') AS hora,
            DATE_FORMAT(m.fecha, '%d/%m/%Y') AS dia
        FROM mensajes m
        WHERE (m.emisor_id=? AND m.receptor_id=?) OR (m.emisor_id=? AND m.receptor_id=?)
        ORDER BY m.fecha ASC
        LIMIT 100
    ");
    $stmt->execute([$mi_id, $receptor_id, $receptor_id, $mi_id]);
    $mensajes_iniciales = $stmt->fetchAll();
}

// Lista de conversaciones
$stmt2 = $pdo->prepare("
    SELECT DISTINCT u.id, u.nombre, u.foto_perfil, u.rol,
        (SELECT mensaje FROM mensajes 
         WHERE (emisor_id=u.id AND receptor_id=?) OR (emisor_id=? AND receptor_id=u.id)
         ORDER BY fecha DESC LIMIT 1) AS ultimo_mensaje,
        (SELECT imagen FROM mensajes 
         WHERE (emisor_id=u.id AND receptor_id=?) OR (emisor_id=? AND receptor_id=u.id)
         ORDER BY fecha DESC LIMIT 1) AS ultima_imagen,
        (SELECT DATE_FORMAT(fecha,'%H:%i') FROM mensajes 
         WHERE (emisor_id=u.id AND receptor_id=?) OR (emisor_id=? AND receptor_id=u.id)
         ORDER BY fecha DESC LIMIT 1) AS hora,
        (SELECT COUNT(*) FROM mensajes WHERE emisor_id=u.id AND receptor_id=? AND leido=0) AS no_leidos
    FROM mensajes m
    JOIN usuarios u ON u.id = IF(m.emisor_id=?, m.receptor_id, m.emisor_id)
    WHERE m.emisor_id=? OR m.receptor_id=?
    GROUP BY u.id
    ORDER BY (SELECT fecha FROM mensajes 
              WHERE (emisor_id=u.id AND receptor_id=?) OR (emisor_id=? AND receptor_id=u.id)
              ORDER BY fecha DESC LIMIT 1) DESC
");
$stmt2->execute([$mi_id,$mi_id,$mi_id,$mi_id,$mi_id,$mi_id,$mi_id,$mi_id,$mi_id,$mi_id,$mi_id,$mi_id]);
$conversaciones = $stmt2->fetchAll();

$mi_info = $pdo->prepare("SELECT * FROM usuarios WHERE id=?");
$mi_info->execute([$mi_id]);
$mi_info = $mi_info->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - EquiRed</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .chat-page { display:flex; height:calc(100vh - 65px); overflow:hidden; background:#f4f4f8; }

        /* ── Sidebar ── */
        .chat-sidebar { width:320px; background:white; border-right:1px solid #f0f0f0; display:flex; flex-direction:column; flex-shrink:0; }
        .sidebar-header { padding:20px; border-bottom:1px solid #f0f0f0; }
        .sidebar-header h2 { font-size:20px; font-weight:900; color:#1a1a2e; }
        .sidebar-search { position:relative; margin-top:12px; }
        .sidebar-search input { width:100%; padding:10px 14px 10px 36px; border:1.5px solid #e8e8e8; border-radius:10px; font-size:14px; font-family:inherit; outline:none; background:#f9f9f9; }
        .sidebar-search input:focus { border-color:#7b2ff7; background:white; }
        .sidebar-search .si { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#aaa; }
        .chat-list { flex:1; overflow-y:auto; }
        .chat-item { display:flex; align-items:center; gap:12px; padding:14px 20px; cursor:pointer; border-bottom:1px solid #f9f9f9; transition:background 0.2s; }
        .chat-item:hover { background:#faf5ff; }
        .chat-item.active { background:#f3e8ff; }
        .chat-av { width:46px; height:46px; border-radius:50%; background:linear-gradient(135deg,#7b2ff7,#a855f7); display:flex; align-items:center; justify-content:center; font-size:18px; color:white; font-weight:800; flex-shrink:0; overflow:hidden; }
        .chat-av img { width:100%; height:100%; object-fit:cover; }
        .chat-info { flex:1; min-width:0; }
        .chat-nombre { font-size:14px; font-weight:800; color:#1a1a2e; }
        .chat-preview { font-size:12px; color:#aaa; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
        .chat-meta { display:flex; flex-direction:column; align-items:flex-end; gap:4px; }
        .chat-hora { font-size:11px; color:#aaa; }
        .chat-badge { background:#7b2ff7; color:white; border-radius:50%; width:18px; height:18px; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center; }
        .no-chats { text-align:center; padding:40px 20px; color:#aaa; }
        .no-chats .icon { font-size:40px; margin-bottom:10px; }

        /* ── Área de chat ── */
        .chat-main { flex:1; display:flex; flex-direction:column; overflow:hidden; }
        .chat-header { background:white; padding:16px 24px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:14px; }
        .chat-header-av { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#7b2ff7,#a855f7); display:flex; align-items:center; justify-content:center; font-size:18px; color:white; font-weight:800; overflow:hidden; flex-shrink:0; cursor:pointer; }
        .chat-header-av img { width:100%; height:100%; object-fit:cover; }
        .chat-header-info { flex:1; }
        .chat-header-nombre { font-size:16px; font-weight:900; color:#1a1a2e; cursor:pointer; }
        .chat-header-nombre:hover { color:#7b2ff7; }
        .chat-header-rol { font-size:12px; color:#a855f7; font-weight:700; text-transform:capitalize; }
        .btn-ver-perfil { padding:8px 16px; background:#f3e8ff; color:#7b2ff7; border:none; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-block; }

        /* Mensajes */
        .chat-messages { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:10px; }
        .msg-wrap { display:flex; align-items:flex-end; gap:8px; }
        .msg-wrap.mio { flex-direction:row-reverse; }
        .msg-av-small { width:28px; height:28px; border-radius:50%; background:linear-gradient(135deg,#7b2ff7,#a855f7); display:flex; align-items:center; justify-content:center; font-size:11px; color:white; font-weight:800; flex-shrink:0; overflow:hidden; }
        .msg-av-small img { width:100%; height:100%; object-fit:cover; }
        .msg-burbuja { max-width:65%; padding:10px 14px; border-radius:16px; font-size:14px; line-height:1.5; position:relative; }
        .msg-burbuja.otro { background:white; color:#333; border-radius:16px 16px 16px 4px; box-shadow:0 1px 4px rgba(0,0,0,0.08); }
        .msg-burbuja.mio { background:linear-gradient(135deg,#7b2ff7,#a855f7); color:white; border-radius:16px 16px 4px 16px; }
        .msg-hora { font-size:10px; opacity:0.6; margin-top:4px; display:block; }
        .msg-img { max-width:220px; border-radius:12px; display:block; margin-bottom:4px; cursor:pointer; }
        .msg-img:hover { opacity:0.9; }
        .fecha-sep { text-align:center; font-size:12px; color:#aaa; font-weight:700; margin:10px 0; }

        /* Input */
        .chat-input-area { background:white; border-top:1px solid #f0f0f0; padding:14px 20px; }
        .img-preview-wrap { margin-bottom:10px; display:none; position:relative; width:fit-content; }
        .img-preview-wrap.visible { display:block; }
        .img-preview-wrap img { max-height:100px; border-radius:10px; display:block; }
        .btn-remove-img { position:absolute; top:-6px; right:-6px; background:#dc2626; color:white; border:none; border-radius:50%; width:20px; height:20px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
        .chat-input-row { display:flex; gap:10px; align-items:center; }
        .btn-img-attach { background:none; border:none; font-size:22px; cursor:pointer; color:#aaa; padding:6px; border-radius:8px; transition:background 0.2s; flex-shrink:0; }
        .btn-img-attach:hover { background:#f3e8ff; color:#7b2ff7; }
        .chat-input { flex:1; padding:12px 16px; border:1.5px solid #e8e8e8; border-radius:12px; font-size:14px; font-family:inherit; outline:none; background:#f9f9f9; resize:none; max-height:100px; }
        .chat-input:focus { border-color:#7b2ff7; background:white; }
        .btn-send { background:linear-gradient(135deg,#7b2ff7,#a855f7); color:white; border:none; border-radius:12px; width:44px; height:44px; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:opacity 0.2s; }
        .btn-send:hover { opacity:0.9; }

        /* Sin chat seleccionado */
        .chat-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#aaa; }
        .chat-empty .icon { font-size:60px; margin-bottom:16px; }
        .chat-empty p { font-size:16px; font-weight:700; }
        .chat-empty span { font-size:14px; margin-top:6px; }

        /* Modal imagen ampliada */
        .img-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:500; align-items:center; justify-content:center; }
        .img-modal.active { display:flex; }
        .img-modal img { max-width:90vw; max-height:90vh; border-radius:12px; }
        .img-modal-close { position:absolute; top:20px; right:24px; background:none; border:none; color:white; font-size:32px; cursor:pointer; }
    </style>
</head>
<body>
<?php include("includes/navbar.php"); ?>

<div class="chat-page">

    <!-- Sidebar conversaciones -->
    <div class="chat-sidebar">
        <div class="sidebar-header">
            <h2>💬 Mensajes</h2>
            <div class="sidebar-search">
                <span class="si">🔍</span>
                <input type="text" id="buscarChat" placeholder="Buscar conversación..." oninput="filtrarChats()">
            </div>
        </div>
        <div class="chat-list" id="chatList">
            <?php if(empty($conversaciones)): ?>
                <div class="no-chats">
                    <div class="icon">💬</div>
                    <p>Sin conversaciones aún</p>
                </div>
            <?php else: foreach($conversaciones as $conv): ?>
            <div class="chat-item <?= $conv['id']==$receptor_id?'active':'' ?>"
                onclick="window.location.href='chat.php?con=<?= $conv['id'] ?>'"
                data-nombre="<?= htmlspecialchars(strtolower($conv['nombre'])) ?>">
                <div class="chat-av">
                    <?php if(!empty($conv['foto_perfil'])): ?>
                        <img src="uploads/<?= htmlspecialchars($conv['foto_perfil']) ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($conv['nombre'],0,1)) ?>
                    <?php endif; ?>
                </div>
                <div class="chat-info">
                    <div class="chat-nombre"><?= htmlspecialchars($conv['nombre']) ?></div>
                    <div class="chat-preview">
                        <?php if(!empty($conv['ultima_imagen']) && empty($conv['ultimo_mensaje'])): ?>
                            📷 Imagen
                        <?php else: ?>
                            <?= htmlspecialchars(substr($conv['ultimo_mensaje']??'',0,35)) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="chat-meta">
                    <span class="chat-hora"><?= $conv['hora'] ?></span>
                    <?php if($conv['no_leidos']>0): ?>
                        <span class="chat-badge"><?= $conv['no_leidos'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Área principal -->
    <div class="chat-main">
        <?php if($receptor): ?>

        <!-- Header del chat -->
        <div class="chat-header">
            <div class="chat-header-av" onclick="window.location.href='perfil.php?id=<?= $receptor['id'] ?>'">
                <?php if(!empty($receptor['foto_perfil'])): ?>
                    <img src="uploads/<?= htmlspecialchars($receptor['foto_perfil']) ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($receptor['nombre'],0,1)) ?>
                <?php endif; ?>
            </div>
            <div class="chat-header-info">
                <div class="chat-header-nombre" onclick="window.location.href='perfil.php?id=<?= $receptor['id'] ?>'">
                    <?= htmlspecialchars($receptor['nombre']) ?>
                </div>
                <div class="chat-header-rol"><?= htmlspecialchars($receptor['rol']) ?></div>
            </div>
            <a href="perfil.php?id=<?= $receptor['id'] ?>" class="btn-ver-perfil">👤 Ver perfil</a>
        </div>

        <!-- Mensajes -->
        <div class="chat-messages" id="chatMessages">
            <?php
            $dia_anterior = '';
            foreach($mensajes_iniciales as $m):
                $es_mio = ($m['emisor_id'] == $mi_id);
                if($m['dia'] !== $dia_anterior):
                    $dia_anterior = $m['dia'];
            ?>
                <div class="fecha-sep">📅 <?= $m['dia'] ?></div>
            <?php endif; ?>
            <div class="msg-wrap <?= $es_mio?'mio':'' ?>" id="msg-<?= $m['id'] ?>">
                <div class="msg-av-small">
                    <?php
                    $foto_msg = $es_mio ? $mi_info['foto_perfil'] : $receptor['foto_perfil'];
                    if(!empty($foto_msg)): ?>
                        <img src="uploads/<?= htmlspecialchars($foto_msg) ?>" alt="">
                    <?php else: ?>
                        <?= $es_mio ? strtoupper(substr($mi_info['nombre'],0,1)) : strtoupper(substr($receptor['nombre'],0,1)) ?>
                    <?php endif; ?>
                </div>
                <div class="msg-burbuja <?= $es_mio?'mio':'otro' ?>">
                    <?php if(!empty($m['imagen'])): ?>
                        <img src="uploads/<?= htmlspecialchars($m['imagen']) ?>" class="msg-img" onclick="ampliarImg(this.src)" alt="">
                    <?php endif; ?>
                    <?php if(!empty($m['mensaje'])): ?>
                        <?= nl2br(htmlspecialchars($m['mensaje'])) ?>
                    <?php endif; ?>
                    <span class="msg-hora"><?= $m['hora'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Input -->
        <div class="chat-input-area">
            <div class="img-preview-wrap" id="imgPreviewWrap">
                <img id="imgPreview" src="" alt="">
                <button class="btn-remove-img" onclick="quitarImg()">✕</button>
            </div>
            <div class="chat-input-row">
                <input type="file" id="imgInput" accept="image/*" style="display:none" onchange="previewImg(this)">
                <button class="btn-img-attach" onclick="document.getElementById('imgInput').click()" title="Enviar imagen">📷</button>
                <textarea class="chat-input" id="msgInput" placeholder="Escribe un mensaje..." rows="1"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();enviarMensaje()}"
                    oninput="autoResize(this)"></textarea>
                <button class="btn-send" onclick="enviarMensaje()">➤</button>
            </div>
        </div>

        <?php else: ?>
        <div class="chat-empty">
            <div class="icon">💬</div>
            <p>Selecciona una conversación</p>
            <span>o ve al perfil de alguien para iniciar un chat</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal imagen ampliada -->
<div class="img-modal" id="imgModal" onclick="document.getElementById('imgModal').classList.remove('active')">
    <button class="img-modal-close">✕</button>
    <img id="imgModalSrc" src="" alt="">
</div>

<script>
const MI_ID       = <?= $mi_id ?>;
const RECEPTOR_ID = <?= $receptor_id ?: 0 ?>;
let ultimoId      = <?= !empty($mensajes_iniciales) ? end($mensajes_iniciales)['id'] : 0 ?>;
let intervalo     = null;

// Auto scroll al fondo
function scrollFondo(){
    const c = document.getElementById('chatMessages');
    if(c) c.scrollTop = c.scrollHeight;
}
scrollFondo();

// Auto resize textarea
function autoResize(el){
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

// Preview imagen
function previewImg(input){
    if(input.files && input.files[0]){
        const wrap = document.getElementById('imgPreviewWrap');
        document.getElementById('imgPreview').src = URL.createObjectURL(input.files[0]);
        wrap.classList.add('visible');
    }
}
function quitarImg(){
    document.getElementById('imgInput').value = '';
    document.getElementById('imgPreviewWrap').classList.remove('visible');
}

// Enviar mensaje
function enviarMensaje(){
    const input  = document.getElementById('msgInput');
    const texto  = input.value.trim();
    const imgFile = document.getElementById('imgInput').files[0];
    if(!texto && !imgFile) return;

    const fd = new FormData();
    fd.append('receptor_id', RECEPTOR_ID);
    fd.append('mensaje', texto);
    if(imgFile) fd.append('imagen', imgFile);

    fetch('acciones/enviar_mensaje.php', { method:'POST', body: fd })
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            agregarMensaje({
                id: d.id,
                emisor_id: MI_ID,
                mensaje: d.mensaje,
                imagen: d.imagen,
                hora: d.fecha
            }, true);
            ultimoId = d.id;
            input.value = '';
            input.style.height = 'auto';
            quitarImg();
        }
    });
}

// Agregar burbuja al DOM
function agregarMensaje(m, esMio){
    const cont = document.getElementById('chatMessages');
    if(!cont) return;
    const div = document.createElement('div');
    div.className = 'msg-wrap ' + (esMio?'mio':'');
    div.id = 'msg-'+m.id;

    const inicial = esMio
        ? '<?= strtoupper(substr($mi_info['nombre'],0,1)) ?>'
        : '<?= $receptor ? strtoupper(substr($receptor['nombre'],0,1)) : '?' ?>';

    const fotoMio   = '<?= !empty($mi_info['foto_perfil']) ? "uploads/".htmlspecialchars($mi_info['foto_perfil']) : '' ?>';
    const fotoOtro  = '<?= $receptor && !empty($receptor['foto_perfil']) ? "uploads/".htmlspecialchars($receptor['foto_perfil']) : '' ?>';
    const foto      = esMio ? fotoMio : fotoOtro;
    const avatarHtml = foto
        ? `<img src="${foto}" style="width:100%;height:100%;object-fit:cover">`
        : inicial;

    let contenido = '';
    if(m.imagen) contenido += `<img src="uploads/${m.imagen}" class="msg-img" onclick="ampliarImg(this.src)" alt="">`;
    if(m.mensaje) contenido += nl2br(m.mensaje);

    div.innerHTML = `
        <div class="msg-av-small">${avatarHtml}</div>
        <div class="msg-burbuja ${esMio?'mio':'otro'}">
            ${contenido}
            <span class="msg-hora">${m.hora}</span>
        </div>`;
    cont.appendChild(div);
    scrollFondo();
}

function nl2br(str){ return str.replace(/\n/g,'<br>'); }

// Polling mensajes nuevos
function polling(){
    if(!RECEPTOR_ID) return;
    fetch(`acciones/obtener_mensajes.php?receptor_id=${RECEPTOR_ID}&ultimo_id=${ultimoId}`)
    .then(r=>r.json()).then(data=>{
        data.forEach(m=>{
            if(!document.getElementById('msg-'+m.id)){
                agregarMensaje(m, m.emisor_id==MI_ID);
                ultimoId = m.id;
            }
        });
    });
}

if(RECEPTOR_ID) intervalo = setInterval(polling, 3000);

// Ampliar imagen
function ampliarImg(src){
    document.getElementById('imgModalSrc').src = src;
    document.getElementById('imgModal').classList.add('active');
}

// Filtrar chats en sidebar
function filtrarChats(){
    const q = document.getElementById('buscarChat').value.toLowerCase();
    document.querySelectorAll('.chat-item').forEach(item=>{
        item.style.display = item.dataset.nombre.includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>