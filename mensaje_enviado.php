<?php
$title = "Mensaje enviado - PI Pisos & Inmuebles";
$cssPagina = "mensaje.css";
require_once("cabecera.inc");
require_once("inicioLog.inc");

// Sólo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mensaje.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Recoger y sanear datos
$tipo = isset($_POST['tipo_mensaje']) ? trim((string)$_POST['tipo_mensaje']) : '';
$mensaje = isset($_POST['mensaje']) ? trim((string)$_POST['mensaje']) : '';
$anuncioId = isset($_POST['anuncio']) && ctype_digit($_POST['anuncio']) ? intval($_POST['anuncio']) : null;
$usuDestino = isset($_POST['usu_destino']) && ctype_digit((string)$_POST['usu_destino']) ? intval($_POST['usu_destino']) : null;

$errors = [];

// Validaciones locales
if ($tipo === '') $errors[] = 'tipo_mensaje';
if (mb_strlen(preg_replace('/\s+/', '', $mensaje)) < 10) $errors[] = 'mensaje';

// Autenticación: debe existir usuario origen
$usuOrigen = $_SESSION['id'] ?? null;
if (!$usuOrigen) {
    $errors[] = 'no_auth';
}

// Si hay errores, no insertar
$insertOk = false;
if (empty($errors) && file_exists(__DIR__ . '/includes/conexion.php')) {
    require_once __DIR__ . '/includes/conexion.php';
    try {
        // Determinar IdTMensaje: si viene numérico, usar; si viene nombre (raro), intentar buscar
        $idTipo = null;
        if (ctype_digit((string)$tipo)) {
            $idTipo = intval($tipo);
        } else {
            $s = $conexion->prepare('SELECT IdTMensaje FROM TiposMensajes WHERE NomTMensaje LIKE ? LIMIT 1');
            $s->execute(["%$tipo%"]);
            $r = $s->fetch(PDO::FETCH_ASSOC);
            if ($r) $idTipo = $r['IdTMensaje'];
        }

        // Si no tenemos destinatario, intentar inferirlo desde el anuncio
        if (empty($usuDestino) && $anuncioId) {
            $q = $conexion->prepare('SELECT Usuario FROM Anuncios WHERE IdAnuncio = ? LIMIT 1');
            $q->execute([$anuncioId]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if ($row) $usuDestino = $row['Usuario'];
        }

        // Inserción
        $ins = $conexion->prepare('INSERT INTO Mensajes (TMensaje, Texto, Anuncio, UsuOrigen, UsuDestino, FRegistro) VALUES (?, ?, ?, ?, ?, NOW())');
        $ins->execute([
            $idTipo,
            $mensaje,
            $anuncioId,
            $usuOrigen,
            $usuDestino
        ]);
        $insertOk = true;
    } catch (Exception $e) {
        // registrar/logging opcional; marcar error
        $errors[] = 'db';
    }
}

?>

<main>
    <section>
        <?php if (empty($errors) && $insertOk): ?>
            <h2>Mensaje enviado con éxito</h2>
            <h3>Datos del mensaje:</h3>
            <ul>
                <li><strong>Descripción del mensaje:</strong> <?php echo nl2br(htmlspecialchars($mensaje)); ?></li>
                <li><strong>Fecha del mensaje:</strong> <?php echo date('d/m/Y H:i'); ?></li>
            </ul>
        <?php else: ?>
            <h2>Error al enviar el mensaje</h2>
            <p class="mensaje-confirmacion">No se ha podido enviar el mensaje por los siguientes motivos:</p>
            <ul>
                <?php if (in_array('no_auth', $errors)): ?>
                    <li>No estás identificado. Inicia sesión para poder enviar mensajes.</li>
                <?php endif; ?>
                <?php if (in_array('tipo_mensaje', $errors)): ?>
                    <li>Debes seleccionar un tipo de mensaje válido.</li>
                <?php endif; ?>
                <?php if (in_array('mensaje', $errors)): ?>
                    <li>Escribe al menos 10 caracteres en el texto del mensaje.</li>
                <?php endif; ?>
                <?php if (in_array('db', $errors)): ?>
                    <li>Error interno al guardar el mensaje. Inténtalo más tarde.</li>
                <?php endif; ?>
            </ul>

            <p>
                <a href="mensaje.php" class="btn"><strong>VOLVER AL FORMULARIO</strong></a>
            </p>
        <?php endif; ?>
    </section>

    <?php require_once("salto.inc"); ?>

</main>

<?php
require_once("pie.inc");
?>
