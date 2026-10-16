<?php
require_once __DIR__ . '/config.php';

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $file = db_file();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $pdo = new PDO('sqlite:' . $file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function init_db() {
    $pdo = db();

    $pdo->exec("CREATE TABLE IF NOT EXISTS alertas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nivel TEXT NOT NULL,
        tipo TEXT NOT NULL,
        lugar TEXT NOT NULL,
        tiempo TEXT NOT NULL,
        icon TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reportes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        lugar TEXT NOT NULL,
        tiempo TEXT NOT NULL,
        estado TEXT NOT NULL,
        icon TEXT NOT NULL,
        descripcion TEXT NOT NULL DEFAULT ''
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS kits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        categoria TEXT NOT NULL,
        tipo TEXT NOT NULL,
        estado TEXT NOT NULL,
        ubicacion TEXT NOT NULL,
        cantidad TEXT NOT NULL,
        vencimiento TEXT NOT NULL,
        icon TEXT NOT NULL,
        color TEXT NOT NULL DEFAULT 'blue'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS albergues (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        distancia TEXT NOT NULL,
        capacidad TEXT NOT NULL,
        estado TEXT NOT NULL DEFAULT 'Disponible'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS capas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        activa INTEGER NOT NULL DEFAULT 1
    )");

    seed_if_empty($pdo);
    migrar_coordenadas($pdo);
}

function ensure_column($pdo, $tabla, $columna, $def) {
    $cols = $pdo->query("PRAGMA table_info(" . $tabla . ")")->fetchAll();
    foreach ($cols as $c) {
        if ($c['name'] === $columna) {
            return;
        }
    }
    $pdo->exec("ALTER TABLE " . $tabla . " ADD COLUMN " . $columna . " " . $def);
}

function migrar_coordenadas($pdo) {
    ensure_column($pdo, 'reportes', 'lat', 'REAL');
    ensure_column($pdo, 'reportes', 'lon', 'REAL');
    ensure_column($pdo, 'alertas', 'lat', 'REAL');
    ensure_column($pdo, 'alertas', 'lon', 'REAL');
    ensure_column($pdo, 'albergues', 'lat', 'REAL');
    ensure_column($pdo, 'albergues', 'lon', 'REAL');

    $coords_reportes = [
        1 => [13.4833, -88.1833], 2 => [13.6768, -89.2903],
        3 => [14.0333, -88.9333], 4 => [13.7500, -89.3500],
        5 => [13.9357, -89.0259], 6 => [13.6970, -89.2162],
        7 => [13.3500, -88.4400], 8 => [13.4900, -88.1900],
        9 => [13.6800, -89.3000], 10 => [13.6800, -89.3200],
    ];
    $coords_alertas = [
        1 => [13.4833, -88.1833], 2 => [13.6768, -89.2903],
        3 => [13.7200, -89.7200], 4 => [13.7000, -89.2500],
        5 => [13.9500, -89.5500],
    ];
    $coords_albergues = [
        1 => [13.4833, -88.1833], 2 => [13.6768, -89.2903],
        3 => [13.3500, -88.4400],
    ];
    $mapas = ['reportes' => $coords_reportes, 'alertas' => $coords_alertas, 'albergues' => $coords_albergues];
    foreach ($mapas as $tabla => $coords) {
        $st = $pdo->prepare("UPDATE " . $tabla . " SET lat = ?, lon = ? WHERE id = ? AND lat IS NULL");
        foreach ($coords as $id => $ll) {
            $st->execute([$ll[0], $ll[1], $id]);
        }
    }
}

function seed_if_empty($pdo) {
    if ((int) $pdo->query("SELECT COUNT(*) FROM alertas")->fetchColumn() > 0) {
        return;
    }

    $alertas = [
        ['alta',  'Deslizamiento de tierra', 'San Miguel, La Libertad',     'Hace 12 min', 'fa-hill-rockslide'],
        ['alta',  'Inundación',              'Santa Tecla, La Libertad',    'Hace 28 min', 'fa-water'],
        ['media', 'Lluvias intensas',        'Sonsonate, Ciudad Lusitania', 'Hace 15 min', 'fa-cloud-showers-heavy'],
        ['baja',  'Ruta habilitada',         'Carretera Panamericana',      'Hace 30 min', 'fa-road'],
        ['alta',  'Incendio forestal',       'Cerro Verde, Santa Ana',      'Hace 30 min', 'fa-fire'],
    ];
    $st = $pdo->prepare("INSERT INTO alertas (nivel, tipo, lugar, tiempo, icon) VALUES (?, ?, ?, ?, ?)");
    foreach ($alertas as $a) { $st->execute($a); }

    $reportes = [
        ['Deslizamiento de tierra', 'San Miguel, La Libertad', 'Hace 12 min', 'Nuevo', 'fa-hill-rockslide', 'Se reporta un deslizamiento de tierra en la carretera principal, hay derrumbes y todo en la vía al paso está parcialmente bloqueado.'],
        ['Inundación', 'Santa Tecla, La Libertad', 'Hace 28 min', 'En revisión', 'fa-water', 'Nivel del río por encima de lo normal afectando viviendas cercanas al cauce.'],
        ['Incendio forestal', 'Plan de La Laguna, Chalatenango', 'Hace 28 min', 'En revisión', 'fa-fire', 'Foco de incendio en zona boscosa, se solicita apoyo de equipos de respuesta.'],
        ['Carretera bloqueada', 'Panamericana km 51', 'Hace 51 min', 'En atención', 'fa-road', 'Derrumbe bloquea un carril. Precaución al circular por la zona.'],
        ['Lluvias intensas', 'Suchitoto, Cuscatlán', 'Hace 1 h', 'Resuelto', 'fa-cloud-showers-heavy', 'Lluvias intensas con acumulados importantes durante la madrugada.'],
        ['Árbol caído', 'Bulevar Los Próceres, San Salvador', 'Hace 1 h', 'Nuevo', 'fa-tree', 'Árbol cae sobre la vía dificultando el paso vehicular.'],
        ['Inundación', 'Barrio San José, Usulután', 'Hace 2 h', 'En atención', 'fa-water', 'Calles anegadas por lluvias; se coordina evacuación preventiva.'],
        ['Deslizamiento de tierra', 'Cantón El Volcán, San Miguel', 'Hace 3 h', 'Resuelto', 'fa-hill-rockslide', 'Pequeño deslizamiento ya removido por cuadrilla municipal.'],
        ['Vivienda dañada', 'Colonia Santa Lucía, La Libertad', 'Hace 4 h', 'En revisión', 'fa-house-crack', 'Techo colapsado por vientos fuertes; familia necesita albergue temporal.'],
        ['Carretera bloqueada', 'Carretera a Los Chorros', 'Hace 5 h', 'Resuelto', 'fa-road', 'Roca en la vía ya retirada; circulación normal restablecida.'],
    ];
    $st = $pdo->prepare("INSERT INTO reportes (titulo, lugar, tiempo, estado, icon, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($reportes as $r) { $st->execute($r); }

    $kits = [
        ['Kit de primeros auxilios', 'Médico', 'Tipo 1', 'Disponible', 'Usulután', 'Cantidad: 51 kits', 'Fecha de vencimiento: 2026-08-15', 'fa-suitcase-medical', 'blue'],
        ['Kit de alimentos y agua', 'Humanitario', 'Tipo 3', 'Disponible', 'La Libertad', 'Cantidad: 30 kits', 'Fecha de vencimiento: 2026-10-10', 'fa-box', 'green'],
        ['Kit de rescate', 'Rescate', 'Tipo 2', 'En uso', 'San Salvador', 'Cantidad: 4 kits', 'Fecha de vencimiento: 2027-12-05', 'fa-toolbox', 'red'],
        ['Kit de comunicación', 'Comunicación', 'Tipo 5', 'Disponible', 'Sonsonate', 'Cantidad: 8 kits', 'Fecha de vencimiento: 2026-12-12', 'fa-radio', 'blue'],
        ['Kit de albergue temporal', 'Albergue', 'Tipo 1', 'Disponible', 'Usulután', 'Cantidad: 2 kits', 'Fecha de vencimiento: 2027-03-15', 'fa-tent', 'purple'],
        ['Kit de energía', 'Energía', 'Tipo 4', 'Disponible', 'La Paz', 'Cantidad: 7 kits', 'Fecha de vencimiento: 2027-12-05', 'fa-bolt', 'amber'],
    ];
    $st = $pdo->prepare("INSERT INTO kits (nombre, categoria, tipo, estado, ubicacion, cantidad, vencimiento, icon, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($kits as $k) { $st->execute($k); }

    $albergues = [
        ['Albergue Municipal San Miguel', '4.2 km', 'Capacidad: 30 personas', 'Disponible'],
        ['Centro Escolar El Progreso', '5.7 km', 'Capacidad: 60 personas', 'Disponible'],
        ['Escuela Lot. De Septiembre', '13.1 km', 'Capacidad: 41 personas', 'Disponible'],
    ];
    $st = $pdo->prepare("INSERT INTO albergues (nombre, distancia, capacidad, estado) VALUES (?, ?, ?, ?)");
    foreach ($albergues as $a) { $st->execute($a); }

    $capas = [
        ['Zonas de riesgo', 1],
        ['Incendios forestales', 1],
        ['Inundaciones', 1],
        ['Deslizamientos', 1],
        ['Red de comunicación', 1],
        ['Recursos y albergues', 0],
        ['Límites territoriales', 0],
    ];
    $st = $pdo->prepare("INSERT INTO capas (nombre, activa) VALUES (?, ?)");
    foreach ($capas as $c) { $st->execute($c); }
}

function conteo_reportes() {
    $pdo = db();
    $rows = $pdo->query("SELECT estado, COUNT(*) AS n FROM reportes GROUP BY estado")->fetchAll();
    $out = ['todos' => 0, 'nuevo' => 0, 'revision' => 0, 'atencion' => 0, 'resuelto' => 0];
    $map = ['Nuevo' => 'nuevo', 'En revisión' => 'revision', 'En atención' => 'atencion', 'Resuelto' => 'resuelto'];
    foreach ($rows as $r) {
        $out['todos'] += (int) $r['n'];
        if (isset($map[$r['estado']])) {
            $out[$map[$r['estado']]] = (int) $r['n'];
        }
    }
    return $out;
}

function conteo_kits() {
    $pdo = db();
    $rows = $pdo->query("SELECT categoria, COUNT(*) AS n FROM kits GROUP BY categoria")->fetchAll();
    $out = ['total' => 0];
    foreach ($rows as $r) {
        $out[$r['categoria']] = (int) $r['n'];
        $out['total'] += (int) $r['n'];
    }
    return $out;
}

function reporte_destacado() {
    $pdo = db();
    $row = $pdo->query("SELECT id, titulo, lugar, tiempo, estado, icon, lat, lon, descripcion AS \"desc\" FROM reportes WHERE estado = 'Nuevo' ORDER BY id LIMIT 1")->fetch();
    if (!$row) {
        $row = $pdo->query("SELECT id, titulo, lugar, tiempo, estado, icon, lat, lon, descripcion AS \"desc\" FROM reportes ORDER BY id LIMIT 1")->fetch();
    }
    return $row ?: null;
}

function obtener_reporte($id) {
    $st = db()->prepare("SELECT id, titulo, lugar, tiempo, estado, icon, lat, lon, descripcion AS \"desc\" FROM reportes WHERE id = ?");
    $st->execute([(int) $id]);
    return $st->fetch() ?: null;
}

function crear_reporte($d) {
    $estados = ['Nuevo', 'En revisión', 'En atención', 'Resuelto'];
    $st = db()->prepare("INSERT INTO reportes (titulo, lugar, tiempo, estado, icon, descripcion, lat, lon) VALUES (?, ?, 'Hace un momento', 'Nuevo', ?, ?, ?, ?)");
    $st->execute([
        mb_substr(trim($d['titulo'] ?? ''), 0, 120),
        mb_substr(trim($d['lugar'] ?? ''), 0, 120),
        $d['icon'] ?? 'fa-triangle-exclamation',
        mb_substr(trim($d['descripcion'] ?? ''), 0, 1000),
        (float) ($d['lat'] ?? env('MAP_CENTER_LAT', 13.7942)),
        (float) ($d['lon'] ?? env('MAP_CENTER_LON', -88.8965)),
    ]);
    return (int) db()->lastInsertId();
}

function actualizar_estado_reporte($id, $estado) {
    $validos = ['Nuevo', 'En revisión', 'En atención', 'Resuelto'];
    if (!in_array($estado, $validos, true)) {
        return false;
    }
    $st = db()->prepare("UPDATE reportes SET estado = ? WHERE id = ?");
    $st->execute([$estado, (int) $id]);
    return $st->rowCount() > 0;
}

function crear_kit($d) {
    $estados = ['Disponible', 'En uso', 'Mantenimiento'];
    $estado = in_array($d['estado'] ?? '', $estados, true) ? $d['estado'] : 'Disponible';
    $iconos = [
        'Médico' => ['fa-suitcase-medical', 'blue'],
        'Humanitario' => ['fa-box', 'green'],
        'Rescate' => ['fa-toolbox', 'red'],
        'Comunicación' => ['fa-radio', 'blue'],
        'Albergue' => ['fa-tent', 'purple'],
        'Energía' => ['fa-bolt', 'amber'],
    ];
    $cat = $d['categoria'] ?? 'Humanitario';
    if (!isset($iconos[$cat])) {
        $cat = 'Humanitario';
    }
    $st = db()->prepare("INSERT INTO kits (nombre, categoria, tipo, estado, ubicacion, cantidad, vencimiento, icon, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $st->execute([
        mb_substr(trim($d['nombre'] ?? ''), 0, 120),
        $cat,
        $d['tipo'] ?? 'Tipo 1',
        $estado,
        mb_substr(trim($d['ubicacion'] ?? ''), 0, 120),
        'Cantidad: ' . max(1, (int) ($d['cantidad'] ?? 1)) . ' kits',
        'Fecha de vencimiento: ' . ($d['vencimiento'] ?? date('Y-m-d')),
        $iconos[$cat][0],
        $iconos[$cat][1],
    ]);
    return (int) db()->lastInsertId();
}

function actualizar_estado_kit($id, $estado) {
    $validos = ['Disponible', 'En uso', 'Mantenimiento'];
    if (!in_array($estado, $validos, true)) {
        return false;
    }
    $st = db()->prepare("UPDATE kits SET estado = ? WHERE id = ?");
    $st->execute([$estado, (int) $id]);
    return $st->rowCount() > 0;
}

function geo_reportes() {
    return db()->query("SELECT id, titulo, lugar, tiempo, estado, icon, lat, lon FROM reportes WHERE lat IS NOT NULL AND lon IS NOT NULL ORDER BY id")->fetchAll();
}

function geo_alertas() {
    return db()->query("SELECT id, nivel, tipo, lugar, tiempo, icon, lat, lon FROM alertas WHERE lat IS NOT NULL AND lon IS NOT NULL ORDER BY id")->fetchAll();
}

function geo_albergues() {
    return db()->query("SELECT id, nombre AS n, distancia AS loc, capacidad AS cap, estado, lat, lon FROM albergues WHERE lat IS NOT NULL AND lon IS NOT NULL ORDER BY id")->fetchAll();
}
