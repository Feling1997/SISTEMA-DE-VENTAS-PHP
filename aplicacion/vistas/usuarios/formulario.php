<?php
$es_editar=false;
$accion="index.php?c=usuarios&a=crear";
$titulo="Nuevo Usuario";
$texto_btn="Crear";

if(isset($modo) && $modo==="editar"){
    $es_editar=true;
    $accion="index.php?c=usuarios&a=actualizar";
    $titulo="Editar Usuario";
    $texto_btn="Guardar cambios";
}

$id=(int)($u["id"]??0);
$usuario=(string)($u["usuario"]??"");
$rol=(string)($u["rol"]??"VENDEDOR");
$activo=(int)($u["activo"]??1);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?= htmlspecialchars($titulo) ?></h3>
  <a class="btn btn-outline-secondary" href="index.php?c=usuarios&a=index">Volver</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="POST" action="<?= htmlspecialchars($accion) ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($es_editar): ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input class="form-control" name="usuario" value="<?= htmlspecialchars($usuario) ?>" placeholder="Ingresar usuario">
      </div>

      <div class="mb-3">
        <label class="form-label">Contraseña <?= $es_editar ? "(solo si querés cambiarla)" : "" ?></label>
        <input type="password" class="form-control" name="clave" placeholder="<?= $es_editar ? "Dejar vacío para mantener" : "Ingresar contraseña" ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Rol</label>
        <select class="form-select" name="rol">
          <option value="VENDEDOR" <?= ($rol === "VENDEDOR") ? "selected" : "" ?>>VENDEDOR</option>
          <option value="ADMIN" <?= ($rol === "ADMIN") ? "selected" : "" ?>>ADMIN</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Activo</label>
        <select class="form-select" name="activo">
          <option value="1" <?= ($activo === 1) ? "selected" : "" ?>>Sí</option>
          <option value="0" <?= ($activo === 0) ? "selected" : "" ?>>No</option>
        </select>
      </div>

      <button class="btn btn-primary"><?= htmlspecialchars($texto_btn) ?></button>
    </form>
  </div>
</div>