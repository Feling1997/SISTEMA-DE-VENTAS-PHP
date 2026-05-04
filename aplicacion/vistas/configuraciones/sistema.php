<?php
$config = $config ?? [];
function cfg_valor(array $config, string $clave): string {
    return htmlspecialchars((string)($config[$clave] ?? ""));
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-1">Configuracion del sistema</h3>
    <div class="text-muted small">Datos que aparecen en la cabecera de tickets, presupuestos y facturas.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="POST" action="index.php?c=configuraciones&a=guardar_sistema" class="row g-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <div class="col-md-6">
        <label class="form-label">Nombre del comercio</label>
        <input class="form-control" name="nombre_comercio" value="<?= cfg_valor($config, "nombre_comercio") ?>" placeholder="Ej: Mi Comercio">
      </div>

      <div class="col-md-6">
        <label class="form-label">Razon social</label>
        <input class="form-control" name="razon_social" value="<?= cfg_valor($config, "razon_social") ?>" placeholder="Nombre fiscal o titular">
      </div>

      <div class="col-md-4">
        <label class="form-label">CUIT</label>
        <input class="form-control" name="cuit" value="<?= cfg_valor($config, "cuit") ?>" placeholder="20-00000000-0">
      </div>

      <div class="col-md-4">
        <label class="form-label">Condicion IVA</label>
        <select class="form-select" name="condicion_iva">
          <?php
          $condicion = (string)($config["condicion_iva"] ?? "");
          $opciones = ["", "Responsable Inscripto", "Monotributista", "Exento", "Consumidor Final", "No Responsable"];
          foreach ($opciones as $opcion):
          ?>
            <option value="<?= htmlspecialchars($opcion) ?>" <?= $condicion === $opcion ? "selected" : "" ?>><?= $opcion === "" ? "Seleccionar" : htmlspecialchars($opcion) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Punto de venta</label>
        <input type="number" min="1" class="form-control" name="punto_venta" value="<?= (int)($config["punto_venta"] ?? 1) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Direccion</label>
        <input class="form-control" name="domicilio" value="<?= cfg_valor($config, "domicilio") ?>" placeholder="Calle, numero, local">
      </div>

      <div class="col-md-3">
        <label class="form-label">Localidad</label>
        <input class="form-control" name="localidad" value="<?= cfg_valor($config, "localidad") ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label">Provincia</label>
        <input class="form-control" name="provincia" value="<?= cfg_valor($config, "provincia") ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Telefonos</label>
        <input class="form-control" name="telefonos" value="<?= cfg_valor($config, "telefonos") ?>" placeholder="Lineas separadas por coma">
      </div>

      <div class="col-md-4">
        <label class="form-label">WhatsApp</label>
        <input class="form-control" name="whatsapp" value="<?= cfg_valor($config, "whatsapp") ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="<?= cfg_valor($config, "email") ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Sitio web o redes</label>
        <input class="form-control" name="sitio_web" value="<?= cfg_valor($config, "sitio_web") ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Ingresos brutos / No contrib.</label>
        <input class="form-control" name="ingresos_brutos" value="<?= cfg_valor($config, "ingresos_brutos") ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Inicio de actividades</label>
        <input type="date" class="form-control" name="inicio_actividades" value="<?= cfg_valor($config, "inicio_actividades") ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Texto al pie del ticket</label>
        <textarea class="form-control" name="texto_pie_ticket" rows="2"><?= cfg_valor($config, "texto_pie_ticket") ?></textarea>
      </div>

      <div class="col-12 d-flex justify-content-end">
        <button class="btn btn-primary">Guardar configuracion</button>
      </div>
    </form>
  </div>
</div>
