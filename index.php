<?php
// Detectar si el navegador ya tiene una cookie de sesión activa (PHPSESSID)
$hadSessionCookie = isset($_COOKIE[session_name()]);

// Iniciar sesión
session_start();
require_once __DIR__ . '/includes/precio.php';

// Restaurar usuario desde cookie si no había sesión previa (nuevo navegador) y existe cookie 'usuario'
$restored = false;
if (!isset($_SESSION['usuario']) && !$hadSessionCookie && isset($_COOKIE['usuario'])) {
  $cookieUser = $_COOKIE['usuario'];
  $cookieClave = $_COOKIE['clave'] ?? null;

  // Intentar restaurar desde BD si hay conexión y cookie con hash
  if ($cookieClave && file_exists(__DIR__ . '/includes/conexion.php')) {
    try {
      require_once __DIR__ . '/includes/conexion.php';
      $s = $conexion->prepare('SELECT IdUsuario, NomUsuario, Estilo, Clave, Foto FROM Usuarios WHERE NomUsuario = ? LIMIT 1');
      $s->execute([$cookieUser]);
      $row = $s->fetch(PDO::FETCH_ASSOC);
      if ($row && isset($row['Clave']) && hash_equals($row['Clave'], $cookieClave)) {
        $_SESSION['usuario'] = $row['NomUsuario'];
        $_SESSION['id'] = $row['IdUsuario'];
        $_SESSION['estilo'] = $row['Estilo'];
        $_SESSION['style'] = $row['Estilo'] ?? 'default';
        // foto
        if (!empty($row['Foto'])) {
          $_SESSION['foto'] = resolve_image_url($row['Foto']);
        }
        $restored = true;
      }
    } catch (Exception $e) {
      // si falla la BD, seguimos con la compatibilidad estática
      $restored = false;
    }
  }

  // Si no se pudo restaurar desde BD, probar el array estático (compatibilidad antigua)
  if (!$restored) {
    include_once __DIR__ . '/usuarios.php';
    $usuarios = crearUsuarios();
    foreach ($usuarios as $u) {
      if ($u[0] === $cookieUser) {
        $_SESSION['usuario'] = $cookieUser;
        $_SESSION['style'] = isset($u[2]) ? $u[2] : 'default';
        $restored = true;
        break;
      }
    }
  }
}

// Determinar nombre del usuario
$nombre = 'Invitado';
if (isset($_SESSION['usuario'])) {
    $nombre = htmlspecialchars($_SESSION['usuario']);
} elseif (isset($_COOKIE['usuario'])) {
    $nombre = htmlspecialchars($_COOKIE['usuario']);
}

// Obtener última visita
$ultima = $_COOKIE['ultima_visita'] ?? 'primera vez';

// Determinar saludo según la hora
$hora = date('H');
if ($hora >= 6 && $hora < 12) {
    $saludo = "Buenos días";
} elseif ($hora >= 12 && $hora < 16) {
    $saludo = "Hola";
} elseif ($hora >= 16 && $hora < 20) {
    $saludo = "Buenas tardes";
} else {
    $saludo = "Buenas noches";
}

// Mostrar mensaje solo si la sesión se ha restaurado desde cookie (es decir, el usuario eligió 'recordarme')
if ($restored) {
  echo "<section class='recordatorio'>
      <p>$saludo <strong>$nombre</strong>, tu última visita fue el <strong>$ultima</strong>.</p>
      <a href='index_logueado.php' class='btn'>Acceder</a>
      <a href='logout.php' class='btn'>Salir</a>
      </section>";

  // Actualizar cookie de última visita sólo si hemos restaurado la sesión desde cookie
  setcookie('ultima_visita', date('d/m/Y H:i'), time() + 90 * 24 * 60 * 60, '/');
}

$title = "PI - Pisos & Inmuebles";
$cssPagina = "index.css";
require_once("cabecera.inc");
require_once("inicio.inc");
require_once("acceso.inc");
require_once __DIR__ . '/includes/conexion.php';
?>

<main>

  <section>
    <h2>BÚSQUEDA RÁPIDA</h2>
    <form action="resultados.php" method="get">
      <p>
        <label for="consulta">Ciudad:</label>
        <input type="text" id="consulta" name="ciudad" placeholder="Ej. Madrid">
        <button type="submit"><strong>BUSCAR</strong></button>
      </p>
    </form>
  </section>

  <section class="anuncios">
    <h2>ÚLTIMOS 5 ANUNCIOS PUBLICADOS</h2>
    <ul>
      <?php
      try {
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
          echo '<li>No hay anuncios disponibles.</li>';
      } else {
          foreach ($ultimos as $a) {
            // Comprobar que la imagen existe; si no, usar imagen por defecto
            $img = 'DAW/practica/imagenes/anuncio2.jpg';
            if (!empty($a['FPrincipal'])) {
              $img = resolve_image_url($a['FPrincipal']);
            }
              $titulo = htmlspecialchars($a['Titulo'] ?: 'Sin título');
              $ciudad = htmlspecialchars($a['Ciudad'] ?: '—');
              $pais = htmlspecialchars($a['NomPais'] ?: '—');
              $precio = $a['Precio'] !== null ? number_format((float)$a['Precio'], 2, ',', '.') . ' €' : '—';
              echo "<li><article><a href=\"anuncio.php?id={$a['IdAnuncio']}\"><img src=\"{$img}\" alt=\"{$titulo}\" width=\"150\"><h3>{$titulo}</h3></a><p>Fecha: {$a['FRegistro']} | Ciudad: {$ciudad} <br>País: {$pais} | Precio: {$precio}</p></article></li>";
          }
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
