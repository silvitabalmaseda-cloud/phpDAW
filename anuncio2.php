<?php

// --- Control de acceso: solo usuarios logueados o recordados ---
session_start();

// Función de usuarios válida (igual que en usuario.php)
function crearUsuarios() {
    return [
        ["usuario1", "usuario1"],
        ["usuario2", "usuario2"],
        ["silvia", "silvia123"],
        ["carmen", "carmen123"]
    ];
}

$usuarios = crearUsuarios();
$usuarioAutenticado = false;

// Sesión activa
if (isset($_SESSION['usuario'])) {
    $usuarioAutenticado = true;
}
// Cookie “recordarme”
elseif (isset($_COOKIE['recordar_usuario'])) {
    $recordado = json_decode($_COOKIE['recordar_usuario'], true);
    if ($recordado && isset($recordado['usuario']) && isset($recordado['clave'])) {
        foreach ($usuarios as $user) {
            if ($user[0] === $recordado['usuario'] && $user[1] === $recordado['clave']) {
                $_SESSION['usuario'] = $recordado['usuario'];
                $usuarioAutenticado = true;

                // Actualizar la cookie con la nueva fecha de visita
                $recordado['ultima_visita'] = date("d/m/Y H:i");
                setcookie("recordar_usuario", json_encode($recordado), time() + 90 * 24 * 60 * 60, "/");
                break;
            }
        }
    }
}

// Si no está autenticado
if (!$usuarioAutenticado) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['flash']['acceso_error'] = 'Debes identificarte para ver el detalle del anuncio.';
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    header('Location: index.php');
    exit();
}

// Determinar qué anuncio mostrar basado en el ID (par o impar)
$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? intval($_GET['id']) : 1;
$esPar = ($id % 2 == 0);

if ($esPar) {
    // Anuncio par
    $anuncio = [
        'tipoAnuncio' => 'Alquiler',
        'tipoVivienda' => 'Apartamento',
        'fotoPrincipal' => 'DAW/practica/imagenes/anuncio4.jpg',
        'titulo' => 'Apartamento en alquiler en Bilbao',
        'precio' => '600 €/mes',
        'texto' => 'Amplio apartamento situado a 5 minutos del centro, con vistas y todas las comodidades. Perfecto para familias o profesionales.',
        'fecha' => '2025-09-19',
        'ciudad' => 'Bilbao',
        'pais' => 'España',
        'caracteristicas' => ['80m²', '3 habitaciones', '2 baños', '2ª planta', '2015'],
        'fotos' => ['anuncio4.jpg', 'anuncio4.jpg', 'anuncio4.jpg'],
        'usuario' => 'Carlos Garcia'
    ];
} else {
    // Anuncio impar
    $anuncio = [
        'tipoAnuncio' => 'Venta',
        'tipoVivienda' => 'Casa',
        'fotoPrincipal' => 'DAW/practica/imagenes/anuncio3.jpg',
        'titulo' => 'Casa en venta en Portugal',
        'precio' => '150.000 €',
        'texto' => 'Casa completamente reformado en el centro de la ciudad, con excelentes comunicaciones y cerca de todos los servicios.',
        'fecha' => '2025-09-19',
        'ciudad' => 'Oporto',
        'pais' => 'España',
        'caracteristicas' => ['80m²', '3 habitaciones', '2 baños', '2ª planta', '2015'],
        'fotos' => ['anuncio3.jpg', 'anuncio3.jpg', 'anuncio3.jpg'],
        'usuario' => 'Carlos Garcia'
    ];
}

// --- guardar anuncio visitado ---

// Comprobar que se ha recibido un id válido
if (isset($_GET['id']) && isset($anuncio['titulo'])) {
    require_once __DIR__ . '/includes/precio.php';
    $precio_val = null;
    if (isset($anuncio['precio'])) {
        $rawp = $anuncio['precio'];
        if (is_numeric($rawp)) $precio_val = (float)$rawp;
        else $precio_val = $rawp;
    }

    $visitado = [
        'id' => isset($_GET['id']) ? intval($_GET['id']) : 0,
        'titulo' => $anuncio['titulo'] ?? '',
        'ciudad' => $anuncio['ciudad'] ?? '',
        'pais' => $anuncio['pais'] ?? '',
        'precio' => $precio_val,
        'imagen' => $anuncio['fotoPrincipal'] ?? ''
    ];

    // Leer cookie existente o crear nueva
    $visitados = isset($_COOKIE['visitados']) ? json_decode($_COOKIE['visitados'], true) : [];

    // Filtrar posibles entradas vacías o sin id
    $visitados = array_filter($visitados, fn($a) => isset($a['id']) && $a['id'] > 0);

    // Evitar duplicados del mismo anuncio
    $visitados = array_filter($visitados, fn($a) => $a['id'] != $visitado['id']);

    // Insertar nuevo al principio
    array_unshift($visitados, $visitado);

    // Limitar a 4 anuncios
    $visitados = array_slice($visitados, 0, 4);

    // Guardar cookie durante 7 días
    setcookie('visitados', json_encode($visitados), time() + 7 * 24 * 60 * 60, '/');
}

$title = "PI - Pisos & Inmuebles";
$cssPagina = "anuncio.css";
require_once("cabecera.inc");
require_once("inicioLog.inc");
?>

<main>
    <section class="anuncio-detalle">
        <h2><?= htmlspecialchars($anuncio['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
        <section class="fotos">
            <img src="<?= htmlspecialchars($anuncio['fotoPrincipal'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="Foto principal del anuncio <?= htmlspecialchars($anuncio['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                 class="foto-principal" width="600" height="400">
        </section>

        <section class="informacion">
            <h2>Información del anuncio</h2>
            <aside class="detalles">
                <p><strong>Tipo de anuncio:</strong> <?= htmlspecialchars($anuncio['tipoAnuncio'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Tipo de vivienda:</strong> <?= htmlspecialchars($anuncio['tipoVivienda'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Precio:</strong> <span class="precio"><?= htmlspecialchars($anuncio['precio'], ENT_QUOTES, 'UTF-8') ?></span></p>
                <p><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($anuncio['texto'], ENT_QUOTES, 'UTF-8')) ?></p>
                <p><strong>Fecha de publicación:</strong> <?= htmlspecialchars($anuncio['fecha'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Ciudad:</strong> <?= htmlspecialchars($anuncio['ciudad'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>País:</strong> <?= htmlspecialchars($anuncio['pais'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Usuario:</strong> <?= htmlspecialchars($anuncio['usuario'], ENT_QUOTES, 'UTF-8') ?></p>
            </aside>

            <h3>Características</h3>
            <ul class="caracteristicas">
                <?php foreach ($anuncio['caracteristicas'] as $caracteristica): ?>
                    <li><?= htmlspecialchars($caracteristica, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>

            <section>
                <aside class="miniaturas">
                    <?php foreach ($anuncio['fotos'] as $index => $foto): ?>
                        <img src="DAW/practica/imagenes/<?= htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') ?>"
                            alt="Miniatura <?= $index + 1 ?> del anuncio" class="miniatura" width="150" height="150">
                    <?php endforeach; ?>
                </aside>
            </section>

            <aside class="acciones">
                <a href="anyadir_foto.php?id=<?= $id ?>" class="btn">Añadir foto a este anuncio</a>
                <a href="mis_anuncios.php" class="btn">Volver a mis anuncios</a>
            </aside>
        </section>
    </section>

    <?php require_once("salto.inc"); ?>
</main>

<?php require_once("pie.inc"); ?>
