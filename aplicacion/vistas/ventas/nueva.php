<?php
$form_venta = $form_venta ?? [];
$id_cliente_actual = (int)($form_venta["id_cliente"] ?? 1);
$buscar_cliente_actual = (string)($form_venta["buscar_cliente"] ?? "");
$id_producto_actual = (string)($form_venta["id_producto"] ?? "");
$cantidad_actual = (string)($form_venta["cantidad"] ?? "1");
$descuento_actual = (string)($form_venta["descuento"] ?? "0");
$tipo_comprobante_actual = (int)($form_venta["tipo_comprobante"] ?? 6);
$buscar_producto_actual = (string)($form_venta["buscar_producto"] ?? "");
$tipos_comprobante = FacturaFiscal::tipos_comprobante();
if (!isset($tipos_comprobante[$tipo_comprobante_actual]))
  $tipo_comprobante_actual = 6;
$tipo_comprobante_info = $tipos_comprobante[$tipo_comprobante_actual];
$cliente_actual_nombre = "Consumidor Final";
$clientes_json = [];
foreach ($clientes as $cliente_item) {
  $cliente_texto = (string)($cliente_item["nombre"] ?? "");
  if ((int)($cliente_item["id"] ?? 0) === 1 && $cliente_texto === "") {
    $cliente_texto = "Consumidor Final";
  }
  $tipo_doc_cliente = (string)($cliente_item["tipo_documento"] ?? "DNI");
  if (!empty($cliente_item["dni"])) {
    $cliente_texto .= " - " . $tipo_doc_cliente . ": " . (string)$cliente_item["dni"];
  }
  $condicion_cliente = (string)($cliente_item["condicion_iva"] ?? "");
  $clientes_json[] = [
    "id" => (int)($cliente_item["id"] ?? 0),
    "texto" => $cliente_texto,
    "documento" => (string)($cliente_item["dni"] ?? ""),
    "tipo_documento" => $tipo_doc_cliente,
    "condicion_iva" => $condicion_cliente
  ];
  if ((int)($cliente_item["id"] ?? 0) === $id_cliente_actual) {
    $cliente_actual_nombre = $cliente_texto;
  }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Nueva venta</h3>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=lista">Ir a lista</a>
</div>

<div class="sales-workspace">
  <div class="card sales-summary-card sales-client-card mb-3">
    <div class="card-body p-4">
      <div class="sales-client-layout">
        <div class="invoice-type-card">
          <div class="invoice-type-letter" id="facturaLetra"><?= htmlspecialchars($tipo_comprobante_info["letra"]) ?></div>
          <div class="invoice-type-body">
            <label class="form-label mb-1" for="tipoComprobanteTop">Tipo de comprobante</label>
            <select class="form-select" id="tipoComprobanteTop">
              <?php foreach ($tipos_comprobante as $codigo => $info): ?>
                <option value="<?= (int)$codigo ?>"
                        data-letra="<?= htmlspecialchars($info["letra"]) ?>"
                        data-requisito="<?= htmlspecialchars($info["requisito"]) ?>"
                        <?= $tipo_comprobante_actual === (int)$codigo ? "selected" : "" ?>>
                  <?= htmlspecialchars($info["texto"]) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invoice-type-rule" id="facturaRegla"><?= htmlspecialchars($tipo_comprobante_info["requisito"]) ?></div>
          </div>
        </div>
        <div class="sales-client-main">
          <div class="sales-client-head">
            <div>
              <strong class="sales-client-title" id="clienteActualTitulo"><?= htmlspecialchars($cliente_actual_nombre) ?></strong>
            </div>
            <div class="sales-client-head-actions">
              <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleClientePanel">Seleccionar cliente</button>
            </div>
          </div>
          <div class="sales-client-picker">
            <div class="sales-client-input-group">
              <input id="clienteSelectorInput" class="form-control form-control-lg" list="listaClientesAuto" placeholder="Escrib&iacute; nombre o DNI..." autocomplete="off" value="<?= htmlspecialchars($buscar_cliente_actual !== "" ? $buscar_cliente_actual : $cliente_actual_nombre) ?>">
            </div>
            <div class="sales-client-panel d-none" id="clientePanel">
              <input id="clientePanelBuscar" class="form-control" placeholder="Filtrar clientes..." autocomplete="off" value="<?= htmlspecialchars($buscar_cliente_actual) ?>">
              <div id="clientePanelSelect" class="sales-client-list" role="listbox" aria-label="Lista de clientes">
                <button type="button" class="sales-client-option <?= $id_cliente_actual === 1 ? "active" : "" ?>" data-id="1">Consumidor Final</button>
                <?php foreach ($clientes as $c): ?>
                  <?php if ((int)$c["id"] !== 1): ?>
                    <?php
                      $texto_doc = "";
                      if (!empty($c["dni"]))
                        $texto_doc = (string)($c["tipo_documento"] ?? "DNI") . ": " . (string)$c["dni"];
                    ?>
                    <button type="button" class="sales-client-option <?= $id_cliente_actual === (int)$c["id"] ? "active" : "" ?>" data-id="<?= (int)$c["id"] ?>">
                      <strong><?= htmlspecialchars($c["nombre"]) ?></strong>
                      <span><?= htmlspecialchars(trim($texto_doc . " " . (string)($c["condicion_iva"] ?? ""))) ?></span>
                    </button>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
            <input id="buscarCliente" type="hidden" value="<?= htmlspecialchars($buscar_cliente_actual) ?>">
            <select id="selectCliente" class="d-none" name="id_cliente_form" aria-label="Cliente">
              <option value="1" <?= $id_cliente_actual === 1 ? "selected" : "" ?>>Consumidor Final</option>
              <?php foreach ($clientes as $c): ?>
                <?php if ((int)$c["id"] !== 1): ?>
                  <option value="<?= (int)$c["id"] ?>" <?= $id_cliente_actual === (int)$c["id"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($c["nombre"]) ?><?= !empty($c["dni"]) ? (" - DNI: " . htmlspecialchars($c["dni"])) : "" ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
            <datalist id="listaClientesAuto">
              <?php foreach ($clientes_json as $cliente_auto): ?>
                <option value="<?= htmlspecialchars((string)$cliente_auto["texto"]) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
        </div>
        <div class="sales-total-panel">
          <div class="sales-total-box">
            <div class="sales-total-inline">
              <span class="sales-total-label">Total:</span>
              <strong class="sales-total-amount"><?= htmlspecialchars(moneda_para_mostrar($total)) ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card sales-cart-card sales-detail-card">
    <div class="card-body p-4">
      <div class="sales-detail-head">
        <div>
          <h5 class="mb-1">Detalle de venta</h5>
        </div>
        <span class="cart-counter"><?= count($carrito) ?> item<?= count($carrito) === 1 ? "" : "s" ?></span>
      </div>

      <div class="card form-shell sales-add-panel mb-3">
        <div class="card-body p-4">
          <form method="POST" action="index.php?c=ventas&a=agregar" class="smart-form sales-add-form" id="formAgregarVenta">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="id_cliente" id="idClienteHiddenAgregar" value="<?= $id_cliente_actual ?>">
            <input type="hidden" name="buscar_cliente" id="buscarClienteHiddenAgregar" value="<?= htmlspecialchars($buscar_cliente_actual) ?>">
            <input type="hidden" name="tipo_comprobante" id="tipoComprobanteAgregar" value="<?= $tipo_comprobante_actual ?>">
            <div class="sales-add-grid">
              <div class="sales-field-wide">
                <input id="buscarProducto" name="buscar_producto" class="form-control form-control-lg" placeholder="Nombre o c&oacute;digo de barras..." autocomplete="off" value="<?= htmlspecialchars($buscar_producto_actual) ?>">
              </div>
              <div class="sales-field-wide">
                <select class="form-select form-select-lg" id="selectProducto" name="id_producto" required>
                  <option value="" selected disabled>Seleccion&aacute; un producto</option>
                  <?php foreach ($productos as $p): ?>
                    <option value="<?= (int)$p["id"] ?>"
                            data-cb="<?= htmlspecialchars((string)($p["cod_barras"] ?? "")) ?>"
                            data-nombre="<?= htmlspecialchars((string)($p["nombre"] ?? "")) ?>"
                            <?= $id_producto_actual !== "" && (int)$id_producto_actual === (int)$p["id"] ? "selected" : "" ?>>
                      <?= htmlspecialchars($p["nombre"]) ?> | <?= htmlspecialchars(moneda_para_mostrar($p["precio_final"] ?? 0)) ?> | CB: <?= htmlspecialchars($p["cod_barras"]) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <input type="number" step="0.001" class="form-control form-control-lg" name="cantidad" value="<?= htmlspecialchars($cantidad_actual) ?>" required>
              </div>
              <div>
                <div class="input-group input-group-lg">
                  <input type="number" step="0.01" min="0" max="100" class="form-control" name="descuento" value="<?= htmlspecialchars($descuento_actual) ?>">
                  <span class="input-group-text">%</span>
                </div>
              </div>
              <div class="sales-add-action">
                <button class="btn btn-primary btn-lg w-100">Agregar producto</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="sales-lines-panel">
        <div class="table-responsive">
          <table class="table table-striped align-middle sales-table">
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
              <?php $sub = Venta::calcular_subtotal((float)$it["cantidad"], (float)$it["precio_unit"], (float)$it["descuento"]); ?>
              <tr>
                <td><?= htmlspecialchars($it["nombre"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$it["cantidad"]) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars(moneda_para_mostrar($it["precio_unit"] ?? 0)) ?></td>
                <td style="text-align:right;"><?= htmlspecialchars((string)$it["descuento"]) ?> %</td>
                <td style="text-align:right;"><?= htmlspecialchars(moneda_para_mostrar($sub)) ?></td>
                <td style="text-align:right;">
                  <a class="btn btn-sm btn-outline-danger" href="index.php?c=ventas&a=quitar&id_producto=<?= (int)$it["id_producto"] ?>" onclick="return confirm('&iquest;Quitar item?');">Quitar</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($carrito) === 0): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Todav&iacute;a no hay productos cargados.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="sales-detail-footer">
          <div class="sales-footer-actions">
            <a class="btn btn-outline-danger" href="index.php?c=ventas&a=vaciar" onclick="return confirm('&iquest;Vaciar detalle?');">Vaciar</a>
            <form id="formConfirmarBottom" method="POST" action="index.php?c=ventas&a=confirmar" class="m-0">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="id_cliente" id="idClienteHiddenBottom" value="<?= $id_cliente_actual ?>">
              <input type="hidden" name="buscar_cliente" id="buscarClienteHiddenBottom" value="<?= htmlspecialchars($buscar_cliente_actual) ?>">
              <input type="hidden" name="tipo_comprobante" id="tipoComprobanteBottom" value="<?= $tipo_comprobante_actual ?>">
              <button class="btn btn-success w-100" id="btnConfirmarComprobante" <?= count($carrito) === 0 ? "disabled" : "" ?> onclick="return confirm('&iquest;Confirmar comprobante?');">
                Confirmar comprobante
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  const inputCli = document.getElementById('buscarCliente');
  const clienteSelectorInput = document.getElementById('clienteSelectorInput');
  const clientePanel = document.getElementById('clientePanel');
  const clientePanelBuscar = document.getElementById('clientePanelBuscar');
  const clientePanelSelect = document.getElementById('clientePanelSelect');
  const toggleClientePanel = document.getElementById('toggleClientePanel');
  const selCli = document.getElementById('selectCliente');
  const hidBot = document.getElementById('idClienteHiddenBottom');
  const hidAdd = document.getElementById('idClienteHiddenAgregar');
  const buscarCliBottom = document.getElementById('buscarClienteHiddenBottom');
  const buscarCliAdd = document.getElementById('buscarClienteHiddenAgregar');
  const clienteActualTitulo = document.getElementById('clienteActualTitulo');
  const cliOptionsHTML = selCli ? selCli.innerHTML : '';
  const cliPanelOptionsHTML = clientePanelSelect ? clientePanelSelect.innerHTML : '';
  const tipoComprobanteTop = document.getElementById('tipoComprobanteTop');
  const tipoComprobanteBottom = document.getElementById('tipoComprobanteBottom');
  const tipoComprobanteAgregar = document.getElementById('tipoComprobanteAgregar');
  const facturaLetra = document.getElementById('facturaLetra');
  const facturaRegla = document.getElementById('facturaRegla');
  const btnConfirmarComprobante = document.getElementById('btnConfirmarComprobante');
  const clientesData = <?= json_encode($clientes_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const inputProd = document.getElementById('buscarProducto');
  const selProd = document.getElementById('selectProducto');
  const prodOptionsHTML = selProd ? selProd.innerHTML : '';
  const storageKey = 'ventas.nueva.form';

  function guardarEstadoLocal() {
    const estado = {
      cliente: selCli ? selCli.value : '1',
      buscarCliente: inputCli ? inputCli.value : '',
      tipoComprobante: tipoComprobanteTop ? tipoComprobanteTop.value : '6'
    };
    localStorage.setItem(storageKey, JSON.stringify(estado));
  }

  function cargarEstadoLocal() {
    try {
      const bruto = localStorage.getItem(storageKey);
      if (!bruto)
        return;
      const estado = JSON.parse(bruto);
      if (clienteSelectorInput && !clienteSelectorInput.value)
        clienteSelectorInput.value = estado.buscarCliente || '';
      if (inputCli)
        inputCli.value = estado.buscarCliente || '';
      if (clientePanelBuscar)
        clientePanelBuscar.value = estado.buscarCliente || '';
      if (selCli && estado.cliente)
        selCli.value = estado.cliente;
      if (tipoComprobanteTop && estado.tipoComprobante)
        tipoComprobanteTop.value = estado.tipoComprobante;
    } catch (e) {}
  }

  function syncTipoComprobante() {
    const valor = tipoComprobanteTop ? tipoComprobanteTop.value : '6';
    const opcion = tipoComprobanteTop ? tipoComprobanteTop.options[tipoComprobanteTop.selectedIndex] : null;
    if (tipoComprobanteBottom)
      tipoComprobanteBottom.value = valor;
    if (tipoComprobanteAgregar)
      tipoComprobanteAgregar.value = valor;
    if (facturaLetra)
      facturaLetra.textContent = opcion ? (opcion.dataset.letra || 'B') : 'B';
    if (facturaRegla)
      facturaRegla.textContent = opcion ? (opcion.dataset.requisito || '') : '';
    if (btnConfirmarComprobante) {
      const texto = opcion ? (opcion.textContent || '').trim() : 'comprobante';
      btnConfirmarComprobante.textContent = texto === 'Presupuesto' ? 'Generar presupuesto' : 'Confirmar ' + texto;
    }
    guardarEstadoLocal();
  }

  function obtenerTextoClientePorId(id) {
    for (let i = 0; i < clientesData.length; i++) {
      if (String(clientesData[i].id) === String(id))
        return clientesData[i].texto;
    }
    return 'Consumidor Final';
  }

  function obtenerIdClientePorTexto(texto) {
    const textoNorm = (texto || '').toLowerCase().trim();
    if (textoNorm === '')
      return '';
    for (let i = 0; i < clientesData.length; i++) {
      if ((clientesData[i].texto || '').toLowerCase() === textoNorm)
        return String(clientesData[i].id);
    }
    return '';
  }

  function syncClienteHidden(){
    const valor = selCli && selCli.value ? selCli.value : '1';
    const texto = clienteSelectorInput ? clienteSelectorInput.value : '';
    const seleccionado = obtenerTextoClientePorId(valor);
    if (hidBot)
      hidBot.value = valor;
    if (hidAdd)
      hidAdd.value = valor;
    if (buscarCliBottom)
      buscarCliBottom.value = texto;
    if (buscarCliAdd)
      buscarCliAdd.value = texto;
    if (inputCli)
      inputCli.value = texto;
    if (clientePanelBuscar && document.activeElement !== clientePanelBuscar)
      clientePanelBuscar.value = texto;
    if (clientePanelSelect) {
      clientePanelSelect.querySelectorAll('.sales-client-option').forEach(function(btn){
        btn.classList.toggle('active', String(btn.dataset.id || '') === String(valor));
      });
    }
    if (clienteActualTitulo)
      clienteActualTitulo.textContent = seleccionado || 'Consumidor Final';
    guardarEstadoLocal();
  }

  function filtrarClientes(textoEntrada){
    if (!clientePanelSelect)
      return;
    const texto = (textoEntrada || '').toLowerCase().trim();
    const seleccionado = selCli ? selCli.value : '1';
    if (texto === '') {
      clientePanelSelect.innerHTML = cliPanelOptionsHTML;
    } else {
      const temp = document.createElement('div');
      temp.innerHTML = cliPanelOptionsHTML;
      const opciones = Array.from(temp.querySelectorAll('.sales-client-option'));
      clientePanelSelect.innerHTML = '';
      opciones.forEach(op => {
        const contenido = (op.textContent || '').toLowerCase();
        if (contenido.includes(texto))
          clientePanelSelect.appendChild(op);
      });
    }
    activarBotonCliente(seleccionado);
    enlazarBotonesCliente();
  }

  function aplicarClienteDesdeTexto() {
    if (!clienteSelectorInput || !selCli)
      return;
    const idExacto = obtenerIdClientePorTexto(clienteSelectorInput.value);
    if (idExacto !== '') {
      selCli.value = idExacto;
      syncClienteHidden();
      return true;
    }
    return false;
  }

  function seleccionarClienteDesdePanel() {
    if (!clientePanelSelect || !selCli)
      return;
    const activo = clientePanelSelect.querySelector('.sales-client-option.active') || clientePanelSelect.querySelector('.sales-client-option');
    const valor = activo ? (activo.dataset.id || '1') : '1';
    selCli.value = valor;
    if (clienteSelectorInput)
      clienteSelectorInput.value = obtenerTextoClientePorId(valor);
    syncClienteHidden();
  }

  function activarBotonCliente(valor) {
    if (!clientePanelSelect)
      return;
    clientePanelSelect.querySelectorAll('.sales-client-option').forEach(function(btn){
      btn.classList.toggle('active', String(btn.dataset.id || '') === String(valor));
    });
  }

  function enlazarBotonesCliente() {
    if (!clientePanelSelect)
      return;
    clientePanelSelect.querySelectorAll('.sales-client-option').forEach(function(btn){
      if (btn.dataset.bound === '1')
        return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', function(){
        activarBotonCliente(btn.dataset.id || '1');
        seleccionarClienteDesdePanel();
      });
      btn.addEventListener('dblclick', function(){
        activarBotonCliente(btn.dataset.id || '1');
        seleccionarClienteDesdePanel();
        if (clientePanel)
          clientePanel.classList.add('d-none');
      });
    });
  }

  function seleccionarCBExacto(valor){
    if (!selProd || valor === '')
      return false;
    const temp = document.createElement('select');
    temp.innerHTML = prodOptionsHTML;
    const opciones = Array.from(temp.querySelectorAll('option'));
    for (let i = 0; i < opciones.length; i++) {
      const op = opciones[i];
      if (!op.value)
        continue;
      const cb = (op.getAttribute('data-cb') || '').trim();
      if (cb === valor) {
        selProd.innerHTML = prodOptionsHTML;
        selProd.value = op.value;
        guardarEstadoLocal();
        return true;
      }
    }
    return false;
  }

  function filtrarProductos(){
    if (!selProd)
      return;
    const texto = (inputProd ? inputProd.value : '').toLowerCase().trim();
    const seleccionado = selProd.value;
    if (texto === '') {
      selProd.innerHTML = prodOptionsHTML;
    } else {
      const temp = document.createElement('select');
      temp.innerHTML = prodOptionsHTML;
      const opciones = Array.from(temp.querySelectorAll('option'));
      selProd.innerHTML = '';
      opciones.forEach(op => {
        if (!op.value) {
          selProd.appendChild(op);
          return;
        }
        const nombre = (op.textContent || '').toLowerCase();
        const cb = (op.getAttribute('data-cb') || '').toLowerCase();
        if (nombre.includes(texto) || cb.includes(texto))
          selProd.appendChild(op);
      });
    }
    if (seleccionado)
      selProd.value = seleccionado;
    guardarEstadoLocal();
  }

  cargarEstadoLocal();
  filtrarClientes(clienteSelectorInput ? clienteSelectorInput.value : '');
  if (clienteSelectorInput && !aplicarClienteDesdeTexto())
    clienteSelectorInput.value = obtenerTextoClientePorId(selCli ? selCli.value : '1');
  if (inputProd && inputProd.value)
    filtrarProductos();
  syncTipoComprobante();
  syncClienteHidden();

  if (selCli)
    selCli.addEventListener('change', syncClienteHidden);
  if (toggleClientePanel && clientePanel) {
    toggleClientePanel.addEventListener('click', function(){
      clientePanel.classList.toggle('d-none');
      if (!clientePanel.classList.contains('d-none') && clientePanelBuscar) {
        clientePanelBuscar.value = '';
        filtrarClientes(clientePanelBuscar.value);
        clientePanelBuscar.focus();
      }
    });
  }
  if (clienteSelectorInput) {
    clienteSelectorInput.addEventListener('input', function(){
      if (inputCli)
        inputCli.value = clienteSelectorInput.value;
      filtrarClientes(clienteSelectorInput.value);
      aplicarClienteDesdeTexto();
      syncClienteHidden();
    });
    clienteSelectorInput.addEventListener('change', function(){
      if (!aplicarClienteDesdeTexto() && selCli)
        clienteSelectorInput.value = obtenerTextoClientePorId(selCli.value);
      syncClienteHidden();
    });
    clienteSelectorInput.addEventListener('keydown', function(e){
      if (e.key === 'Enter') {
        e.preventDefault();
        if (!aplicarClienteDesdeTexto() && clientePanelSelect && clientePanelSelect.querySelectorAll('.sales-client-option').length === 1) {
          activarBotonCliente(clientePanelSelect.querySelector('.sales-client-option').dataset.id || '1');
          seleccionarClienteDesdePanel();
        }
      }
    });
  }
  if (clientePanelBuscar) {
    clientePanelBuscar.addEventListener('input', function(){
      filtrarClientes(clientePanelBuscar.value);
    });
  }
  enlazarBotonesCliente();
  if (tipoComprobanteTop)
    tipoComprobanteTop.addEventListener('change', syncTipoComprobante);
  if (inputProd && selProd) {
    inputProd.addEventListener('input', function(){
      const valor = inputProd.value.trim();
      if (!seleccionarCBExacto(valor))
        filtrarProductos();
    });
    inputProd.addEventListener('keydown', function(e){
      if (e.key === 'Enter') {
        e.preventDefault();
        const valor = inputProd.value.trim();
        if (!seleccionarCBExacto(valor))
          filtrarProductos();
      }
    });
    selProd.addEventListener('change', guardarEstadoLocal);
  }
  document.querySelectorAll('#formAgregarVenta input, #formAgregarVenta select').forEach(function(el){
    if (el.name === 'cantidad' || el.name === 'descuento' || el.name === 'id_producto' || el.name === 'buscar_producto')
      return;
    el.addEventListener('change', guardarEstadoLocal);
    el.addEventListener('input', guardarEstadoLocal);
  });
})();
</script>
