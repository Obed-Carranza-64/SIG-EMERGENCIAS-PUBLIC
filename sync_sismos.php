<?php
require_once __DIR__ . '/includes/sismos.php';

init_db();

/* Sincroniza los sismos del servicio externo con la base local.
   Uso web:   sync_sismos.php[?volver=reportes.php]
   Uso cron:  php sync_sismos.php  (imprime el resultado en consola) */

$force = true;
$res = sincronizar_sismos($force, 20);

if (PHP_SAPI === 'cli') {
    if ($res['ok']) {
        echo sprintf(
            "Sismos sincronizados: %d nuevos, %d actualizados (%d en total).\n",
            $res['insertados'], $res['actualizados'], $res['total']
        );
        exit(0);
    }
    echo 'Error al sincronizar sismos: ' . $res['error'] . PHP_EOL;
    exit(1);
}

$volver = isset($_GET['volver']) ? (string) $_GET['volver'] : 'reportes.php';
$volver = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $volver);
if ($volver === '' || substr($volver, -4) !== '.php' || strpos($volver, '..') !== false) {
    $volver = 'reportes.php';
}
if ($res['ok']) {
    $sismos = $res['insertados'] + $res['actualizados'];
} else {
    $sismos = -1;
}
$sep = strpos($volver, '?') === false ? '?' : '&';
header('Location: ' . $volver . $sep . 'sismos=' . $sismos);
exit;
