<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);
$logueado      = isset($_SESSION['usuario']);

// Foto de perfil en navbar
$foto_nav = null;
if($logueado && isset($_SESSION['usuario_id'])) {
    global $pdo;
    if(isset($pdo)) {
        $fn = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id=?");
        $fn->execute([$_SESSION['usuario_id']]);
        $foto_nav = $fn->fetchColumn();
    }
}
?>
<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <div class="brand-icon">
            <img src="img/logocirculo.png" alt="EquiRed" onerror="this.style.display='none'">
        </div>
        <span class="brand-name">Equi<span>Red</span></span>
    </a>

    <div class="menu">
        <?php if($logueado): ?>
            <a href="home.php"      class="<?= $pagina_actual==='home.php'      ? 'active':'' ?>">Inicio</a>
            <a href="empleos.php"   class="<?= $pagina_actual==='empleos.php'   ? 'active':'' ?>">Empleos</a>
            <a href="donar.php"     class="<?= $pagina_actual==='donar.php'     ? 'active':'' ?>">Donar</a>
            <a href="asesorias.php" class="<?= $pagina_actual==='asesorias.php' ? 'active':'' ?>">Asesorías</a>

            <a href="perfil.php" class="nav-perfil-btn" title="Mi perfil">
                <div class="nav-avatar">
                    <?php if(!empty($foto_nav)): ?>
                        <img src="uploads/<?= htmlspecialchars($foto_nav) ?>" alt="Mi perfil">
                    <?php else: ?>
                        <?= strtoupper(substr($_SESSION['usuario'],0,1)) ?>
                    <?php endif; ?>
                </div>
                <span><?= htmlspecialchars(explode(' ', $_SESSION['usuario'])[0]) ?></span>
            </a>
            <a href="#" class="btn-nav-logout" onclick="confirmarSalir(event)">Salir</a>

        <?php endif; ?>
    </div>
</nav>

<style>
.nav-perfil-btn {
    display:flex; align-items:center; gap:8px;
    padding:6px 12px; border-radius:50px;
    background:#f3e8ff; color:#7b2ff7;
    font-weight:700; font-size:14px;
    text-decoration:none; margin-left:8px;
    transition:background 0.2s;
}
.nav-perfil-btn:hover { background:#e9d5ff; color:#7b2ff7; }
.nav-avatar {
    width:30px; height:30px; border-radius:50%;
    background:linear-gradient(135deg,#7b2ff7,#a855f7);
    display:flex; align-items:center; justify-content:center;
    font-size:13px; color:white; font-weight:800;
    overflow:hidden; flex-shrink:0;
}
.nav-avatar img { width:100%; height:100%; object-fit:cover; }

</style>
<script>
function confirmarSalir(e) {
    e.preventDefault();
    if(confirm('¿Estás seguro que deseas cerrar sesión?')) {
        window.location.href = 'acciones/logout.php';
    }
}
</script>

</style>