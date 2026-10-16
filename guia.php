<?php
require_once __DIR__ . '/includes/data.php';
$active = 'guia';
$page_title = 'Guía de emergencia';
require __DIR__ . '/includes/header.php';

$GUIA = [
    [
        'icon' => 'fa-house-crack', 'color' => 'amber', 't' => 'Sismo fuerte',
        'pasos' => [
            'Antes: fija muebles altos, identifica zonas seguras y prepara tu mochila de emergencia.',
            'Durante: agáchate, cúbrete bajo una mesa firme y sujétate. No uses ascensores.',
            'Después: revisa fugas de gas, evita cables caídos y reporta daños en Reportes Ciudadanos.',
        ],
    ],
    [
        'icon' => 'fa-water', 'color' => 'blue', 't' => 'Inundación',
        'pasos' => [
            'Antes: conoce las rutas de evacuación y guarda documentos en bolsas impermeables.',
            'Durante: sube a zonas altas. Nunca cruces corrientes de agua a pie o en vehículo.',
            'Después: no consumas agua contaminada y espera la autorización para regresar.',
        ],
    ],
    [
        'icon' => 'fa-fire', 'color' => 'red', 't' => 'Incendio forestal',
        'pasos' => [
            'Antes: mantén rondas limpias alrededor de viviendas y reporta quemas no controladas.',
            'Durante: aléjate en dirección contraria al viento y cubre nariz y boca con un paño húmedo.',
            'Después: no regreses hasta que las autoridades confirmen que es seguro.',
        ],
    ],
    [
        'icon' => 'fa-hill-rockslide', 'color' => 'purple', 't' => 'Deslizamiento de tierra',
        'pasos' => [
            'Antes: observa grietas o inclinaciones nuevas en laderas cercanas a tu vivienda.',
            'Durante: evacúa de inmediato hacia terreno estable y alerta a tus vecinos.',
            'Después: no camines sobre escombros inestables y solicita evaluación técnica.',
        ],
    ],
];
?>

<div class="section-label" style="margin-top:4px"><i class="fa-solid fa-clipboard-list"></i> Guía de emergencia · Qué hacer ante cada situación</div>

<div class="grid" style="grid-template-columns:1fr 1fr;">
  <?php foreach ($GUIA as $g): ?>
    <div class="panel">
      <div class="panel-head"><h3><i class="fa-solid <?= $g['icon'] ?>"></i> <?= $g['t'] ?></h3></div>
      <div class="list">
        <?php foreach ($g['pasos'] as $p): ?>
          <div class="row">
            <div class="ic <?= $g['color'] ?>"><i class="fa-solid fa-check"></i></div>
            <div class="txt"><strong><?= htmlspecialchars($p) ?></strong></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel" style="margin-top:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
  <div class="ic red" style="width:46px;height:46px;border-radius:12px;display:grid;place-items:center;font-size:18px"><i class="fa-solid fa-phone-volume"></i></div>
  <div style="flex:1;min-width:220px">
    <strong>¿Ves una emergencia en curso?</strong>
    <p style="color:var(--muted);font-size:12.5px;margin:4px 0 0">Repórtala con ubicación y fotos para activar a los equipos de respuesta.</p>
  </div>
  <a class="btn btn-red" href="reportar.php"><i class="fa-solid fa-triangle-exclamation"></i> Reportar emergencia</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
