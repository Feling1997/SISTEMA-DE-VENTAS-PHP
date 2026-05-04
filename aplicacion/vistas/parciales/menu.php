<?php
$logueado = false;
$usuario = "";
$rol = "";
$menu_inicio = null;
$modulos_visibles = [];
$modulos_config = [];
$url_actual = (string)($_SERVER["REQUEST_URI"] ?? "index.php?c=ventas&a=inicio");

if (isset($_SESSION["usuario_logueado"])) {
    $logueado = true;
    $usuario = (string)($_SESSION["usuario_logueado"]["usuario"] ?? "");
    $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
    $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
    $permitidos = menu_modulos_permitidos_por_rol($rol);
    $claves_visibles = menu_obtener_preferencias_usuario($id_usuario, $rol);
    if (isset($permitidos["inicio"]))
        $menu_inicio = $permitidos["inicio"];
    foreach ($claves_visibles as $clave) {
        if (isset($permitidos[$clave]))
            $modulos_visibles[$clave] = $permitidos[$clave];
    }
    foreach ($permitidos as $clave => $modulo) {
        if ($clave === "inicio")
            continue;
        $modulo["clave"] = $clave;
        $modulo["activo"] = isset($modulos_visibles[$clave]);
        $modulos_config[$clave] = $modulo;
    }
}
?>
<nav class="app-navbar">
  <div class="container">
    <div class="app-navbar-row">
      <?php if ($logueado && $menu_inicio !== null): ?>
        <a class="menu-icono menu-icono-inline" href="<?= htmlspecialchars($menu_inicio["url"]) ?>">
          <i class="bi <?= htmlspecialchars($menu_inicio["icono"]) ?> <?= htmlspecialchars($menu_inicio["clase"]) ?>"></i>
          <span><?= htmlspecialchars($menu_inicio["texto"]) ?></span>
        </a>
      <?php endif; ?>

      <a class="navbar-brand app-brand" href="index.php?c=ventas&a=inicio">
        <span class="app-brand-mark">
          <i class="bi bi-shop-window"></i>
        </span>
        <span class="app-brand-text">Sistema de Ventas</span>
      </a>

      <?php if ($logueado): ?>
        <div class="app-module-bar-inline">
          <?php foreach ($modulos_visibles as $modulo): ?>
            <a class="menu-icono menu-icono-inline" href="<?= htmlspecialchars($modulo["url"]) ?>">
              <i class="bi <?= htmlspecialchars($modulo["icono"]) ?> <?= htmlspecialchars($modulo["clase"]) ?>"></i>
              <span><?= htmlspecialchars($modulo["texto"]) ?></span>
            </a>
          <?php endforeach; ?>
        </div>

        <details class="menu-config">
          <summary>Barra</summary>
          <form method="POST" action="index.php?c=ventas&a=guardar_menu" class="menu-config-panel">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="volver" value="<?= htmlspecialchars($url_actual) ?>">
            <div class="menu-config-title">Elegí qué módulos querés ver arriba</div>
            <?php foreach ($modulos_config as $modulo): ?>
              <label class="menu-config-item">
                <input type="checkbox" name="modulos_menu[]" value="<?= htmlspecialchars($modulo["clave"]) ?>" <?= $modulo["activo"] ? "checked" : "" ?>>
                <span><?= htmlspecialchars($modulo["texto"]) ?></span>
              </label>
            <?php endforeach; ?>
            <button class="btn btn-sm btn-primary w-100 mt-2">Guardar barra</button>
          </form>
        </details>

        <div class="app-navbar-user">
          <?php if ($rol === "ADMIN"): ?>
            <a class="btn btn-sm btn-outline-light" href="index.php?c=configuraciones&a=sistema">Config</a>
          <?php endif; ?>
          <span class="app-user-pill"><?= htmlspecialchars($usuario) ?> <small><?= htmlspecialchars($rol) ?></small></span>
          <a class="btn btn-sm btn-outline-light" href="index.php?c=auth&a=salir">Salir</a>
        </div>
      <?php else: ?>
        <div class="app-navbar-user">
          <a class="btn btn-sm btn-outline-light" href="index.php?c=auth&a=login">Login</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>
