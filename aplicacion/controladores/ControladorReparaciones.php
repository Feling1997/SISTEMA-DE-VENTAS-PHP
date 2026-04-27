<?php
require_once __DIR__ . "/../modelos/Reparacion.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";

class ControladorReparaciones {
    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenés que iniciar sesión.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN","VENDEDOR","TECNICO"])) {
                flash_error("No tenés permisos para Reparaciones.");
                redirigir("index.php?c=ventas&a=lista");
            } else
                $ok = true;
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
        if (!$this->permiso()) return;
        
        if (!csrf_valido($_POST["csrf_token"] ?? "")) {
            flash_error("Token CSRF inválido.");
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
            flash_ok("Reparación creada correctamente.");
            redirigir("index.php?c=reparaciones&a=index");
        } else {
            flash_error("Error al crear la reparación.");
            redirigir("index.php?c=reparaciones&a=nuevo");
        }
    }

    public function editar(): void {
        if (!$this->permiso()) return;
        
        $id = (int)obtener_get("id", 0);
        if ($id <= 0) {
            flash_error("ID inválido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $p = Reparacion::buscar_por_id($id);
        if (!$p) {
            flash_error("Reparación no encontrada.");
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
        if (!$this->permiso()) return;
        
        if (!csrf_valido($_POST["csrf_token"] ?? "")) {
            flash_error("Token CSRF inválido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $id = (int)obtener_post("id", 0);
        if ($id <= 0) {
            flash_error("ID inválido.");
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
            flash_ok("Reparación actualizada correctamente.");
            redirigir("index.php?c=reparaciones&a=index");
        } else {
            flash_error("Error al actualizar la reparación.");
            redirigir("index.php?c=reparaciones&a=editar&id=" . $id);
        }
    }

    public function eliminar(): void {
        if (!$this->permiso()) return;
        
        $id = (int)obtener_get("id", 0);
        if ($id <= 0) {
            flash_error("ID inválido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $ok = Reparacion::eliminar($id);
        if ($ok) {
            flash_ok("Reparación eliminada correctamente.");
        } else {
            flash_error("Error al eliminar la reparación.");
        }
        redirigir("index.php?c=reparaciones&a=index");
    }

    public function imprimir(): void {
        if (!$this->permiso()) return;
        
        $id = (int)obtener_get("id", 0);
        if ($id <= 0) {
            flash_error("ID inválido.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $r = Reparacion::buscar_por_id($id);
        if (!$r) {
            flash_error("Reparación no encontrada.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        $this->generar_ticket($r);
    }

    private function generar_ticket(array $r): void {
        $base = __DIR__ . "/../../";
        $autoload = $base . "vendor/autoload.php";
        
        if (!file_exists($autoload)) {
            flash_error("No se puede generar ticket. Falta autoload.");
            redirigir("index.php?c=reparaciones&a=index");
            return;
        }

        require_once $autoload;

        $html = $this->html_ticket_80mm($r);
        
        $dompdf = new \Dompdf\Dompdf();
        // 80mm x 297mm (rollo), landscape para ticket
        $dompdf->setPaper([0, 0, 226, 800], "portrait");
        $dompdf->loadHtml($html, "UTF-8");
        $dompdf->render();

        // Descargar directamente
        $dompdf->stream("reparacion_" . $r["codigo"] . ".pdf", ["Attachment" => false]);
    }

    private function html_ticket_80mm(array $r): string {
        $codigo = htmlspecialchars($r["codigo"] ?? "");
        $fecha = htmlspecialchars($r["fecha_ingreso"] ?? date("Y-m-d"));
        $cliente = htmlspecialchars($r["cliente_nombre"] ?? "");
        $telefono = htmlspecialchars($r["cliente_telefono"] ?? "");
        $marca = htmlspecialchars($r["marca"] ?? "");
        $modelo = htmlspecialchars($r["modelo"] ?? "");
        $imei = htmlspecialchars($r["imei"] ?? "");
        $falla = htmlspecialchars($r["falla"] ?? "");
        $diagnostico = htmlspecialchars($r["diagnostico"] ?? "");
        $estado = htmlspecialchars($r["estado"] ?? "PENDIENTE");
        $precio = number_format((float)($r["precio"] ?? 0), 2, ",", ".");
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
<div class='titulo'>TICKET DE REPARACIÓN</div>
<div class='subtitulo'>$codigo</div>

<div class='linea'></div>

<div class='dato'><span class='label'>Fecha:</span> $fecha</div>
<div class='dato'><span class='label'>Cliente:</span> $cliente</div>
<div class='dato'><span class='label'>Teléfono:</span> $telefono</div>

<div class='linea'></div>

<div class='dato'><span class='label'>Equipo:</span> $marca $modelo</div>
<div class='dato'><span class='label'>IMEI:</span> $imei</div>

<div class='linea'></div>

<div class='dato'><span class='label'>Falla reportada:</span></div>
<div class='dato'>$falla</div>

" . (!empty($diagnostico) ? "
<div class='dato'><span class='label'>Diagnóstico:</span></div>
<div class='dato'>$diagnostico</div>
" : "") . "

<div class='linea'></div>

<div class='dato'><span class='label'>Estado:</span> $estado</div>
<div class='dato negrita derecha'>PRECIO: \$$precio</div>

" . (!empty($observaciones) ? "
<div class='dato'><span class='label'>Observaciones:</span></div>
<div class='dato'>$observaciones</div>
" : "") . "

<div class='linea'></div>

<div class='subtitulo'>
Gracias por su confianza<br>
Guarde este ticket para retirar
</div>
</body>
</html>";
    }
}