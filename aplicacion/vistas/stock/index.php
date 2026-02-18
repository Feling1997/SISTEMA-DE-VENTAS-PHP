<?php
$viendo_productos = false;
if (isset($productos) && is_array($productos) && isset($s) && is_array($s))
    $viendo_productos = true;
?>
<?php if ($viendo_productos): ?>
  <?php
    $id_stock = (int)($s["id"] ?? 0);
    $nombre_stock = (string)($s["nombre"] ?? "");
    $unidad = (string)($s["unidad"] ?? "");
    $cantidad = (string)($s["cantidad"] ?? "");
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Productos asociados al stock</h3>
      <div class="text-muted">
        Stock #<?= $id_stock ?> - <?= htmlspecialchars($nombre_stock) ?> (<?= htmlspecialchars($unidad) ?>) | Cantidad: <?= htmlspecialchars($cantidad) ?>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="index.php?c=stock&a=index">Volver</a>
      <a class="btn btn-success" href="index.php?c=productos&a=nuevo&id_stock=<?= $id_stock ?>">
        + Crear producto
      </a>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="tablaStock" class="table table-striped align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Código barras</th>
              <th>Factor</th>
              <th>Ganancia %</th>
              <th>Precio final</th>
              <th>Activo</th>
              <th style="width: 140px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($productos as $p): ?>
            <tr>
              <td><?= (int)$p["id"] ?></td>
              <td><?= htmlspecialchars((string)($p["nombre"] ?? "")) ?></td>
              <td><?= htmlspecialchars((string)($p["cod_barras"] ?? "")) ?></td>
              <td><?= htmlspecialchars((string)($p["factor_conversion"] ?? "")) ?></td>
              <td><?= htmlspecialchars((string)($p["ganancia"] ?? "")) ?></td>
              <td><?= htmlspecialchars((string)($p["precio_final"] ?? "")) ?></td>
              <td><?= ((int)($p["activo"] ?? 0) === 1) ? "Sí" : "No" ?></td>
              <td>
                <a class="btn btn-sm btn-secondary"
                   href="index.php?c=productos&a=editar&id=<?= (int)$p["id"] ?>">
                   Editar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($productos) === 0): ?>
            <tr><td colspan="8" class="text-center text-muted">No hay productos asociados a este stock.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Stock</h3>
    <a class="btn btn-primary" href="index.php?c=stock&a=nuevo">+ Nuevo</a>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="tablaStockProductos" class="table table-striped align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Cantidad</th>
              <th>Unidad</th>
              <th>Precio costo</th>
              <th>Activo</th>
              <th>Creado</th>
              <th style="width: 260px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($items as $fila): ?>
            <tr>
              <td><?= (int)$fila["id"] ?></td>
              <td><?= htmlspecialchars($fila["nombre"] ?? "") ?></td>
              <td><?= htmlspecialchars($fila["cantidad"] ?? "") ?></td>
              <td><?= htmlspecialchars($fila["unidad"] ?? "") ?></td>
              <td><?= htmlspecialchars($fila["precio_costo"] ?? "") ?></td>
              <td><?= ((int)($fila["activo"] ?? 0) === 1) ? "Sí" : "No" ?></td>
              <td><?= htmlspecialchars($fila["creado_en"] ?? "") ?></td>
              <td>
                <div class="acciones-grid">
                  <a class="btn btn-sm btn-secondary accion-btn"
                    href="index.php?c=stock&a=editar&id=<?= (int)$fila["id"] ?>">
                    Editar
                  </a>
                  <a class="btn btn-sm btn-outline-success accion-btn"
                    href="index.php?c=productos&a=nuevo&id_stock=<?= (int)$fila["id"] ?>&nombre_stock=<?= urlencode((string)($fila["nombre"] ?? "")) ?>">
                    Crear
                  </a>
                  <a class="btn btn-sm btn-outline-primary accion-btn"
                    href="index.php?c=stock&a=productos&id=<?= (int)$fila["id"] ?>">
                    Productos
                  </a>
                  <a class="btn btn-sm btn-danger accion-btn"
                    href="index.php?c=stock&a=eliminar&id=<?= (int)$fila["id"] ?>"
                    onclick="return confirm('¿Eliminar stock?');">
                    Eliminar
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($items) === 0): ?>
            <tr><td colspan="8" class="text-center text-muted">Sin stock.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    let ok = true;
    const tabla = document.getElementById('tablaStockProductos');
    if (!tabla) ok = false;
    if (ok) {
      new DataTable('#tablaStockProductos', {
        language: {
          search: "Buscar:",
          lengthMenu: "Mostrar _MENU_",
          info: "Mostrando _START_ a _END_ de _TOTAL_",
          infoEmpty: "Sin datos",
          zeroRecords: "No se encontraron resultados",
          paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
      });
    }
  });
})();
</script>

