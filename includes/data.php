<?php
require_once __DIR__ . '/config.php';
$APP = [
    'nombre' => env('APP_NAME', 'SIG - EMERGENCIA'),
    'subtitulo' => env('APP_SUBTITLE', 'Sistema Integrado de Decisiones Rápidas y Asistencia de Recursos'),
    'ultima_actualizacion' => env('APP_UPDATED_AT', '14:25'),
    'pais' => env('APP_COUNTRY', 'El Salvador'),
];

$NAV = [
    ['id' => 'inicio',   'label' => 'Inicio',              'icon' => 'fa-house',            'href' => 'index.php'],
    ['id' => 'mapa',     'label' => 'Mapa',                'icon' => 'fa-map',              'href' => 'mapa.php'],
    ['id' => 'reportes', 'label' => 'Reportes Ciudadanos', 'icon' => 'fa-triangle-exclamation', 'href' => 'reportes.php'],
    ['id' => 'recursos', 'label' => 'Kits y Recursos',     'icon' => 'fa-toolbox',          'href' => 'recursos.php'],
    ['id' => 'coord',    'label' => 'Coordinación',        'icon' => 'fa-diagram-project',  'href' => 'coordinacion.php'],
];

$STATS_INICIO = [
    ['valor' => '15', 'label' => 'Nodos de red',            'sub' => 'Activos',      'icon' => 'fa-circle-nodes', 'color' => 'green'],
    ['valor' => '03', 'label' => 'Alertas recientes',       'sub' => 'Por revisar',  'icon' => 'fa-triangle-exclamation', 'color' => 'red'],
    ['valor' => '12', 'label' => 'Torres de comunicación',  'sub' => 'Operativas',   'icon' => 'fa-tower-broadcast', 'color' => 'blue'],
    ['valor' => '08', 'label' => 'Equipos de respuesta',    'sub' => 'Desplegados',  'icon' => 'fa-people-group', 'color' => 'purple'],
];

$ACCESOS = [
    ['icon' => 'fa-map-location-dot', 'title' => 'Mapa de zonas de riesgo', 'desc' => 'Visualiza incidentes, áreas de riesgo y recursos disponibles.', 'cta' => 'Explorar mapa', 'href' => 'mapa.php'],
    ['icon' => 'fa-toolbox',          'title' => 'Kits y recursos',         'desc' => 'Consulta y gestiona los recursos disponibles.',                 'cta' => '',              'href' => 'recursos.php'],
    ['icon' => 'fa-bullhorn',         'title' => 'Reportes ciudadanos',     'desc' => 'Tu reporte salva vidas.',                                      'cta' => '',              'href' => 'reportes.php'],
    ['icon' => 'fa-clipboard-list',   'title' => 'Guía de emergencia',       'desc' => 'Qué hacer ante una emergencia.',                               'cta' => '',              'href' => 'guia.php' ],
];

$ALERTAS = [
    ['nivel' => 'alta',  'tipo' => 'Deslizamiento de tierra', 'lugar' => 'San Miguel, La Libertad',       'tiempo' => 'Hace 12 min', 'icon' => 'fa-hill-rockslide'],
    ['nivel' => 'alta',  'tipo' => 'Inundación',              'lugar' => 'Santa Tecla, La Libertad',      'tiempo' => 'Hace 28 min', 'icon' => 'fa-water'],
    ['nivel' => 'media', 'tipo' => 'Lluvias intensas',        'lugar' => 'Sonsonate, Cuidad Lusitania',   'tiempo' => 'Hace 15 min', 'icon' => 'fa-cloud-showers-heavy'],
    ['nivel' => 'baja',  'tipo' => 'Ruta habilitada',         'lugar' => 'Carretera Panamericana',        'tiempo' => 'Hace 30 min', 'icon' => 'fa-road'],
    ['nivel' => 'alta',  'tipo' => 'Incendio forestal',       'lugar' => 'Cerro Verde, Santa Ana',        'tiempo' => 'Hace 30 min', 'icon' => 'fa-fire'],
];

$REPORTES = [
    ['titulo' => 'Deslizamiento de tierra', 'lugar' => 'San Miguel, La Libertad',   'tiempo' => 'Hace 12 min', 'estado' => 'Nuevo',      'icon' => 'fa-hill-rockslide', 'desc' => 'Se reporta un deslizamiento de tierra en la carretera principal, hay derrumbes y todo en la vía al paso está parcialmente bloqueado.'],
    ['titulo' => 'Inundación',              'lugar' => 'Santa Tecla, La Libertad',  'tiempo' => 'Hace 28 min', 'estado' => 'En revisión','icon' => 'fa-water', 'desc' => 'Nivel del río por encima de lo normal afectando viviendas cercanas al cauce.'],
    ['titulo' => 'Incendio forestal',       'lugar' => 'Plan de La Laguna, Chalatenango', 'tiempo' => 'Hace 28 min', 'estado' => 'En revisión', 'icon' => 'fa-fire', 'desc' => 'Foco de incendio en zona boscosa, se solicita apoyo de equipos de respuesta.'],
    ['titulo' => 'Carretera bloqueada',     'lugar' => 'Panamericana km 51',        'tiempo' => 'Hace 51 min', 'estado' => 'En atención', 'icon' => 'fa-road', 'desc' => 'Derrumbe bloquea un carril. Precaución al circular por la zona.'],
    ['titulo' => 'Lluvias intensas',        'lugar' => 'Suchitoto, Cuscatlán',      'tiempo' => 'Hace 1 h',    'estado' => 'Resuelto',   'icon' => 'fa-cloud-showers-heavy', 'desc' => 'Lluvias intensas con acumulados importantes durante la madrugada.'],
    ['titulo' => 'Árbol caído',             'lugar' => 'Bulevar Los Próceres, San Salvador', 'tiempo' => 'Hace 1 h', 'estado' => 'Nuevo', 'icon' => 'fa-tree', 'desc' => 'Árbol cae sobre la vía dificultando el paso vehicular.'],
];

$RECURSOS_RESUMEN = [
    ['valor' => '3',  'label' => 'Zonas de alto riesgo',  'color' => 'red'],
    ['valor' => '7',  'label' => 'Zonas en atención',     'color' => 'amber'],
    ['valor' => '12', 'label' => 'Recursos activos',      'color' => 'blue'],
    ['valor' => '15', 'label' => 'Nodos de red',          'color' => 'green'],
];

$ALBERGUES = [
    ['n' => 'Albergue Municipal San Miguel',   'loc' => '4.2 km', 'cap' => 'Capacidad: 30 personas',  'estado' => 'Disponible'],
    ['n' => 'Centro Escolar El Progreso',      'loc' => '5.7 km', 'cap' => 'Capacidad: 60 personas',  'estado' => 'Disponible'],
    ['n' => 'Escuela Lot. De Septiembre',      'loc' => '13.1 km','cap' => 'Capacidad: 41 personas',  'estado' => 'Disponible'],
];

$KITS = [
    ['icon' => 'fa-suitcase-medical', 'n' => 'Kit de primeros auxilios', 'cat' => 'Médico',    'tipo' => 'Tipo 1', 'disp' => 'Disponible', 'loc' => 'Usulután', 'cant' => 'Cantidad: 51 kits', 'fecha' => 'Fecha de vencimiento: 2026-08-15', 'color' => 'blue'],
    ['icon' => 'fa-box',             'n' => 'Kit de alimentos y agua',  'cat' => 'Humanitario','tipo' => 'Tipo 3', 'disp' => 'Disponible', 'loc' => 'La Libertad', 'cant' => 'Cantidad: 30 kits', 'fecha' => 'Fecha de vencimiento: 2026-10-10', 'color' => 'green'],
    ['icon' => 'fa-toolbox',         'n' => 'Kit de rescate',           'cat' => 'Rescate',   'tipo' => 'Tipo 2', 'disp' => 'En uso',     'loc' => 'San Salvador', 'cant' => 'Cantidad: 4 kits',  'fecha' => 'Fecha de vencimiento: 2027-12-05', 'color' => 'red'],
    ['icon' => 'fa-radio',           'n' => 'Kit de comunicación',      'cat' => 'Comunicación','tipo' => 'Tipo 5','disp' => 'Disponible', 'loc' => 'Sonsonate',   'cant' => 'Cantidad: 8 kits',  'fecha' => 'Fecha de vencimiento: 2026-12-12', 'color' => 'blue'],
    ['icon' => 'fa-tent',            'n' => 'Kit de albergue temporal', 'cat' => 'Albergue',  'tipo' => 'Tipo 1', 'disp' => 'Disponible', 'loc' => 'Usulután',    'cant' => 'Cantidad: 2 kits',  'fecha' => 'Fecha de vencimiento: 2027-03-15', 'color' => 'purple'],
    ['icon' => 'fa-bolt',            'n' => 'Kit de energía',           'cat' => 'Energía',   'tipo' => 'Tipo 4', 'disp' => 'Disponible', 'loc' => 'La Paz',      'cant' => 'Cantidad: 7 kits',  'fecha' => 'Fecha de vencimiento: 2027-12-05', 'color' => 'amber'],
];

$CAPAS = [
    ['n' => 'Zonas de riesgo',           'on' => true],
    ['n' => 'Incendios forestales',      'on' => true],
    ['n' => 'Inundaciones',              'on' => true],
    ['n' => 'Deslizamientos',            'on' => true],
    ['n' => 'Red de comunicación',       'on' => true],
    ['n' => 'Recursos y albergues',      'on' => false],
    ['n' => 'Límites territoriales',     'on' => false],
];

try {
    require_once __DIR__ . '/db.php';
    init_db();
    $pdo = db();

    $ALERTAS = $pdo->query("SELECT id, nivel, tipo, lugar, tiempo, icon FROM alertas ORDER BY id")->fetchAll();

    $REPORTES = $pdo->query("SELECT id, titulo, lugar, tiempo, estado, icon, lat, lon, descripcion AS \"desc\" FROM reportes ORDER BY id")->fetchAll();

    $KITS = $pdo->query("SELECT id, nombre AS n, categoria AS cat, tipo, estado AS disp, ubicacion AS loc, cantidad AS cant, vencimiento AS fecha, icon, color FROM kits ORDER BY id")->fetchAll();

    $ALBERGUES = $pdo->query("SELECT nombre AS n, distancia AS loc, capacidad AS cap, estado FROM albergues ORDER BY id")->fetchAll();

    $capas_db = $pdo->query("SELECT nombre AS n, activa FROM capas ORDER BY id")->fetchAll();
    $CAPAS = array_map(function ($c) {
        return ['n' => $c['n'], 'on' => (bool) $c['activa']];
    }, $capas_db);
} catch (Throwable $e) {
}
