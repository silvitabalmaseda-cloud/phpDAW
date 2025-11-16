<section class="anuncios visitados">
  <h2>Últimos anuncios visitados</h2>

  <?php
    if (isset($_COOKIE['visitados'])) {
      $visitados = json_decode($_COOKIE['visitados'], true);

      require_once __DIR__ . '/includes/precio.php';

      if (is_array($visitados) && count($visitados) > 0) {
          echo "<ul class='lista-visitados'>";
              foreach ($visitados as $a) {
                $id_int = isset($a['id']) ? intval($a['id']) : 0;
                $titulo = isset($a['titulo']) ? htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') : '';
                $ciudad = isset($a['ciudad']) ? htmlspecialchars($a['ciudad'], ENT_QUOTES, 'UTF-8') : '';
                $pais = isset($a['pais']) ? htmlspecialchars($a['pais'], ENT_QUOTES, 'UTF-8') : '';
                $imagen = isset($a['imagen']) ? $a['imagen'] : '';
                // resolver ruta de imagen preferente
                require_once __DIR__ . '/includes/precio.php';
                $img_src = resolve_image_url($imagen);

                // Formatear precio usando funciones existentes
                $precio_display = '—';
                if (isset($a['precio'])) {
                  $rawp = $a['precio'];
                  if (is_numeric($rawp)) {
                    $precio_display = formatearPrecio($rawp);
                  } else {
                    // intentar extraer número y formatear
                    $p = preg_replace('/[^0-9,\.]/', '', (string)$rawp);
                    if ($p !== '') {
                      if (strpos($p, ',') !== false && strpos($p, '.') !== false) {
                        $p = str_replace('.', '', $p);
                        $p = str_replace(',', '.', $p);
                      } elseif (strpos($p, ',') !== false && strpos($p, '.') === false) {
                        $p = str_replace(',', '.', $p);
                      }
                      if (is_numeric($p)) {
                        $precio_display = formatearPrecio((float)$p);
                      } else {
                        $precio_display = htmlspecialchars((string)$rawp, ENT_QUOTES, 'UTF-8');
                      }
                    } else {
                      $precio_display = htmlspecialchars((string)$rawp, ENT_QUOTES, 'UTF-8');
                    }
                  }
                }

              // Determinar enlace: si id válido usar anuncio.php?id=, si no usar link si existe
              if ($id_int > 0) {
                $href = 'anuncio.php?id=' . $id_int;
              } elseif (isset($a['link'])) {
                $href = htmlspecialchars($a['link'], ENT_QUOTES, 'UTF-8');
              } else {
                $href = '#';
              }

              echo "
              <li>
                <article>
                  <a href='{$href}'>
                    <img src='{$img_src}' alt='Foto de {$titulo}' width='120' height='80'>
                    <h3>{$titulo}</h3>
                    <p>{$ciudad}, {$pais}<br><strong>{$precio_display}</strong></p>
                  </a>
                </article>
              </li>";
            }
          echo "</ul>";
      } else {
          echo "<p>No has visitado ningún anuncio todavía.</p>";
      }
  } else {
      echo "<p>No has visitado ningún anuncio todavía.</p>";
  }
  ?>
</section>
