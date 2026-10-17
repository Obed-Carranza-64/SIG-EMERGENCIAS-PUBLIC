<?php
require_once __DIR__ . '/db.php';

function haversine_km($lat1, $lon1, $lat2, $lon2) {
    $r = 6371.0;
    $dlat = deg2rad($lat2 - $lat1);
    $dlon = deg2rad($lon2 - $lon1);
    $a = sin($dlat / 2) * sin($dlat / 2)
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
        * sin($dlon / 2) * sin($dlon / 2);
    return 2 * $r * asin(min(1, sqrt($a)));
}

function mesh_rango() {
    return max(5.0, (float) env('MESH_RANGE_KM', 60));
}

function mesh_grafo() {
    $rango = mesh_rango();
    $nodos = mesh_nodos();
    $byId = [];
    foreach ($nodos as $n) {
        $byId[(int) $n['id']] = $n;
    }
    $enlaces = [];
    $ids = array_keys($byId);
    $m = count($ids);
    for ($i = 0; $i < $m; $i++) {
        for ($j = $i + 1; $j < $m; $j++) {
            $a = $byId[$ids[$i]];
            $b = $byId[$ids[$j]];
            if ($a['estado'] !== 'activo' || $b['estado'] !== 'activo') {
                continue;
            }
            $km = haversine_km((float) $a['lat'], (float) $a['lon'], (float) $b['lat'], (float) $b['lon']);
            if ($km <= $rango) {
                $q = $km < $rango * 0.4 ? 'fuerte' : ($km < $rango * 0.7 ? 'media' : 'débil');
                $enlaces[] = [(int) $a['id'], (int) $b['id'], round($km, 1), $q];
            }
        }
    }
    return ['nodos' => $nodos, 'enlaces' => $enlaces, 'rango' => $rango];
}

function mesh_ruta($origen, $destino) {
    $g = mesh_grafo();
    $byId = [];
    foreach ($g['nodos'] as $n) {
        $byId[(int) $n['id']] = $n;
    }
    $origen = (int) $origen;
    $destino = (int) $destino;
    if (!isset($byId[$origen]) || !isset($byId[$destino])) {
        return null;
    }
    if ($byId[$origen]['estado'] !== 'activo' || $byId[$destino]['estado'] !== 'activo') {
        return null;
    }
    if ($origen === $destino) {
        return ['path' => [$origen], 'km' => 0.0, 'hops' => 0];
    }
    $adj = [];
    foreach ($g['enlaces'] as $e) {
        $adj[$e[0]][] = [$e[1], $e[2]];
        $adj[$e[1]][] = [$e[0], $e[2]];
    }
    $dist = [$origen => 0.0];
    $prev = [];
    $vis = [];
    while (true) {
        $u = null;
        $best = INF;
        foreach ($dist as $id => $d) {
            if (!isset($vis[$id]) && $d < $best) {
                $best = $d;
                $u = $id;
            }
        }
        if ($u === null || $u === $destino) {
            break;
        }
        $vis[$u] = true;
        foreach ($adj[$u] ?? [] as $par) {
            [$v, $w] = $par;
            $nd = $dist[$u] + $w;
            if (!isset($dist[$v]) || $nd < $dist[$v]) {
                $dist[$v] = $nd;
                $prev[$v] = $u;
            }
        }
    }
    if (!isset($dist[$destino])) {
        return null;
    }
    $path = [$destino];
    while (end($path) !== $origen) {
        $path[] = $prev[end($path)];
    }
    $path = array_reverse($path);
    return ['path' => $path, 'km' => round($dist[$destino], 1), 'hops' => count($path) - 1];
}

function mesh_stats() {
    $g = mesh_grafo();
    $activos = 0;
    foreach ($g['nodos'] as $n) {
        if ($n['estado'] === 'activo') {
            $activos++;
        }
    }
    $conectados = [];
    foreach ($g['enlaces'] as $e) {
        $conectados[$e[0]] = true;
        $conectados[$e[1]] = true;
    }
    $aislados = 0;
    foreach ($g['nodos'] as $n) {
        if ($n['estado'] === 'activo' && !isset($conectados[(int) $n['id']])) {
            $aislados++;
        }
    }
    return [
        'total' => count($g['nodos']),
        'activos' => $activos,
        'enlaces' => count($g['enlaces']),
        'aislados' => $aislados,
        'rango' => $g['rango'],
    ];
}
