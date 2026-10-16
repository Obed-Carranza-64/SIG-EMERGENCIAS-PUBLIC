<?php
require_once __DIR__ . '/includes/data.php';
$active = 'inicio';
$page_title = 'Inicio';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div id="mapInicio" class="hero-map" aria-hidden="true"></div>
  <div class="hero-heat"></div>
  <div class="hero-heat b"></div>
  <div class="hero-content">
    <div class="hero-badge"><i class="fa-solid fa-satellite-dish"></i> Monitoreo · Alertas · Coordinación</div>
    <h1>SIG - <span>Emergencia</span></h1>
    <div class="tagline">Decisiones rápidas para salvar vidas</div>
    <p>Visualiza zonas de riesgo, monitorea la red de comunicación y coordina recursos en tiempo real ante emergencias.</p>
    <div class="hero-actions">
      <a class="btn btn-red" href="reportar.php"><i class="fa-solid fa-triangle-exclamation"></i> Reportar emergencia</a>
      <a class="btn btn-ghost" href="mapa.php"><i class="fa-solid fa-map"></i> Ver mapa</a>
    </div>
  </div>
  <small class="hero-attr">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors</small>
  <div class="hero-legend">
    <span><b class="sig-prev" style="--c:#ff2d3f"><i class="fa-solid fa-triangle-exclamation"></i></b> Zona de riesgo</span>
    <span><b class="sig-prev" style="--c:#2ecc71"><i class="fa-solid fa-tower-broadcast"></i></b> Nodo de red (activo)</span>
    <span><b class="sig-prev" style="--c:#3b82f6"><i class="fa-solid fa-circle-nodes"></i></b> Nodos de red (activo)</span>
    <span><b class="sig-prev" style="--c:#8b5cf6"><i class="fa-solid fa-satellite"></i></b> Zona de comunicación</span>
    <span><b class="sig-prev" style="--c:#f59e0b"><i class="fa-solid fa-share-nodes"></i></b> Conexión mesh</span>
  </div>
</section>

<div class="grid stats">
  <?php foreach ($STATS_INICIO as $s): ?>
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

<div class="section-label"><i class="fa-solid fa-bolt"></i> Accesos rápidos</div>

<div class="grid cards-2">
  <div class="card">
    <div class="ic"><i class="fa-solid fa-map-location-dot"></i></div>
    <h4>Mapa de zonas de riesgo</h4>
    <p>Visualiza incidentes, áreas de riesgo y recursos disponibles.</p>
    <a class="btn btn-sm btn-red" href="mapa.php" style="align-self:flex-start"><i class="fa-solid fa-up-right-from-square"></i> Explorar mapa</a>
  </div>

  <?php foreach (array_slice($ACCESOS, 1) as $a): ?>
    <a class="card" href="<?= $a['href'] ?>">
      <div class="ic"><i class="fa-solid <?= $a['icon'] ?>"></i></div>
      <h4><?= $a['title'] ?></h4>
      <p><?= $a['desc'] ?></p>
      <span class="cta"><?= $a['cta'] ?: 'Ver más' ?> <i class="fa-solid fa-arrow-right"></i></span>
    </a>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
