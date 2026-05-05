import json
import webbrowser
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlparse

from database import inicializar_base
from modelos import ESTADOS, validar_datos
from repositorio import ReparacionRepositorio
from tickets import armar_ticket_html


HOST = "127.0.0.1"
PORT = 8765
BASE_DIR = Path(__file__).resolve().parent
CONFIG_PATH = BASE_DIR / "comercio_config.json"


def config_vacia():
    datos = {
        "nombre": "",
        "telefono": "",
        "direccion": "",
        "documento": "",
        "email": "",
        "observaciones": "",
    }
    return datos


def cargar_config():
    datos = config_vacia()
    if CONFIG_PATH.exists():
        try:
            contenido = json.loads(CONFIG_PATH.read_text(encoding="utf-8"))
            if isinstance(contenido, dict):
                datos.update({clave: str(contenido.get(clave, "")).strip() for clave in datos})
        except (OSError, json.JSONDecodeError):
            datos = config_vacia()
    return datos


def guardar_config(datos):
    guardado = False
    limpio = config_vacia()
    for clave in limpio:
        limpio[clave] = str(datos.get(clave, "")).strip()
    try:
        CONFIG_PATH.write_text(json.dumps(limpio, ensure_ascii=False, indent=2), encoding="utf-8")
        guardado = True
    except OSError:
        guardado = False
    return guardado


def json_respuesta(datos):
    contenido = json.dumps(datos, ensure_ascii=False).encode("utf-8")
    return contenido


def leer_cuerpo(handler):
    largo = int(handler.headers.get("Content-Length", "0"))
    datos = {}
    if largo > 0:
        cuerpo = handler.rfile.read(largo).decode("utf-8")
        datos = json.loads(cuerpo)
    return datos


def normalizar_id(ruta):
    valor = 0
    partes = ruta.strip("/").split("/")
    if len(partes) >= 3 and partes[2].isdigit():
        valor = int(partes[2])
    return valor


class ReparacionesHandler(BaseHTTPRequestHandler):
    repositorio = ReparacionRepositorio()

    def do_GET(self):
        ruta = urlparse(self.path).path
        if ruta == "/":
            self._enviar_html(pagina_principal())
        elif ruta == "/api/config":
            self._enviar_json({"ok": True, "datos": cargar_config()})
        elif ruta == "/api/reparaciones":
            self._enviar_json({"ok": True, "datos": self.repositorio.listar()})
        elif ruta.startswith("/api/reparaciones/"):
            reparacion_id = normalizar_id(ruta)
            reparacion = self.repositorio.buscar_por_id(reparacion_id)
            self._enviar_json({"ok": reparacion is not None, "datos": reparacion})
        elif ruta.startswith("/ticket/"):
            self._enviar_ticket(ruta)
        else:
            self._enviar_json({"ok": False, "mensaje": "Ruta no encontrada"}, 404)

    def do_POST(self):
        ruta = urlparse(self.path).path
        if ruta == "/api/config":
            datos = leer_cuerpo(self)
            ok = guardar_config(datos)
            mensaje = "Datos del comercio guardados." if ok else "No se pudo guardar la configuracion."
            self._enviar_json({"ok": ok, "mensaje": mensaje})
        elif ruta == "/api/reparaciones":
            datos = leer_cuerpo(self)
            errores = validar_datos(datos)
            if errores:
                self._enviar_json({"ok": False, "mensaje": "\n".join(errores)}, 400)
            else:
                nuevo_id = self.repositorio.crear(datos)
                ok = nuevo_id > 0
                mensaje = "Reparacion creada correctamente." if ok else "No se pudo crear."
                self._enviar_json({"ok": ok, "mensaje": mensaje, "id": nuevo_id})
        else:
            self._enviar_json({"ok": False, "mensaje": "Ruta no encontrada"}, 404)

    def do_PUT(self):
        ruta = urlparse(self.path).path
        reparacion_id = normalizar_id(ruta)
        if reparacion_id > 0:
            datos = leer_cuerpo(self)
            errores = validar_datos(datos)
            if errores:
                self._enviar_json({"ok": False, "mensaje": "\n".join(errores)}, 400)
            else:
                ok = self.repositorio.actualizar(reparacion_id, datos)
                mensaje = "Reparacion actualizada correctamente." if ok else "No se pudo actualizar."
                self._enviar_json({"ok": ok, "mensaje": mensaje})
        else:
            self._enviar_json({"ok": False, "mensaje": "ID invalido"}, 400)

    def do_DELETE(self):
        ruta = urlparse(self.path).path
        reparacion_id = normalizar_id(ruta)
        if reparacion_id > 0:
            ok = self.repositorio.eliminar(reparacion_id)
            mensaje = "Reparacion eliminada correctamente." if ok else "No se pudo eliminar."
            self._enviar_json({"ok": ok, "mensaje": mensaje})
        else:
            self._enviar_json({"ok": False, "mensaje": "ID invalido"}, 400)

    def log_message(self, formato, *args):
        pass

    def _enviar_html(self, contenido):
        datos = contenido.encode("utf-8")
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
        self.send_header("Pragma", "no-cache")
        self.send_header("Content-Length", str(len(datos)))
        self.end_headers()
        self.wfile.write(datos)

    def _enviar_json(self, datos, codigo=200):
        contenido = json_respuesta(datos)
        self.send_response(codigo)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
        self.send_header("Pragma", "no-cache")
        self.send_header("Content-Length", str(len(contenido)))
        self.end_headers()
        self.wfile.write(contenido)

    def _enviar_ticket(self, ruta):
        partes = ruta.strip("/").split("/")
        reparacion_id = 0
        if len(partes) == 2 and partes[1].isdigit():
            reparacion_id = int(partes[1])

        reparacion = self.repositorio.buscar_por_id(reparacion_id)
        if reparacion:
            contenido = armar_ticket_html(reparacion).encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
            self.send_header("Pragma", "no-cache")
            self.send_header("Content-Length", str(len(contenido)))
            self.end_headers()
            self.wfile.write(contenido)
        else:
            self._enviar_json({"ok": False, "mensaje": "Reparacion no encontrada"}, 404)


def pagina_principal():
    estados = "".join(f"<option value='{clave}'>{nombre}</option>" for clave, nombre in ESTADOS.items())
    html = f"""<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reparaciones</title>
  <style>
    :root {{
      --azul:#0f2f45; --panel:#ffffff; --fondo:#eef2f7; --linea:#d8e0ea;
      --texto:#0f172a; --muted:#5b6b7f; --cyan:#0e7490; --verde:#16a34a;
      --amarillo:#ca8a04; --rojo:#dc2626; --violeta:#7c3aed;
    }}
    * {{ box-sizing:border-box; }}
    body {{ margin:0; font-family:Segoe UI, Arial, sans-serif; color:var(--texto); background:var(--fondo); }}
    .topbar {{ height:38px; background:var(--azul); display:flex; align-items:center; gap:4px; padding:0 6px; color:white; }}
    .topbar button {{ border:0; color:white; background:#123b56; padding:8px 13px; border-radius:8px; font-weight:600; cursor:pointer; }}
    .topbar button:hover, .topbar button.activo {{ background:#0e7490; }}
    .topbar .salir {{ margin-left:auto; background:#111827; }}
    main {{ padding:12px 18px 24px; }}
    .hero {{ min-height:132px; border-radius:0 0 18px 18px; background:linear-gradient(115deg,#155e75,#48aaa5); color:white; display:flex; align-items:center; padding:22px; box-shadow:0 16px 36px rgba(15,23,42,.15); }}
    .hero-icon {{ width:52px; height:52px; border-radius:15px; background:#f59e0b; color:#111827; display:grid; place-items:center; font-weight:800; margin-right:16px; }}
    .hero h1 {{ margin:10px 0 6px; font-size:28px; }}
    .hero p {{ margin:0; }}
    .hero .quick {{ margin-left:auto; border:1px solid rgba(255,255,255,.35); background:rgba(255,255,255,.18); color:white; padding:13px 20px; border-radius:14px; font-weight:700; cursor:pointer; }}
    .vista {{ display:none; }}
    .vista.activa {{ display:block; }}
    .cards {{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-top:22px; }}
    .card {{ background:var(--panel); border:1px solid var(--linea); border-radius:18px; min-height:136px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:18px; box-shadow:0 14px 30px rgba(15,23,42,.08); cursor:pointer; }}
    .card:hover {{ transform:translateY(-2px); box-shadow:0 18px 36px rgba(15,23,42,.12); }}
    .icon {{ width:54px; height:54px; border-radius:16px; color:white; display:grid; place-items:center; font-size:22px; font-weight:800; margin-bottom:10px; }}
    .card h3 {{ margin:0 0 7px; font-size:16px; }}
    .card p {{ margin:0; color:var(--muted); font-size:13px; }}
    .section-title {{ display:flex; justify-content:space-between; align-items:end; margin:16px 0 12px; }}
    .section-title h2 {{ margin:0; font-size:24px; }}
    .section-title p {{ margin:4px 0 0; color:var(--muted); }}
    .panel {{ background:white; border:1px solid var(--linea); border-radius:16px; padding:16px; box-shadow:0 12px 28px rgba(15,23,42,.07); }}
    form .grid {{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }}
    label {{ font-size:13px; color:#334155; font-weight:600; display:block; margin-bottom:5px; }}
    input, select, textarea {{ width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 11px; font:inherit; background:white; }}
    textarea {{ min-height:86px; resize:vertical; }}
    .full {{ grid-column:span 4; }}
    .acciones {{ margin-top:14px; display:flex; gap:8px; }}
    .btn {{ border:0; padding:10px 14px; border-radius:10px; color:white; font-weight:700; cursor:pointer; background:var(--cyan); }}
    .btn.sec {{ background:#475569; }} .btn.danger {{ background:var(--rojo); }} .btn.ok {{ background:var(--verde); }}
    .metrics {{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:12px; }}
    .metric {{ background:white; border:1px solid var(--linea); border-radius:14px; padding:12px; border-left:5px solid var(--cyan); }}
    .metric strong {{ display:block; font-size:24px; }} .metric span {{ color:var(--muted); font-size:13px; }}
    .tools {{ display:flex; gap:10px; align-items:center; margin-bottom:12px; }}
    .tools input {{ max-width:360px; }}
    .table-wrap {{ overflow:auto; border:1px solid var(--linea); border-radius:14px; }}
    table {{ width:100%; border-collapse:collapse; background:white; }}
    th,td {{ padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:13px; }}
    th {{ background:#e8edf3; font-weight:800; }}
    tr:hover {{ background:#f8fafc; cursor:pointer; }}
    tr.sel {{ outline:2px solid #38bdf8; background:#ecfeff; }}
    .layout-consulta {{ display:grid; grid-template-columns:minmax(0,1fr) 330px; gap:12px; }}
    .detail {{ white-space:pre-wrap; color:#334155; line-height:1.45; }}
    .badge {{ display:inline-block; padding:5px 9px; border-radius:999px; color:white; font-size:12px; font-weight:700; }}
    .PENDIENTE {{ background:var(--amarillo); }} .ENTREGADO {{ background:var(--violeta); }}
    @media (max-width:900px) {{ .cards,.metrics,form .grid,.layout-consulta {{ grid-template-columns:1fr; }} .full {{ grid-column:span 1; }} .topbar {{ overflow:auto; }} }}
  </style>
</head>
<body>
  <nav class="topbar">
    <button data-view="inicio" class="activo">Inicio</button>
    <button data-view="nueva">Nueva reparacion</button>
    <button data-view="consultas" data-estado="TODOS">Consultas</button>
    <button data-view="consultas" data-estado="PENDIENTE">Pendientes</button>
    <button data-view="consultas" data-estado="ENTREGADO">Entregados</button>
    <button data-view="config">Config</button>
    <button>admin</button>
    <button class="salir" onclick="window.close()">Salir</button>
  </nav>

  <main>
    <section id="inicio" class="vista activa">
      <div class="hero">
        <div class="hero-icon">REP</div>
        <div>
          <strong>Reparaciones</strong>
          <h1>Panel principal</h1>
          <p>Entra al modulo que necesitas desde un panel simple, visual y facil de entender.</p>
        </div>
        <button class="quick" data-view="nueva">Acceso rapido</button>
      </div>
      <div class="cards">
        <article class="card" data-view="nueva"><div class="icon" style="background:#0f766e">+</div><h3>Nueva reparacion</h3><p>Registrar ingreso, cliente, equipo, falla y precio.</p></article>
        <article class="card" data-view="consultas" data-estado="TODOS"><div class="icon" style="background:#2563eb">?</div><h3>Consultas</h3><p>Buscar, filtrar, editar y generar tickets.</p></article>
        <article class="card" data-view="consultas" data-estado="PENDIENTE"><div class="icon" style="background:#ca8a04">!</div><h3>Pendientes</h3><p>Ver equipos ingresados aun sin resolver.</p></article>
        <article class="card" data-view="consultas" data-estado="ENTREGADO"><div class="icon" style="background:#7c3aed">OK</div><h3>Entregados</h3><p>Historial de trabajos ya entregados.</p></article>
      </div>
    </section>

    <section id="nueva" class="vista">
      <div class="section-title"><div><h2>Nueva reparacion</h2><p>Carga fija de ingresos al taller.</p></div></div>
      <form id="formReparacion" class="panel">
        <input type="hidden" id="id">
        <div class="grid">
          <div><label>Cliente *</label><input id="cliente_nombre" required></div>
          <div><label>Telefono</label><input id="cliente_telefono"></div>
          <div><label>Marca</label><input id="marca"></div>
          <div><label>Modelo</label><input id="modelo"></div>
          <div><label>Garantia</label><input id="garantia" placeholder="30 dias, 3 meses"></div>
          <div><label>Precio</label><input id="precio" type="number" step="0.01" min="0"></div>
          <div><label>Estado</label><select id="estado">{estados}</select></div>
          <div><label>Ingreso</label><input id="fecha_ingreso" type="date"></div>
          <div><label>Entrega</label><input id="fecha_entrega" type="date"></div>
          <div class="full"><label>Falla reportada</label><textarea id="falla"></textarea></div>
          <div class="full"><label>Diagnostico</label><textarea id="diagnostico"></textarea></div>
          <div class="full"><label>Observaciones</label><textarea id="observaciones"></textarea></div>
        </div>
        <div class="acciones">
          <button class="btn ok" type="submit">Guardar</button>
          <button class="btn sec" type="button" id="limpiar">Limpiar</button>
          <button class="btn sec" type="button" data-view="consultas" data-estado="TODOS">Ir a consultas</button>
          <button class="btn danger" type="button" id="eliminar">Eliminar</button>
        </div>
      </form>
    </section>

    <section id="consultas" class="vista">
      <div class="section-title"><div><h2>Consultas</h2><p>Busqueda, filtros, estados, edicion y tickets.</p></div></div>
      <div class="metrics" id="metrics"></div>
      <div class="panel">
        <div class="tools">
          <input id="buscar" placeholder="Buscar por codigo, cliente, telefono, marca o modelo...">
          <select id="filtro"><option value="TODOS">Todos</option>{estados}</select>
          <button class="btn sec" id="limpiarFiltros">Limpiar filtros</button>
          <button class="btn" id="editar">Editar seleccionado</button>
          <button class="btn ok" id="ticket">Ticket</button>
        </div>
        <div class="layout-consulta">
          <div class="table-wrap"><table><thead><tr><th>Codigo</th><th>Cliente</th><th>Telefono</th><th>Equipo</th><th>Estado</th><th>Precio</th><th>Ingreso</th><th>Entrega</th></tr></thead><tbody id="tabla"></tbody></table></div>
          <aside class="panel detail" id="detalle">Seleccione una reparacion para ver el detalle.</aside>
        </div>
      </div>
    </section>

    <section id="config" class="vista">
      <div class="section-title"><div><h2>Configuracion</h2><p>Datos locales del programa y del comercio.</p></div></div>
      <form id="formConfig" class="panel">
        <div class="grid">
          <div><label>Nombre del comercio</label><input id="cfg_nombre" placeholder="Opcional"></div>
          <div><label>Telefono</label><input id="cfg_telefono" placeholder="Opcional"></div>
          <div><label>RUC / Documento</label><input id="cfg_documento" placeholder="Opcional"></div>
          <div><label>Email</label><input id="cfg_email" type="email" placeholder="Opcional"></div>
          <div class="full"><label>Direccion</label><input id="cfg_direccion" placeholder="Opcional"></div>
          <div class="full"><label>Observaciones del comercio</label><textarea id="cfg_observaciones" placeholder="Opcional"></textarea></div>
        </div>
        <div class="acciones">
          <button class="btn ok" type="submit">Guardar configuracion</button>
          <button class="btn sec" type="button" id="limpiarConfig">Limpiar</button>
        </div>
      </form>
      <div class="panel" style="margin-top:12px">
        <p><strong>Base de datos:</strong> reparaciones.db</p>
        <p><strong>Tickets:</strong> carpeta tickets</p>
        <p><strong>Configuracion:</strong> comercio_config.json</p>
        <p><strong>Servidor local:</strong> Python sin librerias externas</p>
      </div>
    </section>
  </main>

<script>
const estados = {json.dumps(ESTADOS, ensure_ascii=False)};
let reparaciones = [];
let seleccion = null;

function hoy() {{ return new Date().toISOString().slice(0, 10); }}
function qs(id) {{ return document.getElementById(id); }}

function vista(nombre, estado='TODOS') {{
  document.querySelectorAll('.vista').forEach(v => v.classList.remove('activa'));
  qs(nombre).classList.add('activa');
  document.querySelectorAll('.topbar button').forEach(b => b.classList.remove('activo'));
  document.querySelectorAll(`[data-view="${{nombre}}"]`).forEach(b => b.classList.add('activo'));
  if (nombre === 'nueva') limpiarFormulario();
  if (nombre === 'consultas') {{ qs('filtro').value = estado; cargar(); }}
  if (nombre === 'config') cargarConfig();
}}

document.addEventListener('click', e => {{
  const item = e.target.closest('[data-view]');
  if (item) vista(item.dataset.view, item.dataset.estado || 'TODOS');
}});

function datosForm() {{
  const datos = {{}};
  ['cliente_nombre','cliente_telefono','marca','modelo','garantia','precio','estado','fecha_ingreso','fecha_entrega','falla','diagnostico','observaciones'].forEach(id => datos[id] = qs(id).value.trim());
  datos.activo = true;
  return datos;
}}

function datosConfig() {{
  const datos = {{
    nombre: qs('cfg_nombre').value.trim(),
    telefono: qs('cfg_telefono').value.trim(),
    direccion: qs('cfg_direccion').value.trim(),
    documento: qs('cfg_documento').value.trim(),
    email: qs('cfg_email').value.trim(),
    observaciones: qs('cfg_observaciones').value.trim()
  }};
  return datos;
}}

function limpiarConfig() {{
  ['cfg_nombre','cfg_telefono','cfg_direccion','cfg_documento','cfg_email','cfg_observaciones'].forEach(id => qs(id).value = '');
}}

async function cargarConfig() {{
  const resp = await fetch('/api/config');
  const data = await resp.json();
  const cfg = data.datos || {{}};
  qs('cfg_nombre').value = cfg.nombre || '';
  qs('cfg_telefono').value = cfg.telefono || '';
  qs('cfg_direccion').value = cfg.direccion || '';
  qs('cfg_documento').value = cfg.documento || '';
  qs('cfg_email').value = cfg.email || '';
  qs('cfg_observaciones').value = cfg.observaciones || '';
}}

async function guardarConfig(e) {{
  e.preventDefault();
  const resp = await fetch('/api/config', {{method:'POST', headers:{{'Content-Type':'application/json'}}, body:JSON.stringify(datosConfig())}});
  const data = await resp.json();
  alert(data.mensaje);
}}

function limpiarFormulario() {{
  qs('formReparacion').reset();
  qs('id').value = '';
  qs('fecha_ingreso').value = hoy();
  qs('estado').value = 'PENDIENTE';
  qs('cliente_nombre').focus();
}}

async function guardar(e) {{
  e.preventDefault();
  const id = qs('id').value;
  const metodo = id ? 'PUT' : 'POST';
  const url = id ? `/api/reparaciones/${{id}}` : '/api/reparaciones';
  const resp = await fetch(url, {{method: metodo, headers: {{'Content-Type':'application/json'}}, body: JSON.stringify(datosForm())}});
  const data = await resp.json();
  alert(data.mensaje);
  if (data.ok) {{ limpiarFormulario(); }}
}}

async function eliminar() {{
  const id = qs('id').value || (seleccion && seleccion.id);
  if (id && confirm('Eliminar reparacion seleccionada?')) {{
    const resp = await fetch(`/api/reparaciones/${{id}}`, {{method:'DELETE'}});
    const data = await resp.json();
    alert(data.mensaje);
    if (data.ok) {{ limpiarFormulario(); cargar(); }}
  }}
}}

async function cargar() {{
  const resp = await fetch('/api/reparaciones');
  const data = await resp.json();
  reparaciones = data.datos || [];
  metricas();
  render();
}}

function metricas() {{
  const m = {{total:reparaciones.length, pendiente:0, entregado:0}};
  reparaciones.forEach(r => {{
    if (r.estado === 'PENDIENTE') m.pendiente++;
    if (r.estado === 'ENTREGADO') m.entregado++;
  }});
  qs('metrics').innerHTML = `
    <div class="metric"><strong>${{m.total}}</strong><span>Total activas</span></div>
    <div class="metric" style="border-left-color:#ca8a04"><strong>${{m.pendiente}}</strong><span>Pendientes</span></div>
    <div class="metric" style="border-left-color:#7c3aed"><strong>${{m.entregado}}</strong><span>Entregados</span></div>`;
}}

function filtradas() {{
  const texto = qs('buscar').value.toLowerCase().trim();
  const estado = qs('filtro').value;
  const lista = reparaciones.filter(r => {{
    const coincideEstado = estado === 'TODOS' || r.estado === estado;
    const contenido = [r.codigo,r.cliente_nombre,r.cliente_telefono,r.marca,r.modelo,r.falla,r.garantia].join(' ').toLowerCase();
    return coincideEstado && (!texto || contenido.includes(texto));
  }});
  return lista;
}}

function render() {{
  const lista = filtradas();
  qs('tabla').innerHTML = lista.map(r => `<tr data-id="${{r.id}}" class="${{seleccion && seleccion.id === r.id ? 'sel' : ''}}">
    <td>${{r.codigo}}</td><td>${{r.cliente_nombre}}</td><td>${{r.cliente_telefono || ''}}</td>
    <td>${{[r.marca,r.modelo].filter(Boolean).join(' ')}}</td><td><span class="badge ${{r.estado}}">${{estados[r.estado] || r.estado}}</span></td>
    <td>${{Number(r.precio || 0).toFixed(2)}}</td><td>${{r.fecha_ingreso || ''}}</td><td>${{r.fecha_entrega || ''}}</td>
  </tr>`).join('');
}}

function detalle(r) {{
  qs('detalle').textContent = `Codigo: ${{r.codigo}}
Cliente: ${{r.cliente_nombre}}
Telefono: ${{r.cliente_telefono || ''}}

Equipo: ${{[r.marca,r.modelo].filter(Boolean).join(' ')}}

Estado: ${{estados[r.estado] || r.estado}}
Precio: ${{Number(r.precio || 0).toFixed(2)}}
Garantia: ${{r.garantia || ''}}
Ingreso: ${{r.fecha_ingreso || ''}}
Entrega: ${{r.fecha_entrega || ''}}

Falla:
${{r.falla || ''}}

Diagnostico:
${{r.diagnostico || ''}}

Observaciones:
${{r.observaciones || ''}}`;
}}

function editarSeleccionado() {{
  if (seleccion) {{
    vista('nueva');
    Object.keys(seleccion).forEach(k => {{ if (qs(k)) qs(k).value = seleccion[k] || ''; }});
    qs('id').value = seleccion.id;
  }} else {{
    alert('Seleccione una reparacion.');
  }}
}}

qs('formReparacion').addEventListener('submit', guardar);
qs('formConfig').addEventListener('submit', guardarConfig);
qs('limpiar').addEventListener('click', limpiarFormulario);
qs('limpiarConfig').addEventListener('click', limpiarConfig);
qs('eliminar').addEventListener('click', eliminar);
qs('buscar').addEventListener('input', render);
qs('filtro').addEventListener('change', render);
qs('limpiarFiltros').addEventListener('click', () => {{ qs('buscar').value=''; qs('filtro').value='TODOS'; render(); }});
qs('editar').addEventListener('click', editarSeleccionado);
qs('ticket').addEventListener('click', () => {{ if (seleccion) window.open(`/ticket/${{seleccion.id}}`); else alert('Seleccione una reparacion.'); }});
qs('tabla').addEventListener('click', e => {{
  const tr = e.target.closest('tr');
  if (tr) {{
    seleccion = reparaciones.find(r => r.id === Number(tr.dataset.id));
    detalle(seleccion);
    render();
  }}
}});
limpiarFormulario();
</script>
</body>
</html>"""
    return html


def iniciar():
    inicializar_base()
    servidor = ThreadingHTTPServer((HOST, PORT), ReparacionesHandler)
    url = f"http://{HOST}:{PORT}"
    webbrowser.open(url)
    print(f"Sistema de reparaciones abierto en {url}")
    servidor.serve_forever()


if __name__ == "__main__":
    iniciar()
