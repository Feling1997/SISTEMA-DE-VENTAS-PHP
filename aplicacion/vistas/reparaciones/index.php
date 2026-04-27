<?php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Reparaciones</h3>
  <a class="btn btn-primary" href="index.php?c=reparaciones&a=nuevo">+ Nueva</a>
</div>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaReparaciones" class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Código</th>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th>Marca/Modelo</th>
            <th>IMEI</th>
            <th>Falla</th>
            <th>Estado</th>
            <th>Precio</th>
            <th>Ingreso</th>
            <th>Entrega</th>
            <th style="width: 180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($reparaciones as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r["codigo"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["cliente_nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["cliente_telefono"] ?? "") ?></td>
            <td><?= htmlspecialchars(($r["marca"] ?? "") . " " . ($r["modelo"] ?? "")) ?></td>
            <td><?= htmlspecialchars($r["imei"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["falla"] ?? "") ?></td>
            <td>
              <?php 
                $estado = $r["estado"] ?? "PENDIENTE";
                $clase = match($estado) {
                    "PENDIENTE" => "bg-warning",
                    "EN_REPARACION" => "bg-info",
                    "ESP_REPUESTOS" => "bg-secondary",
                    "REPARADO" => "bg-success",
                    "ENTREGADO" => "bg-primary",
                    "CANCELADO" => "bg-danger",
                    default => "bg-secondary"
                };
              ?>
              <span class="badge <?= $clase ?>"><?= $estados[$estado] ?? $estado ?></span>
            </td>
            <td>$<?= number_format((float)($r["precio"] ?? 0), 2, ",", ".") ?></td>
            <td><?= htmlspecialchars($r["fecha_ingreso"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["fecha_entrega"] ?? "-") ?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="index.php?c=reparaciones&a=editar&id=<?= (int)$r["id"] ?>">Editar</a>
              <a class="btn btn-sm btn-info" href="index.php?c=reparaciones&a=imprimir&id=<?= (int)$r["id"] ?>" target="_blank">Imprimir</a>
              <a class="btn btn-sm btn-danger"
                 href="index.php?c=reparaciones&a=eliminar&id=<?= (int)$r["id"] ?>"
                 onclick="return confirm('¿Eliminar reparación?');">
                 Eliminar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($reparaciones) === 0): ?>
          <tr><td colspan="11" class="text-center text-muted">Sin reparaciones.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>