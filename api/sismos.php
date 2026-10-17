<?php
require_once __DIR__ . '/../includes/sismos.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    init_db();

    $force = !isset($_GET['force']) || $_GET['force'] !== '0';
    $res = sincronizar_sismos($force, 8);

    $rows = geo_sismos();
    $actualizado = $res['actualizado'] ?? null;
    $ts = $actualizado ? strtotime($actualizado) : false;

    echo json_encode([
        'ok' => $res['ok'],
        'saltado' => $res['saltado'],
        'nuevos' => $res['insertados'],
        'actualizados' => $res['actualizados'],
        'total' => count($rows),
        'error' => $res['error'],
        'actualizado' => $ts ? date('d/m/Y H:i', $ts) : null,
        'sismos' => $rows,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno', 'sismos' => []], JSON_UNESCAPED_UNICODE);
}
