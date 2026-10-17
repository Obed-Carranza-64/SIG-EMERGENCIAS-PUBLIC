<?php

function risk_scale() {
    return [
        ['key' => 'incendio',      'valor' => 0.50, 'label' => 'Incendio forestal'],
        ['key' => 'deslizamiento', 'valor' => 0.50, 'label' => 'Deslizamiento de tierra'],
        ['key' => 'inundacion',    'valor' => 0.40, 'label' => 'Inundación'],
        ['key' => 'vivienda',      'valor' => 0.35, 'label' => 'Vivienda dañada'],
        ['key' => 'lluvia',        'valor' => 0.20, 'label' => 'Lluvias intensas'],
        ['key' => 'carretera',     'valor' => 0.10, 'label' => 'Carretera bloqueada / Ruta habilitada'],
        ['key' => 'arbol',         'valor' => 0.15, 'label' => 'Árbol caído'],
    ];
}

function risk_min() {
    return 0.10;
}

function risk_normalize($texto) {
    $t = mb_strtolower((string) $texto);
    return strtr($t, [
        'á' => 'a', 'à' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u',
        'ñ' => 'n',
    ]);
}

function risk_value($texto) {
    $t = risk_normalize($texto);
    $best = risk_min();
    foreach (risk_scale() as $r) {
        if (strpos($t, $r['key']) !== false && $r['valor'] > $best) {
            $best = $r['valor'];
        }
    }
    return $best;
}

function risk_color($valor) {
    if ($valor >= 0.5) {
        return '#ff2d3f';
    }
    if ($valor >= 0.4) {
        return '#ff7a00';
    }
    if ($valor >= 0.3) {
        return '#f59e0b';
    }
    if ($valor >= 0.2) {
        return '#3b82f6';
    }
    return '#2ecc71';
}

function risk_scale_map() {
    $out = [];
    foreach (risk_scale() as $r) {
        $out[$r['key']] = $r['valor'];
    }
    return $out;
}
