<?php
$title = "PI - PI Pisos & Inmuebles";
$cssPagina = "mis_mensajes.css";
require_once("cabecera.inc");
require_once(__DIR__ . '/privado.inc');
require_once("inicioLog.inc");
require_once __DIR__ . '/includes/conexion.php';

$userId = $_SESSION['id'] ?? null;
$mensajesEnviados = [];
$mensajesRecibidos = [];
if ($userId && isset($conexion)) {
    // Mensajes enviados
    $s1 = $conexion->prepare('SELECT m.IdMensaje, m.Texto, m.FRegistro, a.Titulo AS AnuncioTitulo, tm.NomTMensaje, u.NomUsuario AS Destino
                              FROM Mensajes m
                              LEFT JOIN Anuncios a ON m.Anuncio = a.IdAnuncio
                              LEFT JOIN TiposMensajes tm ON m.TMensaje = tm.IdTMensaje
                              LEFT JOIN Usuarios u ON m.UsuDestino = u.IdUsuario
                              WHERE m.UsuOrigen = ? ORDER BY m.FRegistro DESC');
    $s1->execute([$userId]);
    $mensajesEnviados = $s1->fetchAll(PDO::FETCH_ASSOC);

    // Mensajes recibidos
    $s2 = $conexion->prepare('SELECT m.IdMensaje, m.Texto, m.FRegistro, a.Titulo AS AnuncioTitulo, tm.NomTMensaje, u.NomUsuario AS Origen
                              FROM Mensajes m
                              LEFT JOIN Anuncios a ON m.Anuncio = a.IdAnuncio
                              LEFT JOIN TiposMensajes tm ON m.TMensaje = tm.IdTMensaje
                              LEFT JOIN Usuarios u ON m.UsuOrigen = u.IdUsuario
                              WHERE m.UsuDestino = ? ORDER BY m.FRegistro DESC');
    $s2->execute([$userId]);
    $mensajesRecibidos = $s2->fetchAll(PDO::FETCH_ASSOC);
}
?>

<main>
    <section>
        <h3>Mensajes enviados (<?php echo count($mensajesEnviados); ?>)</h3>
        <?php if (empty($mensajesEnviados)): ?>
            <p>No has enviado mensajes todavía.</p>
        <?php else: ?>
            <?php foreach ($mensajesEnviados as $m): ?>
                <article>
                    <p><strong>Para:</strong> <?php echo htmlspecialchars($m['Destino'] ?? '—'); ?></p>
                    <p><strong>Tipo de mensaje:</strong> <?php echo htmlspecialchars($m['NomTMensaje'] ?? '—'); ?></p>
                    <p><strong>Texto del mensaje:</strong> <?php echo nl2br(htmlspecialchars($m['Texto'] ?? '')); ?></p>
                    <p><strong>Fecha del mensaje:</strong> <?php echo htmlspecialchars($m['FRegistro'] ?? ''); ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section>
        <h3>Mensajes recibidos (<?php echo count($mensajesRecibidos); ?>)</h3>
        <?php if (empty($mensajesRecibidos)): ?>
            <p>No has recibido mensajes todavía.</p>
        <?php else: ?>
            <?php foreach ($mensajesRecibidos as $m): ?>
                <article>
                    <p><strong>De:</strong> <?php echo htmlspecialchars($m['Origen'] ?? '—'); ?></p>
                    <p><strong>Tipo de mensaje:</strong> <?php echo htmlspecialchars($m['NomTMensaje'] ?? '—'); ?></p>
                    <p><strong>Texto del mensaje:</strong> <?php echo nl2br(htmlspecialchars($m['Texto'] ?? '')); ?></p>
                    <p><strong>Fecha del mensaje:</strong> <?php echo htmlspecialchars($m['FRegistro'] ?? ''); ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

<?php
    require_once("salto.inc");  
?>

</main>

<?php
require_once("pie.inc");    
?>