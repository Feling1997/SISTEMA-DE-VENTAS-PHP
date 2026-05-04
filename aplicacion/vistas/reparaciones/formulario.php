<?php
$es_edicion = $modo === "editar";
$titulo = $es_edicion ? "Editar Reparación" : "Nueva Reparación";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?= $titulo ?></h3>
  <a class="btn btn-secondary" href="index.php?c=reparaciones&a=index">Volver</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="post" action="index.php?c=reparaciones&a=<?= $es_edicion ? 'actualizar' : 'guardar' ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf ?? "") ?>">
      <?php if ($es_edicion): ?>
        <input type="hidden" name="id" value="<?= (int)$p["id"] ?>">
      <?php endif; ?>
      
      <div class="row g-3">
        <!-- Datos del cliente -->
        <div class="col-md-6">
          <label for="cliente_nombre" class="form-label">Nombre del Cliente *</label>
          <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" 
                 value="<?= htmlspecialchars($p["cliente_nombre"] ?? "") ?>" required>
        </div>
        <div class="col-md-6">
          <label for="cliente_telefono" class="form-label">Teléfono</label>
          <input type="text" class="form-control" id="cliente_telefono" name="cliente_telefono" 
                 value="<?= htmlspecialchars($p["cliente_telefono"] ?? "") ?>">
        </div>

        <!-- Datos del equipo -->
        <div class="col-md-4">
          <label for="marca" class="form-label">Marca</label>
          <input type="text" class="form-control" id="marca" name="marca" 
                 value="<?= htmlspecialchars($p["marca"] ?? "") ?>" placeholder="ej: Samsung, Apple">
        </div>
        <div class="col-md-4">
          <label for="modelo" class="form-label">Modelo</label>
          <input type="text" class="form-control" id="modelo" name="modelo" 
                 value="<?= htmlspecialchars($p["modelo"] ?? "") ?>" placeholder="ej: Galaxy S21">
        </div>
        <div class="col-md-4">
          <label for="imei" class="form-label">IMEI</label>
          <input type="text" class="form-control" id="imei" name="imei" 
                 value="<?= htmlspecialchars($p["imei"] ?? "") ?>">
        </div>

        <!-- Falla y diagnóstico -->
        <div class="col-md-6">
          <label for="falla" class="form-label">Falla Reportada</label>
          <textarea class="form-control" id="falla" name="falla" rows="2"><?= htmlspecialchars($p["falla"] ?? "") ?></textarea>
        </div>
        <div class="col-md-6">
          <label for="diagnostico" class="form-label">Diagnóstico</label>
          <textarea class="form-control" id="diagnostico" name="diagnostico" rows="2"><?= htmlspecialchars($p["diagnostico"] ?? "") ?></textarea>
        </div>

        <!-- Estado y precio -->
        <div class="col-md-4">
          <label for="estado" class="form-label">Estado</label>
          <select class="form-select" id="estado" name="estado">
            <?php foreach ($estados as $clave => $nombre): ?>
              <option value="<?= $clave ?>" <?= ($p["estado"] ?? "PENDIENTE") === $clave ? "selected" : "" ?>>
                <?= $nombre ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="precio" class="form-label">Precio ($)</label>
          <input type="number" class="form-control" id="precio" name="precio" 
                 value="<?= (float)($p["precio"] ?? 0) ?>" step="0.01" min="0">
        </div>
        <div class="col-md-4">
          <label for="fecha_ingreso" class="form-label">Fecha Ingreso</label>
          <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" 
                 value="<?= htmlspecialchars($p["fecha_ingreso"] ?? date("Y-m-d")) ?>">
        </div>

        <div class="col-md-6">
          <label for="garantia" class="form-label">Tiempo de garantia</label>
          <input type="text" class="form-control" id="garantia" name="garantia"
                 value="<?= htmlspecialchars($p["garantia"] ?? "") ?>" placeholder="ej: 30 dias, 3 meses">
        </div>

        <!-- Fechas y observaciones -->
        <div class="col-md-6">
          <label for="fecha_entrega" class="form-label">Fecha Entrega</label>
          <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" 
                 value="<?= htmlspecialchars($p["fecha_entrega"] ?? "") ?>">
        </div>
        <div class="col-md-6">
          <label for="observaciones" class="form-label">Observaciones</label>
          <textarea class="form-control" id="observaciones" name="observaciones" rows="2"><?= htmlspecialchars($p["observaciones"] ?? "") ?></textarea>
        </div>

        <?php if ($es_edicion): ?>
        <div class="col-md-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= ($p["activo"] ?? 1) == 1 ? "checked" : "" ?>>
            <label class="form-check-label" for="activo">Activo</label>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary"><?= $es_edicion ? "Actualizar" : "Guardar" ?></button>
        <a class="btn btn-secondary" href="index.php?c=reparaciones&a=index">Cancelar</a>
      </div>
    </form>
  </div>
</div>
