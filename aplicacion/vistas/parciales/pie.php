</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<script>
(function () {
  function inicializarDataTables(root) {
    if (typeof DataTable === 'undefined')
      return;
    const tablas = [
      '#tablaClientes',
      '#tablaUsuarios',
      '#tablaProductos',
      '#tablaStockProductos',
      '#tablaStock',
      '#tablaReparaciones'
    ];
    tablas.forEach(function (selector) {
      const tabla = root.querySelector(selector);
      if (!tabla)
        return;
      if (tabla.dataset.dtReady === '1' || tabla.classList.contains('dataTable'))
        return;
      new DataTable(selector, {
        searching: false,
        language: {
          search: 'Buscar:',
          lengthMenu: 'Mostrar _MENU_',
          info: 'Mostrando _START_ a _END_ de _TOTAL_',
          infoEmpty: 'Sin datos',
          zeroRecords: 'No se encontraron resultados',
          paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
        }
      });
      tabla.dataset.dtReady = '1';
    });
  }

  function construirUrl(form) {
    const action = form.getAttribute('action') || window.location.pathname;
    const method = (form.getAttribute('method') || 'get').toLowerCase();
    const data = new FormData(form);
    const params = new URLSearchParams();
    data.forEach(function (value, key) {
      params.append(key, value);
    });
    if (method === 'get')
      return action + (action.indexOf('?') === -1 ? '?' : '&') + params.toString();
    return action;
  }

  function actualizarContenido(form, url) {
    const selectorObjetivo = form.getAttribute('data-search-target') || '';
    const objetivoActual = selectorObjetivo ? document.querySelector(selectorObjetivo) : null;
    const contenedorActual = document.querySelector('.container.py-5');
    if (!contenedorActual)
      return;
    fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        if (selectorObjetivo && objetivoActual) {
          const objetivoNuevo = doc.querySelector(selectorObjetivo);
          if (!objetivoNuevo)
            return;
          objetivoActual.outerHTML = objetivoNuevo.outerHTML;
        } else {
          const contenedorNuevo = doc.querySelector('.container.py-5');
          if (!contenedorNuevo)
            return;
          contenedorActual.innerHTML = contenedorNuevo.innerHTML;
        }
        window.history.replaceState({}, '', url);
        enlazarBusquedas(document);
        inicializarDataTables(document);
      })
      .catch(function () {
        window.location.href = url;
      });
  }

  function enlazarBusquedas(root) {
    const forms = root.querySelectorAll('form[data-auto-submit-search="true"]');
    forms.forEach(function (form) {
      if (form.dataset.searchBound === '1')
        return;
      form.dataset.searchBound = '1';
      const inputBuscar = form.querySelector('input[name="buscar"]');
      const inputDesde = form.querySelector('input[name="fecha_desde"]');
      const inputHasta = form.querySelector('input[name="fecha_hasta"]');
      const selectCampo = form.querySelector('select[name="campo"]');
      const selectMetodo = form.querySelector('select[name="metodo"]');
      let timer = null;

      function enviarConDemora() {
        if (timer)
          clearTimeout(timer);
        timer = setTimeout(function () {
          actualizarContenido(form, construirUrl(form));
        }, 250);
      }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        actualizarContenido(form, construirUrl(form));
      });
      if (inputBuscar)
        inputBuscar.addEventListener('input', enviarConDemora);
      if (inputDesde)
        inputDesde.addEventListener('change', enviarConDemora);
      if (inputHasta)
        inputHasta.addEventListener('change', enviarConDemora);
      if (selectCampo)
        selectCampo.addEventListener('change', enviarConDemora);
      if (selectMetodo)
        selectMetodo.addEventListener('change', enviarConDemora);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    enlazarBusquedas(document);
    inicializarDataTables(document);
  });
})();
</script>
</body>
</html>
