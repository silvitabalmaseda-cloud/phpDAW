<?php
$title = "PI - Pisos & Inmuebles";
$cssPagina = "mensaje.css";
require_once("cabecera.inc");
require_once(__DIR__ . '/privado.inc');
require_once("inicioLog.inc");

// Cargar tipos de mensajes desde BD y, si se proporciona, obtener anuncio y su propietario
$tiposMensajes = [];
$anuncioId = isset($_GET['anuncio']) && ctype_digit($_GET['anuncio']) ? intval($_GET['anuncio']) : null;
$usuDestino = null;
if (file_exists(__DIR__ . '/includes/conexion.php')) {
    require_once __DIR__ . '/includes/conexion.php';
    try {
        $s = $conexion->query('SELECT IdTMensaje, NomTMensaje FROM TiposMensajes ORDER BY IdTMensaje');
        $tiposMensajes = $s->fetchAll(PDO::FETCH_ASSOC);

        if ($anuncioId) {
            $q = $conexion->prepare('SELECT Usuario FROM Anuncios WHERE IdAnuncio = ? LIMIT 1');
            $q->execute([$anuncioId]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            if ($r) $usuDestino = $r['Usuario'];
        }
    } catch (Exception $e) {
        $tiposMensajes = [];
    }
}
?>

<main>
    <section>
        <h3>Enviar Mensaje</h3>
        <form id="formMensaje" action="mensaje_enviado.php" method="post">
            <p>
                <label for="mensaje"><strong>Mensaje:</strong></label><br>
                <textarea name="mensaje" id="mensaje" placeholder="Mensaje al anunciante" rows="6" cols="60"></textarea>
            </p>

            <p class="tipo-mensaje">
                <label for="tipo_mensaje"><strong>Tipo de mensaje:</strong></label><br>
                <select name="tipo_mensaje" id="tipo_mensaje">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($tiposMensajes as $t): ?>
                        <option value="<?php echo htmlspecialchars($t['IdTMensaje']); ?>"><?php echo htmlspecialchars($t['NomTMensaje']); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <?php if ($anuncioId): ?>
                <input type="hidden" name="anuncio" value="<?php echo $anuncioId; ?>">
            <?php endif; ?>
            <?php if ($usuDestino): ?>
                <input type="hidden" name="usu_destino" value="<?php echo htmlspecialchars($usuDestino); ?>">
            <?php endif; ?>

            <p>
                <button type="submit"><strong>ENVIAR MENSAJE</strong></button>
            </p>
        </form>
    </section>

    <?php
    require_once("salto.inc");
    ?>

</main>

<script src="DAW/practica/js/mensaje.js"></script>

<?php
require_once("pie.inc");
?>