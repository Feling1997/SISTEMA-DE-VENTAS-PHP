<?php
$es_editar = false;
$accion = "index.php?c=productos&a=crear";
$titulo = "Nuevo producto";
$texto_btn = "Crear";
if (isset($modo) && $modo === "editar") {
    $es_editar = true;
    $accion = "index.php?c=productos&a=actualizar";
    $titulo = "Editar producto";
    $texto_btn = "Guardar cambios";
}
$id = (int)($p["id"] ?? 0);
$nombre = (string)($p["nombre"] ?? "");
$cod_barras = (string)($p["cod_barras"] ?? "");
$id_stock = $p["id_stock"] ?? null;
if ($id_stock !== null) { $id_stock = (int)$id_stock; }
//si venimos desde stock, el stock es fijo
$stock_fijo = false;
$id_stock_get = (int)($_GET["id_stock"] ?? 0);
if ($id_stock_get > 0) {
    $stock_fijo = true;
    $id_stock = $id_stock_get;
}

$factor_conversion = (string)($p["factor_conversion"] ?? "1");
$ganancia = (string)($p["ganancia"] ?? "0");
$precio_final = (string)($p["precio_final"] ?? "0");
$activo = (int)($p["activo"] ?? 1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?= htmlspecialchars($titulo) ?></h3>
  <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Volver</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="<?= htmlspecialchars($accion) ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($es_editar): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label">Nombre *</label>
        <input class="form-control" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Ingresar nombre" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Código de barras *</label>
        <input class="form-control" name="cod_barras" value="<?= htmlspecialchars($cod_barras) ?>" placeholder="Ingresar código de barras" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Stock principal *</label>
        <select class="form-select" name="id_stock" required <?= $stock_fijo ? "disabled" : "" ?>>
          <option value="" selected disabled>— Seleccioná un stock —</option>
          <?php foreach ($stocks as $s): ?>
            <?php $sid = (int)$s["id"]; ?>
            <option value="<?= $sid ?>" <?= ($id_stock !== null && $id_stock === $sid) ? "selected" : "" ?>>
              #<?= $sid ?> - <?= htmlspecialchars($s["nombre"]) ?> (<?= htmlspecialchars($s["unidad"]) ?>) costo: <?= htmlspecialchars($s["precio_costo"]) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($stock_fijo): ?>
          <input type="hidden" name="id_stock" value="<?= (int)$id_stock ?>">
          <div class="form-text">Stock preseleccionado desde Stock. No se puede cambiar en esta pantalla.</div>
        <?php else: ?>
          <div class="form-text">El producto siempre debe estar asociado a un stock principal.</div>
        <?php endif; ?>
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Factor de conversión</label>
          <input type="number" step="0.0001" class="form-control" name="factor_conversion" value="<?= htmlspecialchars($factor_conversion) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Ganancia (%)</label>
          <input type="number" step="0.01" class="form-control" name="ganancia" value="<?= htmlspecialchars($ganancia) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Precio final (calculado)</label>
          <input class="form-control" value="<?= htmlspecialchars($precio_final) ?>" disabled>
          <div class="form-text">Se recalcula al guardar, según el costo del stock.</div>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Activo</label>
        <select class="form-select" name="activo">
          <option value="1" <?= ($activo === 1) ? "selected" : "" ?>>Sí</option>
          <option value="0" <?= ($activo === 0) ? "selected" : "" ?>>No</option>
        </select>
      </div>
      <button class="btn btn-primary"><?= htmlspecialchars($texto_btn) ?></button>
    </form>
  </div>
</div>
