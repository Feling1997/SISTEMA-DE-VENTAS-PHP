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
            <th>Codigo</th>
            <th>Cliente</th>
            <th>Telefono</th>
            <th>Marca/Modelo</th>
            <th>IMEI</th>
            <th>Falla</th>
            <th>Garantia</th>
            <th>Estado</th>
            <th>Precio</th>
            <th>Ingreso</th>
            <th>Entrega</th>
            <th style="width: 260px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($reparaciones as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r["codigo"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["cliente_nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["cliente_telefono"] ?? "") ?></td>
            <td><?= htmlspecialchars(trim(($r["marca"] ?? "") . " " . ($r["modelo"] ?? ""))) ?></td>
            <td><?= htmlspecialchars($r["imei"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["falla"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["garantia"] ?? "") ?></td>
            <td>
              <?php
                $estado = $r["estado"] ?? "PENDIENTE";
                $clase = match ($estado) {
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
            <td><?= htmlspecialchars(moneda_para_mostrar($r["precio"] ?? 0)) ?></td>
            <td><?= htmlspecialchars($r["fecha_ingreso"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["fecha_entrega"] ?? "-") ?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="index.php?c=reparaciones&a=editar&id=<?= (int)$r["id"] ?>">Editar</a>
              <a class="btn btn-sm btn-success" href="index.php?c=reparaciones&a=imprimir&id=<?= (int)$r["id"] ?>" target="_blank">Ticket</a>
              <a class="btn btn-sm btn-info" href="index.php?c=reparaciones&a=imprimir&id=<?= (int)$r["id"] ?>&modo=pdf" target="_blank">PDF</a>
              <a class="btn btn-sm btn-danger"
                 href="index.php?c=reparaciones&a=eliminar&id=<?= (int)$r["id"] ?>"
                 onclick="return confirm('Eliminar reparacion?');">
                 Eliminar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($reparaciones) === 0): ?>
          <tr><td colspan="12" class="text-center text-muted">Sin reparaciones.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
