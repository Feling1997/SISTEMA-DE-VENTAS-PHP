<?php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Productos</h3>
  <a class="btn btn-primary" href="index.php?c=productos&a=nuevo">+ Nuevo</a>
</div>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Código barras</th>
            <th>Stock</th>
            <th>Factor</th>
            <th>Ganancia %</th>
            <th>Precio final</th>
            <th>Activo</th>
            <th style="width: 180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $p): ?>
          <tr>
            <td><?= (int)$p["id"] ?></td>
            <td><?= htmlspecialchars($p["nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars($p["cod_barras"] ?? "") ?></td>
            <td><?= htmlspecialchars($p["stock_nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars($p["factor_conversion"] ?? "") ?></td>
            <td><?= htmlspecialchars($p["ganancia"] ?? "") ?></td>
            <td><?= htmlspecialchars($p["precio_final"] ?? "") ?></td>
            <td><?= ((int)$p["activo"] === 1) ? "Sí" : "No" ?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="index.php?c=productos&a=editar&id=<?= (int)$p["id"] ?>">Editar</a>
              <a class="btn btn-sm btn-danger"
                 href="index.php?c=productos&a=eliminar&id=<?= (int)$p["id"] ?>"
                 onclick="return confirm('¿Eliminar producto?');">
                 Eliminar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($productos) === 0): ?>
          <tr><td colspan="9" class="text-center text-muted">Sin productos.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
