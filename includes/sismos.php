<?php
require_once __DIR__ . '/db.php';

/* Descarga el JSON de la API de sismos (cURL si está disponible). */
function sismos_http($url, $timeout = null) {
    $timeout = $timeout === null ? sismos_timeout() : max(1, (int) $timeout);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min($timeout, 6),
            CURLOPT_USERAGENT => 'SIG-Emergencia/1.0 (+sismos)',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false || $body === '' || $code >= 400) {
            return [null, $err !== '' ? $err : ('HTTP ' . $code)];
        }
        return [$body, null];
    }

    $ctx = stream_context_create(['http' => [
        'timeout' => $timeout,
        'header' => "Accept: application/json\r\nUser-Agent: SIG-Emergencia/1.0\r\n",
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false || $body === '') {
        return [null, 'No se pudo conectar con la API'];
    }
    return [$body, null];
}

/* Nivel de alerta según la magnitud (normaliza a: alta / media / baja). */
function sismos_nivel($magnitud) {
    $m = (float) $magnitud;
    if ($m >= 5.0) {
        return 'alta';
    }
    if ($m >= 4.0) {
        return 'media';
    }
    return 'baja';
}

/* Tiempo relativo en español a partir de un timestamp ISO. */
function sismos_tiempo($iso) {
    $ts = strtotime((string) $iso);
    if ($ts === false) {
        return 'Hace un momento';
    }
    $diff = time() - $ts;
    if ($diff < 0) {
        $diff = 0;
    }
    if ($diff < 60) {
        return 'Hace un momento';
    }
    $min = (int) floor($diff / 60);
    if ($min < 60) {
        return 'Hace ' . $min . ' min';
    }
    $h = (int) floor($min / 60);
    if ($h < 24) {
        return 'Hace ' . $h . ' h';
    }
    return 'Hace ' . (int) floor($h / 24) . ' d';
}

/* Normaliza un sismo de la API al formato interno de "alertas". */
function sismos_normalizar($s) {
    $fecha = (string) ($s['fecha'] ?? '');
    $ts = strtotime($fecha);
    $fuente = trim((string) ($s['fuente'] ?? 'MARN'));

    return [
        'ref' => (string) ($s['id'] ?? ''),
        'nivel' => sismos_nivel($s['magnitud'] ?? 0),
        'tipo' => 'Sismo',
        'lugar' => mb_substr(trim((string) ($s['lugar'] ?? 'Sismo')), 0, 200),
        'tiempo' => sismos_tiempo($fecha),
        'icon' => sismos_icon(),
        'lat' => isset($s['latitud']) && $s['latitud'] !== null ? (float) $s['latitud'] : null,
        'lon' => isset($s['longitud']) && $s['longitud'] !== null ? (float) $s['longitud'] : null,
        'magnitud' => isset($s['magnitud']) && $s['magnitud'] !== null ? (float) $s['magnitud'] : null,
        'profundidad' => isset($s['profundidad_km']) && $s['profundidad_km'] !== null ? (float) $s['profundidad_km'] : null,
        'url' => trim((string) ($s['url'] ?? '')),
        'fuente' => $fuente,
        'evento' => $ts !== false ? gmdate('Y-m-d H:i:s', $ts) : gmdate('Y-m-d H:i:s'),
    ];
}

/* Descarga y normaliza la lista de sismos. Devuelve [items|null, error|null]. */
function sismos_obtener($timeout = null) {
    list($body, $err) = sismos_http(sismos_url(), $timeout);
    if ($body === null) {
        return [null, $err];
    }
    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['sismos']) || !is_array($data['sismos'])) {
        return [null, 'Respuesta con formato inesperado'];
    }
    $out = [];
    foreach ($data['sismos'] as $s) {
        if (is_array($s) && !empty($s['id'])) {
            $out[] = sismos_normalizar($s);
        }
    }
    return [$out, null];
}

/* Sincroniza los sismos con la base local (idempotente por 'ref').
   Respeta el TTL salvo que $force sea true. */
function sincronizar_sismos($force = false, $timeout = null) {
    $estado = sync_state_get('sismos');
    if (!$force && sismos_ttl() > 0 && $estado) {
        $ts = strtotime((string) ($estado['actualizado'] ?? ''));
        if ($ts !== false && (time() - $ts) < sismos_ttl()) {
            return [
                'ok' => true, 'saltado' => true,
                'insertados' => 0, 'actualizados' => 0, 'total' => 0,
                'error' => null, 'actualizado' => $estado['actualizado'],
            ];
        }
    }

    list($items, $err) = sismos_obtener($timeout);
    if ($items === null) {
        return [
            'ok' => false, 'saltado' => false,
            'insertados' => 0, 'actualizados' => 0, 'total' => 0,
            'error' => $err, 'actualizado' => $estado['actualizado'] ?? null,
        ];
    }

    $ins = 0;
    $upd = 0;
    foreach ($items as $it) {
        $res = guardar_alerta_externa($it);
        if ($res['accion'] === 'insert') {
            $ins++;
        } else {
            $upd++;
        }
    }

    podar_alertas_externas(sismos_keep_days());
    sync_state_set('sismos', (string) count($items));

    return [
        'ok' => true, 'saltado' => false,
        'insertados' => $ins, 'actualizados' => $upd, 'total' => count($items),
        'error' => null, 'actualizado' => date('Y-m-d H:i:s'),
    ];
}
