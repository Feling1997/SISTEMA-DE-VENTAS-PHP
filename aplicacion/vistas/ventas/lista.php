<?php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Lista de ventas</h3>
  <a class="btn btn-primary" href="index.php?c=ventas&a=nueva">+ Nueva venta</a>
</div>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Vendedor</th>
            <th style="text-align:right;">Total</th>
            <th>PDF</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($ventas as $v): ?>
          <?php $id = (int)$v["id"]; ?>
          <tr>
            <td><?= $id ?></td>
            <td><?= htmlspecialchars((string)$v["fecha"]) ?></td>
            <td><?= htmlspecialchars((string)$v["cliente_nombre"]) ?></td>
            <td><?= htmlspecialchars((string)$v["usuario_nombre"]) ?></td>
            <td style="text-align:right;">$<?= htmlspecialchars((string)$v["total"]) ?></td>
            <td>
              <?php
                $ruta = "../almacenamiento/pdf/venta_" . $id . ".pdf";
              ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($ruta) ?>" target="_blank">Ver PDF</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($ventas) === 0): ?>
          <tr><td colspan="6" class="text-center text-muted">Sin ventas.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
