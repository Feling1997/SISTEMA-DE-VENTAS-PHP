<?php
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Nueva venta</h3>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=lista">Ir a lista</a>
</div>

<!-- CABECERA: CLIENTE + ACCIONES -->
<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-lg-7">
        <label class="form-label mb-1">Cliente (opcional)</label>

        <!-- Buscador cliente -->
        <input id="buscarCliente" class="form-control mb-2" placeholder="Buscar cliente por nombre o DNI..." autocomplete="off">

        <select id="selectCliente" class="form-select" name="id_cliente_form" aria-label="Cliente">
          <option value="1">#1 - Consumidor Final</option>
          <?php foreach ($clientes as $c): ?>
            <?php if ((int)$c["id"] !== 1): ?>
              <option value="<?= (int)$c["id"] ?>">
                #<?= (int)$c["id"] ?> - <?= htmlspecialchars($c["nombre"]) ?>
                <?= !empty($c["dni"]) ? (" (DNI: " . htmlspecialchars($c["dni"]) . ")") : "" ?>
              </option>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>

        <div class="form-text">Tip: escribí parte del nombre o DNI y te filtra la lista.</div>
      </div>

      <div class="col-lg-5">
        <div class="mt-2 text-end">
          <span class="me-2 text-muted">Total:</span>
          <strong class="see-total">$<?= htmlspecialchars((string)$total) ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Agregar producto</h5>
        <label class="form-label mb-1">Buscar producto</label>
        <input id="buscarProducto" class="form-control mb-2" placeholder="Buscar por nombre o código de barras..." autocomplete="off">
        <form method="POST" action="index.php?c=ventas&a=agregar">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <div class="mb-2">
            <label class="form-label">Producto</label>
            <select class="form-select" id="selectProducto" name="id_producto" required>
              <option value="" selected disabled>— Seleccioná —</option>
              <?php foreach ($productos as $p): ?>
                <option value="<?= (int)$p["id"] ?>"
                        data-cb="<?= htmlspecialchars((string)($p["cod_barras"] ?? "")) ?>"
                        data-nombre="<?= htmlspecialchars((string)($p["nombre"] ?? "")) ?>">
                  <?= htmlspecialchars($p["nombre"]) ?>
                  | $<?= htmlspecialchars($p["precio_final"]) ?>
                  | CB: <?= htmlspecialchars($p["cod_barras"]) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-2">
              <label class="form-label">Cantidad</label>
              <input type="number" step="0.001" class="form-control" name="cantidad" value="1" required>
            </div>
            <div class="col-6 mb-2">
              <label class="form-label">Descuento</label>
              <div class="input-group">
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="descuento" value="0">
                <span class="input-group-text">%</span>
              </div>
            </div>
          </div>
          <button class="btn btn-primary w-100">Agregar al carrito</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <h5>Carrito</h5>

        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>Producto</th>
                <th style="text-align:right;">Cant</th>
                <th style="text-align:right;">P.Unit</th>
                <th style="text-align:right;">Desc</th>
                <th style="text-align:right;">Sub</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($carrito as $it): ?>
              <?php
                $sub = Venta::calcular_subtotal((float)$it["cantidad"], (float)$it["precio_unit"], (float)$it["descuento"]);
              ?>
              <tr>
                <td><?= htmlspecialchars($it["nombre"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$it["cantidad"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$it["precio_unit"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$it["descuento"]) ?> %</td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$sub) ?></td>
                <td style="text-align:right;">
                  <a class="btn btn-sm btn-outline-danger"
                     href="index.php?c=ventas&a=quitar&id_producto=<?= (int)$it["id_producto"] ?>"
                     onclick="return confirm('¿Quitar item?');">Quitar</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($carrito) === 0): ?>
              <tr><td colspan="6" class="text-center text-muted">Carrito vacío.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <hr>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-danger w-25"
              href="index.php?c=ventas&a=vaciar"
              onclick="return confirm('¿Vaciar carrito?');">
              Vaciar
            </a>
            <form id="formConfirmarBottom" method="POST" action="index.php?c=ventas&a=confirmar" class="flex-grow-1 m-0">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="id_cliente" id="idClienteHiddenBottom" value="1">
              <button class="btn btn-success w-100"
                      <?= (count($carrito) === 0) ? "disabled" : "" ?>
                      onclick="return confirm('¿Confirmar venta?');">
                Confirmar venta (genera PDF)
              </button>
            </form>
          </div>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  const inputCli = document.getElementById('buscarCliente');
  const selCli   = document.getElementById('selectCliente');
  const hidBot   = document.getElementById('idClienteHiddenBottom');
  const cliOptionsHTML = (selCli ? selCli.innerHTML : '');
  function syncClienteHidden(){
    let valor = '1';
    if (selCli && selCli.value)
      valor = selCli.value;
    if (hidBot)
      hidBot.value = valor;
  }

  function filtrarClientes(){
    let habilitado = true;
    if (!selCli)
      habilitado = false;
    if (habilitado) {
      const texto = (inputCli ? inputCli.value : '').toLowerCase().trim();
      if (texto === '')
        selCli.innerHTML = cliOptionsHTML;
      else {
        const temp = document.createElement('select');
        temp.innerHTML = cliOptionsHTML;
        const opciones = Array.from(temp.querySelectorAll('option'));
        selCli.innerHTML = '';
        opciones.forEach(op => {
          let mostrar = false;
          if (op.value === '1')
            mostrar = true;
          else if ((op.textContent || '').toLowerCase().includes(texto))
            mostrar = true;
          if (mostrar)
            selCli.appendChild(op);
      });
      }
    }
    syncClienteHidden();
  }
  if (selCli) {
    selCli.addEventListener('change', syncClienteHidden);
    syncClienteHidden();
  }
  if (inputCli)
    inputCli.addEventListener('input', filtrarClientes);
  //buscar por nombre o codigo de barras
  const inputProd = document.getElementById('buscarProducto');
  const selProd   = document.getElementById('selectProducto');
  const prodOptionsHTML = (selProd ? selProd.innerHTML : '');

  function seleccionarCBExacto(valor){
    let encontrado = false;
    if (selProd && valor !== '') {
      const temp = document.createElement('select');
      temp.innerHTML = prodOptionsHTML;
      const opciones = Array.from(temp.querySelectorAll('option'));
      opciones.forEach(op => {
        if (!encontrado && op.value) {
          const cb = (op.getAttribute('data-cb') || '').trim();
          if (cb === valor) {
            selProd.innerHTML = prodOptionsHTML;
            selProd.value = op.value;
            encontrado = true;
          }
        }
      });
    }
    return encontrado;
  }

  function filtrarProductos(){
    let habilitado = true;
    if (!selProd)
      habilitado = false;
    if (habilitado) {
      const texto = (inputProd ? inputProd.value : '').toLowerCase().trim();
      if (texto === '')
        selProd.innerHTML = prodOptionsHTML;
      else {
        const temp = document.createElement('select');
        temp.innerHTML = prodOptionsHTML;
        const opciones = Array.from(temp.querySelectorAll('option'));
        selProd.innerHTML = '';
        opciones.forEach(op => {
          let mostrar = false;
          if (!op.value)
            mostrar = true;
          else {
            const nombre = (op.textContent || '').toLowerCase();
            const cb = (op.getAttribute('data-cb') || '').toLowerCase();
            if (nombre.includes(texto) || cb.includes(texto))
              mostrar = true;
          }
          if (mostrar)
            selProd.appendChild(op);
        });
      }
    }
  }
  if (inputProd && selProd) {
    inputProd.addEventListener('input', function(){
      const valor = inputProd.value.trim();
      let exacto = false;
      if (valor !== '')
        exacto = seleccionarCBExacto(valor);
      if (!exacto)
        filtrarProductos();
    });
    inputProd.addEventListener('keydown', function(e){
      if (e.key === 'Enter') {
        e.preventDefault();
        const valor = inputProd.value.trim();
        let exacto = false;
        if (valor !== '')
          exacto = seleccionarCBExacto(valor);
        if (!exacto)
          filtrarProductos();
      }
    });
  }
})();
</script>
