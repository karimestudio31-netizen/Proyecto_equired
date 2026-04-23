<?php session_start(); 
if(isset($_SESSION['usuario'])){ header("Location: home.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquiRed - Conectando oportunidades</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<?php include("includes/navbar.php"); ?>

<div class="hero">
    <div class="hero-left">
        <div class="hero-badge">
            <div class="badge-icon">👥</div>
            <span>EquiRed</span>
        </div>
        <h1>Bienvenido a EquiRed</h1>
        <p class="subtitle">Conectando oportunidades, construyendo igualdad.</p>
        <p class="description">
            Una plataforma inclusiva que conecta a personas vulnerables con
            información, empleo, apoyo profesional y solidaridad.
            Creando una red real de inclusión.
        </p>
        <div class="hero-buttons">
            <a href="registro.php" class="btn-hero-primary">Únete y genera un cambio &nbsp;→</a>
            <a href="login.php"    class="btn-hero-secondary">Ingresar</a>
        </div>
    </div>

    <div class="hero-right">
        <div class="hero-card">
            <img src="img/logocirculo.png" alt="EquiRed Logo">
            <div class="card-title">Equi<span>Red</span></div>
            <p class="card-subtitle">Conectando oportunidades,<br>Construyendo igualdad.</p>
        </div>
    </div>
</div>

<footer class="footer">© 2026 EquiRed. Conectando oportunidades, construyendo igualdad.</footer>
</body>
</html>