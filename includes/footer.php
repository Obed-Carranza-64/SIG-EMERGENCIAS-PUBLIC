</main>

<footer class="statusbar">
  <div class="statusbar-left">
    <span class="ok"><i class="fa-solid fa-circle-check"></i> Sistema operativo</span>
    <span class="sep"></span>
    <span>Última actualización: <?= htmlspecialchars($APP['ultima_actualizacion']) ?></span>
    <span class="sep"></span>
    <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($APP['pais']) ?></span>
  </div>
  <div class="statusbar-right">
    <span>Más información: Mejores decisiones. Más vidas protegidas.</span>
    <i class="fa-solid fa-shield-halved"></i>
  </div>
</footer>

<?php
$TOASTS = [
    'creado' => ['ok' => true, 'msg' => 'Reporte registrado correctamente.'],
    'estado' => ['ok' => true, 'msg' => 'Estado del reporte actualizado.'],
    'kit' => ['ok' => true, 'msg' => 'Kit registrado correctamente.'],
    'asignado' => ['ok' => true, 'msg' => 'Kit asignado (En uso).'],
    'liberado' => ['ok' => true, 'msg' => 'Kit liberado (Disponible).'],
    'nodo' => ['ok' => true, 'msg' => 'Estado del nodo actualizado. Topología recalculada.'],
];
$toast = null;
if (isset($_GET['ok']) && isset($TOASTS[$_GET['ok']])) {
    $toast = $TOASTS[$_GET['ok']];
} elseif (isset($_GET['err'])) {
    $toast = ['ok' => false, 'msg' => 'Ocurrió un error. Intenta de nuevo.'];
}
?>
<?php if ($toast): ?>
  <div class="toast <?= $toast['ok'] ? 'is-ok' : 'is-err' ?>" data-show>
    <i class="fa-solid <?= $toast['ok'] ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
    <?= htmlspecialchars($toast['msg']) ?>
  </div>
<?php endif; ?>
<div class="toast" id="jsToast" aria-hidden="true"></div>

<?php
if (!function_exists('sig_config')) {
    require_once __DIR__ . '/config.php';
}
?>
<script>window.SIG_CONFIG = <?= json_encode(sig_config(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
