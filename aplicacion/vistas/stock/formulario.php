<?php
$es_editar = false;
$accion = "index.php?c=stock&a=crear";
$titulo = "Nuevo stock";
$texto_btn = "Crear";
if (isset($modo) && $modo === "editar") {
    $es_editar = true;
    $accion = "index.php?c=stock&a=actualizar";
    $titulo = "Editar stock";
    $texto_btn = "Guardar cambios";
}
$id = (int)($s["id"] ?? 0);
$nombre = (string)($s["nombre"] ?? "");
$unidad = (string)($s["unidad"] ?? "u");
$cantidad = (string)($s["cantidad"] ?? "0");
$precio_costo = (string)($s["precio_costo"] ?? "0");
$activo = (int)($s["activo"] ?? 1);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?= htmlspecialchars($titulo) ?></h3>
  <a class="btn btn-outline-secondary" href="index.php?c=stock&a=index">Volver</a>
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
        <input class="form-control" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Ingresar nombre">
      </div>
      <div class="mb-3">
        <label class="form-label">Unidad</label>
        <input class="form-control" name="unidad" value="<?= htmlspecialchars($unidad) ?>" placeholder="u / kg / lt">
      </div>
      <div class="mb-3">
        <label class="form-label">Cantidad</label>
        <input type="number" step="0.001" class="form-control" name="cantidad" value="<?= htmlspecialchars($cantidad) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Precio costo</label>
        <input type="number" step="0.01" class="form-control" name="precio_costo" value="<?= htmlspecialchars($precio_costo) ?>">
        <div class="form-text">
          Si modificás el precio costo, se recalcula el precio final de los productos vinculados a este stock.
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
