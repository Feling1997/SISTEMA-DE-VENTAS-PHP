<?php
$modulos = $modulos ?? [];
?>
<section class="home-hero mb-4">
  <div class="home-hero-content">
    <div>
      <div class="hero-brand mb-3">
        <span class="hero-brand-mark"><i class="bi bi-shop-window"></i></span>
        <div class="hero-brand-text">
          <strong>Ventas</strong>
          <small>Sistema de gestión</small>
        </div>
      </div>
      <h1 class="home-title">Panel principal</h1>
      <p class="home-subtitle">Entrá al módulo que necesitás desde un panel simple, visual y fácil de entender.</p>
    </div>
    <div class="home-badge">
      <i class="bi bi-mouse2-fill"></i>
      <span>Acceso rápido</span>
    </div>
  </div>
</section>

<section class="desktop-grid">
  <?php foreach ($modulos as $modulo): ?>
    <a class="desktop-tile <?= htmlspecialchars($modulo["clase"]) ?>" href="<?= htmlspecialchars($modulo["url"]) ?>">
      <div class="desktop-icon">
        <i class="bi <?= htmlspecialchars($modulo["icono"]) ?>"></i>
      </div>
      <div class="desktop-title"><?= htmlspecialchars($modulo["titulo"]) ?></div>
      <div class="desktop-text"><?= htmlspecialchars($modulo["texto"]) ?></div>
    </a>
  <?php endforeach; ?>
</section>
