<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mesh.php';

header('Content-Type: application/json; charset=utf-8');

try {
    init_db();
    $origen = (int) ($_GET['origen'] ?? 0);
    $destino = isset($_GET['destino']) ? (int) $_GET['destino'] : (int) env('MESH_NODO_CENTRAL', 1);

    $r = mesh_ruta($origen, $destino);
    if (!$r) {
        echo json_encode(['ok' => false, 'error' => 'Sin ruta: nodos inactivos, inexistentes o fuera de alcance']);
        exit;
    }
    $nombres = [];
    foreach (mesh_nodos() as $n) {
        $nombres[(int) $n['id']] = $n['nombre'];
    }
    $nom = [];
    foreach ($r['path'] as $id) {
        $nom[] = $nombres[$id] ?? ('Nodo ' . $id);
    }
    echo json_encode([
        'ok' => true,
        'path' => $r['path'],
        'nombres' => $nom,
        'hops' => $r['hops'],
        'km' => $r['km'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno']);
}
