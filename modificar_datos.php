<?php
<?php
$title = "PI - Modificar datos";
$cssPagina = "registro.css";
require_once("cabecera.inc");
require_once(__DIR__ . '/privado.inc');
require_once("inicioLog.inc");
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/precio.php';

$user = null;
if (isset($_SESSION['id']) && isset($conexion)) {
	$stmt = $conexion->prepare('SELECT IdUsuario, NomUsuario, Email, Sexo, FNacimiento, Ciudad, Pais, Foto FROM Usuarios WHERE IdUsuario = ?');
	$stmt->execute([$_SESSION['id']]);
	$user = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($user) {
		// Aceptar que Sexo en BD sea numérico (1=Hombre,2=Mujer) o la letra H/M/O
		$sexoStored = $user['Sexo'];
		if (is_numeric($sexoStored)) {
			$user['SexoChar'] = ($sexoStored == 1) ? 'H' : (($sexoStored == 2) ? 'M' : 'O');
		} else {
			$user['SexoChar'] = $sexoStored;
		}
	}
}
?>

<main>
	<section>
		<h2>Modificar mis datos</h2>
		<?php if (!$user): ?>
			<p>No se han podido cargar tus datos. Asegúrate de estar identificado.</p>
		<?php else: ?>
			<form action="actualizar_datos.php" method="post" enctype="multipart/form-data">
				<p>
					<label>Nombre de usuario: </label>
					<input type="text" name="usuario" value="<?= htmlspecialchars($user['NomUsuario']) ?>" readonly>
				</p>
				<p>
					<label>Email:</label>
					<input type="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>">
				</p>
				<p>
					<label>Sexo:</label>
					<select name="sexo">
						<option value="">--</option>
							<option value="H" <?= (isset($user['SexoChar']) && $user['SexoChar'] === 'H') ? 'selected' : '' ?>>Hombre</option>
							<option value="M" <?= (isset($user['SexoChar']) && $user['SexoChar'] === 'M') ? 'selected' : '' ?>>Mujer</option>
							<option value="O" <?= (isset($user['SexoChar']) && $user['SexoChar'] === 'O') ? 'selected' : '' ?>>Otro</option>
					</select>
				</p>
				<p>
					<label>Fecha nacimiento:</label>
					<input type="date" name="nacimiento" value="<?= htmlspecialchars($user['FNacimiento']) ?>">
				</p>
				<p>
					<label>Ciudad:</label>
					<input type="text" name="ciudad" value="<?= htmlspecialchars($user['Ciudad']) ?>">
				</p>
				<p>
					<label>País:</label>
					<select name="pais">
						<option value="">--</option>
						<?php
						try {
							$rs = $conexion->query('SELECT IdPais, NomPais FROM Paises ORDER BY NomPais');
							$ps = $rs->fetchAll(PDO::FETCH_ASSOC);
						} catch (Exception $e) {
							$ps = [];
						}
						foreach ($ps as $p) {
							$sel = ($p['IdPais'] == $user['Pais']) ? 'selected' : '';
							echo "<option value='{$p['IdPais']}' $sel>" . htmlspecialchars($p['NomPais']) . "</option>";
						}
						?>
					</select>
				</p>
				<p>
					<label>Foto actual:</label>
					<?php if ($user['Foto']): ?>
						<?php $avatar = resolve_image_url($user['Foto']); ?>
						<br><img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="Foto" width="120">
					<?php else: ?>No hay foto<?php endif; ?>
				</p>
				<p>
					<label>Subir nueva foto:</label>
					<input type="file" name="foto" accept="image/*">
				</p>
				<p>
					<button type="submit">Guardar cambios</button>
				</p>
			</form>
		<?php endif; ?>
	</section>
</main>

<?php
require_once("pie.inc");    
?>