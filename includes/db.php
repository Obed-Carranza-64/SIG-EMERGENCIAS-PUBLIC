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

    $pdo->exec("CREATE TABLE IF NOT EXISTS reporte_imagenes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reporte_id INTEGER NOT NULL,
        archivo TEXT NOT NULL,
        creado TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS capas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        activa INTEGER NOT NULL DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sync_state (
        nombre TEXT PRIMARY KEY,
        valor TEXT NOT NULL,
        actualizado TEXT NOT NULL
    )");

    seed_if_empty($pdo);
    migrar_coordenadas($pdo);
    asegurar_capas($pdo);

    $pdo->exec("CREATE TABLE IF NOT EXISTS nodos_mesh (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        lat REAL NOT NULL,
        lon REAL NOT NULL,
        tipo TEXT NOT NULL DEFAULT 'repetidor',
        estado TEXT NOT NULL DEFAULT 'activo',
        bateria INTEGER NOT NULL DEFAULT 100
    )");

    if ((int) $pdo->query("SELECT COUNT(*) FROM nodos_mesh")->fetchColumn() === 0) {
        $nodos = [
            ['Nodo Central', 13.6929, -89.2182, 'central', 'activo', 100],
            ['Nodo Santa Tecla', 13.6768, -89.2903, 'repetidor', 'activo', 87],
            ['Nodo San Miguel', 13.4833, -88.1833, 'repetidor', 'activo', 76],
            ['Nodo Santa Ana', 13.9942, -89.5597, 'repetidor', 'activo', 64],
            ['Nodo Chalatenango', 14.0333, -88.9333, 'repetidor', 'activo', 92],
            ['Nodo Usulután', 13.3500, -88.4400, 'repetidor', 'activo', 58],
            ['Nodo Suchitoto', 13.9357, -89.0259, 'sensor', 'activo', 81],
            ['Nodo La Unión', 13.3350, -87.8410, 'sensor', 'activo', 45],
            ['Nodo San Vicente', 13.6447, -88.7853, 'repetidor', 'activo', 70],
        ];
        $st = $pdo->prepare("INSERT INTO nodos_mesh (nombre, lat, lon, tipo, estado, bateria) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($nodos as $n) {
            $st->execute($n);
        }
    }
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
    ensure_column($pdo, 'alertas', 'ref', 'TEXT');
    ensure_column($pdo, 'alertas', 'magnitud', 'REAL');
    ensure_column($pdo, 'alertas', 'profundidad', 'REAL');
    ensure_column($pdo, 'alertas', 'url', 'TEXT');
    ensure_column($pdo, 'alertas', 'fuente', 'TEXT');
    ensure_column($pdo, 'alertas', 'evento', 'TEXT');
    ensure_column($pdo, 'albergues', 'lat', 'REAL');
    ensure_column($pdo, 'albergues', 'lon', 'REAL');

    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_alertas_ref ON alertas(ref)");

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

function asegurar_capas($pdo) {
    foreach (['Sismos'] as $nombre) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM capas WHERE nombre = ?");
        $st->execute([$nombre]);
        if ((int) $st->fetchColumn() === 0) {
            $ins = $pdo->prepare("INSERT INTO capas (nombre, activa) VALUES (?, 1)");
            $ins->execute([$nombre]);
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

function guardar_imagen_reporte($reporte_id, $archivo) {
    $st = db()->prepare("INSERT INTO reporte_imagenes (reporte_id, archivo, creado) VALUES (?, ?, ?)");
    $st->execute([(int) $reporte_id, $archivo, date('d/m/Y H:i')]);
    return (int) db()->lastInsertId();
}

function imagenes_reporte($reporte_id) {
    $st = db()->prepare("SELECT id, archivo, creado FROM reporte_imagenes WHERE reporte_id = ? ORDER BY id");
    $st->execute([(int) $reporte_id]);
    return $st->fetchAll();
}

function contar_imagenes_reporte($reporte_id) {
    $st = db()->prepare("SELECT COUNT(*) FROM reporte_imagenes WHERE reporte_id = ?");
    $st->execute([(int) $reporte_id]);
    return (int) $st->fetchColumn();
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
    return db()->query("SELECT id, nivel, tipo, lugar, tiempo, icon, lat, lon, magnitud, profundidad, url, fuente, ref FROM alertas WHERE lat IS NOT NULL AND lon IS NOT NULL ORDER BY id")->fetchAll();
}

function alerta_por_ref($ref) {
    $st = db()->prepare("SELECT id FROM alertas WHERE ref = ? LIMIT 1");
    $st->execute([(string) $ref]);
    $r = $st->fetch();
    return $r ? (int) $r['id'] : null;
}

function crear_alerta_externa($d) {
    $st = db()->prepare("INSERT INTO alertas (nivel, tipo, lugar, tiempo, icon, lat, lon, ref, magnitud, profundidad, url, fuente, evento)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $st->execute([
        $d['nivel'], $d['tipo'], $d['lugar'], $d['tiempo'], $d['icon'],
        $d['lat'] ?? null, $d['lon'] ?? null, $d['ref'] ?? null,
        $d['magnitud'] ?? null, $d['profundidad'] ?? null,
        $d['url'] ?? null, $d['fuente'] ?? null, $d['evento'] ?? null,
    ]);
    return (int) db()->lastInsertId();
}

function actualizar_alerta_externa($id, $d) {
    $st = db()->prepare("UPDATE alertas SET nivel = ?, tipo = ?, lugar = ?, tiempo = ?, icon = ?, lat = ?, lon = ?,
        magnitud = ?, profundidad = ?, url = ?, fuente = ?, evento = ? WHERE id = ?");
    $st->execute([
        $d['nivel'], $d['tipo'], $d['lugar'], $d['tiempo'], $d['icon'],
        $d['lat'] ?? null, $d['lon'] ?? null,
        $d['magnitud'] ?? null, $d['profundidad'] ?? null,
        $d['url'] ?? null, $d['fuente'] ?? null, $d['evento'] ?? null,
        (int) $id,
    ]);
    return $st->rowCount() > 0;
}

function guardar_alerta_externa($d) {
    $id = alerta_por_ref($d['ref'] ?? '');
    if ($id) {
        actualizar_alerta_externa($id, $d);
        return ['accion' => 'update', 'id' => $id];
    }
    return ['accion' => 'insert', 'id' => crear_alerta_externa($d)];
}

function podar_alertas_externas($dias = 30) {
    $st = db()->prepare("DELETE FROM alertas WHERE ref IS NOT NULL AND evento IS NOT NULL AND datetime(evento) < datetime('now', ?)");
    $st->execute(['-' . max(1, (int) $dias) . ' days']);
    return $st->rowCount();
}

function contar_alertas_externas() {
    return (int) db()->query("SELECT COUNT(*) FROM alertas WHERE ref IS NOT NULL")->fetchColumn();
}

function sync_state_get($nombre) {
    $st = db()->prepare("SELECT valor, actualizado FROM sync_state WHERE nombre = ?");
    $st->execute([(string) $nombre]);
    return $st->fetch() ?: null;
}

function sync_state_set($nombre, $valor) {
    $st = db()->prepare("INSERT INTO sync_state (nombre, valor, actualizado) VALUES (?, ?, ?)
        ON CONFLICT(nombre) DO UPDATE SET valor = excluded.valor, actualizado = excluded.actualizado");
    $st->execute([(string) $nombre, (string) $valor, date('Y-m-d H:i:s')]);
}

function geo_albergues() {
    return db()->query("SELECT id, nombre AS n, distancia AS loc, capacidad AS cap, estado, lat, lon FROM albergues WHERE lat IS NOT NULL AND lon IS NOT NULL ORDER BY id")->fetchAll();
}

function mesh_nodos($solo_activos = false) {
    $sql = "SELECT id, nombre, lat, lon, tipo, estado, bateria FROM nodos_mesh ORDER BY id";
    if ($solo_activos) {
        $sql = "SELECT id, nombre, lat, lon, tipo, estado, bateria FROM nodos_mesh WHERE estado = 'activo' ORDER BY id";
    }
    return db()->query($sql)->fetchAll();
}

function actualizar_estado_nodo($id, $estado) {
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        return false;
    }
    $st = db()->prepare("UPDATE nodos_mesh SET estado = ? WHERE id = ?");
    $st->execute([$estado, (int) $id]);
    return $st->rowCount() > 0;
}
