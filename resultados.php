<?php
$title = "PI - PI Pisos & Inmuebles";
$cssPagina = "resultados.css";

// ==============================
// VALIDACIÓN EN PHP
// ==============================

// Guardamos errores y valores previos
$errors = [];
$old = [];

// Función auxiliar
function getParam($name) {
    return isset($_GET[$name]) ? trim($_GET[$name]) : '';
}

// Recogemos los valores del formulario
$campos = ['tipo_anuncio', 'vivienda', 'ciudad', 'pais', 'precio_min', 'precio_max', 'fecha_desde', 'fecha_hasta'];
foreach ($campos as $campo) {
    $old[$campo] = getParam($campo);
}

// Aceptar también el parámetro rápido 'q'
if (empty($old['ciudad']) && isset($_GET['q']) && trim((string)$_GET['q']) !== '') {
    $old['ciudad'] = trim((string)$_GET['q']);
}

// ==============================
// VALIDACIONES
// ==============================

$advancedFields = ['tipo_anuncio','vivienda','precio_min','precio_max','fecha_desde','fecha_hasta','pais'];
$shouldValidate = false;
foreach ($advancedFields as $f) {
    if (isset($_GET[$f]) && trim((string)$_GET[$f]) !== '') { $shouldValidate = true; break; }
}

if ($shouldValidate) {
    // Tipo de anuncio
    if ($old['tipo_anuncio'] === '') {
        $errors[] = 'tipo_anuncio';
        $old['msg_tipo_anuncio'] = 'Debes seleccionar un tipo de anuncio.';
    }

    // Precios
    if ($old['precio_min'] !== '' && (!is_numeric($old['precio_min']) || $old['precio_min'] < 0)) {
        $errors[] = 'precio_min';
        $old['msg_precio_min'] = 'El precio mínimo no puede ser negativo.';
    }
    if ($old['precio_max'] !== '' && (!is_numeric($old['precio_max']) || $old['precio_max'] < 0)) {
        $errors[] = 'precio_max';
        $old['msg_precio_max'] = 'El precio máximo no puede ser negativo.';
    }
    if ($old['precio_max'] !== '' && $old['precio_min'] === '') {
        $errors[] = 'precio_min';
        $old['msg_precio_min'] = 'Si indicas un precio máximo, debes indicar también el mínimo.';
    }
    if ($old['precio_min'] !== '' && $old['precio_max'] !== '' && $old['precio_min'] > $old['precio_max']) {
        $errors[] = 'precio_max';
        $old['msg_precio_max'] = 'El precio máximo no puede ser menor que el mínimo.';
    }

    // Fechas
    $hoy = date('Y-m-d');
    if ($old['fecha_hasta'] !== '' && $old['fecha_desde'] === '') {
        $errors[] = 'fecha_desde';
        $old['msg_fecha_desde'] = 'Si indicas una fecha final, debes indicar también la inicial.';
    }
    if ($old['fecha_desde'] !== '' && $old['fecha_hasta'] !== '' &&
        strtotime($old['fecha_desde']) > strtotime($old['fecha_hasta'])) {
        $errors[] = 'fecha_hasta';
        $old['msg_fecha_hasta'] = 'La fecha final no puede ser anterior a la inicial.';
    }
    if ($old['fecha_desde'] !== '' && strtotime($old['fecha_desde']) > strtotime($hoy)) {
        $errors[] = 'fecha_desde';
        $old['msg_fecha_desde'] = 'La fecha inicial no puede ser posterior a hoy.';
    }
    if ($old['fecha_hasta'] !== '' && strtotime($old['fecha_hasta']) > strtotime($hoy)) {
        $errors[] = 'fecha_hasta';
        $old['msg_fecha_hasta'] = 'La fecha final no puede ser posterior a hoy.';
    }
}

// ==============================
// REDIRECCIÓN SI HAY ERRORES
// ==============================
if (!empty($errors)) {
    $query = ['errors' => implode(',', $errors)];
    foreach ($old as $k => $v) {
        if ($v !== '') $query["old_" . $k] = $v;
    }
    header("Location: busqueda.php?" . http_build_query($query));
    exit;
}

require_once("cabecera.inc");
require_once("inicioLog.inc");
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/precio.php';
?>

<main>
    <h2>Resultados de la búsqueda</h2>
    <section>
        <?php
        // Construir consulta dinámica
        $sql = "SELECT a.IdAnuncio, a.Titulo, a.FPrincipal, a.FRegistro, a.Ciudad, p.NomPais, a.Precio
                FROM Anuncios a
                LEFT JOIN Paises p ON a.Pais = p.IdPais
                WHERE 1";
        $params = [];

        if ($old['tipo_anuncio'] !== '') {
            $sql .= " AND a.TAnuncio = ?";
            $params[] = $old['tipo_anuncio'];
        }
        if ($old['vivienda'] !== '') {
            $sql .= " AND a.TVivienda = ?";
            $params[] = $old['vivienda'];
        }
        if ($old['ciudad'] !== '') {
            // Usar búsqueda insensible a mayúsculas
            $sql .= " AND LOWER(a.Ciudad) LIKE ?";
            $params[] = '%' . mb_strtolower($old['ciudad']) . '%';
        }
        if ($old['pais'] !== '') {
            $sql .= " AND a.Pais = ?";
            $params[] = $old['pais'];
        }
        if ($old['precio_min'] !== '') {
            $sql .= " AND a.Precio >= ?";
            $params[] = $old['precio_min'];
        }
        if ($old['precio_max'] !== '') {
            $sql .= " AND a.Precio <= ?";
            $params[] = $old['precio_max'];
        }
        if ($old['fecha_desde'] !== '') {
            $sql .= " AND a.FRegistro >= ?";
            $params[] = $old['fecha_desde'];
        }
        if ($old['fecha_hasta'] !== '') {
            $sql .= " AND a.FRegistro <= ?";
            $params[] = $old['fecha_hasta'] . ' 23:59:59';
        }

        $sql .= " ORDER BY a.FRegistro DESC";

        try {
            $stmt = $conexion->prepare($sql);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $resultados = [];
        }

        if (empty($resultados)) {
            echo '<p>No se han encontrado anuncios que cumplan los filtros.</p>';
        } else {
            foreach ($resultados as $r) {
                // resolver imagen principal prefiriendo carpeta practica
                $img = resolve_image_url($r['FPrincipal'] ?? '');
                $titulo = htmlspecialchars($r['Titulo'] ?: 'Sin título');
                $ciudad = htmlspecialchars($r['Ciudad'] ?: '—');
                $pais = htmlspecialchars($r['NomPais'] ?: '—');
                $precio = $r['Precio'] !== null ? number_format((float)$r['Precio'], 2, ',', '.') . ' €' : '—';
                echo "<article><h2><a href=\"anuncio.php?id={$r['IdAnuncio']}\">{$titulo}</a></h2><a href=\"anuncio.php?id={$r['IdAnuncio']}\"><img src=\"{$img}\" alt=\"{$titulo}\" width=\"200\"></a><p><strong>Ciudad:</strong> {$ciudad} | <strong>País:</strong> {$pais} | <strong>Precio:</strong> {$precio}</p></article>";
            }
        }
        ?>
    </section>

    <?php require_once("salto.inc"); ?>
</main>

<?php require_once("pie.inc"); ?>
