<?php
$texto_buscar = $texto_buscar ?? "";
$campo_buscar = $campo_buscar ?? "todos";
$metodo_buscar = $metodo_buscar ?? "contiene";
$campos_busqueda = $campos_busqueda ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Productos</h3>
    <div class="text-muted small">Lista simple para revisar, editar o eliminar productos.</div>
  </div>
  <a class="btn btn-primary" href="index.php?c=productos&a=nuevo">+ Nuevo</a>
</div>
<div class="card search-shell mb-3">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-auto-submit-search="true" data-search-target="#productosResultados">
      <input type="hidden" name="c" value="productos">
      <input type="hidden" name="a" value="index">
      <div class="col-lg-5">
        <label class="form-label">Buscar</label>
        <input type="text" class="form-control" name="buscar" value="<?= htmlspecialchars($texto_buscar) ?>" placeholder="Ej: nombre, código o precio">
      </div>
      <div class="col-md-3 col-lg-3">
        <label class="form-label">Campo</label>
        <select class="form-select" name="campo">
          <option value="todos" <?= $campo_buscar === "todos" ? "selected" : "" ?>>Todos</option>
          <?php foreach ($campos_busqueda as $clave => $etiqueta): ?>
            <option value="<?= htmlspecialchars($clave) ?>" <?= $campo_buscar === $clave ? "selected" : "" ?>><?= htmlspecialchars($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-lg-2">
        <label class="form-label">Método</label>
        <select class="form-select" name="metodo">
          <option value="contiene" <?= $metodo_buscar === "contiene" ? "selected" : "" ?>>Contiene</option>
          <option value="exacto" <?= $metodo_buscar === "exacto" ? "selected" : "" ?>>Exacto</option>
          <option value="empieza" <?= $metodo_buscar === "empieza" ? "selected" : "" ?>>Empieza</option>
          <option value="termina" <?= $metodo_buscar === "termina" ? "selected" : "" ?>>Termina</option>
        </select>
      </div>
      <div class="col-md-2 col-lg-2 d-flex gap-2">
        <button class="btn btn-primary flex-grow-1">Buscar</button>
        <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Limpiar</a>
      </div>
    </form>
  </div>
</div>
<div id="productosResultados">
  <div class="card list-shell">
    <div class="card-body p-4">
      <div class="table-responsive">
        <table id="tablaProductos" class="table table-striped align-middle admin-table">
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
            <td><?= htmlspecialchars(numero_para_mostrar($p["factor_conversion"] ?? 0, 4)) ?></td>
            <td><?= htmlspecialchars($p["ganancia"] ?? "") ?></td>
            <td><?= htmlspecialchars(moneda_para_mostrar($p["precio_final"] ?? 0)) ?></td>
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
</div>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    let ok = true;
    const tabla = document.getElementById('tablaProductos');
    if (!tabla) ok = false;
    if (ok) {
      new DataTable('#tablaProductos', {
        searching: false,
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
