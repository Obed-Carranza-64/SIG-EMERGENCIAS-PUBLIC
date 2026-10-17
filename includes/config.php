<?php
require_once __DIR__ . '/risk.php';

function project_root() {
    return dirname(__DIR__);
}

function env_load() {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    $file = project_root() . '/.env';
    if (!is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            continue;
        }
        $len = strlen($val);
        if ($len >= 2 && $val[0] === '"' && $val[$len - 1] === '"') {
            $val = substr($val, 1, -1);
            $val = str_replace(['\\"', '\\\\'], ['"', '\\'], $val);
        } elseif ($len >= 2 && $val[0] === "'" && $val[$len - 1] === "'") {
            $val = substr($val, 1, -1);
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $val;
        }
    }
}

function env($key, $default = null) {
    env_load();
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }
    $v = getenv($key);
    return $v === false ? $default : $v;
}

function db_file() {
    $p = env('DB_PATH', 'database/sig_emergencia.sqlite');
    $p = str_replace('\\', '/', $p);
    if (preg_match('#^([a-zA-Z]:/|/)#', $p)) {
        return $p;
    }
    return project_root() . '/' . ltrim($p, '/');
}

function upload_dir() {
    $p = env('UPLOAD_DIR', 'C:/xampp/htdocs/temp');
    $p = str_replace('\\', '/', $p);
    if (!preg_match('#^([a-zA-Z]:/|/)#', $p)) {
        $p = project_root() . '/' . ltrim($p, '/');
    }
    return rtrim($p, '/');
}

function upload_url($archivo = '') {
    $base = rtrim(env('UPLOAD_URL', '/temp'), '/');
    return $archivo === '' ? $base : $base . '/' . rawurlencode($archivo);
}

function sismos_url() {
    return env('SISMOS_URL', 'https://sismia.onrender.com/api/sismos');
}

function sismos_icon() {
    return env('SISMOS_ICON', 'fa-house-crack');
}

function sismos_ttl() {
    return max(0, (int) env('SISMOS_TTL', 900));
}

function sismos_timeout() {
    return max(1, (int) env('SISMOS_TIMEOUT', 8));
}

function sismos_autosync() {
    return env('SISMOS_AUTOSYNC', '1') !== '0';
}

function sismos_keep_days() {
    return max(1, (int) env('SISMOS_KEEP_DAYS', 30));
}

function sig_config() {
    return [
        'tileUrl' => env('TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'tileSubdomains' => env('TILE_SUBDOMAINS', 'abc'),
        'tileMaxZoom' => (int) env('TILE_MAX_ZOOM', 19),
        'tileAttribution' => env('TILE_ATTRIBUTION', "&copy; <a href='https://www.openstreetmap.org/copyright' target='_blank' rel='noopener'>OpenStreetMap</a> contributors"),
        'nominatimUrl' => rtrim(env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'), '/'),
        'nominatimLang' => env('NOMINATIM_LANG', 'es'),
        'nominatimLimit' => (int) env('NOMINATIM_LIMIT', 6),
        'nominatimCountry' => env('NOMINATIM_COUNTRY', ''),
        'mapCenterLat' => (float) env('MAP_CENTER_LAT', 13.7942),
        'mapCenterLon' => (float) env('MAP_CENTER_LON', -88.8965),
        'mapZoom' => (int) env('MAP_ZOOM', 8),
        'mapHeatZoom' => (int) env('MAP_HEAT_ZOOM', 9),
        'riskScale' => risk_scale_map(),
        'riskMin' => risk_min(),
    ];
}
