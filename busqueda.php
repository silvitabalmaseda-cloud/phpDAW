<?php
$title = "PI - PI Pisos & Inmuebles";
$cssPagina = "busqueda.css";

// Inicializamos variables
$errores = [];
$valores = [
    "tipo_anuncio" => "",
    "vivienda" => "",
    "ciudad" => "",
    "pais" => "",
    "precio_min" => "",
    "precio_max" => "",
    "fecha_desde" => "",
    "fecha_hasta" => ""
];

// Si el formulario se ha enviado
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["enviado"])) {

    // Leer valores
    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim($_GET[$campo] ?? "");
    }

    // Validación tipo de anuncio
    if (empty($valores["tipo_anuncio"])) {
        $errores["tipo_anuncio"] = "Debes seleccionar un tipo de anuncio.";
    }

    // Validación precios
    if ($valores["precio_min"] !== "" && $valores["precio_min"] < 0) {
        $errores["precio_min"] = "El precio mínimo no puede ser negativo.";
    }
    if ($valores["precio_max"] !== "" && $valores["precio_max"] < 0) {
        $errores["precio_max"] = "El precio máximo no puede ser negativo.";
    }
    if ($valores["precio_max"] !== "" && $valores["precio_min"] === "") {
        $errores["precio_min"] = "Si indicas un precio máximo, debes indicar también el mínimo.";
    }
    if ($valores["precio_min"] !== "" && $valores["precio_max"] !== "" &&
        $valores["precio_min"] > $valores["precio_max"]) {
        $errores["precio_max"] = "El precio máximo no puede ser menor que el mínimo.";
    }

    // Validación fechas
    $fechaActual = date("Y-m-d");

    if ($valores["fecha_hasta"] !== "" && $valores["fecha_desde"] === "") {
        $errores["fecha_desde"] = "Si indicas una fecha final, debes indicar también la inicial.";
    }

    if ($valores["fecha_desde"] !== "" && $valores["fecha_hasta"] !== "") {
        if ($valores["fecha_desde"] > $valores["fecha_hasta"]) {
            $errores["fecha_hasta"] = "La fecha final no puede ser anterior a la inicial.";
        }
    }

    if ($valores["fecha_desde"] !== "" && $valores["fecha_desde"] > $fechaActual) {
        $errores["fecha_desde"] = "La fecha inicial no puede ser posterior a hoy.";
    }

    if ($valores["fecha_hasta"] !== "" && $valores["fecha_hasta"] > $fechaActual) {
        $errores["fecha_hasta"] = "La fecha final no puede ser posterior a hoy.";
    }

    // Ciudad y país
    if ($valores["ciudad"] !== "" && $valores["pais"] === "") {
        $errores["pais"] = "Si indicas una ciudad, debes indicar también un país.";
    }

    // Si no hay errores, mostramos los resultados simulando resultados.php
    if (empty($errores)) {
        // Redirigimos a resultados.php pasando los parámetros para que esa página cargue su propio CSS
        $query = [];
        foreach ($valores as $k => $v) {
            // incluir todos los parámetros, incluso vacíos si es necesario
            $query[$k] = $v;
        }
        header('Location: resultados.php?' . http_build_query($query));
        exit;
    }
}

// Include header and inicio only after processing/possible redirect to avoid 'headers already sent'
require_once("cabecera.inc");
require_once("inicioLog.inc");
?>

<main>
    <section>
        <h2>Formulario de búsqueda</h2>

        <form id="formBuscar" action="busqueda.php" method="get">
            <input type="hidden" name="enviado" value="1">

            <p class="anuncio <?php echo isset($errores['tipo_anuncio']) ? 'campo-error' : ''; ?>">
                <strong>Tipo de anuncio:</strong><br>
                <label><input type="radio" name="tipo_anuncio" value="venta"
                    <?php if ($valores["tipo_anuncio"] === "venta") echo "checked"; ?>> Venta</label>
                <label><input type="radio" name="tipo_anuncio" value="alquiler"
                    <?php if ($valores["tipo_anuncio"] === "alquiler") echo "checked"; ?>> Alquiler</label>
            </p>
            <?php if (isset($errores["tipo_anuncio"])): ?>
                <span class="error-campo"><?php echo $errores["tipo_anuncio"]; ?></span>
            <?php endif; ?>

            <p>
                <label for="vivienda">Tipo de vivienda:</label>
                <select id="vivienda" name="vivienda">
                    <option value="">Seleccione un tipo de vivienda</option>
                    <?php
                    // Cargar tipos de vivienda desde BD
                    $tiposV = [];
                    try {
                        if (!isset($conexion)) require_once __DIR__ . '/includes/conexion.php';
                        $rs2 = $conexion->query('SELECT IdTVivienda, NomTVivienda FROM TiposViviendas ORDER BY NomTVivienda');
                        $tiposV = $rs2->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $tiposV = [];
                    }
                    foreach ($tiposV as $t) {
                        $valor = $t['IdTVivienda'];
                        $texto = htmlspecialchars($t['NomTVivienda']);
                        $sel = ($valores["vivienda"] == $valor) ? "selected" : "";
                        echo "<option value='$valor' $sel>$texto</option>";
                    }
                    ?>
                </select>
            </p>

            <p>
                <label for="ciudad">Ciudad:</label>
                <input type="text" id="ciudad" name="ciudad"
                       value="<?php echo htmlspecialchars($valores["ciudad"]); ?>">
            </p>

            <p>
                <label for="pais">País:</label>
                <select id="pais" name="pais">
                    <option value="">Seleccione un país</option>
                    <?php
                                        // Cargar países desde la BD
                                        $paisesDb = [];
                                        try {
                                            require_once __DIR__ . '/includes/conexion.php';
                                            $rs = $conexion->query('SELECT IdPais, NomPais FROM Paises ORDER BY NomPais');
                                            $paisesDb = $rs->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (Exception $e) {
                                            $paisesDb = [];
                                        }
                                        foreach ($paisesDb as $p) {
                                            $valor = $p['IdPais'];
                                            $texto = htmlspecialchars($p['NomPais']);
                                            $sel = ($valores["pais"] == $valor) ? "selected" : "";
                                            echo "<option value='$valor' $sel>$texto</option>";
                                        }
                    ?>
                </select>
            </p>
            <?php if (isset($errores["pais"])): ?>
                <span class="error-campo"><?php echo $errores["pais"]; ?></span>
            <?php endif; ?>

            <p>
                <label for="precio-min">Precio entre:</label>
                <input id="precio-min" name="precio_min" type="number" min="0"
                       value="<?php echo htmlspecialchars($valores["precio_min"]); ?>"> y
                <input id="precio-max" name="precio_max" type="number" min="0"
                       value="<?php echo htmlspecialchars($valores["precio_max"]); ?>">
            </p>
            <?php if (isset($errores["precio_min"])): ?>
                <span class="error-campo"><?php echo $errores["precio_min"]; ?></span>
            <?php endif; ?>
            <?php if (isset($errores["precio_max"])): ?>
                <span class="error-campo"><?php echo $errores["precio_max"]; ?></span>
            <?php endif; ?>

            <p>
                <label for="fecha-desde">Fecha de publicación entre:</label>
                <input id="fecha-desde" name="fecha_desde" type="date"
                       value="<?php echo htmlspecialchars($valores["fecha_desde"]); ?>"> y
                <input id="fecha-hasta" name="fecha_hasta" type="date"
                       value="<?php echo htmlspecialchars($valores["fecha_hasta"]); ?>">
            </p>
            <?php if (isset($errores["fecha_desde"])): ?>
                <span class="error-campo"><?php echo $errores["fecha_desde"]; ?></span>
            <?php endif; ?>
            <?php if (isset($errores["fecha_hasta"])): ?>
                <span class="error-campo"><?php echo $errores["fecha_hasta"]; ?></span>
            <?php endif; ?>

            <p>
                <button type="submit">Buscar</button>
                <button type="reset">Borrar</button>
            </p>
        </form>
    </section>
</main>

<?php
require_once("pie.inc");
?>
