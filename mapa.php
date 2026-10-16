<?php
require_once __DIR__ . '/includes/data.php';
$active = 'mapa';
$page_title = 'Mapa';

$CAPA_KEYS = [
    'Zonas de riesgo' => 'riesgo',
    'Incendios forestales' => 'incendios',
    'Inundaciones' => 'inundaciones',
    'Deslizamientos' => 'deslizamientos',
    'Red de comunicación' => 'red',
    'Recursos y albergues' => 'recursos',
    'Límites territoriales' => 'limites',
];

try {
    $geo = ['reportes' => geo_reportes(), 'alertas' => geo_alertas(), 'albergues' => geo_albergues()];
    $target = null;
    if (isset($_GET['reporte'])) {
        $rep = obtener_reporte((int) $_GET['reporte']);
        if ($rep && $rep['lat'] !== null && $rep['lon'] !== null) {
            $target = ['lat' => (float) $rep['lat'], 'lon' => (float) $rep['lon'], 'label' => $rep['titulo'] . ' — ' . $rep['lugar']];
        }
    } elseif (isset($_GET['alerta'])) {
        $aid = (int) $_GET['alerta'];
        foreach ($geo['alertas'] as $ga) {
            if ((int) $ga['id'] === $aid) {
                $target = ['lat' => (float) $ga['lat'], 'lon' => (float) $ga['lon'], 'label' => $ga['tipo'] . ' — ' . $ga['lugar']];
                break;
            }
        }
    }
} catch (Throwable $e) {
    $geo = ['reportes' => [], 'alertas' => [], 'albergues' => []];
    $target = null;
}

require __DIR__ . '/includes/header.php';
?>

<div class="grid cols-3">
  <div class="stack">
    <div class="panel layer-panel">
      <div class="panel-head"><h3><i class="fa-solid fa-layer-group"></i> Capas del mapa</h3></div>
      <?php foreach ($CAPAS as $c): ?>
        <?php $lk = $CAPA_KEYS[$c['n']] ?? ''; ?>
        <div class="layer" style="opacity:<?= $c['on'] ? '1' : '.55' ?>">
          <span><?= htmlspecialchars($c['n']) ?></span>
          <label class="switch">
            <input type="checkbox" data-layer="<?= $lk ?>" <?= $c['on'] ? 'checked' : '' ?>>
            <span class="sl"></span>
          </label>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><h3><i class="fa-solid fa-list"></i> Leyenda</h3></div>
      <div class="legend">
        <div class="li"><span class="sw" style="background:linear-gradient(135deg,#ff2d3f,#ff7a00)"></span> Zona de alto riesgo</div>
        <div class="li"><span class="sw" style="background:#f59e0b"></span> Zona de riesgo medio</div>
        <div class="li"><span class="sw" style="background:#2ecc71"></span> Zona de riesgo bajo</div>
        <div class="li"><span class="sw" style="background:#3b82f6"></span> Límite municipal</div>
        <div class="li"><span class="ln" style="border-color:#8b5cf6"></span> Ríos</div>
        <div class="li"><span class="ln" style="border-color:#f59e0b"></span> Carreteras principales</div>
      </div>
      <div class="section-label" style="margin:14px 2px 8px"><i class="fa-solid fa-shapes"></i> Simbología</div>
      <div class="legend">
        <div class="li"><span class="sig-prev" style="--c:#ff2d3f"><i class="fa-solid fa-fire"></i></span> Incendio forestal</div>
        <div class="li"><span class="sig-prev" style="--c:#3b82f6"><i class="fa-solid fa-water"></i></span> Inundación</div>
        <div class="li"><span class="sig-prev" style="--c:#f59e0b"><i class="fa-solid fa-hill-rockslide"></i></span> Deslizamiento</div>
        <div class="li"><span class="sig-prev" style="--c:#2ecc71"><i class="fa-solid fa-tower-broadcast"></i></span> Nodo de red (activo)</div>
        <div class="li"><span class="sig-prev" style="--c:#8b5cf6"><i class="fa-solid fa-house-chimney"></i></span> Albergue / recurso</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3><i class="fa-solid fa-tower-broadcast"></i> Estado de la red mesh</h3></div>
      <div class="meta-row"><span class="dot-g"></span> <strong style="color:#8ff0b4">15 / 15 nodos activos</strong></div>
    </div>
  </div>

  <div class="map-wrap">
    <div id="mapFull" class="map"></div>
    <div class="map-search">
      <div class="input-icon">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" data-osm-search data-osm-target="mapFull" placeholder="Buscar ubicación..." autocomplete="off">
      </div>
      <div class="osm-results"></div>
    </div>
    <div class="map-tools">
      <button class="map-tool" data-map-action="zoom-in"><i class="fa-solid fa-plus"></i></button>
      <button class="map-tool" data-map-action="zoom-out"><i class="fa-solid fa-minus"></i></button>
      <button class="map-tool" data-map-action="locate"><i class="fa-solid fa-crosshairs"></i></button>
      <button class="map-tool" data-map-action="theme" title="Tema claro / oscuro"><i class="fa-solid fa-circle-half-stroke"></i></button>
      <button class="map-tool" data-map-action="expand"><i class="fa-solid fa-expand"></i></button>
    </div>
    <div class="map-coords"><i class="fa-solid fa-location-crosshairs"></i> Haz clic en el mapa para ver la dirección</div>
  </div>

  <div class="stack">
    <div class="panel">
      <div class="panel-head">
        <h3><i class="fa-solid fa-bell"></i> Alertas en tiempo real</h3>
        <span class="badge alta">3 nuevas</span>
      </div>
      <div class="tabs" id="alertTabs" style="margin-bottom:12px">
        <button class="tab is-active" type="button" data-anivel="all">Todo</button>
        <button class="tab" type="button" data-anivel="alta">Emergencias</button>
        <button class="tab" type="button" data-anivel="info">Información</button>
      </div>
      <div class="list">
        <?php foreach ($ALERTAS as $al): ?>
          <a class="row js-alerta" data-nivel="<?= $al['nivel'] ?>" href="mapa.php?alerta=<?= (int)($al['id'] ?? 0) ?>">
            <div class="ic <?= $al['nivel'] === 'baja' ? 'green' : ($al['nivel'] === 'media' ? 'amber' : 'red') ?>"><i class="fa-solid <?= htmlspecialchars($al['icon']) ?>"></i></div>
            <div class="txt"><strong><?= htmlspecialchars($al['tipo']) ?></strong><small><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($al['lugar']) ?></small></div>
            <span class="time"><?= htmlspecialchars($al['tiempo']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3><i class="fa-solid fa-bullhorn"></i> Reportes ciudadanos</h3>
        <a class="link" href="reportes.php">Ver todos</a>
      </div>
      <div class="list">
        <?php foreach (array_slice($REPORTES, 0, 3) as $r): ?>
          <a class="row" href="reportes.php?ver=<?= (int)($r['id'] ?? 0) ?>">
            <div class="ic blue"><i class="fa-solid <?= htmlspecialchars($r['icon']) ?>"></i></div>
            <div class="txt"><strong><?= htmlspecialchars($r['titulo']) ?></strong><small><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($r['lugar']) ?></small></div>
            <span class="time"><?= htmlspecialchars($r['tiempo']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <a class="btn btn-red btn-block" href="reportar.php"><i class="fa-solid fa-triangle-exclamation"></i> Reportar emergencia</a>
  </div>
</div>

<div class="section-label"><i class="fa-solid fa-gauge-high"></i> Resumen operativo</div>

<div class="grid" style="grid-template-columns:1.1fr 1.3fr 1.3fr;">
  <div class="panel">
    <div class="panel-head"><h3><i class="fa-solid fa-chart-simple"></i> Resumen de emergencia</h3></div>
    <div class="list">
      <?php foreach ($RECURSOS_RESUMEN as $r): ?>
        <div class="row">
          <div class="ic <?= $r['color'] ?>"><strong style="font-size:16px"><?= $r['valor'] ?></strong></div>
          <div class="txt"><strong><?= $r['label'] ?></strong></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3><i class="fa-solid fa-house-chimney"></i> Recursos y albergues cercanos</h3><a class="link" href="recursos.php">Ver todos</a></div>
    <div class="list">
      <?php foreach ($ALBERGUES as $a): ?>
        <div class="row">
          <div class="ic blue"><i class="fa-solid fa-bed"></i></div>
          <div class="txt"><strong><?= htmlspecialchars($a['n']) ?></strong><small><?= htmlspecialchars($a['cap']) ?></small></div>
          <div style="text-align:right"><div class="time"><?= htmlspecialchars($a['loc']) ?></div><span class="badge disp"><?= htmlspecialchars($a['estado']) ?></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3><i class="fa-solid fa-satellite"></i> Últimos reportes en el mapa</h3><a class="link" href="reportes.php">Ver todos</a></div>
    <div class="list">
      <?php foreach (array_slice($REPORTES, 1, 3) as $r): ?>
        <a class="row" href="reportes.php?ver=<?= (int)($r['id'] ?? 0) ?>">
          <div class="ic red"><i class="fa-solid <?= htmlspecialchars($r['icon']) ?>"></i></div>
          <div class="txt"><strong><?= htmlspecialchars($r['titulo']) ?></strong><small><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($r['lugar']) ?></small></div>
          <span class="time"><?= htmlspecialchars($r['tiempo']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
window.GEO = <?= json_encode($geo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.MAP_TARGET = <?= $target ? json_encode($target, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null' ?>;
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
