<?php
require_once __DIR__ . "/../modelos/Venta.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";

class ControladorVentas {
    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenés que iniciar sesión.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN","VENDEDOR"])) {
                flash_error("No tenés permisos para Ventas.");
                redirigir("index.php?c=auth&a=login");
            } else
                $ok = true;
        }
        return $ok;
    }

    private function obtener_carrito(): array {
        iniciar_sesion();
        $carrito = [];
        if (isset($_SESSION["carrito"]) && is_array($_SESSION["carrito"]))
            $carrito = $_SESSION["carrito"];
        return $carrito;
    }

    private function guardar_carrito(array $carrito): void {
        iniciar_sesion();
        $_SESSION["carrito"] = $carrito;
    }

    private function vaciar_carrito_interno(): void {
        iniciar_sesion();
        $_SESSION["carrito"] = [];
    }

    private function listar_clientes_select(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, dni FROM clientes ORDER BY (id=1) DESC, nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ControladorVentas::listar_clientes_select", $e->getMessage());
            }
        }
        return $lista;
    }

    private function listar_productos_select(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, cod_barras, precio_final, factor_conversion, id_stock, id_asociado FROM productos WHERE activo = 1 ORDER BY nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ControladorVentas::listar_productos_select", $e->getMessage());
            }
        }
        return $lista;
    }

    private function calcular_total_carrito(array $carrito): float {
        $total = 0.0;
        foreach ($carrito as $it) {
            $cantidad = (float)($it["cantidad"] ?? 0);
            $precio_unit = (float)($it["precio_unit"] ?? 0);
            $descuento = (float)($it["descuento"] ?? 0);
            $sub = Venta::calcular_subtotal($cantidad, $precio_unit, $descuento);
            $total += $sub;
        }
        return $total;
    }

    public function lista(): void {
        if ($this->permiso()) {
            $ventas = Venta::listar_ventas();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/lista.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function nueva(): void {
        if ($this->permiso()) {
            $clientes = $this->listar_clientes_select();
            $productos = $this->listar_productos_select();
            $carrito = $this->obtener_carrito();
            $total = $this->calcular_total_carrito($carrito);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/nueva.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function agregar(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    $id_producto = (int)obtener_post("id_producto", 0);
                    $cantidad = (float)obtener_post("cantidad", 0);
                    $descuento = (float)obtener_post("descuento", 0);
                    if ($id_producto <= 0 || $cantidad <= 0)
                        $error = "Producto o cantidad inválidos.";
                    else {
                        if ($descuento < 0)
                            $descuento = 0;
                        if ($descuento > 100)
                            $descuento = 100;
                        $prod = Venta::obtener_producto_para_venta($id_producto);
                        if ($prod === null || (int)$prod["activo"] !== 1)
                            $error = "Producto no disponible.";
                        else {
                            $precio_unit = (float)$prod["precio_final"];
                            $factor = (float)$prod["factor_conversion"];
                            if ($factor < 0) { $factor = 0; }
                            $id_stock_consumo = Venta::obtener_id_stock_consumo($prod);
                            if ($id_stock_consumo !== null) {
                                $stock = Venta::obtener_stock_por_id($id_stock_consumo);
                                if ($stock === null)
                                    $error = "Stock no encontrado para el producto.";
                                else {
                                    $consumo = Venta::calcular_consumo_stock($cantidad, $factor);
                                    $disp = (float)$stock["cantidad"];
                                    if ($consumo > $disp + 0.0000001)
                                        $error = "Stock insuficiente. Disponible: " . $disp;
                                }
                            }
                            if ($error === "") {
                                $carrito = $this->obtener_carrito();
                                $encontrado = false;
                                foreach ($carrito as &$it) {
                                    if ((int)$it["id_producto"] === $id_producto) {
                                        $it["cantidad"] = (float)$it["cantidad"] + $cantidad;
                                        $it["descuento"] = (float)$it["descuento"] + $descuento;
                                        $it["precio_unit"] = $precio_unit; // actualizamos por si cambió
                                        $it["nombre"] = (string)$prod["nombre"];
                                        $encontrado = true;
                                    }
                                }
                                unset($it);
                                if (!$encontrado)
                                    $carrito[] = ["id_producto" => $id_producto, "nombre" => (string)$prod["nombre"], "cantidad" => $cantidad, "precio_unit" => $precio_unit, "descuento" => $descuento];
                                $this->guardar_carrito($carrito);
                                flash_ok("Producto agregado al carrito.");
                                redirigir("index.php?c=ventas&a=nueva");
                            }
                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                redirigir("index.php?c=ventas&a=nueva");
            }
        }
    }

    public function quitar(): void {
        if ($this->permiso()) {
            $id_producto = (int)obtener_get("id_producto", 0);
            $carrito = $this->obtener_carrito();
            $nuevo = [];
            foreach ($carrito as $it) {
                if ((int)$it["id_producto"] !== $id_producto)
                    $nuevo[] = $it;
            }
            $this->guardar_carrito($nuevo);
            flash_ok("Item quitado del carrito.");
            redirigir("index.php?c=ventas&a=nueva");
        }
    }

    public function vaciar(): void {
        if ($this->permiso()) {
            $this->vaciar_carrito_interno();
            flash_ok("Carrito vaciado.");
            redirigir("index.php?c=ventas&a=nueva");
        }
    }

    public function confirmar(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    iniciar_sesion();
                    $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
                    $id_cliente = (int)obtener_post("id_cliente", 1);
                    if ($id_cliente <= 0)
                        $id_cliente = 1;
                    $carrito = $this->obtener_carrito();
                    $r = Venta::confirmar_venta($id_cliente, $id_usuario, $carrito);
                    if ($r["ok"] === true) {
                        $id_venta = (int)$r["id_venta"];
                        $this->vaciar_carrito_interno();
                        $ok_pdf = $this->generar_pdf_comprobante($id_venta);
                        if ($ok_pdf)
                            flash_ok("Venta confirmada. PDF generado.");
                        else
                            flash_ok("Venta confirmada. (No se pudo generar PDF: ver logs)");
                        redirigir("index.php?c=ventas&a=lista");
                    } else
                        $error = (string)$r["error"];
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                redirigir("index.php?c=ventas&a=nueva");
            }
        }
    }

    private function generar_pdf_comprobante(int $id_venta): bool {
        $ok = false;
        try {
            $base = __DIR__ . "/../../";
            $autoload = $base . "vendor/autoload.php";
            if (file_exists($autoload)) {
                require_once $autoload;
                $pdo = obtener_pdo();
                if ($pdo !== null) {
                    $sql = "SELECT v.id, v.fecha, v.total, c.nombre AS cliente_nombre, u.usuario AS usuario_nombre FROM ventas v INNER JOIN clientes c ON c.id = v.id_cliente INNER JOIN usuarios u ON u.id = v.id_usuario WHERE v.id = ? LIMIT 1";
                    $st = $pdo->prepare($sql);
                    $st->execute([$id_venta]);
                    $venta = $st->fetch();
                    if ($venta) {
                        $items = Venta::obtener_detalle($id_venta);
                        $html = $this->html_comprobante($venta, $items);//genera un html como si fuera una página web
                        $dompdf = new \Dompdf\Dompdf();//crea el generador de pdf
                        $dompdf->loadHtml($html, "UTF-8");//paso el html a generar el pdf
                        $dompdf->setPaper("A4", "portrait");//tamaño de la hoja
                        $dompdf->render(); //convierte a pdf interno, en memoria
                        $carpeta = $base . "almacenamiento/pdf";
                        if (!is_dir($carpeta))
                            @mkdir($carpeta, 0777, true);
                        $archivo = $carpeta . "/venta_" . $id_venta . ".pdf";
                        $bytes = $dompdf->output(); //obtiene el contenido del pdf en binario
                        $ok = (bool)@file_put_contents($archivo, $bytes); //guarda en la dirección indicada
                    }
                }
            } else
                registrar_log("PDF", "No existe vendor/autoload.php. Instalá dompdf con Composer.");
        } catch (Throwable $e) {
            $ok = false;
            registrar_log("PDF", $e->getMessage());
        }
        return $ok;
    }

    private function html_comprobante(array $venta, array $items): string {
        $id = (int)$venta["id"];
        $fecha = htmlspecialchars((string)$venta["fecha"]);
        $cliente = htmlspecialchars((string)$venta["cliente_nombre"]);
        $usuario = htmlspecialchars((string)$venta["usuario_nombre"]);
        $total = htmlspecialchars((string)$venta["total"]);
        $filas = "";
        foreach ($items as $it) {
            $p = htmlspecialchars((string)$it["producto_nombre"]);
            $cant = htmlspecialchars((string)$it["cantidad"]);
            $pu = htmlspecialchars((string)$it["precio_unit"]);
            $desc_raw = (float)($it["descuento"] ?? 0);
            $desc_fmt = (abs($desc_raw - round($desc_raw)) < 0.00001)
                ? (string)((int)round($desc_raw))
                : rtrim(rtrim(number_format($desc_raw, 2, ".", ""), "0"), ".");
            $desc = htmlspecialchars($desc_fmt);
            $sub = htmlspecialchars((string)$it["subtotal"]);
            $filas .= "<tr>
                        <td>$p</td><td style='text-align:right;'>$cant</td>
                        <td style='text-align:right;'>$pu</td>
                        <td style='text-align:right;'>$desc%</td>
                        <td style='text-align:right;'>$sub</td>
                       </tr>";
        }
        $html = "<!doctype html>
            <html lang='es'>
            <head>
            <meta charset='utf-8'>
            <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            h2 { margin: 0 0 10px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #333; padding: 6px; }
            th { background: #eee; }
            .box { margin-top: 8px; }
            </style>
            </head>
            <body>
            <h2>Comprobante de Venta #$id</h2>
            <div class='box'><b>Fecha:</b> $fecha</div>
            <div class='box'><b>Cliente:</b> $cliente</div>
            <div class='box'><b>Vendedor:</b> $usuario</div>

            <table>
                <thead>
                <tr>
                    <th>Producto</th>
                    <th style='text-align:right;'>Cantidad</th>
                    <th style='text-align:right;'>Precio Unit</th>
                    <th style='text-align:right;'>Descuento</th>
                    <th style='text-align:right;'>Subtotal</th>
                </tr>
                </thead>
                <tbody>
                $filas
                </tbody>
            </table>

            <div class='box' style='text-align:right; font-size: 14px;'>
                <b>Total:</b> $total
            </div>
            </body>
            </html>";
        return $html;
    }
}
