<?php
require_once __DIR__ . '/includes/data.php';
$active = 'coord';
$page_title = 'Coordinación';
require __DIR__ . '/includes/header.php';
?>

<div class="section-label" style="margin-top:4px"><i class="fa-solid fa-diagram-project"></i> Coordinación interinstitucional</div>

<div class="grid stats">
  <div class="stat"><div class="ic blue"><i class="fa-solid fa-people-group"></i></div><div><div class="val">08</div><div class="lbl">Equipos desplegados</div><div class="sub blue"><span class="d"></span> En campo</div></div></div>
  <div class="stat"><div class="ic green"><i class="fa-solid fa-handshake"></i></div><div><div class="val">05</div><div class="lbl">Instituciones activas</div><div class="sub green"><span class="d"></span> Coordinando</div></div></div>
  <div class="stat"><div class="ic amber"><i class="fa-solid fa-clipboard-list"></i></div><div><div class="val">12</div><div class="lbl">Tareas asignadas</div><div class="sub amber"><span class="d"></span> En proceso</div></div></div>
  <div class="stat"><div class="ic red"><i class="fa-solid fa-bell"></i></div><div><div class="val">03</div><div class="lbl">Alertas por revisar</div><div class="sub red"><span class="d"></span> Prioridad alta</div></div></div>
</div>

<div class="grid" style="grid-template-columns:1.4fr 1fr;">
  <div class="panel">
    <div class="panel-head"><h3><i class="fa-solid fa-bell"></i> Centro de alertas y coordinación</h3><span class="chip"><span class="dot-g"></span> En línea</span></div>
    <div class="list">
      <?php foreach ($ALERTAS as $al): ?>
        <div class="row">
          <div class="ic <?= $al['nivel'] === 'baja' ? 'green' : ($al['nivel'] === 'media' ? 'amber' : 'red') ?>"><i class="fa-solid <?= htmlspecialchars($al['icon']) ?>"></i></div>
          <div class="txt"><strong><?= htmlspecialchars($al['tipo']) ?></strong><small><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($al['lugar']) ?></small></div>
          <span class="badge <?= $al['nivel'] ?>"><?= ucfirst($al['nivel']) ?></span>
          <span class="time"><?= htmlspecialchars($al['tiempo']) ?></span>
          <a class="btn btn-ghost btn-xs" href="mapa.php?alerta=<?= (int)($al['id'] ?? 0) ?>"><i class="fa-solid fa-map"></i> Ver en mapa</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="panel">
    <div class="panel-head"><h3><i class="fa-solid fa-users-gear"></i> Equipos en campo</h3></div>
    <div class="list">
      <?php foreach ($ALBERGUES as $a): ?>
        <div class="row">
          <div class="ic blue"><i class="fa-solid fa-user-shield"></i></div>
          <div class="txt"><strong><?= $a['n'] ?></strong><small><?= $a['cap'] ?></small></div>
          <span class="badge disp"><?= $a['estado'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <a class="btn btn-red btn-block" style="margin-top:14px" href="reportar.php"><i class="fa-solid fa-triangle-exclamation"></i> Reportar emergencia</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
