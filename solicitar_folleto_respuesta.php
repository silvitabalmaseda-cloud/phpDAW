<?php
$title = "PI - PI Pisos & Inmuebles";
$cssPagina = "solicitar_folleto.css";
require_once("cabecera.inc");
require_once(__DIR__ . '/privado.inc');
require_once("inicioLog.inc");
require_once(__DIR__ . '/includes/precio.php');

// Insertar solicitud en la base de datos
if (file_exists(__DIR__ . '/includes/conexion.php')) {
    require_once __DIR__ . '/includes/conexion.php';
    try {
        $direccion = "$calle, $numero, $piso, $codigo_postal, $localidad, $provincia, $pais";
        $stmt = $conexion->prepare("INSERT INTO Solicitudes (Anuncio, Texto, Nombre, Email, Direccion, Telefono, Color, Copias, Resolucion, Fecha, IColor, IPrecio, Coste) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $anuncio !== '' ? $anuncio : null,
            $texto,
            $nombre,
            $correo,
            $direccion,
            $telefono,
            $color_portada,
            $copias,
            $resolucion,
            $fecha !== '' ? $fecha : null,
            $isColor ? 1 : 0,
            ($mostrar_precio === 'si') ? 1 : 0,
            $precioTotal
        ]);
    } catch (Exception $e) {
        // Silencioso: mostrar la respuesta pero no detener si la BD falla
    }
}

// ===== RECOGER DATOS DEL FORMULARIO =====
$nombre = $_POST["nombre"] ?? "";
$correo = $_POST["correo"] ?? "";
$telefono = $_POST["telefono"] ?? "";
$calle = $_POST["calle"] ?? "";
$numero = $_POST["numero"] ?? "";
$piso = $_POST["piso"] ?? "";
$codigo_postal = $_POST["codigo_postal"] ?? "";
$localidad = $_POST["localidad"] ?? "";
$provincia = $_POST["provincia"] ?? "";
$pais = $_POST["pais"] ?? "";
$texto = $_POST["texto"] ?? "";
$color_portada = $_POST["color"] ?? "#000000";
$paginas = intval($_POST["paginas"] ?? 8);
$copias = intval($_POST["copias"] ?? 1);
$resolucion = intval($_POST["resolucion"] ?? 150);
$anuncio = $_POST["anuncio"] ?? "";
$fecha = $_POST["fecha"] ?? "";
$impresion_color = $_POST["impresion_color"] ?? "";
$mostrar_precio = $_POST["mostrar_precio"] ?? "";

// ===== CÁLCULO DEL COSTE =====
$isColor = ($impresion_color === "color");
$precioTotal = calcularPrecio($paginas, $isColor, $resolucion, $copias); 
$precioUnitario = calcularPrecio($paginas, $isColor, $resolucion, 1); 
$total_formateado = formatearPrecio($precioTotal);
?>

<main>
    <p class="mensaje-confirmacion">Tu solicitud ha sido registrada correctamente.</p>

    <p class="coste-total">
        <strong>Coste total del folleto:</strong>
        <?php echo $total_formateado; ?>
    </p>

    <section>
        <h2>Respuestas del formulario:</h2>

        <fieldset>
            <legend><strong>RESPUESTA</strong></legend>

            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($nombre); ?></p>
            <p><strong>Correo:</strong> <?php echo htmlspecialchars($correo); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($telefono); ?></p>
            <p><strong>Dirección:</strong>
                <?php
                    echo htmlspecialchars("$calle, $numero, $piso, $codigo_postal, $localidad, $provincia, $pais");
                ?>
            </p>
            <p><strong>Texto adicional:</strong> <?php echo htmlspecialchars($texto ?: "—"); ?></p>
            <p><strong>Color de portada:</strong>
                <input type="color" value="<?php echo htmlspecialchars($color_portada); ?>" disabled>
            </p>
            <p><strong>Número de copias:</strong> <?php echo htmlspecialchars($copias); ?></p>
            <p><strong>Resolución:</strong> <?php echo htmlspecialchars($resolucion); ?> dpi</p>
            <p><strong>Anuncio seleccionado:</strong> <?php echo htmlspecialchars($anuncio ?: "—"); ?></p>
            <p><strong>Fecha de recepción:</strong>
                <?php echo $fecha ? date("d/m/Y", strtotime($fecha)) : "—"; ?>
            </p>
            <p><strong>Impresión a color:</strong>
                <?php echo $isColor ? "A color" : "Blanco y negro"; ?>
            </p>
            <p><strong>Mostrar precio:</strong>
                <?php echo ($mostrar_precio === "si") ? "Sí" : "No"; ?>
            </p>
        </fieldset>
    </section>

    <?php require_once("salto.inc"); ?>
</main>

<?php
require_once("pie.inc");
?>
