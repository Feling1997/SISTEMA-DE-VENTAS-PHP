<?php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Stock</h3>
  <a class="btn btn-primary" href="index.php?c=stock&a=nuevo">+ Nuevo</a>
</div>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Unidad</th>
            <th>Cantidad</th>
            <th>Precio costo</th>
            <th>Activo</th>
            <th>Creado</th>
            <th style="width: 200px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $s): ?>
          <tr>
            <td><?= (int)$s["id"] ?></td>
            <td><?= htmlspecialchars($s["nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars($s["unidad"] ?? "") ?></td>
            <td><?= htmlspecialchars($s["cantidad"] ?? "") ?></td>
            <td><?= htmlspecialchars($s["precio_costo"] ?? "") ?></td>
            <td><?= ((int)$s["activo"] === 1) ? "Sí" : "No" ?></td>
            <td><?= htmlspecialchars($s["creado_en"] ?? "") ?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="index.php?c=stock&a=editar&id=<?= (int)$s["id"] ?>">Editar</a>
              <a class="btn btn-sm btn-danger"
                 href="index.php?c=stock&a=eliminar&id=<?= (int)$s["id"] ?>"
                 onclick="return confirm('¿Eliminar stock?');">
                 Eliminar
              </a>
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
