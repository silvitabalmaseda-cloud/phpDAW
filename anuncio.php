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
    // Usar flash y redirigir a index para mostrar mensaje
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['flash']['acceso_error'] = 'Debes identificarte para ver el detalle del anuncio.';
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    header('Location: index.php');
    exit();
}

// Determinar qué anuncio mostrar basado en el ID (par o impar)
// Obtener anuncio de la BD
$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? intval($_GET['id']) : 0;
require_once __DIR__ . '/includes/conexion.php';
$anuncio = null;
if ($id > 0 && isset($conexion)) {
    try {
        $stmt = $conexion->prepare("SELECT a.IdAnuncio,
                                          a.Titulo AS titulo,
                                          a.FPrincipal AS fotoPrincipal,
                                          a.Texto AS texto,
                                          a.Precio AS precio,
                                          a.FRegistro AS fecha,
                                          a.Ciudad AS ciudad,
                                          p.NomPais AS pais,
                                          u.NomUsuario AS usuario,
                                          ta.NomTAnuncio AS tipoAnuncio,
                                          tv.NomTVivienda AS tipoVivienda
                                     FROM Anuncios a
                                     LEFT JOIN Usuarios u ON a.Usuario = u.IdUsuario
                                     LEFT JOIN Paises p ON a.Pais = p.IdPais
                                     LEFT JOIN TiposAnuncios ta ON a.TAnuncio = ta.IdTAnuncio
                                     LEFT JOIN TiposViviendas tv ON a.TVivienda = tv.IdTVivienda
                                     WHERE a.IdAnuncio = ? LIMIT 1");
        $stmt->execute([$id]);
        $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($anuncio) {
            $stmt2 = $conexion->prepare("SELECT Titulo, Foto, Alternativo FROM Fotos WHERE Anuncio = ?");
            $stmt2->execute([$id]);
            $fotos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            // normalizar para la plantilla
            $anuncio['fotos'] = array_map(function($f){ return $f['Foto']; }, $fotos);
            $anuncio['caracteristicas'] = [];
            // garantizar que fotoPrincipal sea la ruta correcta (preferir practica)
            require_once __DIR__ . '/includes/precio.php';
            $anuncio['fotoPrincipal'] = resolve_image_url($anuncio['fotoPrincipal'] ?? '');
        } else {
            $fotos = [];
        }
    } catch (Exception $e) {
        $anuncio = null;
        $fotos = [];
    }
} else {
    $fotos = [];
}

// --- guardar anuncio visitado ---
// Comprobar que se ha recibido un id válido
if ($id > 0 && $anuncio && isset($anuncio['titulo'])) {
    require_once __DIR__ . '/includes/precio.php';

    // Mantener el valor original salvo que sea claramente numérico
    $precio_val = null;
    if (isset($anuncio['precio'])) {
        $rawp = $anuncio['precio'];
        if (is_numeric($rawp)) $precio_val = (float)$rawp;
        else $precio_val = $rawp;
    }

    $visitado = [
        'id' => (int)$id,
        'titulo' => $anuncio['titulo'] ?? '',
        'ciudad' => $anuncio['ciudad'] ?? '',
        'pais' => $anuncio['pais'] ?? '',
        'precio' => $precio_val,
        // fotoPrincipal ya contiene la ruta relativa (ej. DAW/imagenes/xxx) o el fallback
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
                <?php
                // Mostrar 'Añadir foto' solo si el usuario autenticado es el propietario del anuncio
                $usuarioLog = $_SESSION['usuario'] ?? null;
                $propietario = $anuncio['usuario'] ?? null;
                if ($usuarioLog && $propietario && $usuarioLog === $propietario): ?>
                    <a href="anyadir_foto.php?id=<?= $id ?>" class="btn">Añadir foto a este anuncio</a>
                    <a href="mis_anuncios.php" class="btn">Volver a mis anuncios</a>
                <?php else: ?>
                    <a href="mensaje.php?anuncio=<?= $id ?>" class="btn">Enviar mensaje al anunciante</a>
                    <a href="index.php" class="btn">Volver al inicio</a>
                <?php endif; ?>
            </aside>
        </section>
    </section>

    <?php require_once("salto.inc"); ?>
</main>

<?php require_once("pie.inc"); ?>
