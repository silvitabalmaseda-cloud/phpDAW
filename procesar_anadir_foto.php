<?php
session_start();

$title = "PI - Procesar Añadir Foto";

// Helper de logging para depuración de subidas
if (!function_exists('log_addfoto')) {
    function log_addfoto($msg) {
        $dir = __DIR__ . '/logs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/addfoto.log';
        $line = date('[Y-m-d H:i:s] ') . $msg . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}

// Requerir conexión
if (!file_exists(__DIR__ . '/includes/conexion.php')) {
    $_SESSION['flash']['error'] = 'Error interno: falta la conexión a la base de datos.';
    header('Location: anyadir_foto.php');
    exit();
}
require_once __DIR__ . '/includes/conexion.php';

// Comprobar usuario identificado
if (empty($_SESSION['usuario'])) {
    $_SESSION['flash']['error'] = 'Debes iniciar sesión para añadir fotos.';
    header('Location: index.php');
    exit();
}

$usuario = $_SESSION['usuario'];

// Validar id del anuncio
if (!isset($_POST['id_anuncio']) || !ctype_digit((string)$_POST['id_anuncio'])) {
    $msg = 'Anuncio inválido. POST keys: ' . implode(',', array_keys($_POST));
    log_addfoto($msg);
    $_SESSION['flash']['error'] = 'Anuncio inválido.';
    header('Location: anyadir_foto.php');
    exit();
}
$idAnuncio = (int)$_POST['id_anuncio'];

try {
    // Obtener IdUsuario del usuario actual
    $s = $conexion->prepare('SELECT IdUsuario FROM Usuarios WHERE NomUsuario = ? LIMIT 1');
    $s->execute([$usuario]);
    $uRow = $s->fetch(PDO::FETCH_ASSOC);
    if (!$uRow) throw new Exception('Usuario no encontrado.');
    $idUsuario = (int)$uRow['IdUsuario'];

    // Comprobar que el anuncio pertenece al usuario
    $q = $conexion->prepare('SELECT Usuario FROM Anuncios WHERE IdAnuncio = ? LIMIT 1');
    $q->execute([$idAnuncio]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    if (!$r) throw new Exception('Anuncio no encontrado.');
    $propietarioId = (int)$r['Usuario'];
    if ($propietarioId !== $idUsuario) {
        $_SESSION['flash']['error'] = 'No tienes permiso para añadir fotos a ese anuncio.';
        header('Location: anuncio.php?id=' . $idAnuncio);
        exit();
    }

    // Validar archivo
    if (empty($_FILES['foto'])) {
        log_addfoto("No se recibió el campo 'foto' en \\$_FILES. Keys: " . implode(',', array_keys($_FILES)));
        $_SESSION['flash']['error'] = 'Falta el archivo o hubo un error en la subida.';
        header('Location: anyadir_foto.php?id=' . $idAnuncio);
        exit();
    }
    $file = $_FILES['foto'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        log_addfoto("Upload error code: " . (int)$file['error'] . " name=" . ($file['name'] ?? '') );
        $_SESSION['flash']['error'] = 'Falta el archivo o hubo un error en la subida (código ' . (int)$file['error'] . ').';
        header('Location: anyadir_foto.php?id=' . $idAnuncio);
        exit();
    }
    // Limitar tamaño a 2MB
    if ($file['size'] > 2 * 1024 * 1024) {
        $_SESSION['flash']['error'] = 'El archivo excede el tamaño máximo (2MB).';
        header('Location: anyadir_foto.php?id=' . $idAnuncio);
        exit();
    }
    // Tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (strpos($mime, 'image/') !== 0) {
        log_addfoto("Mime inválido: $mime para archivo " . ($file['name'] ?? '')); 
        $_SESSION['flash']['error'] = 'El archivo subido no es una imagen válida.';
        header('Location: anyadir_foto.php?id=' . $idAnuncio);
        exit();
    }

    // Preparar nombre y mover
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $random = bin2hex(random_bytes(6));
    $nombreFoto = time() . '_' . $random . '.' . ($ext ?: 'jpg');
    $dir = __DIR__ . '/DAW/practica/imagenes/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dest = $dir . $nombreFoto;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        log_addfoto('move_uploaded_file falló. tmp=' . ($file['tmp_name'] ?? '') . ' dest=' . $dest);
        $_SESSION['flash']['error'] = 'No se pudo guardar la imagen en el servidor.';
        header('Location: anyadir_foto.php?id=' . $idAnuncio);
        exit();
    }

    // Insertar en la tabla Fotos
    $titulo = isset($_POST['titulo_foto']) ? trim($_POST['titulo_foto']) : null;
    $alt = isset($_POST['alt']) ? trim($_POST['alt']) : null;
    $fotoDB = $nombreFoto; // guardamos basename para compatibilidad

    $i = $conexion->prepare('INSERT INTO Fotos (Titulo, Foto, Alternativo, Anuncio) VALUES (?, ?, ?, ?)');
    $i->execute([$titulo, $fotoDB, $alt, $idAnuncio]);

    $_SESSION['flash']['ok'] = 'Foto añadida correctamente.';
    header('Location: anuncio.php?id=' . $idAnuncio);
    exit();

} catch (Exception $e) {
    $_SESSION['flash']['error'] = 'Error: ' . $e->getMessage();
    header('Location: anyadir_foto.php');
    exit();
}

?>
