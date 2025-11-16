<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nuevo_anuncio.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/includes/conexion.php';

// Recoger datos y saneado básico
$tipo = trim($_POST['tipo_anuncio'] ?? '');
$vivienda = trim($_POST['vivienda'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$pais = trim($_POST['pais'] ?? '');
$precio = isset($_POST['precio']) && $_POST['precio'] !== '' ? floatval($_POST['precio']) : null;
$fecha_publicacion = null; // no se toma desde el formulario; se usará NOW() en la inserción
$texto = trim($_POST['descripcion'] ?? '');
$superficie = isset($_POST['superficie']) && $_POST['superficie'] !== '' ? floatval($_POST['superficie']) : null;
$habitaciones = isset($_POST['habitaciones']) && $_POST['habitaciones'] !== '' ? intval($_POST['habitaciones']) : null;
$banos = isset($_POST['banos']) && $_POST['banos'] !== '' ? intval($_POST['banos']) : null;
$planta = isset($_POST['planta']) && $_POST['planta'] !== '' ? intval($_POST['planta']) : null;
$anio = isset($_POST['anio']) && $_POST['anio'] !== '' ? intval($_POST['anio']) : null;

// Usuario: preferir sesión
$usuarioId = $_SESSION['id'] ?? null;
if (!$usuarioId) {
    // intentar buscar por nombre si se proporcionó
    $usuarioNom = trim($_POST['usuario'] ?? '');
    if ($usuarioNom !== '') {
        $s = $conexion->prepare('SELECT IdUsuario FROM Usuarios WHERE NomUsuario = ? LIMIT 1');
        $s->execute([$usuarioNom]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        if ($r) $usuarioId = $r['IdUsuario'];
    }
}

$errors = [];
if ($titulo === '') $errors[] = 'titulo';
if (!$usuarioId) $errors[] = 'usuario';

if (!empty($errors)) {
    // flash y redirigir
    $_SESSION['flash']['nuevo_anuncio_errors'] = $errors;
    $_SESSION['flash']['nuevo_anuncio_old'] = $_POST;
    header('Location: nuevo_anuncio.php');
    exit;
}

// Mapear tipo anuncio a IdTAnuncio
$IdTAnuncio = null;
if (ctype_digit((string)$tipo)) {
    $IdTAnuncio = intval($tipo);
} else {
    $stmt = $conexion->prepare('SELECT IdTAnuncio FROM TiposAnuncios WHERE NomTAnuncio LIKE ? LIMIT 1');
    $stmt->execute(["%$tipo%"]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) $IdTAnuncio = $r['IdTAnuncio'];
}

// Mapear tipo vivienda
$IdTVivienda = null;
if (ctype_digit((string)$vivienda)) {
    $IdTVivienda = intval($vivienda);
} else {
    $stmt = $conexion->prepare('SELECT IdTVivienda FROM TiposViviendas WHERE NomTVivienda LIKE ? LIMIT 1');
    $stmt->execute(["%$vivienda%"]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) $IdTVivienda = $r['IdTVivienda'];
}

// Mapear pais
$IdPais = null;
if (ctype_digit((string)$pais)) {
    $IdPais = intval($pais);
} else if ($pais !== '') {
    $stmt = $conexion->prepare('SELECT IdPais FROM Paises WHERE NomPais LIKE ? LIMIT 1');
    $stmt->execute(["%$pais%"]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) $IdPais = $r['IdPais'];
}

$stmt = $conexion->prepare('INSERT INTO Anuncios (TAnuncio, TVivienda, FPrincipal, Alternativo, Titulo, Precio, Texto, Ciudad, Pais, Superficie, NHabitaciones, NBanyos, Planta, Anyo, FRegistro, Usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)');
$alternativo = 'Imagen principal';
$FPrincipal = null; // se actualizará tras subir fotos
$stmt->execute([
    $IdTAnuncio,
    $IdTVivienda,
    $FPrincipal,
    $alternativo,
    $titulo,
    $precio,
    $texto,
    $ciudad,
    $IdPais,
    $superficie,
    $habitaciones,
    $banos,
    $planta,
    $anio,
    $usuarioId
]);
$anuncioId = $conexion->lastInsertId();

// Procesar imágenes
$firstFoto = null;
if (!empty($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {
    $files = $_FILES['imagenes'];
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $tmp = $files['tmp_name'][$i];
        $size = $files['size'][$i];
        $maxSize = 2 * 1024 * 1024;
        if ($size > $maxSize) continue;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        if (!isset($allowed[$mime])) continue;
        $ext = $allowed[$mime];
        try { $rand = bin2hex(random_bytes(6)); } catch (Exception $e) { $rand = uniqid(); }
        $name = time() . '_' . $rand . '.' . $ext;
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'DAW' . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        move_uploaded_file($tmp, $dir . $name);
        // insertar en Fotos
        $s = $conexion->prepare('INSERT INTO Fotos (Titulo, Foto, Alternativo, Anuncio) VALUES (?, ?, ?, ?)');
        $s->execute([$titulo, $name, $alternativo, $anuncioId]);
        if (!$firstFoto) $firstFoto = $name;
    }
}

// Actualizar FPrincipal si hay foto
if ($firstFoto) {
    $u = $conexion->prepare('UPDATE Anuncios SET FPrincipal = ? WHERE IdAnuncio = ?');
    $u->execute([$firstFoto, $anuncioId]);
}

// Redirigir al anuncio creado
header('Location: anuncio.php?id=' . $anuncioId);
exit;
