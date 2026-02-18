<?php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Usuarios</h3>
  <a class="btn btn-primary" href="index.php?c=usuarios&a=nuevo">+ Nuevo</a>
</div>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaUsuarios" class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Activo</th>
            <th>Creado</th>
            <th style="width: 180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= (int)$u["id"] ?></td>
            <td><?= htmlspecialchars($u["usuario"]) ?></td>
            <td><?= htmlspecialchars($u["rol"]) ?></td>
            <td><?= ((int)$u["activo"] === 1) ? "Sí" : "No" ?></td>
            <td><?= date("d/m/Y", strtotime($u["creado_en"])) ?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="index.php?c=usuarios&a=editar&id=<?= (int)$u["id"] ?>">Editar</a>
              <a class="btn btn-sm btn-danger"
                 href="index.php?c=usuarios&a=eliminar&id=<?= (int)$u["id"] ?>"
                 onclick="return confirm('¿Eliminar usuario?');">
                 Eliminar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($usuarios) === 0): ?>
          <tr><td colspan="6" class="text-center text-muted">Sin usuarios.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    let ok = true;
    const tabla = document.getElementById('tablaUsuarios');
    if (!tabla) ok = false;
    if (ok) {
      new DataTable('#tablaUsuarios', {
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
