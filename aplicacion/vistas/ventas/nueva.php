<?php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Nueva venta</h3>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=lista">Ir a lista</a>
</div>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body">
        <h5>Agregar producto</h5>
        <form method="POST" action="index.php?c=ventas&a=agregar">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <div class="mb-2">
            <label class="form-label">Producto</label>
            <select class="form-select" name="id_producto" required>
              <option value="" selected disabled>— Seleccioná —</option>
              <?php foreach ($productos as $p): ?>
                <option value="<?= (int)$p["id"] ?>">
                  <?= htmlspecialchars($p["nombre"]) ?> | $<?= htmlspecialchars($p["precio_final"]) ?> | CB: <?= htmlspecialchars($p["cod_barras"]) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-2">
              <label class="form-label">Cantidad</label>
              <input type="number" step="0.001" class="form-control" name="cantidad" value="1" required>
            </div>
            <div class="col-6 mb-2">
              <label class="form-label">Descuento ($)</label>
              <input type="number" step="0.01" class="form-control" name="descuento" value="0">
            </div>
          </div>
          <button class="btn btn-primary w-100">Agregar al carrito</button>
        </form>
        <hr>
        <a class="btn btn-outline-danger w-100" href="index.php?c=ventas&a=vaciar" onclick="return confirm('¿Vaciar carrito?');">
          Vaciar carrito
        </a>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <h5>Carrito</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>Producto</th>
                <th style="text-align:right;">Cant</th>
                <th style="text-align:right;">P.Unit</th>
                <th style="text-align:right;">Desc</th>
                <th style="text-align:right;">Sub</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($carrito as $it): ?>
              <?php
                $sub = Venta::calcular_subtotal((float)$it["cantidad"], (float)$it["precio_unit"], (float)$it["descuento"]);
              ?>
              <tr>
                <td><?= htmlspecialchars($it["nombre"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$it["cantidad"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$it["precio_unit"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$it["descuento"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$sub) ?></td>
                <td style="text-align:right;">
                  <a class="btn btn-sm btn-outline-danger"
                     href="index.php?c=ventas&a=quitar&id_producto=<?= (int)$it["id_producto"] ?>"
                     onclick="return confirm('¿Quitar item?');">Quitar</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($carrito) === 0): ?>
              <tr><td colspan="6" class="text-center text-muted">Carrito vacío.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-end">
          <div style="min-width: 250px;">
            <div class="d-flex justify-content-between">
              <strong>Total:</strong>
              <strong>$<?= htmlspecialchars((string)$total) ?></strong>
            </div>
          </div>
        </div>
        <hr>
        <form method="POST" action="index.php?c=ventas&a=confirmar">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <div class="mb-2">
            <label class="form-label">Cliente (opcional)</label>
            <select class="form-select" name="id_cliente">
              <option value="1">#1 - Consumidor Final</option>
              <?php foreach ($clientes as $c): ?>
                <?php if ((int)$c["id"] !== 1): ?>
                  <option value="<?= (int)$c["id"] ?>">
                    #<?= (int)$c["id"] ?> - <?= htmlspecialchars($c["nombre"]) ?>
                    <?= !empty($c["dni"]) ? (" (DNI: " . htmlspecialchars($c["dni"]) . ")") : "" ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Si no elegís, queda Consumidor Final.</div>
          </div>
          <button class="btn btn-success w-100"
                  <?= (count($carrito) === 0) ? "disabled" : "" ?>
                  onclick="return confirm('¿Confirmar venta?');">
            Confirmar venta (genera PDF)
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
