<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: registro.php");
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once 'includes/conexion.php';

$errors = [];
$old = [];

// Recoger datos
$campos = ['usuario','contrasena','repetir','email','sexo','nacimiento','ciudad','pais'];
foreach ($campos as $c) $old[$c] = trim($_POST[$c] ?? "");

// Validaciones
if ($old["usuario"] === "") $errors[] = "usuario";
if ($old["contrasena"] === "") $errors[] = "contrasena";
else {
    $pw = $old["contrasena"];
    // Reglas: 6-15 caracteres, al menos una letra, al menos un número,
    // no empezar por número, no contener espacios
    $pw_len = mb_strlen($pw);
    $has_letter = preg_match('/[A-Za-z]/', $pw);
    $has_digit = preg_match('/\d/', $pw);
    $starts_digit = preg_match('/^[0-9]/', $pw);
    $has_space = preg_match('/\s/', $pw);
    if ($pw_len < 6 || $pw_len > 15 || !$has_letter || !$has_digit || $starts_digit || $has_space) {
        $errors[] = 'contrasena_rules';
    }
}
if ($old["repetir"] === "") $errors[] = "repetir";
if ($old["contrasena"] !== $old["repetir"]) $errors[] = "coinciden";
if (!filter_var($old["email"], FILTER_VALIDATE_EMAIL)) $errors[] = "email";
if ($old["pais"] === "") $errors[] = "pais";

// Si hay errores → volver al formulario
if (!empty($errors)) {
    unset($old["contrasena"], $old["repetir"]);
    $_SESSION['flash']['registro_errors'] = $errors;
    $_SESSION['flash']['registro_old'] = $old;
    header("Location: registro.php");
    exit;
}

// Verificar usuario único
$stmt = $conexion->prepare("SELECT IdUsuario FROM Usuarios WHERE NomUsuario = ?");
$stmt->execute([$old["usuario"]]);
if ($stmt->fetch()) {
    $_SESSION['flash']['registro_errors'] = ['usuario_duplicado'];
    $_SESSION['flash']['registro_old'] = $old;
    header("Location: registro.php");
    exit;
}

// Hash contraseña
$passHash = password_hash($old["contrasena"], PASSWORD_DEFAULT);

// Subir foto si existe: validar tipo y tamaño, guardar en DAW/imagenes/
$nombreFoto = null;
if (!empty($_FILES['foto']['name'])) {
    $file = $_FILES['foto'];
    // comprobar errores de upload
    if ($file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'foto';
        unset($old["contrasena"], $old["repetir"]);
        $_SESSION['flash']['registro_errors'] = $errors;
        $_SESSION['flash']['registro_old'] = $old;
        header("Location: registro.php");
        exit;
    }

    if ($file['error'] === UPLOAD_ERR_OK) {
        // límites y tipos permitidos
        $maxSize = 2 * 1024 * 1024; // 2 MB
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        if ($file['size'] > $maxSize) {
            $errors[] = 'foto_size';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            $errors[] = 'foto_type';
        }

        if (!empty($errors)) {
            unset($old["contrasena"], $old["repetir"]);
            $_SESSION['flash']['registro_errors'] = $errors;
            $_SESSION['flash']['registro_old'] = $old;
            header("Location: registro.php");
            exit;
        }

        // preparar nombre seguro y carpeta destino
        $ext = $allowed[$mime];
        try {
            $random = bin2hex(random_bytes(6));
        } catch (Exception $e) {
            $random = uniqid();
        }
        $nombreFoto = time() . "_" . $random . "." . $ext;

        // Guardar las fotos de usuario en la carpeta de práctica tal y como se solicita
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'DAW' . DIRECTORY_SEPARATOR . 'practica' . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        move_uploaded_file($file['tmp_name'], $dir . $nombreFoto);
    }
}

// Insertar usuario
$stmt = $conexion->prepare("\n    INSERT INTO Usuarios\n    (NomUsuario, Clave, Email, Sexo, FNacimiento, Ciudad, Pais, Foto, Estilo)\n    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)\n");

// Mapear sexo a código numérico: H=>1, M=>2, otros=>0
$sexo_input = strtoupper(trim($old['sexo'] ?? ''));
if ($sexo_input === 'H' || $sexo_input === '1') {
    $sexo_db = 1;
} elseif ($sexo_input === 'M' || $sexo_input === '2') {
    $sexo_db = 2;
} else {
    $sexo_db = 0;
}

$stmt->execute([
    $old["usuario"],
    $passHash,
    $old["email"],
    $sexo_db,
    $old["nacimiento"],
    $old["ciudad"],
    $old["pais"],
    $nombreFoto
]);

// Iniciar sesión automáticamente tras registro y guardar foto en sesión (no crear cookies)
$_SESSION['usuario'] = $old['usuario'];
$lastId = $conexion->lastInsertId();
if ($lastId) {
    $_SESSION['id'] = (int)$lastId;
}
if (!empty($nombreFoto)) {
    $pathFoto = 'DAW/practica/imagenes/' . $nombreFoto;
    $_SESSION['foto'] = $pathFoto;
} else {
    $_SESSION['foto'] = 'DAW/practica/imagenes/default-avatar-profile-icon-vector-260nw-1909596082.webp';
}

$title = 'Registrado';
$cssPagina = 'registrado.css';
require_once('cabecera.inc');
require_once('inicio.inc');
?>

<main>
<section>
<h2>Registro completado</h2>

<p><strong>Usuario:</strong> <?= htmlspecialchars($old["usuario"]) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($old["email"]) ?></p>
<?php
// Mostrar etiqueta legible del sexo
if (isset($sexo_db)) {
    $sexo_label = ($sexo_db === 1) ? 'Hombre' : (($sexo_db === 2) ? 'Mujer' : 'Otro');
} else {
    $sexo_label = htmlspecialchars($old["sexo"] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<p><strong>Sexo:</strong> <?= $sexo_label ?></p>
<p><strong>Ciudad:</strong> <?= htmlspecialchars($old["ciudad"]) ?></p>

<p><a href="index.php"><strong>INICIAR SESIÓN</strong></a></p>
</section>
</main>

<?php require_once('pie.inc'); ?>