<?php
require_once __DIR__ . '/includes/data.php';
$active = 'recursos';
$page_title = 'Registrar kit';

$CATEGORIAS = ['Médico', 'Humanitario', 'Rescate', 'Comunicación', 'Albergue', 'Energía'];
$TIPOS_KIT = ['Tipo 1', 'Tipo 2', 'Tipo 3', 'Tipo 4', 'Tipo 5'];
$ESTADOS_KIT = ['Disponible', 'En uso', 'Mantenimiento'];

$error = '';
$old = [
    'nombre' => '', 'categoria' => 'Humanitario', 'tipo' => 'Tipo 1',
    'ubicacion' => '', 'cantidad' => '10', 'vencimiento' => date('Y-m-d', strtotime('+1 year')),
    'estado' => 'Disponible',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nombre'] = trim($_POST['nombre'] ?? '');
    $old['categoria'] = $_POST['categoria'] ?? 'Humanitario';
    $old['tipo'] = $_POST['tipo'] ?? 'Tipo 1';
    $old['ubicacion'] = trim($_POST['ubicacion'] ?? '');
    $old['cantidad'] = $_POST['cantidad'] ?? '1';
    $old['vencimiento'] = $_POST['vencimiento'] ?? date('Y-m-d');
    $old['estado'] = $_POST['estado'] ?? 'Disponible';

    if ($old['nombre'] === '' || $old['ubicacion'] === '') {
        $error = 'Completa el nombre del kit y su ubicación.';
    } elseif (!in_array($old['categoria'], $CATEGORIAS, true)) {
        $error = 'Categoría no válida.';
    } else {
        try {
            init_db();
            crear_kit($old);
            header('Location: recursos.php?ok=kit');
            exit;
        } catch (Throwable $e) {
            $error = 'No se pudo registrar el kit. Intenta de nuevo.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="section-label" style="margin-top:4px"><i class="fa-solid fa-toolbox"></i> Registrar kit / recurso</div>

<form class="panel" style="max-width:640px" method="post" action="registrar_kit.php">
  <div class="panel-head"><h3><i class="fa-solid fa-plus"></i> Datos del kit</h3></div>

  <?php if ($error): ?>
    <div class="alert-err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="field">
    <label for="kNombre">Nombre del kit</label>
    <input id="kNombre" type="text" name="nombre" maxlength="120" required
           placeholder="Ej.: Kit de primeros auxilios" value="<?= htmlspecialchars($old['nombre']) ?>">
  </div>

  <div class="grid" style="grid-template-columns:1fr 1fr;">
    <div class="field">
      <label for="kCat">Categoría</label>
      <select id="kCat" name="categoria">
        <?php foreach ($CATEGORIAS as $c): ?>
          <option <?= $old['categoria'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="kTipo">Tipo</label>
      <select id="kTipo" name="tipo">
        <?php foreach ($TIPOS_KIT as $t): ?>
          <option <?= $old['tipo'] === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="grid" style="grid-template-columns:1fr 1fr;">
    <div class="field">
      <label for="kUbi">Ubicación / bodega</label>
      <input id="kUbi" type="text" name="ubicacion" maxlength="120" required
             placeholder="Ej.: San Salvador" value="<?= htmlspecialchars($old['ubicacion']) ?>">
    </div>
    <div class="field">
      <label for="kCant">Cantidad de kits</label>
      <input id="kCant" type="number" name="cantidad" min="1" max="9999" required value="<?= htmlspecialchars($old['cantidad']) ?>">
    </div>
  </div>

  <div class="grid" style="grid-template-columns:1fr 1fr;">
    <div class="field">
      <label for="kVen">Fecha de vencimiento</label>
      <input id="kVen" type="date" name="vencimiento" required value="<?= htmlspecialchars($old['vencimiento']) ?>">
    </div>
    <div class="field">
      <label for="kEst">Estado inicial</label>
      <select id="kEst" name="estado">
        <?php foreach ($ESTADOS_KIT as $e): ?>
          <option <?= $old['estado'] === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div style="display:flex;gap:10px;margin-top:4px">
    <button class="btn btn-red" type="submit" style="flex:1"><i class="fa-solid fa-floppy-disk"></i> Guardar kit</button>
    <a class="btn btn-ghost" href="recursos.php">Cancelar</a>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
