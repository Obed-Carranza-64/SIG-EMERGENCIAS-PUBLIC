<?php
require_once __DIR__ . '/includes/data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kit_id'], $_POST['kit_accion'])) {
    try {
        init_db();
        $kid = (int) $_POST['kit_id'];
        $nuevo = $_POST['kit_accion'] === 'liberar' ? 'Disponible' : 'En uso';
        if (actualizar_estado_kit($kid, $nuevo)) {
            header('Location: recursos.php?ok=' . ($nuevo === 'En uso' ? 'asignado' : 'liberado'));
        } else {
            header('Location: recursos.php?err=kit');
        }
    } catch (Throwable $e) {
        header('Location: recursos.php?err=kit');
    }
    exit;
}

$active = 'recursos';
$page_title = 'Kits y Recursos';

$f_tipo = $_GET['tipo'] ?? 'Todos';
$f_estado = $_GET['estado'] ?? 'Todos';
$f_ubi = $_GET['ubicacion'] ?? 'Todos';

$KITS_FILTRADOS = array_values(array_filter($KITS, function ($k) use ($f_tipo, $f_estado, $f_ubi) {
    if ($f_tipo !== 'Todos' && ($k['cat'] ?? '') !== $f_tipo) {
        return false;
    }
    if ($f_estado !== 'Todos' && ($k['disp'] ?? '') !== $f_estado) {
        return false;
    }
    if ($f_ubi !== 'Todos' && ($k['loc'] ?? '') !== $f_ubi) {
        return false;
    }
    return true;
}));

$UBICACIONES = array_values(array_unique(array_map(function ($k) {
    return $k['loc'] ?? '';
}, $KITS)));
sort($UBICACIONES);

$resumen_kits = [
  ['valor' => '24', 'label' => 'Kits de emergencia', 'sub' => '7 en uso - 8 en inventario', 'icon' => 'fa-briefcase-medical', 'color' => 'red'],
  ['valor' => '18', 'label' => 'Vehículos',          'sub' => '7 en uso - 9 en inventario', 'icon' => 'fa-truck', 'color' => 'blue'],
  ['valor' => '7',  'label' => 'Maquinaria pesada',  'sub' => '6 en uso - 8 en inventario', 'icon' => 'fa-tractor', 'color' => 'purple'],
  ['valor' => '8',  'label' => 'Albergues',          'sub' => '3 en uso - 8 disponibles',    'icon' => 'fa-house-chimney', 'color' => 'green'],
];
try {
    $ck = conteo_kits();
} catch (Throwable $e) {
    $ck = ['total' => 0];
}
$kits_tabs = [
    ['label' => 'Todas las kits', 'cat' => 'todas', 'n' => $ck['total'] ?? 0],
    ['label' => 'Kits de asistencia humanitaria', 'cat' => 'Humanitario', 'n' => $ck['Humanitario'] ?? 0],
    ['label' => 'Kits médicos', 'cat' => 'Médico', 'n' => $ck['Médico'] ?? 0],
    ['label' => 'Kits de rescate', 'cat' => 'Rescate', 'n' => $ck['Rescate'] ?? 0],
    ['label' => 'Kits de comunicación', 'cat' => 'Comunicación', 'n' => $ck['Comunicación'] ?? 0],
];

require __DIR__ . '/includes/header.php';

function kit_badge($disp) {
    return ['Disponible' => 'disp', 'En uso' => 'uso', 'Mantenimiento' => 'revision'][$disp] ?? 'disp';
}
?>

<div class="grid" style="grid-template-columns:250px 1fr;">
  <aside class="stack">
    <div class="panel">
      <div class="section-label" style="margin-top:0"><i class="fa-solid fa-chart-pie"></i> Resumen</div>
      <div class="side-nav">
        <a class="is-active" href="recursos.php"><i class="fa-solid fa-briefcase-medical"></i> Kits de emergencia</a>
        <a href="recursos.php?estado=Disponible"><i class="fa-solid fa-boxes-stacked"></i> Recursos disponibles</a>
        <a href="recursos.php"><i class="fa-solid fa-warehouse"></i> Inventario</a>
        <a href="recursos.php?estado=En+uso"><i class="fa-solid fa-file-lines"></i> Solicitudes</a>
        <a href="recursos.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
      </div>
    </div>

    <form class="panel" method="get" action="recursos.php">
      <div class="panel-head"><h3><i class="fa-solid fa-sliders"></i> Filtros</h3></div>
      <div class="field">
        <label for="fTipoKit">Tipo de kit</label>
        <select id="fTipoKit" name="tipo">
          <?php foreach (array_merge(['Todos'], ['Médico', 'Humanitario', 'Rescate', 'Comunicación', 'Albergue', 'Energía']) as $o): ?>
            <option <?= $f_tipo === $o ? 'selected' : '' ?>><?= htmlspecialchars($o) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="fEstadoKit">Estado</label>
        <select id="fEstadoKit" name="estado">
          <?php foreach (['Todos', 'Disponible', 'En uso', 'Mantenimiento'] as $o): ?>
            <option <?= $f_estado === $o ? 'selected' : '' ?>><?= htmlspecialchars($o) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="fUbiKit">Ubicación</label>
        <select id="fUbiKit" name="ubicacion">
          <option <?= $f_ubi === 'Todos' ? 'selected' : '' ?>>Todos</option>
          <?php foreach ($UBICACIONES as $o): ?>
            <option <?= $f_ubi === $o ? 'selected' : '' ?>><?= htmlspecialchars($o) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:8px">
        <button class="btn btn-red btn-sm" type="submit" style="flex:1"><i class="fa-solid fa-filter"></i> Aplicar</button>
        <a class="btn btn-ghost btn-sm" href="recursos.php" style="flex:1"><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
      </div>
    </form>

    <div class="panel" style="background:linear-gradient(180deg,rgba(226,31,46,.16),var(--panel-2));border-color:rgba(255,45,63,.3)">
      <div style="display:flex;gap:12px">
        <div class="ic red" style="width:40px;height:40px;border-radius:11px;display:grid;place-items:center"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div><strong style="font-size:13px">Monitorea los kits actualizados</strong>
        <p style="color:var(--muted);font-size:12px;margin:6px 0 0">Solicita kits según la oportunidad.</p></div>
      </div>
    </div>
  </aside>

  <div class="stack">
    <div class="panel">
      <div class="panel-head" style="margin-bottom:4px">
        <div>
          <h3 style="font-size:20px"><i class="fa-solid fa-toolbox"></i> Kits y Recursos</h3>
          <span style="color:var(--muted);font-size:12.5px">Gestión de kits de emergencia y recursos disponibles para la atención de desastres.</span>
        </div>
        <div style="display:flex;gap:10px">
          <div class="input-icon"><i class="fa-solid fa-magnifying-glass"></i><input type="text" data-search=".js-kit" placeholder="Buscar kit, recurso o ubicación..."></div>
          <a class="btn btn-red btn-sm" href="registrar_kit.php"><i class="fa-solid fa-plus"></i> Registrar kit / recurso</a>
        </div>
      </div>
    </div>

    <div class="grid stats" style="grid-template-columns:repeat(4,1fr)">
      <?php foreach ($resumen_kits as $s): ?>
        <div class="stat">
          <div class="ic <?= $s['color'] ?>"><i class="fa-solid <?= $s['icon'] ?>"></i></div>
          <div>
            <div class="val"><?= $s['valor'] ?></div>
            <div class="lbl"><?= $s['label'] ?></div>
            <div class="sub <?= $s['color'] ?>"><span class="d"></span> <?= $s['sub'] ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel">
      <div class="tabs" id="kitTabs">
        <?php $first = true; foreach ($kits_tabs as $t): ?>
          <button class="tab <?= $first ? 'is-active' : '' ?>" type="button" data-kcat="<?= htmlspecialchars($t['cat']) ?>"><?= htmlspecialchars($t['label']) ?> <span class="count"><?= (int)$t['n'] ?></span></button>
        <?php $first = false; endforeach; ?>
      </div>
    </div>

    <div class="kit-grid">
      <?php if (!$KITS_FILTRADOS): ?>
        <div class="panel" style="grid-column:1/-1;text-align:center;color:var(--muted)">
          <i class="fa-solid fa-box-open" style="font-size:26px;margin-bottom:8px"></i>
          <div>Sin resultados para los filtros aplicados.</div>
          <a class="btn btn-ghost btn-sm" style="margin-top:10px" href="recursos.php"><i class="fa-solid fa-rotate-left"></i> Limpiar filtros</a>
        </div>
      <?php endif; ?>
      <?php foreach ($KITS_FILTRADOS as $k): ?>
        <div class="kit js-kit" data-cat="<?= htmlspecialchars($k['cat']) ?>">
          <div class="top">
            <div class="ic <?= $k['color'] ?>"><i class="fa-solid <?= htmlspecialchars($k['icon']) ?>"></i></div>
            <div style="flex:1">
              <h4><?= htmlspecialchars($k['n']) ?></h4>
              <div class="cat"><?= htmlspecialchars($k['cat']) ?> · <?= htmlspecialchars($k['tipo']) ?></div>
            </div>
            <span class="badge <?= kit_badge($k['disp']) ?>"><?= htmlspecialchars($k['disp']) ?></span>
          </div>
          <div class="meta">
            <div><i class="fa-solid fa-location-dot"></i> <strong><?= htmlspecialchars($k['loc']) ?></strong></div>
            <div><i class="fa-solid fa-cubes"></i> <?= htmlspecialchars($k['cant']) ?></div>
            <div><i class="fa-solid fa-calendar-day"></i> <?= htmlspecialchars($k['fecha']) ?></div>
          </div>
          <div class="actions">
            <button class="btn btn-ghost btn-xs js-kit-detail" style="flex:1" type="button"
              data-kit="<?= htmlspecialchars(json_encode($k, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>">
              <i class="fa-solid fa-eye"></i> Ver detalles
            </button>
            <form method="post" action="recursos.php" style="flex:1;display:flex">
              <input type="hidden" name="kit_id" value="<?= (int)$k['id'] ?>">
              <?php if ($k['disp'] === 'En uso'): ?>
                <input type="hidden" name="kit_accion" value="liberar">
                <button class="btn btn-ghost btn-xs" style="flex:1" type="submit"><i class="fa-solid fa-rotate-left"></i> Liberar</button>
              <?php else: ?>
                <input type="hidden" name="kit_accion" value="asignar">
                <button class="btn btn-red btn-xs" style="flex:1" type="submit"><i class="fa-solid fa-hand-holding-medical"></i> Asignar</button>
              <?php endif; ?>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div class="meta-row"><i class="fa-solid fa-clipboard-check" style="color:var(--green-2)"></i> <strong>Estado general de kits y recursos</strong></div>
      <div style="display:flex;gap:22px;flex-wrap:wrap">
        <span class="chip"><span class="dot-g"></span> <b>68</b> disponibles</span>
        <span class="chip"><span style="width:8px;height:8px;border-radius:50%;background:var(--amber);display:inline-block"></span> <b>13</b> en uso</span>
        <span class="chip"><span style="width:8px;height:8px;border-radius:50%;background:var(--red-2);display:inline-block"></span> <b>7</b> en mantenimiento</span>
        <span class="chip"><i class="fa-solid fa-clock"></i> Última actualización <?= htmlspecialchars($APP['ultima_actualizacion']) ?></span>
      </div>
      <span style="color:var(--muted);font-size:12px;font-style:italic">"La preparación hoy, salva vidas mañana."</span>
    </div>
  </div>
</div>

<div class="modal" id="kitModal" aria-hidden="true">
  <div class="modal-box">
    <div class="panel-head">
      <h3><i class="fa-solid fa-toolbox"></i> Detalle del kit</h3>
      <button class="map-tool js-modal-close" type="button"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="kitModalBody"></div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
