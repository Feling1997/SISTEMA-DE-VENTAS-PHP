<?php
$es_editar = false;
$accion = "index.php?c=clientes&a=crear";
$titulo = "Nuevo cliente";
$texto_btn = "Crear";

if (isset($modo) && $modo === "editar") {
    $es_editar = true;
    $accion = "index.php?c=clientes&a=actualizar";
    $titulo = "Editar cliente";
    $texto_btn = "Guardar cambios";
}

$id = (int)($c["id"] ?? 0);
$nombre = (string)($c["nombre"] ?? "");
$dni = (string)($c["dni"] ?? "");
$tipo_documento = (string)($c["tipo_documento"] ?? "DNI");
$condicion_iva = (string)($c["condicion_iva"] ?? "Consumidor Final");
$email = (string)($c["email"] ?? "");
$telefono = (string)($c["telefono"] ?? "");
$direccion = (string)($c["direccion"] ?? "");
$tipos_documento = ["DNI" => "DNI", "CUIT" => "CUIT", "CUIL" => "CUIL", "PASAPORTE" => "Pasaporte"];
$condiciones_iva = [
    "Consumidor Final" => "Consumidor Final",
    "Responsable Inscripto" => "Responsable Inscripto",
    "Monotributista" => "Monotributista",
    "Exento" => "Exento",
    "No Responsable" => "No Responsable"
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?= htmlspecialchars($titulo) ?></h3>
  <a class="btn btn-outline-secondary" href="index.php?c=clientes&a=index">Volver</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="POST" action="<?= htmlspecialchars($accion) ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($es_editar): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Nombre *</label>
        <input class="form-control" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Ingresar nombre">
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label">Tipo documento</label>
          <select class="form-select" name="tipo_documento">
            <?php foreach ($tipos_documento as $valor => $etiqueta): ?>
              <option value="<?= htmlspecialchars($valor) ?>" <?= $tipo_documento === $valor ? "selected" : "" ?>><?= htmlspecialchars($etiqueta) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">CUIT / DNI</label>
          <input class="form-control" name="dni" value="<?= htmlspecialchars($dni) ?>" placeholder="Sin guiones">
        </div>
        <div class="col-md-4">
          <label class="form-label">Condici&oacute;n IVA</label>
          <select class="form-select" name="condicion_iva">
            <?php foreach ($condiciones_iva as $valor => $etiqueta): ?>
              <option value="<?= htmlspecialchars($valor) ?>" <?= $condicion_iva === $valor ? "selected" : "" ?>><?= htmlspecialchars($etiqueta) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Email fiscal (opcional)</label>
        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="cliente@dominio.com">
      </div>

      <div class="mb-3">
        <label class="form-label">Teléfono (opcional)</label>
        <input class="form-control" name="telefono" value="<?= htmlspecialchars($telefono) ?>" placeholder="Ingresar teléfono">
      </div>

      <div class="mb-3">
        <label class="form-label">Dirección (opcional)</label>
        <input class="form-control" name="direccion" value="<?= htmlspecialchars($direccion) ?>" placeholder="Ingresar dirección">
      </div>

      <button class="btn btn-primary"><?= htmlspecialchars($texto_btn) ?></button>
    </form>
  </div>
</div>
