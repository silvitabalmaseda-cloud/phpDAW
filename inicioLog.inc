<header id="inicio">
  <h1>PI - Pisos &amp; Inmuebles</h1>
  <h2>PORTAL DE ANUNCIOS DE VENTA Y ALQUILER DE VIVIENDAS</h2>
</header>


<nav>
    <ul class="menu">
        <li><a href="index_logueado.php">
                <i class="icon-home"></i>
                <strong>INICIO</strong>
            </a></li>
        <li><a href="logout.php">
                <i class="icon-baja"></i>
                <strong>CERRAR SESIÓN</strong></a></li>
        <li><a href="busqueda.php">
                <i class="icon-search"></i>
                <strong>BÚSQUEDA</strong>
            </a></li>
        <li class="perfil">
            <a href="miperfil.php">
                <?php
                require_once __DIR__ . '/includes/precio.php';
                $avatar = 'DAW/practica/imagenes/default-avatar-profile-icon-vector-260nw-1909596082.webp';
                if (isset($_SESSION['foto']) && $_SESSION['foto'] !== '') {
                    $avatar = resolve_image_url($_SESSION['foto']);
                }
                ?>
                <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="Foto de perfil" class="perfil-foto">
                <?php
                if (isset($_SESSION['usuario'])) {
                    echo htmlspecialchars($_SESSION['usuario']);
                } else {
                    echo "Invitado";
                }
                ?>
            </a>
        </li>
    </ul>
</nav>