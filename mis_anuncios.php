<?php
$title = "Mis Anuncios - PI Pisos & Inmuebles";
$cssPagina = "resultados.css";
require_once("cabecera.inc");
require_once(__DIR__ . '/privado.inc');
require_once("inicioLog.inc");
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/precio.php';

$userId = $_SESSION['id'] ?? null;
$anuncios = [];
if ($userId && isset($conexion)) {
    $stmt = $conexion->prepare('SELECT IdAnuncio, Titulo, FPrincipal, FRegistro, Ciudad, Precio FROM Anuncios WHERE Usuario = ? ORDER BY FRegistro DESC');
    $stmt->execute([$userId]);
    $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<main>
  <section>
    <?php if (empty($anuncios)): ?>
        <p>No has publicado anuncios todavía.</p>
    <?php else: ?>
        <?php foreach ($anuncios as $a): ?>
            <article>
                <h2><?= htmlspecialchars($a['Titulo'] ?: 'Sin título') ?></h2>
                <a href="anuncio.php?id=<?= $a['IdAnuncio'] ?>">
                    <?php $imgPath = resolve_image_url($a['FPrincipal'] ?? ''); ?>
                    <img src="<?= $imgPath ?>" alt="Foto" width="200" height="200">
                </a>
                <p><strong>Ciudad:</strong> <?= htmlspecialchars($a['Ciudad'] ?: '—') ?></p>
                <p><strong>Fecha:</strong> <?= htmlspecialchars($a['FRegistro']) ?></p>
                <p><strong>Precio:</strong> <?= $a['Precio'] !== null ? number_format((float)$a['Precio'],2,',','.') . ' €' : '—' ?></p>
                <p><a href="modificar_anuncio.php?id=<?= $a['IdAnuncio'] ?>">Editar</a> | <a href="borrar_anuncio.php?id=<?= $a['IdAnuncio'] ?>">Borrar</a></p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>

  </section>


  <a href="anyadir_foto.php" class="btn">
      <i class="icon-foto"></i>
      <strong>AÑADIR FOTO</strong>
  </a>

  <?php require_once("salto.inc"); ?>
</main>

<?php
require_once("pie.inc");    
?>