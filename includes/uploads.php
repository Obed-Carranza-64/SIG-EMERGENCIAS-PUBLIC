<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Procesa y guarda las imágenes adjuntas de un reporte en la carpeta UPLOAD_DIR.
 *
 * @param int   $reporte_id  Id del reporte recién creado.
 * @param array $files       Estructura $_FILES['imagenes'] (o null).
 * @return array ['guardadas' => string[], 'errores' => string[]]
 */
function subir_imagenes($reporte_id, $files) {
    $out = ['guardadas' => [], 'errores' => []];

    if (!$files || !isset($files['name'])) {
        return $out;
    }

    $dir = upload_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        $out['errores'][] = 'La carpeta de imágenes no está disponible o no tiene permisos de escritura.';
        return $out;
    }

    $maxMb = max(1, (int) env('UPLOAD_MAX_MB', 6));
    $maxFiles = max(1, (int) env('UPLOAD_MAX_FILES', 5));
    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $nombres = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmp = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errs = is_array($files['error']) ? $files['error'] : [$files['error']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

    $guardadas = 0;
    foreach ($nombres as $i => $nombre) {
        if ($guardadas >= $maxFiles) {
            $out['errores'][] = 'Máximo ' . $maxFiles . ' imágenes por reporte.';
            break;
        }

        $e = $errs[$i] ?? UPLOAD_ERR_NO_FILE;
        if ($e === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($e !== UPLOAD_ERR_OK) {
            $out['errores'][] = 'No se pudo subir «' . htmlspecialchars(basename($nombre)) . '».';
            continue;
        }
        if (($sizes[$i] ?? 0) > $maxMb * 1024 * 1024) {
            $out['errores'][] = '«' . htmlspecialchars(basename($nombre)) . '» supera ' . $maxMb . ' MB.';
            continue;
        }

        $tmpName = $tmp[$i] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            continue;
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = finfo_file($fi, $tmpName);
                finfo_close($fi);
            }
        }
        if (!$mime && function_exists('mime_content_type')) {
            $mime = mime_content_type($tmpName);
        }
        if (!$mime || !isset($permitidos[$mime])) {
            $out['errores'][] = 'Formato no permitido: «' . htmlspecialchars(basename($nombre)) . '». Usa JPG, PNG, WEBP o GIF.';
            continue;
        }

        $archivo = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $permitidos[$mime];
        if (!move_uploaded_file($tmpName, $dir . '/' . $archivo)) {
            $out['errores'][] = 'No se pudo guardar «' . htmlspecialchars(basename($nombre)) . '».';
            continue;
        }

        guardar_imagen_reporte($reporte_id, $archivo);
        $out['guardadas'][] = $archivo;
        $guardadas++;
    }

    return $out;
}
