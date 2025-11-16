<?php
$title = "PI - PI Pisos & Inmuebles";
$cssPagina = "nuevo_anuncio.css";
require_once("cabecera.inc");
require_once(__DIR__ . '/privado.inc');
require_once("inicioLog.inc");
?>

<main>
    <section>
        <h2>CREAR NUEVO ANUNCIO</h2>
    </section>

    <section>
        <form id="formNuevoAnuncio" action="procesar_nuevo_anuncio.php" method="post" enctype="multipart/form-data">
            <fieldset>
                <legend>Tipo de anuncio</legend>
                <label><input type="radio" name="tipo_anuncio" value="venta" checked> Venta</label>
                <label><input type="radio" name="tipo_anuncio" value="alquiler"> Alquiler</label>
            </fieldset>

            <p>
                <label for="titulo">Título del anuncio:</label><br>
                <input type="text" id="titulo" name="titulo" required value="<?php echo (isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''); ?>">
            </p>

            <p>
                <label for="vivienda">Tipo de vivienda:</label><br>
                <select id="vivienda" name="vivienda">
                    <option value="">-- Seleccione --</option>
                    <option value="piso">Piso</option>
                    <option value="casa">Casa</option>
                    <option value="estudio">Estudio</option>
                    <option value="ático">Ático</option>
                </select>
            </p>

            <p>
                <label for="ciudad">Ciudad:</label><br>
                <input type="text" id="ciudad" name="ciudad">
            </p>

            <p>
                <label for="pais">País:</label><br>
                <select id="pais" name="pais">
                    <option value="">-- Seleccione --</option>
                    <option value="es">España</option>
                    <option value="pt">Portugal</option>
                    <option value="fr">Francia</option>
                    <option value="it">Italia</option>
                </select>
            </p>

            <p>
                <label for="precio">Precio (€):</label><br>
                <input type="number" id="precio" name="precio" step="0.01">
            </p>

            <!-- Fecha de publicación se asigna automáticamente -->

            <p>
                <label for="descripcion">Descripción:</label><br>
                <textarea id="descripcion" name="descripcion" rows="6"></textarea>
            </p>

            <fieldset>
                <legend>Características</legend>
                <p>
                    <label for="superficie">Superficie (m²):</label><br>
                    <input type="number" id="superficie" name="superficie">
                </p>
                <p>
                    <label for="habitaciones">Habitaciones:</label><br>
                    <input type="number" id="habitaciones" name="habitaciones">
                </p>
                <p>
                    <label for="banos">Baños:</label><br>
                    <input type="number" id="banos" name="banos">
                </p>
                <p>
                    <label for="planta">Planta:</label><br>
                    <input type="number" id="planta" name="planta">
                </p>
                <p>
                    <label for="anio">Año de construcción:</label><br>
                    <input type="number" id="anio" name="anio" min="1800" max="2100">
                </p>
            </fieldset>

            <p>
                <label for="imagenes">Imágenes (puede seleccionar varias):</label><br>
                <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple>
            </p>

            <!-- Usuario se toma de la sesión, no se solicita en el formulario -->

            <p>
                <button type="submit">PUBLICAR ANUNCIO</button>
            </p>
        </form>
    </section>

    <?php
    require_once("salto.inc");
    ?>

</main>
<?php
require_once("pie.inc");
?>