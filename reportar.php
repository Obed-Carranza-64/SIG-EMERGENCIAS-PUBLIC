<?php
require_once __DIR__ . '/includes/data.php';
$active = 'reportes';
$page_title = 'Reportar emergencia';

$TIPOS = [
    'Deslizamiento de tierra' => 'fa-hill-rockslide',
    'Inundación' => 'fa-water',
    'Incendio forestal' => 'fa-fire',
    'Carretera bloqueada' => 'fa-road',
    'Lluvias intensas' => 'fa-cloud-showers-heavy',
    'Árbol caído' => 'fa-tree',
    'Vivienda dañada' => 'fa-house-crack',
    'Otro' => 'fa-triangle-exclamation',
];

$error = '';
$old = ['tipo' => 'Inundación', 'lugar' => '', 'descripcion' => '', 'lat' => (string) env('MAP_CENTER_LAT', 13.7942), 'lon' => (string) env('MAP_CENTER_LON', -88.8965)];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['tipo'] = $_POST['tipo'] ?? 'Inundación';
    $old['lugar'] = trim($_POST['lugar'] ?? '');
    $old['descripcion'] = trim($_POST['descripcion'] ?? '');
    $old['lat'] = $_POST['lat'] ?? (string) env('MAP_CENTER_LAT', 13.7942);
    $old['lon'] = $_POST['lon'] ?? (string) env('MAP_CENTER_LON', -88.8965);

    if (!isset($TIPOS[$old['tipo']])) {
        $old['tipo'] = 'Otro';
    }
    if ($old['lugar'] === '' || $old['descripcion'] === '') {
        $error = 'Completa la ubicación y la descripción del evento.';
    } else {
        try {
            init_db();
            crear_reporte([
                'titulo' => $old['tipo'],
                'lugar' => $old['lugar'],
                'descripcion' => $old['descripcion'],
                'icon' => $TIPOS[$old['tipo']],
                'lat' => $old['lat'],
                'lon' => $old['lon'],
            ]);
            header('Location: reportes.php?ok=creado');
            exit;
        } catch (Throwable $e) {
            $error = 'No se pudo guardar el reporte. Intenta de nuevo.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="section-label" style="margin-top:4px"><i class="fa-solid fa-triangle-exclamation"></i> Nuevo reporte ciudadano</div>

<div class="grid" style="grid-template-columns:1fr 1.2fr;">
  <form class="panel" method="post" action="reportar.php">
    <div class="panel-head"><h3><i class="fa-solid fa-bullhorn"></i> Describe la emergencia</h3></div>

    <?php if ($error): ?>
      <div class="alert-err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="field">
      <label for="fTipo">Tipo de evento</label>
      <select id="fTipo" name="tipo">
        <?php foreach ($TIPOS as $t => $ic): ?>
          <option value="<?= htmlspecialchars($t) ?>" <?= $old['tipo'] === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="fLugar">Ubicación (municipio, referencia)</label>
      <input id="fLugar" type="text" name="lugar" maxlength="120" required
             placeholder="Ej.: Santa Tecla, La Libertad" value="<?= htmlspecialchars($old['lugar']) ?>">
    </div>

    <div class="field">
      <label for="fDesc">Descripción</label>
      <textarea id="fDesc" name="descripcion" rows="4" maxlength="1000" required
                placeholder="¿Qué ocurre? ¿Hay personas afectadas? ¿Vías bloqueadas?"><?= htmlspecialchars($old['descripcion']) ?></textarea>
    </div>

    <input type="hidden" id="fLat" name="lat" value="<?= htmlspecialchars($old['lat']) ?>">
    <input type="hidden" id="fLon" name="lon" value="<?= htmlspecialchars($old['lon']) ?>">

    <div style="display:flex;gap:10px;margin-top:4px">
      <button class="btn btn-red" type="submit" style="flex:1"><i class="fa-solid fa-paper-plane"></i> Enviar reporte</button>
      <a class="btn btn-ghost" href="reportes.php">Cancelar</a>
    </div>
  </form>

  <div class="panel">
    <div class="panel-head">
      <h3><i class="fa-solid fa-map-location-dot"></i> Marca el punto en el mapa</h3>
      <span class="chip"><i class="fa-solid fa-location-dot"></i> <span id="pickerLabel"><?= htmlspecialchars($old['lat']) ?>, <?= htmlspecialchars($old['lon']) ?></span></span>
    </div>
    <div class="map-wrap" style="min-height:380px">
      <div id="pickerMap" class="map" style="min-height:380px"></div>
      <div class="map-coords"><i class="fa-solid fa-hand-pointer"></i> Haz clic para ubicar el evento</div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
