<?php
if (!isset($APP)) { require_once __DIR__ . '/data.php'; }
$active = $active ?? 'inicio';
$page_title = $page_title ?? 'Inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> · <?= htmlspecialchars($APP['nombre']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php">
    <span class="brand-mark"><i class="fa-solid fa-tower-broadcast"></i></span>
    <span class="brand-text">
      <strong><?= htmlspecialchars($APP['nombre']) ?></strong>
      <small><?= htmlspecialchars($APP['subtitulo']) ?></small>
    </span>
  </a>

  <nav class="topnav">
    <?php foreach ($NAV as $item): ?>
      <a href="<?= $item['href'] ?>" class="nav-item <?= $active === $item['id'] ? 'is-active' : '' ?>">
        <i class="fa-solid <?= $item['icon'] ?>"></i>
        <span><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="topbar-right">
    <span class="status-pill"><span class="dot"></span> Sistema activo</span>
    <button class="avatar" type="button" aria-label="Cuenta" data-toast="Operador SIG · Turno activo (demo)"><i class="fa-solid fa-user"></i></button>
  </div>
</header>
<main class="page">
