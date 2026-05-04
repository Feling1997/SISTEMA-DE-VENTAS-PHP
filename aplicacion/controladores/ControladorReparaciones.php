<?php
require_once __DIR__ . "/../modelos/Reparacion.php";
require_once __DIR__ . "/../modelos/ConfiguracionSistema.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";

class ControladorReparaciones {
    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN", "VENDEDOR", "TECNICO"])) {
                flash_error("No tenes permisos para Reparaciones.");
                redirigir("index.php?c=ventas&a=lista");
            } else {
                $ok = true;
            }
        }
        return $ok;
    }

    public function index(): void {
        if ($this->permiso()) {
            $reparaciones = Reparacion::listar_todos();
            $estados = Reparacion::obtener_estados();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/reparaciones/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function nuevo(): void {
        if ($this->permiso()) {
            $modo = "crear";
            $p = [
                "id" => 0,
                "codigo" => "",
                "cliente_nombre" => "",
                "cliente_telefono" => "",
                "marca" => "",
                "modelo" => "",
                "imei" => "",
                "falla" => "",
                "diagnostico" => "",
                "garantia" => "",
                "estado" => "PENDIENTE",
                "precio" => 0,
                "fecha_ingreso" => date("Y-m-d"),
                "fecha_entrega" => "",
                "observaciones" => "",
                "activo" => 1
            ];
            $estados = Reparacion::obtener_estados();
            $csrf = csrf_token();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/reparaciones/formulario.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function guardar(): void {
        if (!$this->permiso()) {
            return;
        }

        if (!csrf_valido($_POST["csrf_token"] ?? "")) {
            flash_error("Token CSRF invalido.");
            redirigir("index.php?c=reparaciones&a=nuevo");
            return;
        }

        $datos = [
            "cliente_nombre" => obtener_post("cliente_nombre", ""),
            "cliente_telefono" => obtener_post("cliente_telefono", ""),
            "marca" => obtener_post("marca", ""),
            "modelo" => obtener_post("modelo", ""),
            "imei" => obtener_post("imei", ""),
            "falla" => obtener_post("falla", ""),
            "diagnostico" => obtener_post("diagnostico", ""),
            "garantia" => obtener_post("garantia", ""),
            "estado" => obtener_post("estado", "PENDIENTE"),
            "precio" => obtener_post("precio", 0),
            "fecha_ingreso" => obtener_post("fecha_ingreso", date("Y-m-d")),
            "fecha_entrega" => obtener_post("fecha_entrega", ""),
            "observaciones" => obtener_post("observaciones", ""),
            "id_usuario" => $_SESSION["usuario_logueado"]["id"] ?? 0
        ];

        if (texto_invalido($datos["cliente_nombre"])) {
            flash_error("El nombre del cliente es obligatorio.");
            redirigir("index.php?c=reparaciones&a=nuevo");
            return;
        }

        $id = Reparacion::crear($datos);
        if ($id !== false) {
            flash_ok("Reparacion creada correctamente.");
            redirigir("index.php?c=reparaciones&a=index");
        } else {
            flash_error("Error al crear la reparacion.");
            redirigir("index.php?c=reparaciones&a=nuevo");
        }
    }

    public function editar(): void {
        if (!$this->permiso()) {
            return;
        }

        $id = (int)obtener_get("id", 0);
        if ($id <= 0) {
            flash_error("ID invalido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $p = Reparacion::buscar_por_id($id);
        if (!$p) {
            flash_error("Reparacion no encontrada.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $modo = "editar";
        $estados = Reparacion::obtener_estados();
        $csrf = csrf_token();
        include __DIR__ . "/../vistas/parciales/encabezado.php";
        include __DIR__ . "/../vistas/reparaciones/formulario.php";
        include __DIR__ . "/../vistas/parciales/pie.php";
    }

    public function actualizar(): void {
        if (!$this->permiso()) {
            return;
        }

        if (!csrf_valido($_POST["csrf_token"] ?? "")) {
            flash_error("Token CSRF invalido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $id = (int)obtener_post("id", 0);
        if ($id <= 0) {
            flash_error("ID invalido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $datos = [
            "cliente_nombre" => obtener_post("cliente_nombre", ""),
            "cliente_telefono" => obtener_post("cliente_telefono", ""),
            "marca" => obtener_post("marca", ""),
            "modelo" => obtener_post("modelo", ""),
            "imei" => obtener_post("imei", ""),
            "falla" => obtener_post("falla", ""),
            "diagnostico" => obtener_post("diagnostico", ""),
            "garantia" => obtener_post("garantia", ""),
            "estado" => obtener_post("estado", "PENDIENTE"),
            "precio" => obtener_post("precio", 0),
            "fecha_ingreso" => obtener_post("fecha_ingreso", date("Y-m-d")),
            "fecha_entrega" => obtener_post("fecha_entrega", ""),
            "observaciones" => obtener_post("observaciones", ""),
            "activo" => obtener_post("activo", 1)
        ];

        if (texto_invalido($datos["cliente_nombre"])) {
            flash_error("El nombre del cliente es obligatorio.");
            redirigir("index.php?c=reparaciones&a=editar&id=" . $id);
            return;
        }

        $ok = Reparacion::actualizar($id, $datos);
        if ($ok) {
            flash_ok("Reparacion actualizada correctamente.");
            redirigir("index.php?c=reparaciones&a=index");
        } else {
            flash_error("Error al actualizar la reparacion.");
            redirigir("index.php?c=reparaciones&a=editar&id=" . $id);
        }
    }

    public function eliminar(): void {
        if (!$this->permiso()) {
            return;
        }

        $id = (int)obtener_get("id", 0);
        if ($id <= 0) {
            flash_error("ID invalido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $ok = Reparacion::eliminar($id);
        if ($ok) {
            flash_ok("Reparacion eliminada correctamente.");
        } else {
            flash_error("Error al eliminar la reparacion.");
        }
        redirigir("index.php?c=reparaciones&a=index");
    }

    public function imprimir(): void {
        if (!$this->permiso()) {
            return;
        }

        $id = (int)obtener_get("id", 0);
        $modo = (string)obtener_get("modo", "ticket");
        if ($id <= 0) {
            flash_error("ID invalido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $r = Reparacion::buscar_por_id($id);
        if (!$r) {
            flash_error("Reparacion no encontrada.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        if ($modo === "pdf") {
            $this->generar_ticket_pdf($r);
            return;
        }

        $this->imprimir_ticket_comandera($r);
    }

    private function generar_ticket_pdf(array $r): void {
        $base = __DIR__ . "/../../";
        $autoload = $base . "vendor/autoload.php";

        if (!file_exists($autoload)) {
            flash_error("No se puede generar ticket. Falta autoload.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        require_once $autoload;

        $html = $this->html_ticket_pdf($r);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->setPaper([0, 0, 226, 800], "portrait");
        $dompdf->loadHtml($html, "UTF-8");
        $dompdf->render();
        $dompdf->stream("reparacion_" . $r["codigo"] . ".pdf", ["Attachment" => false]);
    }

    private function imprimir_ticket_comandera(array $r): void {
        header("Content-Type: text/html; charset=UTF-8");
        echo $this->html_ticket_comandera($r);
    }

    private function html_ticket_pdf(array $r): string {
        $config_sistema = ConfiguracionSistema::obtener_configuracion_fiscal();
        $empresa = $config_sistema["empresa"] ?? [];
        $comercio = htmlspecialchars((string)($empresa["razon_social"] ?? ""));
        $cuit = htmlspecialchars((string)($empresa["cuit"] ?? ""));
        $domicilio_comercio = htmlspecialchars((string)($empresa["domicilio"] ?? ""));
        $telefonos_comercio = htmlspecialchars((string)($empresa["telefonos"] ?? ""));
        $pie_ticket = nl2br(htmlspecialchars((string)($empresa["texto_pie_ticket"] ?? "Guarde este ticket para retirar")));
        $codigo = htmlspecialchars($r["codigo"] ?? "");
        $fecha = htmlspecialchars($r["fecha_ingreso"] ?? date("Y-m-d"));
        $cliente = htmlspecialchars($r["cliente_nombre"] ?? "");
        $telefono = htmlspecialchars($r["cliente_telefono"] ?? "");
        $marca = htmlspecialchars($r["marca"] ?? "");
        $modelo = htmlspecialchars($r["modelo"] ?? "");
        $imei = htmlspecialchars($r["imei"] ?? "");
        $falla = htmlspecialchars($r["falla"] ?? "");
        $diagnostico = htmlspecialchars($r["diagnostico"] ?? "");
        $garantia = htmlspecialchars($r["garantia"] ?? "");
        $estado = htmlspecialchars($r["estado"] ?? "PENDIENTE");
        $precio = moneda_para_mostrar($r["precio"] ?? 0);
        $observaciones = htmlspecialchars($r["observaciones"] ?? "");

        return "<!doctype html>
<html lang='es'>
<head>
<meta charset='utf-8'>
<style>
* { box-sizing: border-box; }
body {
    font-family: 'Courier New', monospace;
    font-size: 10px;
    width: 226px;
    margin: 0;
    padding: 5px;
}
.titulo { text-align: center; font-weight: bold; font-size: 14px; }
.subtitulo { text-align: center; font-size: 11px; margin-bottom: 8px; }
.linea { border-bottom: 1px dashed #333; margin: 8px 0; }
.dato { margin: 3px 0; }
.label { font-weight: bold; }
.negrita { font-weight: bold; }
.derecha { text-align: right; }
</style>
</head>
<body>
<div class='titulo'>$comercio</div>
" . ($cuit !== "" ? "<div class='subtitulo'>CUIT $cuit</div>" : "") . "
" . ($domicilio_comercio !== "" ? "<div class='subtitulo'>$domicilio_comercio</div>" : "") . "
" . ($telefonos_comercio !== "" ? "<div class='subtitulo'>Tel: $telefonos_comercio</div>" : "") . "
<div class='linea'></div>
<div class='titulo'>TICKET DE REPARACION</div>
<div class='subtitulo'>$codigo</div>

<div class='linea'></div>

<div class='dato'><span class='label'>Fecha:</span> $fecha</div>
<div class='dato'><span class='label'>Cliente:</span> $cliente</div>
<div class='dato'><span class='label'>Telefono:</span> $telefono</div>

<div class='linea'></div>

<div class='dato'><span class='label'>Equipo:</span> $marca $modelo</div>
<div class='dato'><span class='label'>IMEI:</span> $imei</div>

<div class='linea'></div>

<div class='dato'><span class='label'>Falla reportada:</span></div>
<div class='dato'>$falla</div>

" . (!empty($diagnostico) ? "
<div class='dato'><span class='label'>Diagnostico:</span></div>
<div class='dato'>$diagnostico</div>
" : "") . "

<div class='linea'></div>

<div class='dato'><span class='label'>Estado:</span> $estado</div>
<div class='dato'><span class='label'>Garantia:</span> $garantia</div>
<div class='dato negrita derecha'>PRECIO: $precio</div>

" . (!empty($observaciones) ? "
<div class='dato'><span class='label'>Observaciones:</span></div>
<div class='dato'>$observaciones</div>
" : "") . "

<div class='linea'></div>

<div class='subtitulo'>
$pie_ticket<br>
Guarde este ticket para retirar
</div>
</body>
</html>";
    }

    private function html_ticket_comandera(array $r): string {
        $config_sistema = ConfiguracionSistema::obtener_configuracion_fiscal();
        $empresa = $config_sistema["empresa"] ?? [];
        $comercio = htmlspecialchars((string)($empresa["razon_social"] ?? ""));
        $cuit = htmlspecialchars((string)($empresa["cuit"] ?? ""));
        $domicilio_comercio = htmlspecialchars((string)($empresa["domicilio"] ?? ""));
        $telefonos_comercio = htmlspecialchars((string)($empresa["telefonos"] ?? ""));
        $pie_ticket = nl2br(htmlspecialchars((string)($empresa["texto_pie_ticket"] ?? "Guarde este ticket para retirar")));
        $codigo = htmlspecialchars($r["codigo"] ?? "");
        $fecha = htmlspecialchars($r["fecha_ingreso"] ?? date("Y-m-d"));
        $cliente = htmlspecialchars($r["cliente_nombre"] ?? "");
        $telefono = htmlspecialchars($r["cliente_telefono"] ?? "");
        $marca = htmlspecialchars($r["marca"] ?? "");
        $modelo = htmlspecialchars($r["modelo"] ?? "");
        $imei = htmlspecialchars($r["imei"] ?? "");
        $falla = nl2br(htmlspecialchars($r["falla"] ?? ""));
        $diagnostico = nl2br(htmlspecialchars($r["diagnostico"] ?? ""));
        $garantia = htmlspecialchars($r["garantia"] ?? "");
        $estado = htmlspecialchars($r["estado"] ?? "PENDIENTE");
        $precio = moneda_para_mostrar($r["precio"] ?? 0);
        $observaciones = nl2br(htmlspecialchars($r["observaciones"] ?? ""));
        $id = (int)($r["id"] ?? 0);
        $tieneDiagnostico = trim(strip_tags($diagnostico)) !== "";
        $tieneObservaciones = trim(strip_tags($observaciones)) !== "";

        return "<!doctype html>
<html lang='es'>
<head>
<meta charset='utf-8'>
<title>Ticket reparacion $codigo</title>
<style>
@page { size: 80mm auto; margin: 4mm; }
* { box-sizing: border-box; }
body {
    margin: 0;
    padding: 0;
    background: #fff;
    color: #000;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}
.ticket {
    width: 72mm;
    margin: 0 auto;
    padding: 2mm 0;
}
.titulo { text-align: center; font-size: 16px; font-weight: bold; }
.subtitulo { text-align: center; margin: 2px 0 6px; }
.linea { border-top: 1px dashed #000; margin: 6px 0; }
.fila { margin: 3px 0; word-break: break-word; }
.label { font-weight: bold; }
.precio { text-align: right; font-size: 14px; font-weight: bold; }
.acciones {
    width: 72mm;
    margin: 10px auto 0;
    text-align: center;
    font-family: Arial, sans-serif;
}
.acciones button,
.acciones a {
    display: inline-block;
    margin: 4px 3px 0;
    padding: 8px 12px;
    border: 0;
    border-radius: 4px;
    background: #343a40;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
}
@media print {
    .acciones { display: none; }
}
</style>
</head>
<body>
<div class='ticket'>
    <div class='titulo'>$comercio</div>" .
    ($cuit !== "" ? "
    <div class='subtitulo'>CUIT $cuit</div>" : "") .
    ($domicilio_comercio !== "" ? "
    <div class='subtitulo'>$domicilio_comercio</div>" : "") .
    ($telefonos_comercio !== "" ? "
    <div class='subtitulo'>Tel: $telefonos_comercio</div>" : "") . "
    <div class='linea'></div>
    <div class='titulo'>TICKET DE REPARACION</div>
    <div class='subtitulo'>$codigo</div>

    <div class='linea'></div>

    <div class='fila'><span class='label'>Fecha:</span> $fecha</div>
    <div class='fila'><span class='label'>Cliente:</span> $cliente</div>
    <div class='fila'><span class='label'>Tel:</span> $telefono</div>

    <div class='linea'></div>

    <div class='fila'><span class='label'>Equipo:</span> $marca $modelo</div>
    <div class='fila'><span class='label'>IMEI:</span> $imei</div>

    <div class='linea'></div>

    <div class='fila'><span class='label'>Falla:</span></div>
    <div class='fila'>$falla</div>" .
    ($tieneDiagnostico ? "

    <div class='linea'></div>

    <div class='fila'><span class='label'>Diagnostico:</span></div>
    <div class='fila'>$diagnostico</div>" : "") . "

    <div class='linea'></div>

    <div class='fila'><span class='label'>Estado:</span> $estado</div>
    <div class='fila'><span class='label'>Garantia:</span> $garantia</div>
    <div class='fila precio'>PRECIO: $precio</div>" .
    ($tieneObservaciones ? "

    <div class='linea'></div>

    <div class='fila'><span class='label'>Obs:</span></div>
    <div class='fila'>$observaciones</div>" : "") . "

    <div class='linea'></div>

    <div class='subtitulo'>$pie_ticket<br>Guarde este ticket para retirar</div>
</div>

<div class='acciones'>
    <button type='button' onclick='window.print()'>Imprimir ticket</button>
    <a href='index.php?c=reparaciones&a=imprimir&id=$id&modo=pdf' target='_blank'>Abrir PDF</a>
    <a href='index.php?c=reparaciones&a=index'>Volver</a>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        window.print();
    }, 250);
});
</script>
</body>
</html>";
    }
}
