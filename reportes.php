<?php
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/mesh.php';
require_once __DIR__ . '/includes/sismos.php';

function estado_key($e) {
    return ['Nuevo' => 'nuevo', 'En revisión' => 'revision', 'En atención' => 'atencion', 'Resuelto' => 'resuelto'][$e] ?? 'nuevo';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reporte_id'], $_POST['nuevo_estado'])) {
    try {
        init_db();
        $rid = (int) $_POST['reporte_id'];
        if (actualizar_estado_reporte($rid, $_POST['nuevo_estado'])) {
            header('Location: reportes.php?ver=' . $rid . '&ok=estado');
        } else {
            header('Location: reportes.php?ver=' . $rid . '&err=estado');
        }
    } catch (Throwable $e) {
        header('Location: reportes.php?err=estado');
    }
    exit;
}

$active = 'reportes';
$page_title = 'Reportes Ciudadanos';

$CAPA_KEYS = [
    'Incendios forestales' => 'incendios',
    'Inundaciones' => 'inundaciones',
    'Deslizamientos' => 'deslizamientos',
    'Sismos' => 'sismos',
    'Red de comunicación' => 'red',
    'Recursos y albergues' => 'recursos',
    'Límites territoriales' => 'limites',
];

try {
    if (sismos_autosync()) {
        try {
            sincronizar_sismos(false, 4);
        } catch (Throwable $e) {
        }
    }

    $lista_reportes = ultimos_reportes(10);
    $conteo = ['todos' => 0, 'nuevo' => 0, 'revision' => 0, 'atencion' => 0, 'resuelto' => 0];
    foreach ($lista_reportes as $rr) {
        $conteo['todos']++;
        $kk = estado_key($rr['estado']);
        if (isset($conteo[$kk])) {
            $conteo[$kk]++;
        }
    }
    $ver = isset($_GET['ver']) ? (int) $_GET['ver'] : 0;
    $destacado = $ver > 0 ? obtener_reporte($ver) : null;
    if (!$destacado) {
        $destacado = reporte_destacado();
    }

    $geo = ['reportes' => geo_reportes(), 'alertas' => geo_alertas(), 'albergues' => geo_albergues()];
    $mesh_g = mesh_grafo();
    $geo['nodos'] = $mesh_g['nodos'];
    $geo['enlaces'] = $mesh_g['enlaces'];

    $ultimas_alertas = ultimas_alertas(10);

    $target = null;
    if (isset($_GET['alerta'])) {
        $aid = (int) $_GET['alerta'];
        foreach ($geo['alertas'] as $ga) {
            if ((int) $ga['id'] === $aid) {
                $target = ['lat' => (float) $ga['lat'], 'lon' => (float) $ga['lon'], 'label' => $ga['tipo'] . ' — ' . $ga['lugar'], 'id' => (int) $ga['id'], 'kind' => 'alerta'];
                break;
            }
        }
    }
    if (!$target && $ver > 0 && $destacado && $destacado['lat'] !== null && $destacado['lon'] !== null) {
        $target = ['lat' => (float) $destacado['lat'], 'lon' => (float) $destacado['lon'], 'label' => $destacado['titulo'] . ' — ' . $destacado['lugar'], 'id' => (int) ($destacado['id'] ?? 0), 'kind' => 'reporte'];
    }
} catch (Throwable $e) {
    $conteo = ['todos' => 0, 'nuevo' => 0, 'revision' => 0, 'atencion' => 0, 'resuelto' => 0];
    $lista_reportes = [];
    $destacado = null;
    $geo = ['reportes' => [], 'alertas' => [], 'albergues' => [], 'nodos' => [], 'enlaces' => []];
    $target = null;
    $ultimas_alertas = [];
}
if (!$destacado && !empty($lista_reportes)) {
    $destacado = $lista_reportes[0] + ['id' => 0, 'lat' => null, 'lon' => null];
}
$imagenes = [];
if (!empty($destacado['id'])) {
    try {
        $imagenes = imagenes_reporte($destacado['id']);
    } catch (Throwable $e) {
        $imagenes = [];
    }
}
$dest_badge = estado_key($destacado['estado'] ?? 'Nuevo');
$estados_btn = ['En revisión', 'En atención', 'Resuelto'];

require __DIR__ . '/includes/header.php';
?>

<div class="grid" style="grid-template-columns:250px 1fr;">
  <aside class="stack">
    <div class="panel">
      <div class="side-nav">
        <a href="index.php"><i class="fa-solid fa-house"></i> Inicio</a>
        <a href="mapa.php"><i class="fa-solid fa-fire-flame-curved"></i> Mapa de calor</a>
        <a class="is-active" href="reportes.php"><i class="fa-solid fa-triangle-exclamation"></i> Reportes Ciudadanos</a>
        <a href="recursos.php"><i class="fa-solid fa-toolbox"></i> Kits y Recursos</a>
        <a href="coordinacion.php"><i class="fa-solid fa-diagram-project"></i> Coordinación</a>
      </div>
      <a class="btn btn-red btn-block" style="margin-top:14px" href="reportar.php"><i class="fa-solid fa-triangle-exclamation"></i> Reportar emergencia</a>
    </div>
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
    <div class="panel" style="background:linear-gradient(180deg,rgba(226,31,46,.16),var(--panel-2));border-color:rgba(255,45,63,.3)">
      <div style="display:flex;gap:12px">
        <div class="ic red" style="width:40px;height:40px;border-radius:11px;display:grid;place-items:center"><i class="fa-solid fa-shield-heart"></i></div>
        <div><strong style="font-size:13px">Tu reporte puede salvar vidas</strong>
        <p style="color:var(--muted);font-size:12px;margin:6px 0 0">Comparte información clara, precisa, con ubicación e fotografías.</p></div>
      </div>
    </div>
  </aside>

  <div class="stack">
    <div class="panel">
      <div class="panel-head" style="margin-bottom:0">
        <div>
          <h3 style="font-size:20px"><i class="fa-solid fa-bullhorn"></i> Reportes Ciudadanos</h3>
          <span style="color:var(--muted);font-size:12.5px">Información en tiempo real de la comunidad</span>
        </div>
        <span class="chip"><span class="dot-g"></span> En vivo</span>
      </div>
    </div>

    <div class="grid cols-report">
      <div class="panel" style="align-self:start">
        <div class="panel-head">
          <h3><i class="fa-solid fa-bell"></i> Últimas alertas</h3>
          <div style="display:flex;gap:8px;align-items:center">
            <span class="chip"><span class="dot-g"></span> En vivo</span>
            <span class="badge alta" id="alertNewBadge" style="display:none">0 nuevas</span>
          </div>
        </div>
        <div class="list" id="alertList" style="max-height:calc(100vh - 300px);min-height:220px;overflow:auto;padding-right:2px">
          <?php foreach ($ultimas_alertas as $al): ?>
            <a class="row js-alerta" data-nivel="<?= htmlspecialchars($al['nivel']) ?>" data-alerta-id="<?= (int)($al['id'] ?? 0) ?>" href="reportes.php?alerta=<?= (int)($al['id'] ?? 0) ?>">
              <div class="ic <?= $al['nivel'] === 'baja' ? 'green' : ($al['nivel'] === 'media' ? 'amber' : 'red') ?>"><i class="fa-solid <?= htmlspecialchars($al['icon']) ?>"></i></div>
              <div class="txt"><strong><?= htmlspecialchars($al['tipo']) ?></strong><small><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($al['lugar']) ?></small></div>
              <span class="time"><?= htmlspecialchars($al['tiempo']) ?></span>
            </a>
          <?php endforeach; ?>
          <?php if (empty($ultimas_alertas)): ?>
            <div class="empty"><i class="fa-solid fa-bell-slash"></i><span>Sin alertas recientes</span></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="map-wrap" style="align-self:start;height:calc(100vh - 220px);min-height:520px">
        <div id="mapReportes" class="map" style="min-height:520px"></div>
        <div class="map-search">
          <div class="input-icon">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" data-osm-search data-osm-target="mapReportes" placeholder="Buscar ubicación..." autocomplete="off">
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
        <div class="map-coords"><i class="fa-solid fa-map"></i> Haz clic en el mapa para ver la dirección</div>
      </div>

      <div class="panel r-detail">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <span class="badge <?= $dest_badge ?>"><?= htmlspecialchars($destacado['estado']) ?></span>
          <span class="time">ID: #CC-<?= 4500 + (int) ($destacado['id'] ?? 0) ?></span>
        </div>
        <div class="row" style="background:linear-gradient(135deg,rgba(226,31,46,.18),var(--panel-2));border-color:rgba(255,45,63,.3)">
          <div class="ic red"><i class="fa-solid <?= htmlspecialchars($destacado['icon']) ?>"></i></div>
          <div class="txt"><strong><?= htmlspecialchars($destacado['titulo']) ?></strong><small><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($destacado['lugar']) ?></small></div>
        </div>
        <div class="meta-row" style="margin:12px 0 0"><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($destacado['tiempo']) ?></div>
        <p style="color:var(--muted);font-size:12.5px;margin:10px 0 14px"><?= htmlspecialchars($destacado['desc']) ?></p>
        <div class="meta-row" style="margin-bottom:6px"><i class="fa-solid fa-image"></i> Imágenes adjuntas (<?= count($imagenes) ?>)</div>
        <?php if ($imagenes): ?>
          <div class="thumbs">
            <?php foreach ($imagenes as $im): ?>
              <a class="t" href="<?= htmlspecialchars(upload_url($im['archivo'])) ?>" target="_blank" rel="noopener" title="Adjuntada <?= htmlspecialchars($im['creado']) ?>">
                <img src="<?= htmlspecialchars(upload_url($im['archivo'])) ?>" alt="Imagen adjunta del reporte" loading="lazy">
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="meta-row" style="color:var(--muted)"><i class="fa-solid fa-image"></i> Sin imágenes adjuntas.</div>
        <?php endif; ?>
        <div class="meta-row" style="margin:16px 0 10px;justify-content:space-between">
          <span><i class="fa-solid fa-user-shield"></i> Reportado por: Ciudadano anónimo</span>
        </div>
        <div class="meta-row" style="margin-bottom:14px"><i class="fa-solid fa-circle-check" style="color:var(--green-2)"></i> Verificado · Ubicación confirmada</div>

        <form method="post" action="reportes.php?ver=<?= (int)($destacado['id'] ?? 0) ?>">
          <input type="hidden" name="reporte_id" value="<?= (int)($destacado['id'] ?? 0) ?>">
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach ($estados_btn as $eb): ?>
              <button class="btn btn-sm <?= $destacado['estado'] === $eb ? 'btn-red' : 'btn-ghost' ?>" type="submit" name="nuevo_estado" value="<?= $eb ?>" style="flex:1">
                <?= $eb ?>
              </button>
            <?php endforeach; ?>
          </div>
        </form>

        <?php if (!empty($destacado['lat']) && !empty($destacado['lon'])): ?>
          <a class="btn btn-ghost btn-block" style="margin-top:10px" href="reportes.php?ver=<?= (int)($destacado['id'] ?? 0) ?>"><i class="fa-solid fa-map"></i> Centrar en el mapa</a>
        <?php endif; ?>
        </div>
    </div>
  </div>
</div>

<script>
window.MAP_ID = 'mapReportes';
window.SISMOS_LIVE = true;
window.SISMOS_URL = 'api/sismos.php';
window.SISMOS_POLL = 90000;
window.GEO = <?= json_encode($geo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.MAP_TARGET = <?= $target ? json_encode($target, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null' ?>;
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
