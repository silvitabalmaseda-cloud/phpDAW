<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['mensaje'])) {
    echo "<p class='info'>" . htmlspecialchars($_GET['mensaje'], ENT_QUOTES, 'UTF-8') . "</p>";
}

$nombreUsuario = isset($_SESSION['usuario']) ? htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') : '';
$hora = (int) date('H');
if ($hora >= 6 && $hora <= 11) {
  $saludoPrefijo = 'Buenos días';
} elseif ($hora >= 12 && $hora <= 15) {
  $saludoPrefijo = 'Hola';
} elseif ($hora >= 16 && $hora <= 19) {
  $saludoPrefijo = 'Buenas tardes';
} else {
  $saludoPrefijo = 'Buenas noches';
}
$saludoCompleto = $saludoPrefijo . ' ' . $nombreUsuario . '.';


$title = "PI - Pisos & Inmuebles";
$cssPagina = "index.css";
require_once("cabecera.inc");
require_once("inicioLog.inc");
?>

<main>
  <?php if ($nombreUsuario !== ''): ?>
    <section class="saludo">
      <div class="saludo__box">
        <span class="saludo__prefix"><?php echo $saludoPrefijo; ?></span>
        &nbsp;
        <span class="saludo__user"><?php echo $nombreUsuario; ?></span>
        <span class="saludo__dot">.</span>
      </div>
    </section>
  <?php endif; ?>

  <section>
    <h2>BÚSQUEDA RÁPIDA</h2>
    <form action="resultados.php" method="get">
      <p>
        <label for="consulta">Ciudad:</label>
        <input type="text" id="consulta" name="q" placeholder="Ej. Madrid">
        <button type="submit"><strong>BUSCAR</strong></button>
      </p>
    </form>
  </section>

  <section class="anuncios">
    <h2>ÚLTIMOS 5 ANUNCIOS PUBLICADOS</h2>
    <ul>
      <?php
      // Mostrar últimos 5 anuncios desde la BD
      try {
          if (!isset($conexion)) require_once __DIR__ . '/includes/conexion.php';
          require_once __DIR__ . '/includes/precio.php';
          $stmt = $conexion->query("SELECT a.IdAnuncio, a.Titulo, a.FPrincipal, a.FRegistro, a.Ciudad, p.NomPais, a.Precio
                                      FROM Anuncios a
                                      LEFT JOIN Paises p ON a.Pais = p.IdPais
                                      ORDER BY a.FRegistro DESC
                                      LIMIT 5");
          $ultimos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (Exception $e) {
          $ultimos = [];
      }

        if (empty($ultimos)) {
          // Fallback: anuncios predefinidos para pruebas cuando la BD está vacía
          $ultimos = [
            [
              'Titulo' => 'Piso luminoso en el centro',
              'FPrincipal' => 'anuncio1.jpg',
              'FRegistro' => date('Y-m-d'),
              'Ciudad' => 'Madrid',
              'NomPais' => 'España',
              'Precio' => 85000,
              'link' => 'DAW/practica/anuncio.html'
            ],
            [
              'Titulo' => 'Apartamento junto al parque',
              'FPrincipal' => 'anuncio2.jpg',
              'FRegistro' => date('Y-m-d'),
              'Ciudad' => 'Sevilla',
              'NomPais' => 'España',
              'Precio' => 120000,
              'link' => 'DAW/practica/anuncio.html'
            ],
            [
              'Titulo' => 'Estudio acogedor en zona universitaria',
              'FPrincipal' => '',
              'FRegistro' => date('Y-m-d'),
              'Ciudad' => 'Valencia',
              'NomPais' => 'España',
              'Precio' => 65000,
              'link' => 'DAW/practica/anuncio.html'
            ]
          ];
        }

        foreach ($ultimos as $a) {
          $img = resolve_image_url($a['FPrincipal'] ?? '');
          $titulo = htmlspecialchars($a['Titulo'] ?? 'Sin título');
          $ciudad = htmlspecialchars($a['Ciudad'] ?? '—');
          $pais = htmlspecialchars($a['NomPais'] ?? ($a['NomPais'] ?? '—'));
          $precio = isset($a['Precio']) ? number_format((float)$a['Precio'], 2, ',', '.') . ' €' : '—';
          $link = isset($a['link']) ? $a['link'] : (isset($a['IdAnuncio']) ? "anuncio.php?id={$a['IdAnuncio']}" : '#');
          echo "<li><article><a href=\"{$link}\"><img src=\"{$img}\" alt=\"{$titulo}\" width=\"150\"><h3>{$titulo}</h3></a><p>Fecha: {$a['FRegistro']} | Ciudad: {$ciudad} <br>País: {$pais} | Precio: {$precio}</p></article></li>";
        }
      ?>
    </ul>
  </section>

  <?php
  require_once("panelVisitados.inc");
  require_once("salto.inc");
  ?>

</main>

<?php
require_once("pie.inc");
?>