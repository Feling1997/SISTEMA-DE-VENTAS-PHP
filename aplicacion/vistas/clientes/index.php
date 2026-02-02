<?php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Clientes</h3>
  <a class="btn btn-primary" href="index.php?c=clientes&a=nuevo">+ Nuevo</a>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>DNI</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Creado</th>
            <th style="width: 220px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($clientes as $c): ?>
          <?php $id = (int)$c["id"]; ?>
          <tr>
            <td><?= $id ?></td>
            <td><?= htmlspecialchars($c["nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["dni"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["telefono"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["direccion"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["creado_en"] ?? "") ?></td>
            <td>
              <?php if ($id === 1): ?>
                <span class="badge text-bg-secondary">Fijo (Consumidor Final)</span>
              <?php else: ?>
                <a class="btn btn-sm btn-secondary" href="index.php?c=clientes&a=editar&id=<?= $id ?>">Editar</a>
                <a class="btn btn-sm btn-danger"
                   href="index.php?c=clientes&a=eliminar&id=<?= $id ?>"
                   onclick="return confirm('¿Eliminar cliente?');">
                   Eliminar
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (count($clientes) === 0): ?>
          <tr><td colspan="7" class="text-center text-muted">Sin clientes.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
