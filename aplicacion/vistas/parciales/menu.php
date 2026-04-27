<?php
$logueado = false;
$usuario = "";
$rol = "";

if (isset($_SESSION["usuario_logueado"])) {
    $logueado = true;
    $usuario = (string)($_SESSION["usuario_logueado"]["usuario"] ?? "");
    $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php?c=ventas&a=lista">Ventas</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <?php if ($logueado): ?>
          <li class="nav-item">
            <a class="nav-link menu-icono" href="index.php?c=ventas&a=lista">
              <i class="bi bi-receipt-fill icono-ventas"></i>
              <span>Ventas</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-icono" href="index.php?c=ventas&a=nueva">
              <i class="bi bi-cart-plus-fill icono-nueva"></i>
              <span>Nueva venta</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-icono" href="index.php?c=clientes&a=index">
              <i class="bi bi-people-fill icono-clientes"></i>
              <span>Clientes</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-icono" href="index.php?c=stock&a=index">
              <i class="bi bi-box-seam-fill icono-stock"></i>
              <span>Stock</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-icono" href="index.php?c=productos&a=index">
              <i class="bi bi-bag-fill icono-productos"></i>
              <span>Productos</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-icono" href="index.php?c=reparaciones&a=index">
              <i class="bi bi-tools icono-reparaciones"></i>
              <span>Reparaciones</span>
            </a>
          </li>

          <?php if ($rol === "ADMIN"): ?>
          <li class="nav-item">
            <a class="nav-link menu-icono" href="index.php?c=usuarios&a=index">
              <i class="bi bi-person-gear icono-usuarios"></i>
              <span>Usuarios</span>
            </a>
          </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav">
        <?php if ($logueado): ?>
          <li class="nav-item">
            <span class="navbar-text text-white-50 me-3">
              <?= htmlspecialchars($usuario) ?> (<?= htmlspecialchars($rol) ?>)
            </span>
          </li>
          <li class="nav-item"><a class="nav-link" href="index.php?c=auth&a=salir">Salir</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="index.php?c=auth&a=login">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
