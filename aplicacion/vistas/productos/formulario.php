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
if ($id_stock !== null)
    $id_stock = (int)$id_stock;
$stock_fijo = false;
$id_stock_get = (int)($_GET["id_stock"] ?? 0);
if ($id_stock_get > 0) {
    $stock_fijo = true;
    $id_stock = $id_stock_get;
}
$factor_conversion = numero_para_input($p["factor_conversion"] ?? "1", 4);
$ganancia = numero_para_input($p["ganancia"] ?? "0", 2);
$precio_final = moneda_para_mostrar($p["precio_final"] ?? "0");
$activo = (int)($p["activo"] ?? 1);
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1"><?= htmlspecialchars($titulo) ?></h3>
    <div class="text-muted small">Cargá solo lo importante. El sistema calcula el precio final y puede generar el código si no usás lector.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Volver</a>
</div>
<div class="card form-shell">
  <div class="card-body p-4">
    <form method="POST" action="<?= htmlspecialchars($accion) ?>" class="smart-form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($es_editar): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
      <?php endif; ?>
      <div class="form-intro mb-4">
        <div class="form-intro-icon"><i class="bi bi-bag-check"></i></div>
        <div>
          <strong>Consejo</strong>
          <div class="text-muted small">Usá nombres cortos, código correcto y elegí el stock principal para que los cálculos salgan bien.</div>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre *</label>
          <input class="form-control form-control-lg" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Ej: Gaseosa 500 ml" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Código del producto</label>
          <input class="form-control form-control-lg" name="cod_barras" value="<?= htmlspecialchars($cod_barras) ?>" placeholder="Opcional. Si lo dejás vacío, se genera solo">
          <div class="form-text">Solo hace falta si usás lector o querés un código manual.</div>
        </div>
      </div>
      <div class="mt-3">
        <label class="form-label">Stock principal *</label>
        <?php if (!$stock_fijo): ?>
          <input type="text" class="form-control form-control-lg mb-2" id="buscarStockProducto" placeholder="Buscar stock por nombre, unidad, ID o costo">
        <?php endif; ?>
        <select class="form-select form-select-lg" name="id_stock" required <?= $stock_fijo ? "disabled" : "" ?>>
          <option value="" selected disabled>Seleccioná un stock</option>
          <?php foreach ($stocks as $s): ?>
            <?php $sid = (int)$s["id"]; ?>
            <option value="<?= $sid ?>" data-costo="<?= htmlspecialchars(numero_para_input($s["precio_costo"] ?? "0", 4)) ?>" <?= ($id_stock !== null && $id_stock === $sid) ? "selected" : "" ?>>
              #<?= $sid ?> - <?= htmlspecialchars($s["nombre"]) ?> (<?= htmlspecialchars($s["unidad"]) ?>) costo: <?= htmlspecialchars(moneda_para_mostrar($s["precio_costo"] ?? 0)) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($stock_fijo): ?>
          <input type="hidden" name="id_stock" value="<?= (int)$id_stock ?>">
          <div class="form-text">Este stock fue preseleccionado desde la pantalla de stock.</div>
        <?php else: ?>
          <div class="form-text">Todo producto debe estar vinculado a un stock principal.</div>
        <?php endif; ?>
      </div>
      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <label class="form-label">Uso de stock por cada venta</label>
          <input type="number" step="0.0001" class="form-control form-control-lg" name="factor_conversion" value="<?= htmlspecialchars($factor_conversion) ?>">
          <div class="form-text">Ejemplo: si vendés 1 unidad y consumís 50 del stock, poné 50.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Ganancia (%)</label>
          <input type="number" step="0.01" class="form-control form-control-lg" name="ganancia" value="<?= htmlspecialchars($ganancia) ?>">
          <div class="form-text">Si no querés complicarte, dejá 0 y ajustalo después.</div>
        </div>
      </div>
      <div class="product-summary mt-4 mb-4">
        <div class="product-summary-item">
          <span class="product-summary-label">Precio final estimado</span>
          <strong id="precioFinalPreview"><?= htmlspecialchars($precio_final) ?></strong>
        </div>
        <div class="product-summary-item">
          <span class="product-summary-label">Estado</span>
          <?php if ($es_editar): ?>
            <select class="form-select form-select-lg" name="activo">
              <option value="1" <?= ($activo === 1) ? "selected" : "" ?>>Sí</option>
              <option value="0" <?= ($activo === 0) ? "selected" : "" ?>>No</option>
            </select>
          <?php else: ?>
            <input type="hidden" name="activo" value="1">
            <strong>Activo</strong>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Cancelar</a>
        <button class="btn btn-primary btn-lg px-4"><?= htmlspecialchars($texto_btn) ?></button>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  const input = document.getElementById('buscarStockProducto');
  const select = document.querySelector('select[name="id_stock"]');
  const factor = document.querySelector('input[name="factor_conversion"]');
  const ganancia = document.querySelector('input[name="ganancia"]');
  const preview = document.getElementById('precioFinalPreview');
  if (!select)
    return;
  const originalOptions = select.innerHTML;

  function numeroSeguro(valor) {
    const normalizado = String(valor || '').replace(/\s/g, '').replace(',', '.');
    const numero = parseFloat(normalizado);
    return Number.isFinite(numero) ? numero : 0;
  }

  function actualizarPreview() {
    if (!preview || !select)
      return;
    const op = select.options[select.selectedIndex];
    const costo = op ? numeroSeguro(op.getAttribute('data-costo')) : 0;
    const factorValor = factor ? numeroSeguro(factor.value) : 0;
    const gananciaValor = ganancia ? numeroSeguro(ganancia.value) : 0;
    const precio = (costo * factorValor) * (1 + (gananciaValor / 100));
    preview.textContent = '$ ' + precio.toLocaleString('es-PY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function filtrarStocks() {
    if (!input)
      return;
    const texto = (input.value || '').toLowerCase().trim();
    const seleccionado = select.value;
    if (texto === '') {
      select.innerHTML = originalOptions;
      if (seleccionado)
        select.value = seleccionado;
      return;
    }
    const temp = document.createElement('select');
    temp.innerHTML = originalOptions;
    const opciones = Array.from(temp.querySelectorAll('option'));
    select.innerHTML = '';
    opciones.forEach(function (op) {
      if (!op.value) {
        select.appendChild(op);
        return;
      }
      const contenido = (op.textContent || '').toLowerCase();
      if (contenido.includes(texto))
        select.appendChild(op);
    });
    if (seleccionado)
      select.value = seleccionado;
  }

  if (input)
    input.addEventListener('input', filtrarStocks);
  select.addEventListener('change', actualizarPreview);
  if (factor)
    factor.addEventListener('input', actualizarPreview);
  if (ganancia)
    ganancia.addEventListener('input', actualizarPreview);
  actualizarPreview();
})();
</script>
