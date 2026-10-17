<?php
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/mesh.php';
require_once __DIR__ . '/includes/risk.php';
$active = 'mapa';
$page_title = 'Mapa de calor';

$CAPA_KEYS = [
    'Zonas de riesgo' => 'riesgo',
    'Límites territoriales' => 'limites',
];

try {
    $geo = ['reportes' => geo_reportes(), 'alertas' => geo_alertas(), 'albergues' => geo_albergues()];
    $mesh_g = mesh_grafo();
    $geo['nodos'] = $mesh_g['nodos'];
    $geo['enlaces'] = $mesh_g['enlaces'];
    $mesh_stats = mesh_stats();
    $target = null;
    if (isset($_GET['reporte'])) {
        $rep = obtener_reporte((int) $_GET['reporte']);
        if ($rep && $rep['lat'] !== null && $rep['lon'] !== null) {
            $target = ['lat' => (float) $rep['lat'], 'lon' => (float) $rep['lon'], 'label' => $rep['titulo'] . ' — ' . $rep['lugar'], 'id' => (int) ($rep['id'] ?? 0), 'kind' => 'reporte'];
        }
    } elseif (isset($_GET['alerta'])) {
        $aid = (int) $_GET['alerta'];
        foreach ($geo['alertas'] as $ga) {
            if ((int) $ga['id'] === $aid) {
                $target = ['lat' => (float) $ga['lat'], 'lon' => (float) $ga['lon'], 'label' => $ga['tipo'] . ' — ' . $ga['lugar'], 'id' => (int) $ga['id'], 'kind' => 'alerta'];
                break;
            }
        }
    }
} catch (Throwable $e) {
    $geo = ['reportes' => [], 'alertas' => [], 'albergues' => [], 'nodos' => [], 'enlaces' => []];
    $mesh_stats = ['total' => 0, 'activos' => 0, 'enlaces' => 0, 'aislados' => 0, 'rango' => 60];
    $target = null;
}

require __DIR__ . '/includes/header.php';
?>

<div class="section-label" style="margin-top:4px"><i class="fa-solid fa-fire-flame-curved"></i> Mapa de calor · Vista de usuarios</div>

<div class="grid cols-3">
  <div class="stack">
    <div class="panel layer-panel">
      <div class="panel-head"><h3><i class="fa-solid fa-layer-group"></i> Capas del mapa</h3></div>
      <?php foreach ($CAPAS as $c): ?>
        <?php $lk = $CAPA_KEYS[$c['n']] ?? ''; if ($lk === '') { continue; } ?>
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
      <div class="panel-head"><h3><i class="fa-solid fa-circle-dot"></i> Filtro por radio</h3></div>
      <div class="layer">
        <span>Activar filtro</span>
        <label class="switch">
          <input type="checkbox" id="radiusToggle">
          <span class="sl"></span>
        </label>
      </div>
      <div class="field radius-field" style="margin:12px 0 8px">
        <label for="radiusRange">Radio: <strong id="radiusVal">75</strong> km</label>
        <input type="range" id="radiusRange" min="10" max="200" step="5" value="75">
      </div>
      <div class="meta-row"><i class="fa-solid fa-hand-pointer"></i> Con el filtro activo, haz clic en el mapa para fijar el centro del rango.</div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3><i class="fa-solid fa-list"></i> Leyenda</h3></div>
      <div class="legend">
        <div class="li"><span class="sw" style="background:linear-gradient(135deg,#ff2d3f,#ff7a00)"></span> Zona de alto riesgo</div>
        <div class="li"><span class="sw" style="background:#f59e0b"></span> Zona de riesgo medio</div>
        <div class="li"><span class="sw" style="background:#2ecc71"></span> Zona de riesgo bajo</div>
        <div class="li"><span class="sw" style="background:linear-gradient(90deg,#3b82f6,#f59e0b,#ff2d3f)"></span> Intensidad de calor (suma de riesgo)</div>
        <div class="li"><span class="sw" style="background:#3b82f6"></span> Límite municipal</div>
        <div class="li"><span class="ln" style="border-color:#8b5cf6"></span> Ríos</div>
        <div class="li"><span class="ln" style="border-color:#f59e0b"></span> Carreteras principales</div>
      </div>
      <div class="section-label" style="margin:14px 2px 8px"><i class="fa-solid fa-fire-flame-curved"></i> Clasificación de riesgo (0.1 – 0.5)</div>
      <div class="legend">
        <?php foreach (risk_scale() as $rs): ?>
          <div class="li">
            <span class="chip risk-chip" style="--c:<?= risk_color($rs['valor']) ?>"><?= rtrim(rtrim(number_format($rs['valor'], 2, '.', ''), '0'), '.') ?></span>
            <?= htmlspecialchars($rs['label']) ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="meta-row" style="margin-top:8px"><i class="fa-solid fa-plus"></i> Los valores se suman por zona para formar el mapa de calor.</div>
    </div>

  </div>

  <div class="map-wrap">
    <div id="mapCalor" class="map"></div>
    <div class="map-search">
      <div class="input-icon">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" data-osm-search data-osm-target="mapCalor" placeholder="Buscar ubicación..." autocomplete="off">
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
          <a class="row js-alerta" data-nivel="<?= $al['nivel'] ?>" href="reportes.php?alerta=<?= (int)($al['id'] ?? 0) ?>">
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
window.MAP_ID = 'mapCalor';
window.GEO = <?= json_encode($geo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.MAP_TARGET = <?= $target ? json_encode($target, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null' ?>;
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
