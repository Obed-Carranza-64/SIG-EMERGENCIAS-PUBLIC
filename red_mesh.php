<?php
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/mesh.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nodo_id'], $_POST['nodo_estado'])) {
    try {
        init_db();
        if (actualizar_estado_nodo((int) $_POST['nodo_id'], $_POST['nodo_estado'])) {
            header('Location: red_mesh.php?ok=nodo');
        } else {
            header('Location: red_mesh.php?err=nodo');
        }
    } catch (Throwable $e) {
        header('Location: red_mesh.php?err=nodo');
    }
    exit;
}

$active = 'mapa';
$page_title = 'Red mesh';

try {
    init_db();
    $stats = mesh_stats();
    $grafo = mesh_grafo();
    $central = (int) env('MESH_NODO_CENTRAL', 1);
    $origen = isset($_GET['origen']) ? (int) $_GET['origen'] : 8;
    $destino = isset($_GET['destino']) ? (int) $_GET['destino'] : $central;
    $ruta = mesh_ruta($origen, $destino);
    $nombres = [];
    foreach ($grafo['nodos'] as $n) {
        $nombres[(int) $n['id']] = $n['nombre'];
    }
} catch (Throwable $e) {
    $stats = ['total' => 0, 'activos' => 0, 'enlaces' => 0, 'aislados' => 0, 'rango' => 60];
    $grafo = ['nodos' => [], 'enlaces' => [], 'rango' => 60];
    $origen = 0;
    $destino = 1;
    $ruta = null;
    $nombres = [];
}

require __DIR__ . '/includes/header.php';
?>

<div class="section-label" style="margin-top:4px"><i class="fa-solid fa-tower-broadcast"></i> Red mesh · Topología y enrutamiento</div>

<div class="grid stats">
  <div class="stat"><div class="ic green"><i class="fa-solid fa-tower-broadcast"></i></div><div><div class="val"><?= $stats['activos'] ?>/<?= $stats['total'] ?></div><div class="lbl">Nodos activos</div><div class="sub green"><span class="d"></span> En línea</div></div></div>
  <div class="stat"><div class="ic blue"><i class="fa-solid fa-share-nodes"></i></div><div><div class="val"><?= $stats['enlaces'] ?></div><div class="lbl">Enlaces activos</div><div class="sub blue"><span class="d"></span> Radioenlaces</div></div></div>
  <div class="stat"><div class="ic amber"><i class="fa-solid fa-ruler-horizontal"></i></div><div><div class="val"><?= $stats['rango'] ?></div><div class="lbl">Rango de enlace (km)</div><div class="sub amber"><span class="d"></span> Alcance máximo</div></div></div>
  <div class="stat"><div class="ic <?= $stats['aislados'] > 0 ? 'red' : 'purple' ?>"><i class="fa-solid fa-link-slash"></i></div><div><div class="val"><?= $stats['aislados'] ?></div><div class="lbl">Nodos aislados</div><div class="sub <?= $stats['aislados'] > 0 ? 'red' : 'purple' ?>"><span class="d"></span> Sin vecinos</div></div></div>
</div>

<div class="grid" style="grid-template-columns:1.6fr 1fr;margin-top:16px">
  <div class="map-wrap" style="min-height:520px">
    <div id="meshMap" class="map" style="min-height:520px"></div>
    <div class="map-tools">
      <button class="map-tool" data-map-action="zoom-in"><i class="fa-solid fa-plus"></i></button>
      <button class="map-tool" data-map-action="zoom-out"><i class="fa-solid fa-minus"></i></button>
      <button class="map-tool" data-map-action="theme" title="Tema claro / oscuro"><i class="fa-solid fa-circle-half-stroke"></i></button>
    </div>
    <div class="map-coords"><i class="fa-solid fa-share-nodes"></i> Haz clic en un nodo para usarlo como origen</div>
  </div>

  <div class="stack">
    <form class="panel" method="get" action="red_mesh.php">
      <div class="panel-head"><h3><i class="fa-solid fa-route"></i> Calcular ruta óptima</h3></div>
      <div class="field">
        <label for="routeOrigen">Nodo origen</label>
        <select id="routeOrigen" name="origen">
          <?php foreach ($grafo['nodos'] as $n): ?>
            <option value="<?= (int) $n['id'] ?>" <?= $origen === (int) $n['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($n['nombre']) ?> (<?= $n['estado'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="routeDestino">Nodo destino</label>
        <select id="routeDestino" name="destino">
          <?php foreach ($grafo['nodos'] as $n): ?>
            <option value="<?= (int) $n['id'] ?>" <?= $destino === (int) $n['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($n['nombre']) ?> (<?= $n['estado'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-red btn-block btn-sm" type="submit"><i class="fa-solid fa-route"></i> Calcular (Dijkstra)</button>
    </form>

    <div class="panel">
      <div class="panel-head"><h3><i class="fa-solid fa-signs-post"></i> Ruta óptima</h3><span class="chip">por distancia</span></div>
      <?php if ($ruta): ?>
        <div class="route-path">
          <?php foreach ($ruta['path'] as $i => $id): ?>
            <?php if ($i > 0): ?><i class="fa-solid fa-chevron-right"></i><?php endif; ?>
            <span class="hop <?= $i === 0 ? 'start' : '' ?> <?= $i === count($ruta['path']) - 1 ? 'end' : '' ?>"><?= htmlspecialchars($nombres[$id] ?? ('Nodo ' . $id)) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="meta-row"><i class="fa-solid fa-shuffle"></i> <?= $ruta['hops'] ?> salto(s) · <?= $ruta['km'] ?> km</div>
      <?php else: ?>
        <div class="alert-err"><i class="fa-solid fa-link-slash"></i> Sin ruta entre los nodos elegidos. Revisa nodos inactivos o fuera de alcance.</div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head"><h3><i class="fa-solid fa-list"></i> Calidad de enlace</h3></div>
      <div class="legend">
        <div class="li"><span class="ln" style="border-color:#2ecc71"></span> Fuerte (&lt; 40% del rango)</div>
        <div class="li"><span class="ln" style="border-color:#f59e0b"></span> Media (&lt; 70% del rango)</div>
        <div class="li"><span class="ln" style="border-color:#ff2d3f"></span> Débil (al límite)</div>
      </div>
    </div>
  </div>
</div>

<div class="section-label"><i class="fa-solid fa-server"></i> Nodos de la red</div>

<div class="panel">
  <div class="list">
    <?php foreach ($grafo['nodos'] as $n): ?>
      <?php $nid = (int) $n['id']; $act = $n['estado'] === 'activo'; ?>
      <div class="row">
        <div class="ic <?= $act ? 'green' : '' ?>" style="<?= $act ? '' : 'background:#2a3442;color:#8b98ab' ?>">
          <i class="fa-solid fa-tower-broadcast"></i>
        </div>
        <div class="txt">
          <strong><?= htmlspecialchars($n['nombre']) ?></strong>
          <small style="text-transform:capitalize"><?= htmlspecialchars($n['tipo']) ?> · Batería <?= (int) $n['bateria'] ?>%</small>
          <div class="batt <?= (int) $n['bateria'] < 30 ? 'low' : '' ?>"><span style="width:<?= max(0, min(100, (int) $n['bateria'])) ?>%"></span></div>
        </div>
        <span class="badge <?= $act ? 'disp' : 'uso' ?>"><?= $act ? 'Activo' : 'Inactivo' ?></span>
        <a class="btn btn-ghost btn-xs" href="red_mesh.php?origen=<?= $nid ?>"><i class="fa-solid fa-route"></i> Ruta</a>
        <form method="post" action="red_mesh.php" style="margin:0">
          <input type="hidden" name="nodo_id" value="<?= $nid ?>">
          <input type="hidden" name="nodo_estado" value="<?= $act ? 'inactivo' : 'activo' ?>">
          <button class="btn btn-xs <?= $act ? 'btn-ghost' : 'btn-red' ?>" type="submit">
            <i class="fa-solid fa-power-off"></i> <?= $act ? 'Desactivar' : 'Activar' ?>
          </button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
window.MESH = <?= json_encode(['nodos' => $grafo['nodos'], 'enlaces' => $grafo['enlaces']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.MESH_ROUTE = <?= $ruta ? json_encode($ruta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null' ?>;
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
