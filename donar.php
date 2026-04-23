<?php
session_start();
if(!isset($_SESSION['usuario'])){ header("Location: login.php"); exit(); }
require_once("config/conexion.php");

$buscar     = trim($_GET['buscar'] ?? '');
$usuario_id = $_SESSION['usuario_id'] ?? null;

$where  = ''; $params = [];
if($buscar !== '') {
    $where  = "WHERE (d.titulo LIKE ? OR d.descripcion LIKE ? OR u.nombre LIKE ? OR d.ciudad LIKE ?)";
    $params = ["%$buscar%","%$buscar%","%$buscar%","%$buscar%"];
}

$likeCol = $usuario_id
    ? "(SELECT COUNT(*) FROM likes_donacion WHERE donacion_id=d.id AND usuario_id=$usuario_id) AS yo_di_like"
    : "0 AS yo_di_like";

$sql = "SELECT d.*, u.nombre,
    (SELECT COUNT(*) FROM likes_donacion l WHERE l.donacion_id=d.id) AS total_likes,
    (SELECT COUNT(*) FROM comentarios_donacion c WHERE c.donacion_id=d.id) AS total_comentarios,
    (SELECT COUNT(*) FROM solicitudes_donacion s WHERE s.donacion_id=d.id) AS total_solicitudes,
    $likeCol
    FROM donaciones d JOIN usuarios u ON d.usuario_id=u.id
    $where ORDER BY d.fecha DESC LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donaciones = $stmt->fetchAll();
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donar - EquiRed</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .donar-page{max-width:780px;margin:0 auto;padding:40px 20px}
        .donar-hero{text-align:center;margin-bottom:28px}
        .donar-hero h1{font-size:36px;font-weight:900;color:#1a1a2e}
        .donar-hero h1 span{color:#7b2ff7}
        .donar-hero p{color:#777;font-size:15px;margin-top:10px;max-width:520px;margin-inline:auto;line-height:1.6}

        /* Toast */
        .toast{position:fixed;top:80px;right:24px;z-index:999;padding:14px 22px;border-radius:12px;font-size:15px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,0.15);animation:slideIn 0.3s ease,fadeOut 0.4s ease 3s forwards}
        .toast-exito{background:#d1fae5;color:#059669;border:1px solid #a7f3d0}
        .toast-warning{background:#fef3c7;color:#d97706;border:1px solid #fde68a}
        .toast-error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
        @keyframes slideIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes fadeOut{from{opacity:1}to{opacity:0;visibility:hidden}}

        /* Buscador */
        .search-bar-donar{background:white;border-radius:14px;padding:14px 18px;box-shadow:0 2px 12px rgba(0,0,0,0.06);margin-bottom:20px;display:flex;gap:10px;align-items:center}
        .search-wrap{flex:1;position:relative}
        .search-wrap .sicon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa}
        .search-wrap input{width:100%;padding:10px 14px 10px 38px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:#f9f9f9}
        .search-wrap input:focus{border-color:#7b2ff7;background:white}
        .btn-buscar{padding:10px 20px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-weight:700;font-family:inherit;cursor:pointer;font-size:14px;white-space:nowrap}
        .limpiar-link{color:#7b2ff7;font-weight:700;font-size:14px;text-decoration:none;white-space:nowrap}

        /* CTA */
        .donar-cta-box{background:white;border-radius:14px;padding:16px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}
        .donar-cta-left{display:flex;align-items:center;gap:12px}
        .donar-cta-icon{width:40px;height:40px;background:#f3e8ff;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px}
        .donar-cta-text strong{display:block;font-size:15px;font-weight:800;color:#1a1a2e}
        .donar-cta-text span{font-size:13px;color:#888}
        .btn-donar-cta{background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;padding:10px 22px;border-radius:10px;font-weight:800;font-size:14px;font-family:inherit;cursor:pointer;text-decoration:none;display:inline-block}

        /* Card donación */
        .don-card{background:white;border-radius:16px;margin-bottom:18px;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:visible}
        .don-header{display:flex;justify-content:space-between;align-items:flex-start;padding:16px 20px 10px;position:relative}
        .don-user{display:flex;align-items:center;gap:12px}
        .don-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#f97316,#fb923c);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;color:white;font-weight:800}
        .don-user strong{display:block;font-size:15px;font-weight:800;color:#1a1a2e}
        .don-user span{font-size:12px;color:#aaa}

        /* Menú 3 puntos */
        .don-menu-wrap{position:relative}
        .don-menu-btn{background:none;border:none;font-size:22px;color:#aaa;cursor:pointer;padding:4px 8px;border-radius:8px;line-height:1;transition:background 0.2s}
        .don-menu-btn:hover{background:#f4f4f8;color:#555}
        .don-menu-dropdown{display:none;position:absolute;right:0;top:36px;background:white;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:50;min-width:160px;overflow:hidden;border:1px solid #f0f0f0}
        .don-menu-dropdown.open{display:block}
        .don-menu-item{display:flex;align-items:center;gap:8px;padding:12px 16px;font-size:14px;font-weight:600;color:#555;cursor:pointer;border:none;background:none;width:100%;font-family:inherit;transition:background 0.2s;text-align:left}
        .don-menu-item:hover{background:#f4f4f8}
        .don-menu-item.danger{color:#dc2626}
        .don-menu-item.danger:hover{background:#fee2e2}

        /* Body donación */
        .don-body{padding:0 20px 12px}
        .don-titulo{font-size:17px;font-weight:800;color:#1a1a2e;margin-bottom:6px}
        .don-desc{font-size:14px;color:#555;line-height:1.6;margin-bottom:8px}
        .don-lugar{font-size:13px;color:#aaa;display:flex;align-items:center;gap:5px}

        /* ── Media con soporte vertical ── */
        .don-media-wrap{
            background:#f8f8f8;         /* recuadro gris claro a los lados */
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
            max-height:480px;
            border-top:1px solid #f0f0f0;
            border-bottom:1px solid #f0f0f0;
        }
        .don-media{
            max-width:100%;
            max-height:480px;
            width:auto;
            height:auto;
            object-fit:contain;   /* muestra completa, sin recortar */
            display:block;
        }
        .don-media-video{
            width:100%;
            max-height:400px;
            display:block;
            background:#000;
        }

        /* Stats y acciones */
        .don-stats{padding:10px 20px;display:flex;justify-content:space-between;font-size:13px;color:#aaa;border-top:1px solid #f0f0f0;border-bottom:1px solid #f0f0f0}
        .don-actions{display:flex;padding:4px 10px}
        .don-action-btn{flex:1;background:none;border:none;padding:10px;font-size:13px;font-weight:700;color:#666;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;border-radius:8px;font-family:inherit;transition:background 0.2s,color 0.2s;text-decoration:none}
        .don-action-btn:hover{background:#f4f4f8;color:#7b2ff7}
        .don-action-btn.liked{color:#ef4444}
        .btn-solicitar-don{background:linear-gradient(135deg,#7b2ff7,#a855f7)!important;color:white!important;border-radius:8px!important}
        .btn-solicitar-don:hover{opacity:0.9;color:white!important}

        /* Comentarios */
        .comentarios-section{padding:0 20px 16px;border-top:1px solid #f0f0f0;display:none}
        .comentarios-section.visible{display:block}
        .comentario-item{display:flex;gap:10px;margin-bottom:10px;padding-top:10px}
        .com-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#7b2ff7,#a855f7);display:flex;align-items:center;justify-content:center;font-size:13px;color:white;font-weight:700;flex-shrink:0}
        .com-burbuja{background:#f4f4f8;border-radius:12px;padding:10px 14px;flex:1}
        .com-burbuja strong{font-size:13px;font-weight:800;color:#1a1a2e;display:block;margin-bottom:2px}
        .com-burbuja p{font-size:13px;color:#555}
        .com-form{display:flex;gap:8px;margin-top:10px}
        .com-form input{flex:1;padding:10px 14px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:13px;font-family:inherit;outline:none;background:#f9f9f9}
        .com-form input:focus{border-color:#7b2ff7;background:white}
        .btn-com-send{padding:10px 16px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-weight:700;font-family:inherit;cursor:pointer;font-size:13px}

        /* Modales */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;padding:20px}
        .modal-overlay.active{display:flex}
        .modal{background:white;border-radius:20px;padding:36px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto}
        .modal h3{font-size:20px;font-weight:900;margin-bottom:20px;color:#1a1a2e}
        .modal input,.modal textarea{width:100%;padding:12px 14px;border:1.5px solid #e8e8e8;border-radius:10px;font-size:14px;font-family:inherit;margin-bottom:14px;outline:none;background:#f9f9f9}
        .modal input:focus,.modal textarea:focus{border-color:#7b2ff7;background:white}
        .modal textarea{height:80px;resize:none}
        .modal-close{float:right;background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;margin-top:-8px}
        .modal-btns{display:flex;gap:10px;margin-top:8px}
        .btn-modal-cancel{flex:1;padding:12px;background:#f4f4f8;color:#555;border:none;border-radius:10px;font-weight:700;font-family:inherit;cursor:pointer}
        .btn-modal-send{flex:2;padding:12px;background:linear-gradient(135deg,#7b2ff7,#a855f7);color:white;border:none;border-radius:10px;font-weight:800;font-family:inherit;cursor:pointer}
        .btn-modal-danger{flex:2;padding:12px;background:#dc2626;color:white;border:none;border-radius:10px;font-weight:800;font-family:inherit;cursor:pointer}

        /* Upload preview */
        .file-label{display:flex;align-items:center;gap:8px;padding:12px 14px;border:1.5px dashed #d8b4fe;border-radius:10px;cursor:pointer;font-size:14px;color:#7b2ff7;font-weight:700;margin-bottom:14px;background:#faf5ff;transition:background 0.2s}
        .file-label:hover{background:#f3e8ff}
        .file-label input{display:none}
        .media-preview{width:100%;border-radius:10px;margin-bottom:14px;display:none;max-height:220px;object-fit:contain;background:#f8f8f8}

        .sin-resultados{text-align:center;padding:50px 20px;color:#aaa}
        .sin-resultados .icon{font-size:40px;margin-bottom:12px}

        /* Confirmar eliminar */
        .confirm-box{text-align:center;padding:10px 0}
        .confirm-box p{color:#555;font-size:15px;margin-bottom:20px;line-height:1.6}
    </style>
</head>
<body>

<?php include("includes/navbar.php"); ?>

<!-- Toasts -->
<?php if($msg==='donacion_publicada'): ?><div class="toast toast-exito">✅ ¡Donación publicada exitosamente!</div>
<?php elseif($msg==='solicitud_enviada'): ?><div class="toast toast-exito">✅ ¡Solicitud enviada al donante!</div>
<?php elseif($msg==='ya_solicitado'): ?><div class="toast toast-warning">⚠️ Ya enviaste una solicitud para esta donación.</div>
<?php elseif($msg==='eliminada'): ?><div class="toast toast-exito">🗑️ Publicación eliminada.</div>
<?php elseif($msg==='error'): ?><div class="toast toast-error">❌ No tienes permiso para hacer eso.</div><?php endif; ?>

<div class="donar-page">

    <div class="donar-hero">
        <h1>Dona y <span>Comparte</span></h1>
        <p>Publica objetos o productos que ya no uses y ayuda a quienes más lo necesitan. Tu generosidad puede cambiar vidas.</p>
    </div>

    <!-- Buscador -->
    <form method="GET" action="donar.php">
        <div class="search-bar-donar">
            <div class="search-wrap">
                <span class="sicon">🔍</span>
                <input type="text" name="buscar" placeholder="Buscar por artículo, descripción o usuario..." value="<?= htmlspecialchars($buscar) ?>">
            </div>
            <button type="submit" class="btn-buscar">Buscar</button>
            <?php if($buscar): ?><a href="donar.php" class="limpiar-link">✕ Limpiar</a><?php endif; ?>
        </div>
    </form>

    <!-- CTA publicar -->
    <div class="donar-cta-box">
        <div class="donar-cta-left">
            <div class="donar-cta-icon">📤</div>
            <div class="donar-cta-text">
                <strong>¿Quieres donar algo?</strong>
                <span>Publica fotos o videos de lo que quieres donar</span>
            </div>
        </div>
        <?php if(isset($_SESSION['usuario'])): ?>
            <button class="btn-donar-cta" onclick="document.getElementById('modalDonar').classList.add('active')">Publicar</button>
        <?php else: ?>
            <a href="login.php" class="btn-donar-cta">Publicar</a>
        <?php endif; ?>
    </div>

    <!-- Feed donaciones -->
    <?php if(empty($donaciones)): ?>
        <div class="sin-resultados">
            <div class="icon"><?= $buscar ? '🔍' : '📭' ?></div>
            <p><?= $buscar ? 'No se encontraron resultados para "'.htmlspecialchars($buscar).'"' : 'Aún no hay donaciones. ¡Sé el primero en publicar!' ?></p>
        </div>
    <?php else: foreach($donaciones as $don): ?>

    <div class="don-card" id="card-<?= $don['id'] ?>">

        <!-- Header con menú 3 puntos -->
        <div class="don-header">
            <div class="don-user">
                <div class="don-avatar">
    <?php if(!empty($don['foto_perfil'])): ?>
        <img src="uploads/<?= htmlspecialchars($don['foto_perfil']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
    <?php else: ?>
        <?= strtoupper(substr($don['nombre'],0,1)) ?>
    <?php endif; ?>
</div>
                <div>
                    <strong><?= htmlspecialchars($don['nombre']) ?></strong>
                    <span><?= t($don['fecha']) ?></span>
                </div>
            </div>

            <!-- Menú 3 puntos -->
            <div class="don-menu-wrap">
                <button class="don-menu-btn" onclick="toggleMenu(<?= $don['id'] ?>)">⋮</button>
                <div class="don-menu-dropdown" id="menu-<?= $don['id'] ?>">
                    <?php if(isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $don['usuario_id']): ?>
                        <!-- Solo el dueño puede eliminar -->
                        <button class="don-menu-item danger"
                            onclick="confirmarEliminar(<?= $don['id'] ?>, '<?= htmlspecialchars($don['titulo'] ?? 'esta publicación', ENT_QUOTES) ?>')">
                            🗑️ Eliminar publicación
                        </button>
                    <?php else: ?>
                        <button class="don-menu-item" onclick="cerrarMenu(<?= $don['id'] ?>)">
                            🚩 Reportar
                        </button>
                        <button class="don-menu-item" onclick="copiarEnlace(<?= $don['id'] ?>)">
                            🔗 Copiar enlace
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="don-body">
            <?php if(!empty($don['titulo'])): ?><div class="don-titulo"><?= htmlspecialchars($don['titulo']) ?></div><?php endif; ?>
            <?php if(!empty($don['descripcion'])): ?><div class="don-desc"><?= nl2br(htmlspecialchars($don['descripcion'])) ?></div><?php endif; ?>
            <?php if(!empty($don['ciudad'])): ?><div class="don-lugar">📍 <?= htmlspecialchars($don['ciudad']) ?></div><?php endif; ?>
        </div>

        <!-- Media (foto vertical con recuadro blanco a los lados) -->
        <?php if(!empty($don['imagen'])):
            $ext    = strtolower(pathinfo($don['imagen'], PATHINFO_EXTENSION));
            $esVideo = in_array($ext, ['mp4','webm','ogg','mov']); ?>
            <?php if($esVideo): ?>
                <video class="don-media-video" controls>
                    <source src="uploads/<?= htmlspecialchars($don['imagen']) ?>">
                </video>
            <?php else: ?>
                <!-- Recuadro blanco a los lados para fotos verticales -->
                <div class="don-media-wrap">
                    <img class="don-media"
                         src="uploads/<?= htmlspecialchars($don['imagen']) ?>"
                         alt="Donación">
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="don-stats">
            <span id="likes-<?= $don['id'] ?>"><?= $don['total_likes'] ?> me gusta</span>
            <span id="stats2-<?= $don['id'] ?>"><?= $don['total_comentarios'] ?> comentarios · <?= $don['total_solicitudes'] ?> solicitudes</span>
        </div>

        <div class="don-actions">
            <!-- Like -->
            <?php if(isset($_SESSION['usuario'])): ?>
                <button class="don-action-btn <?= $don['yo_di_like'] ? 'liked':'' ?>"
                    id="like-btn-<?= $don['id'] ?>"
                    onclick="toggleLike(<?= $don['id'] ?>, this)">
                    <?= $don['yo_di_like'] ? '❤️':'♡' ?> Me gusta
                </button>
            <?php else: ?>
                <a href="login.php" class="don-action-btn">♡ Me gusta</a>
            <?php endif; ?>

            <button class="don-action-btn" onclick="toggleComentarios(<?= $don['id'] ?>)">💬 Comentar</button>

            <?php if(isset($_SESSION['usuario']) && $_SESSION['usuario_id'] != $don['usuario_id']): ?>
                <button class="don-action-btn btn-solicitar-don"
                    onclick="abrirSolicitar(<?= $don['id'] ?>, '<?= htmlspecialchars($don['titulo'] ?? 'esta donación', ENT_QUOTES) ?>')">
                    Solicitar
                </button>
            <?php elseif(!isset($_SESSION['usuario'])): ?>
                <a href="login.php" class="don-action-btn btn-solicitar-don">Solicitar</a>
            <?php else: ?>
                <button class="don-action-btn btn-solicitar-don" disabled style="opacity:0.5;cursor:not-allowed">Tu publicación</button>
            <?php endif; ?>
        </div>

        <!-- Comentarios -->
        <div class="comentarios-section" id="comments-<?= $don['id'] ?>">
            <?php
            $sc = $pdo->prepare("SELECT c.*, u.nombre FROM comentarios_donacion c JOIN usuarios u ON c.usuario_id=u.id WHERE c.donacion_id=? ORDER BY c.fecha ASC LIMIT 10");
            $sc->execute([$don['id']]);
            foreach($sc->fetchAll() as $c): ?>
            <div class="comentario-item">
                <div class="com-avatar"><?= strtoupper(substr($c['nombre'],0,1)) ?></div>
                <div class="com-burbuja">
                    <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                    <p><?= htmlspecialchars($c['comentario']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(isset($_SESSION['usuario'])): ?>
            <form class="com-form" onsubmit="enviarComentario(event, <?= $don['id'] ?>)">
                <input type="text" id="input-com-<?= $don['id'] ?>" placeholder="Escribe un comentario...">
                <button type="submit" class="btn-com-send">Enviar</button>
            </form>
            <?php else: ?>
                <p style="text-align:center;color:#aaa;font-size:13px;padding:10px 0"><a href="login.php" style="color:#7b2ff7;font-weight:700">Inicia sesión</a> para comentar</p>
            <?php endif; ?>
        </div>
    </div>

    <?php endforeach; endif; ?>

    <button class="btn-cargar-mas">Cargar más donaciones</button>
</div>

<!-- ── Modal publicar donación ── -->
<div class="modal-overlay" id="modalDonar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalDonar').classList.remove('active')">✕</button>
        <h3>📦 Publicar donación</h3>
        <form action="acciones/publicar_donacion.php" method="POST" enctype="multipart/form-data">
            <input type="text" name="titulo" placeholder="Título (ej: Ropa de niño en buen estado)" required>
            <textarea name="descripcion" placeholder="Describe lo que quieres donar..."></textarea>
            <input type="text" name="ciudad" placeholder="📍 Ciudad (ej: Bogotá, Colombia)">
            <label class="file-label">
                📷 Agregar foto o video
                <input type="file" name="media" accept="image/*,video/*" onchange="previewMedia(this)">
            </label>
            <img id="img-prev" class="media-preview" src="" alt="Preview">
            <video id="vid-prev" class="media-preview" controls style="display:none"></video>
            <div class="modal-btns">
                <button type="button" class="btn-modal-cancel" onclick="document.getElementById('modalDonar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-modal-send">Publicar donación</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal solicitar donación ── -->
<div class="modal-overlay" id="modalSolicitar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalSolicitar').classList.remove('active')">✕</button>
        <h3>✉️ Solicitar donación</h3>
        <p id="sol-titulo" style="color:#888;font-size:14px;margin-bottom:16px"></p>
        <form action="acciones/solicitar_donacion.php" method="POST">
            <input type="hidden" name="donacion_id" id="sol-id">
            <textarea name="mensaje" placeholder="Cuéntale al donante por qué necesitas esta donación..." required></textarea>
            <div class="modal-btns">
                <button type="button" class="btn-modal-cancel" onclick="document.getElementById('modalSolicitar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-modal-send">Enviar solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal confirmar eliminar ── -->
<div class="modal-overlay" id="modalEliminar">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('modalEliminar').classList.remove('active')">✕</button>
        <h3>🗑️ Eliminar publicación</h3>
        <div class="confirm-box">
            <p>¿Estás seguro que quieres eliminar <strong id="eliminar-titulo"></strong>?<br>Esta acción no se puede deshacer.</p>
        </div>
        <form action="acciones/eliminar_donacion.php" method="POST">
            <input type="hidden" name="donacion_id" id="eliminar-id">
            <div class="modal-btns">
                <button type="button" class="btn-modal-cancel" onclick="document.getElementById('modalEliminar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-modal-danger">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">© 2026 EquiRed. Conectando oportunidades, construyendo igualdad.</footer>

<script>
// ── Menú 3 puntos ──
function toggleMenu(id) {
    const menu = document.getElementById('menu-'+id);
    // Cerrar todos los demás
    document.querySelectorAll('.don-menu-dropdown').forEach(m => {
        if(m.id !== 'menu-'+id) m.classList.remove('open');
    });
    menu.classList.toggle('open');
}
function cerrarMenu(id) { document.getElementById('menu-'+id).classList.remove('open'); }

// Cerrar menú al hacer click fuera
document.addEventListener('click', e => {
    if(!e.target.closest('.don-menu-wrap')) {
        document.querySelectorAll('.don-menu-dropdown').forEach(m => m.classList.remove('open'));
    }
});

// ── Confirmar eliminar ──
function confirmarEliminar(id, titulo) {
    document.getElementById('eliminar-id').value = id;
    document.getElementById('eliminar-titulo').textContent = '"' + titulo + '"';
    document.getElementById('menu-'+id).classList.remove('open');
    document.getElementById('modalEliminar').classList.add('active');
}

// ── Copiar enlace ──
function copiarEnlace(id) {
    navigator.clipboard.writeText(window.location.href + '#card-' + id);
    alert('✅ Enlace copiado');
}

// ── Preview media ──
function previewMedia(input) {
    const img = document.getElementById('img-prev');
    const vid = document.getElementById('vid-prev');
    img.style.display='none'; vid.style.display='none';
    if(input.files && input.files[0]) {
        const url = URL.createObjectURL(input.files[0]);
        if(input.files[0].type.startsWith('video/')) { vid.src=url; vid.style.display='block'; }
        else { img.src=url; img.style.display='block'; }
    }
}

// ── Toggle comentarios ──
function toggleComentarios(id) {
    document.getElementById('comments-'+id).classList.toggle('visible');
}

// ── Like AJAX ──
function toggleLike(id, btn) {
    fetch('acciones/like_donacion.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'donacion_id='+id})
    .then(r=>r.json()).then(d=>{
        btn.classList.toggle('liked',d.liked);
        btn.innerHTML=(d.liked?'❤️':'♡')+' Me gusta';
        document.getElementById('likes-'+id).textContent=d.total_likes+' me gusta';
    });
}

// ── Comentar AJAX ──
function enviarComentario(e, id) {
    e.preventDefault();
    const input = document.getElementById('input-com-'+id);
    const texto = input.value.trim();
    if(!texto) return;
    fetch('acciones/comentar_donacion.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'donacion_id='+id+'&comentario='+encodeURIComponent(texto)})
    .then(r=>r.json()).then(d=>{
        if(d.ok){
            const sec  = document.getElementById('comments-'+id);
            const form = sec.querySelector('.com-form');
            const div  = document.createElement('div');
            div.className='comentario-item';
            div.innerHTML=`<div class="com-avatar">${d.inicial}</div><div class="com-burbuja"><strong>${d.nombre}</strong><p>${texto}</p></div>`;
            sec.insertBefore(div, form);
            input.value='';
        }
    });
}

// ── Abrir modal solicitar ──
function abrirSolicitar(id, titulo) {
    document.getElementById('sol-id').value=id;
    document.getElementById('sol-titulo').textContent='📦 '+titulo;
    document.getElementById('modalSolicitar').classList.add('active');
}

// Auto-ocultar toast
setTimeout(()=>{ const t=document.querySelector('.toast'); if(t) t.style.display='none'; },3500);
</script>

<?php function t($f){ $d=time()-strtotime($f); if($d<60) return "ahora"; if($d<3600) return round($d/60)." min"; if($d<86400) return "Hace ".round($d/3600)." horas"; return "Hace ".round($d/86400)." días"; } ?>
</body>
</html>