<?php
// includes/precio.php
// Funciones reutilizables para cálculo y formateo de precios de folletos.

if (!function_exists('calcularPrecio')) {
    function calcularPrecio($numPag, $color, $resolucion, $numCopias = 1){
        $numPag = (int)$numPag;
        $numCopias = max(1, (int)$numCopias);
        $resolucion = (int)$resolucion;
        $precioBase = 10.0;
        $precioPaginas = 0.0;
        $precioColor = 0.0;
        $precioResolucion = 0.0;
        $numFotos = $numPag * 3;

        // Cálculo del precio por páginas
        if ($numPag < 5) {
            $precioPaginas = $numPag * 2.0;
        } elseif ($numPag >= 5 && $numPag <= 10) {
            $precioPaginas = 4.0 * 2.0 + 1.8 * ($numPag - 4);
        } else {
            $precioPaginas = 4.0 * 2.0 + 6.0 * 1.8 + 1.6 * ($numPag - 10);
        }

        // Cálculo del precio por color
        if ($color) {
            $precioColor = $numFotos * 0.5;
        }

        // Cálculo del precio por resolución
        if ($resolucion > 300) {
            $precioResolucion = $numFotos * 0.2;
        }

        $precioUnitario = $precioBase + $precioPaginas + $precioColor + $precioResolucion;
        $precioTotal = $precioUnitario * $numCopias;
        return (float) $precioTotal;
    }
}

if (!function_exists('formatearPrecio')) {
    function formatearPrecio($valor){
        return number_format((float)$valor, 2, ',', '.') . ' €';
    }
}

if (!function_exists('resolve_image_url')) {
    /**
     * Resolve la URL de una imagen de anuncio prefiriendo DAW/practica/imagenes,
     * luego DAW/imagenes y finalmente un fallback.
     * Acepta tanto nombres de archivo como rutas; devuelve una ruta relativa usable en HTML.
     */
    function resolve_image_url($fotoCandidate) {
        if (empty($fotoCandidate)) return 'DAW/practica/imagenes/anuncio2.jpg';
        $basename = basename($fotoCandidate);
        // 1) carpeta ./imagenes (raíz del proyecto)
        $pathRootImg = __DIR__ . '/../imagenes/' . $basename;
        if (file_exists($pathRootImg)) return 'imagenes/' . $basename;
        // 2) carpeta de práctica preferente
        $pathPractica = __DIR__ . '/../DAW/practica/imagenes/' . $basename;
        if (file_exists($pathPractica)) return 'DAW/practica/imagenes/' . $basename;
        // 3) carpeta DAW/imagenes
        $pathImg = __DIR__ . '/../DAW/imagenes/' . $basename;
        if (file_exists($pathImg)) return 'DAW/imagenes/' . $basename;
        return 'DAW/practica/imagenes/anuncio2.jpg';
    }
}

?>