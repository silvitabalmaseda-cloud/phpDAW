<?php
    $title = "PI - PI Pisos & Inmuebles";
    $cssPagina = "registro.css";
    require_once("cabecera.inc");
    require_once(__DIR__ . '/privado.inc');
    require_once("inicioLog.inc");

    // Cargar anuncios del usuario logueado desde la BD
    $anunciosUsuario = [];
    $selectedId = isset($_GET['id']) && ctype_digit($_GET['id']) ? intval($_GET['id']) : null;
    $errorMensaje = '';
    try {
        require_once __DIR__ . '/includes/conexion.php';
        if (isset($_SESSION['usuario']) && isset($conexion)) {
            $stmtU = $conexion->prepare('SELECT IdUsuario FROM Usuarios WHERE NomUsuario = ? LIMIT 1');
            $stmtU->execute([$_SESSION['usuario']]);
            $usuarioRow = $stmtU->fetch(PDO::FETCH_ASSOC);
            if ($usuarioRow) {
                $idUsuario = (int)$usuarioRow['IdUsuario'];
                $stmt = $conexion->prepare('SELECT IdAnuncio, Titulo FROM Anuncios WHERE Usuario = ? ORDER BY FRegistro DESC');
                $stmt->execute([$idUsuario]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    $anunciosUsuario[(int)$r['IdAnuncio']] = ['titulo' => $r['Titulo']];
                }

                // Si se pasó un id en GET comprobar que pertenece al usuario
                if ($selectedId !== null && !array_key_exists($selectedId, $anunciosUsuario)) {
                    $errorMensaje = 'No tienes permiso para añadir fotos a ese anuncio.';
                    $selectedId = null;
                }
            } else {
                $errorMensaje = 'Usuario no encontrado en la base de datos.';
                $selectedId = null;
            }
        } else {
            $errorMensaje = 'No estás identificado.';
            $selectedId = null;
        }
    } catch (Exception $e) {
        $errorMensaje = 'Error al cargar anuncios: ' . $e->getMessage();
        $selectedId = null;
    }

?>

<main>
    <section class="formulario-anadir-foto">
        <h2>Añadir foto a un anuncio</h2>

        <?php if ($errorMensaje !== ''): ?>
            <p class="error"><?= htmlspecialchars($errorMensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="procesar_anadir_foto.php" method="post" enctype="multipart/form-data">

            <p>
                <label for="id_anuncio">Anuncio:</label>
                <select name="id_anuncio" id="id_anuncio" <?php if ($selectedId !== null) echo 'disabled'; ?> >
                    <?php if ($selectedId === null): ?>
                        <option value="">-- Selecciona un anuncio --</option>
                    <?php endif; ?>
                    <?php foreach ($anunciosUsuario as $id => $a): ?>
                        <option value="<?= (int)$id ?>" <?php if ($selectedId !== null && (int)$selectedId === (int)$id) echo 'selected'; ?> >
                            <?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selectedId !== null): ?>
                    <input type="hidden" name="id_anuncio" value="<?= (int)$selectedId ?>">
                <?php endif; ?>
            </p>

            <p>
                <label for="alt">Texto alternativo (alt):</label>
                <input type="text" name="alt" id="alt" maxlength="150" placeholder="Texto alternativo para la imagen">
            </p>

            <p>
                <label for="titulo_foto">Título de la foto:</label>
                <input type="text" name="titulo_foto" id="titulo_foto" maxlength="150" placeholder="Título de la foto">
            </p>

            <p>
                <label for="foto">Foto (archivo):</label>
                <input type="file" name="foto" id="foto" accept="image/*">
            </p>

            <p>
                <button type="submit" class="btn">Enviar</button>
            </p>
        </form>
    </section>

    <?php require_once("salto.inc"); ?>
</main>

<?php require_once("pie.inc"); ?>
