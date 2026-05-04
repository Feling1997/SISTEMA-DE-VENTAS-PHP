<?php
require_once __DIR__ . "/../modelos/Venta.php";
require_once __DIR__ . "/../modelos/FacturaFiscal.php";
require_once __DIR__ . "/../modelos/Cliente.php";
require_once __DIR__ . "/../modelos/Presupuesto.php";
require_once __DIR__ . "/../modelos/ConfiguracionSistema.php";
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

    private function obtener_form_venta(): array {
        $datos = [
            "id_cliente" => 1,
            "buscar_cliente" => "",
            "id_producto" => "",
            "cantidad" => 1,
            "descuento" => 0,
            "tipo_comprobante" => 6,
            "buscar_producto" => ""
        ];
        $flash = obtener_form_data("ventas_form");
        if ($flash !== [])
            $datos = array_merge($datos, $flash);
        return $datos;
    }

    private function guardar_form_venta(array $datos): void {
        flash_form_data("ventas_form", [
            "id_cliente" => (int)($datos["id_cliente"] ?? 1),
            "buscar_cliente" => (string)($datos["buscar_cliente"] ?? ""),
            "id_producto" => (string)($datos["id_producto"] ?? ""),
            "cantidad" => $datos["cantidad"] ?? 1,
            "descuento" => $datos["descuento"] ?? 0,
            "tipo_comprobante" => (int)($datos["tipo_comprobante"] ?? 6),
            "buscar_producto" => (string)($datos["buscar_producto"] ?? "")
        ]);
    }

    private function listar_clientes_select(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                Cliente::asegurar_columnas_fiscales($pdo);
                $sql = "SELECT id, nombre, dni, tipo_documento, condicion_iva, email FROM clientes ORDER BY (id=1) DESC, nombre ASC";
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
            $fecha_desde = trim((string)obtener_get("fecha_desde", ""));
            $fecha_hasta = trim((string)obtener_get("fecha_hasta", ""));
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $ventas = Venta::listar_ventas_periodo($fecha_desde, $fecha_hasta);
            $campos_busqueda = [
                "id" => "ID",
                "fecha" => "Fecha",
                "cliente_nombre" => "Cliente",
                "usuario_nombre" => "Vendedor",
                "total" => "Total"
            ];
            $ventas = filtrar_registros_busqueda($ventas, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            $ids_venta_filtradas = array_map(fn($venta) => (int)($venta["id"] ?? 0), $ventas);
            $resumen_periodo = [
                "cantidad_ventas" => count($ventas),
                "total_vendido" => 0.0,
                "ganancia" => Venta::obtener_ganancia_por_ids($ids_venta_filtradas)
            ];
            foreach ($ventas as $venta)
                $resumen_periodo["total_vendido"] += (float)($venta["total"] ?? 0);
            $estados_fiscales = FacturaFiscal::estado_por_ventas($ids_venta_filtradas);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/lista.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function inicio(): void {
        if ($this->permiso()) {
            iniciar_sesion();
            $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
            $modulos = [
                [
                    "titulo" => "Ventas",
                    "texto" => "Ver historial, filtrar y revisar comprobantes.",
                    "icono" => "bi-receipt-cutoff",
                    "clase" => "modulo-ventas",
                    "url" => "index.php?c=ventas&a=lista"
                ],
                [
                    "titulo" => "Nueva venta",
                    "texto" => "Cargar una venta rápida con cliente y productos.",
                    "icono" => "bi-cart-plus-fill",
                    "clase" => "modulo-nueva",
                    "url" => "index.php?c=ventas&a=nueva"
                ],
                [
                    "titulo" => "Clientes",
                    "texto" => "Buscar, crear y editar clientes.",
                    "icono" => "bi-people-fill",
                    "clase" => "modulo-clientes",
                    "url" => "index.php?c=clientes&a=index"
                ],
                [
                    "titulo" => "Stock",
                    "texto" => "Controlar cantidades, costos y movimientos base.",
                    "icono" => "bi-box-seam-fill",
                    "clase" => "modulo-stock",
                    "url" => "index.php?c=stock&a=index"
                ],
                [
                    "titulo" => "Productos",
                    "texto" => "Administrar productos y su relación con stock.",
                    "icono" => "bi-bag-fill",
                    "clase" => "modulo-productos",
                    "url" => "index.php?c=productos&a=index"
                ],
                [
                    "titulo" => "Reparaciones",
                    "texto" => "Seguir ingresos, estados y entregas.",
                    "icono" => "bi-tools",
                    "clase" => "modulo-reparaciones",
                    "url" => "index.php?c=reparaciones&a=index"
                ]
            ];
            if ($rol === "ADMIN") {
                $modulos[] = [
                    "titulo" => "Usuarios",
                    "texto" => "Administrar accesos, roles y estado.",
                    "icono" => "bi-person-gear",
                    "clase" => "modulo-usuarios",
                    "url" => "index.php?c=usuarios&a=index"
                ];
            }
            $body_class = "bg-light page-home";
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/inicio.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function guardar_menu(): void {
        if ($this->permiso()) {
            $volver = (string)obtener_post("volver", "index.php?c=ventas&a=inicio");
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso inválido.");
                redirigir($volver);
            }
            $csrf = obtener_post("csrf", "");
            if (!csrf_valido($csrf)) {
                flash_error("Token inválido. Recargá la página.");
                redirigir($volver);
            }
            iniciar_sesion();
            $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
            $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
            $seleccion = $_POST["modulos_menu"] ?? [];
            if (!is_array($seleccion))
                $seleccion = [];
            $ok = menu_guardar_preferencias_usuario($id_usuario, $rol, $seleccion);
            if ($ok)
                flash_ok("Barra superior actualizada.");
            else
                flash_error("No se pudo guardar la barra superior.");
            redirigir($volver);
        }
    }

    public function nueva(): void {
        if ($this->permiso()) {
            $clientes = $this->listar_clientes_select();
            $productos = $this->listar_productos_select();
            $carrito = $this->obtener_carrito();
            $total = $this->calcular_total_carrito($carrito);
            $form_venta = $this->obtener_form_venta();
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
                    $datos_form = [
                        "id_cliente" => (int)obtener_post("id_cliente", 1),
                        "buscar_cliente" => trim((string)obtener_post("buscar_cliente", "")),
                        "id_producto" => (string)obtener_post("id_producto", ""),
                        "cantidad" => (float)obtener_post("cantidad", 1),
                        "descuento" => (float)obtener_post("descuento", 0),
                        "tipo_comprobante" => (int)obtener_post("tipo_comprobante", 6),
                        "buscar_producto" => trim((string)obtener_post("buscar_producto", ""))
                    ];
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
                                $this->guardar_form_venta([
                                    "id_cliente" => $datos_form["id_cliente"],
                                    "buscar_cliente" => $datos_form["buscar_cliente"],
                                    "id_producto" => "",
                                    "cantidad" => 1,
                                    "descuento" => 0,
                                    "tipo_comprobante" => $datos_form["tipo_comprobante"],
                                    "buscar_producto" => ""
                                ]);
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
                $this->guardar_form_venta($datos_form ?? []);
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
                    $buscar_cliente = trim((string)obtener_post("buscar_cliente", ""));
                    $tipo_comprobante = (int)obtener_post("tipo_comprobante", 6);
                    $tipos_disponibles = FacturaFiscal::tipos_comprobante();
                    if (!isset($tipos_disponibles[$tipo_comprobante]))
                        $tipo_comprobante = 6;
                    $tipo_info = FacturaFiscal::tipo_comprobante($tipo_comprobante);
                    if ($id_cliente <= 0)
                        $id_cliente = 1;
                    if (in_array((string)$tipo_info["operacion"], ["nota_credito", "nota_debito", "nota_credito_exportacion", "nota_debito_exportacion"], true)) {
                        $error = "Las notas de credito/debito deben referenciar un comprobante autorizado. Falta cargar el modulo de comprobante asociado.";
                    } else if ((string)$tipo_info["operacion"] === "exportacion") {
                        $error = "Factura E requiere datos de exportacion (pais, CUIT pais, moneda, incoterms y datos aduaneros si corresponden). Falta cargar el modulo de exportacion.";
                    } else if ((string)$tipo_info["operacion"] !== "presupuesto") {
                        $cliente_factura = Cliente::buscar_por_id($id_cliente);
                        if ($cliente_factura !== null) {
                            $error = "Para Factura A seleccioná un cliente con datos fiscales.";
                            $error = FacturaFiscal::validar_cliente_para_comprobante($tipo_comprobante, $cliente_factura);
                        }
                    }
                    if ($error === "" && (string)$tipo_info["operacion"] === "presupuesto") {
                        $carrito = $this->obtener_carrito();
                        $r = Presupuesto::confirmar($id_cliente, $id_usuario, $carrito);
                        if ($r["ok"] === true) {
                            $id_presupuesto = (int)$r["id_presupuesto"];
                            $this->vaciar_carrito_interno();
                            $this->guardar_form_venta([]);
                            $ok_pdf = $this->generar_pdf_presupuesto($id_presupuesto);
                            if ($ok_pdf)
                                flash_ok("Presupuesto generado. No descuenta stock ni se envia a ARCA.");
                            else
                                flash_ok("Presupuesto generado. No se pudo generar PDF: ver logs.");
                            redirigir("index.php?c=ventas&a=nueva");
                        } else
                            $error = (string)$r["error"];
                    }
                    if ($error === "" && (string)$tipo_info["operacion"] !== "presupuesto") {
                        $carrito = $this->obtener_carrito();
                        $r = Venta::confirmar_venta($id_cliente, $id_usuario, $carrito);
                        if ($r["ok"] === true) {
                            $id_venta = (int)$r["id_venta"];
                            $this->vaciar_carrito_interno();
                            $this->guardar_form_venta([]);
                            $ok_fiscal = FacturaFiscal::crear_pendiente_para_venta($id_venta, (string)$tipo_info["operacion"], $tipo_comprobante);
                            $ok_pdf = $this->generar_pdf_comprobante($id_venta);
                            if ($ok_pdf && $ok_fiscal)
                                flash_ok("Venta confirmada. PDF generado y factura fiscal en cola.");
                            else if ($ok_pdf)
                                flash_ok("Venta confirmada. PDF generado. Revisar cola fiscal.");
                            else
                                flash_ok("Venta confirmada. Revisar PDF y cola fiscal en logs.");
                            redirigir("index.php?c=ventas&a=lista");
                        } else
                            $error = (string)$r["error"];
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                $this->guardar_form_venta([
                    "id_cliente" => $id_cliente ?? 1,
                    "buscar_cliente" => $buscar_cliente ?? "",
                    "id_producto" => "",
                    "cantidad" => 1,
                    "descuento" => 0,
                    "tipo_comprobante" => $tipo_comprobante ?? 6,
                    "buscar_producto" => ""
                ]);
                redirigir("index.php?c=ventas&a=nueva");
            }
        }
    }

    private function generar_pdf_presupuesto(int $id_presupuesto): bool {
        $ok = false;
        try {
            $base = __DIR__ . "/../../";
            $autoload = $base . "vendor/autoload.php";
            if (file_exists($autoload)) {
                require_once $autoload;
                $presupuesto = Presupuesto::buscar($id_presupuesto);
                if ($presupuesto) {
                    $items = Presupuesto::obtener_detalle($id_presupuesto);
                    $html = $this->html_presupuesto($presupuesto, $items);
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml($html, "UTF-8");
                    $dompdf->setPaper([0, 0, 226.77, 900], "portrait");
                    $dompdf->render();
                    $carpeta = $base . "almacenamiento/pdf";
                    if (!is_dir($carpeta))
                        @mkdir($carpeta, 0777, true);
                    $archivo = $carpeta . "/presupuesto_" . $id_presupuesto . ".pdf";
                    $ok = (bool)@file_put_contents($archivo, $dompdf->output());
                }
            }
        } catch (Throwable $e) {
            $ok = false;
            registrar_log("PDF Presupuesto", $e->getMessage());
        }
        return $ok;
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
                    Cliente::asegurar_columnas_fiscales($pdo);
                    $sql = "SELECT v.id, v.fecha, v.total, v.id_cliente,
                                   c.nombre AS cliente_nombre, c.dni AS cliente_documento, c.tipo_documento, c.condicion_iva, c.direccion AS cliente_direccion,
                                   u.usuario AS usuario_nombre,
                                   f.tipo_comprobante, f.punto_venta, f.numero_comprobante, f.cae, f.cae_vencimiento, f.estado AS fiscal_estado, f.respuesta_json
                            FROM ventas v
                            INNER JOIN clientes c ON c.id = v.id_cliente
                            INNER JOIN usuarios u ON u.id = v.id_usuario
                            LEFT JOIN fiscal_comprobantes f ON f.id_venta = v.id
                            WHERE v.id = ? LIMIT 1";
                    $st = $pdo->prepare($sql);
                    $st->execute([$id_venta]);
                    $venta = $st->fetch();
                    if ($venta) {
                        $items = Venta::obtener_detalle($id_venta);
                        $html = $this->html_comprobante($venta, $items);//genera un html como si fuera una página web
                        $dompdf = new \Dompdf\Dompdf();//crea el generador de pdf
                        $dompdf->loadHtml($html, "UTF-8");//paso el html a generar el pdf
                        $dompdf->setPaper("A4", "portrait");//tamaño de la hoja
                        $dompdf->setPaper([0, 0, 226.77, 900], "portrait");//80 mm
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
        $cliente_doc = htmlspecialchars(trim((string)($venta["tipo_documento"] ?? "") . " " . (string)($venta["cliente_documento"] ?? "")));
        $cliente_iva = htmlspecialchars((string)($venta["condicion_iva"] ?? ""));
        $cliente_dir = htmlspecialchars((string)($venta["cliente_direccion"] ?? ""));
        $usuario = htmlspecialchars((string)$venta["usuario_nombre"]);
        $total = htmlspecialchars(moneda_para_mostrar($venta["total"] ?? 0));
        $tipo_comprobante = (int)($venta["tipo_comprobante"] ?? 6);
        $tipo_info = FacturaFiscal::tipo_comprobante($tipo_comprobante);
        $letra = htmlspecialchars((string)$tipo_info["letra"]);
        $titulo = htmlspecialchars((string)$tipo_info["texto"]);
        $config = ConfiguracionSistema::obtener_configuracion_fiscal();
        $empresa = $config["empresa"] ?? [];
        $comp_def = $config["comprobante_defecto"] ?? [];
        $iva_pct = (float)($comp_def["iva_porcentaje"] ?? 21);
        if ($iva_pct < 0)
            $iva_pct = 0;
        $razon = htmlspecialchars((string)($empresa["razon_social"] ?? ""));
        if ($razon === "")
            $razon = "Comercio";
        $cuit = htmlspecialchars((string)($empresa["cuit"] ?? ""));
        $domicilio = htmlspecialchars((string)($empresa["domicilio"] ?? ""));
        $cond_iva = htmlspecialchars((string)($empresa["condicion_iva"] ?? ""));
        $iibb = htmlspecialchars((string)($empresa["ingresos_brutos"] ?? ""));
        $inicio = htmlspecialchars((string)($empresa["inicio_actividades"] ?? ""));
        $telefonos = htmlspecialchars((string)($empresa["telefonos"] ?? ""));
        $email = htmlspecialchars((string)($empresa["email"] ?? ""));
        $sitio = htmlspecialchars((string)($empresa["sitio_web"] ?? ""));
        $pie_ticket = nl2br(htmlspecialchars((string)($empresa["texto_pie_ticket"] ?? "")));
        $copia = htmlspecialchars((string)($comp_def["copia"] ?? "ORIGINAL"));
        $remito = htmlspecialchars((string)($comp_def["remito"] ?? ""));
        $pv = (int)($venta["punto_venta"] ?? ($empresa["punto_venta"] ?? 1));
        $numero = (int)($venta["numero_comprobante"] ?? 0);
        $cae = htmlspecialchars((string)($venta["cae"] ?? ""));
        $cae_vto = htmlspecialchars((string)($venta["cae_vencimiento"] ?? ""));
        $estado_fiscal = htmlspecialchars((string)($venta["fiscal_estado"] ?? "PENDIENTE"));
        $numero_txt = $numero > 0 ? str_pad((string)$pv, 5, "0", STR_PAD_LEFT) . "-" . str_pad((string)$numero, 8, "0", STR_PAD_LEFT) : "PENDIENTE";
        $codigo_tipo = str_pad((string)$tipo_comprobante, 3, "0", STR_PAD_LEFT);
        $leyenda_receptor = $this->leyenda_iva_receptor((string)($venta["condicion_iva"] ?? ""), (int)($venta["id_cliente"] ?? 0));
        $leyenda_receptor_html = htmlspecialchars($leyenda_receptor);
        $linea = function(string $etiqueta, string $valor): string {
            if (trim($valor) === "")
                return "";
            return "<div class='line'><span>$etiqueta</span><b>$valor</b></div>";
        };
        $empresa_html = "<div class='brand'>$razon</div>"
            . $linea("CUIT", $cuit)
            . $linea("IVA", $cond_iva)
            . $linea("Domicilio", $domicilio)
            . $linea("Tel.", $telefonos)
            . $linea("Email", $email)
            . $linea("Web", $sitio)
            . $linea("IIBB", $iibb)
            . $linea("Inicio act.", $inicio);
        $comprobante_html = "<div class='doc-title'>$titulo</div>"
            . "<div class='copy'>$copia</div>"
            . $linea("Nro.", $numero_txt)
            . $linea("Fecha", $fecha)
            . $linea("Pto. venta", str_pad((string)$pv, 5, "0", STR_PAD_LEFT));
        $venta_html = $linea("Venta interna", "#" . $id)
            . $linea("Vendedor", $usuario)
            . ($remito !== "" ? $linea("Remito", $remito) : "");
        $cliente_html = $linea("Cliente", $cliente)
            . $linea("Doc.", $cliente_doc)
            . $linea("IVA", $leyenda_receptor_html)
            . $linea("Domicilio", $cliente_dir);
        $respuesta_api = [];
        if (!empty($venta["respuesta_json"])) {
            $tmp = json_decode((string)$venta["respuesta_json"], true);
            if (is_array($tmp))
                $respuesta_api = $tmp;
        }
        $qr_html = "";
        if (!empty($respuesta_api["qr_base64"])) {
            $qr = htmlspecialchars((string)$respuesta_api["qr_base64"]);
            $qr_html = "<div class='center'><img class='qr' src='data:image/png;base64,$qr'></div>";
        } else if (!empty($respuesta_api["qr_url"])) {
            $qr_url = htmlspecialchars((string)$respuesta_api["qr_url"]);
            $qr_html = "<div class='center small'>QR: $qr_url</div>";
        } else if ($cae !== "") {
            $qr_html = "<div class='pending'>QR NO INFORMADO POR API</div>";
        }
        $filas = "";
        foreach ($items as $it) {
            $p = htmlspecialchars((string)$it["producto_nombre"]);
            $cant = htmlspecialchars((string)$it["cantidad"]);
            $pu = htmlspecialchars(numero_para_mostrar($it["precio_unit"] ?? 0));
            $desc_raw = (float)($it["descuento"] ?? 0);
            $desc_fmt = (abs($desc_raw - round($desc_raw)) < 0.00001)
                ? (string)((int)round($desc_raw))
                : rtrim(rtrim(number_format($desc_raw, 2, ".", ""), "0"), ".");
            $desc = htmlspecialchars($desc_fmt);
            $sub = htmlspecialchars(numero_para_mostrar($it["subtotal"] ?? 0));
            $filas .= "<tr><td>$p<br><span>$cant x $pu Desc $desc%</span></td><td class='num'>$sub</td></tr>";
        }
        $total_num = (float)($venta["total"] ?? 0);
        $neto = 0.0;
        $iva = 0.0;
        $iva_html = "";
        if ($letra === "A" && $iva_pct > 0) {
            $neto = $total_num / (1 + ($iva_pct / 100));
            $iva = $total_num - $neto;
            $iva_html = "<div class='row'><span>Neto gravado</span><b>" . htmlspecialchars(moneda_para_mostrar($neto)) . "</b></div>
                         <div class='row'><span>IVA " . htmlspecialchars(numero_para_mostrar($iva_pct)) . "%</span><b>" . htmlspecialchars(moneda_para_mostrar($iva)) . "</b></div>";
        } else if ($letra === "B") {
            $iva_html = "<div class='note'>IVA contenido en el precio final.</div>";
        } else if ($letra === "C") {
            $iva_html = "<div class='note'>Operacion sin IVA discriminado.</div>";
        }
        $autorizacion = $cae !== ""
            ? "<div><b>C.A.E. N:</b> $cae</div><div><b>Fecha de Vto.:</b> $cae_vto</div>"
            : "<div class='pending'>CAE PENDIENTE - NO VALIDO FISCAL HASTA AUTORIZACION</div>";
        return "<!doctype html><html lang='es'><head><meta charset='utf-8'><style>
            @page { margin: 5px; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 8.7px; color: #111; }
            .center { text-align: center; }
            .brand { font-size: 12px; font-weight: bold; line-height: 1.12; margin-bottom: 2px; text-transform: uppercase; }
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; border: 1px solid #111; }
            .header-table td { border: 0; padding: 4px 3px; vertical-align: top; }
            .header-left { width: 40%; text-align: left; word-break: break-word; }
            .header-table .header-center { width: 20%; text-align: center; border-left: 1px solid #111; border-right: 1px solid #111; }
            .header-right { width: 40%; text-align: right; word-break: break-word; }
            .letter { width: 34px; height: 34px; border: 2px solid #111; margin: 0 auto 4px; text-align: center; font-size: 25px; font-weight: bold; line-height: 34px; }
            .code { font-size: 7px; text-transform: uppercase; }
            .doc-title { font-size: 10.5px; font-weight: bold; text-transform: uppercase; line-height: 1.15; margin-bottom: 2px; }
            .copy { display: inline-block; border: 1px solid #111; padding: 1px 4px; font-size: 8px; font-weight: bold; margin-bottom: 3px; }
            .line { margin: 1px 0; line-height: 1.18; }
            .line span { display: block; font-size: 6.8px; color: #555; text-transform: uppercase; }
            .line b { font-weight: normal; }
            .sep { border-top: 1px dashed #111; margin: 6px 0; }
            .meta-table { width: 100%; border-collapse: collapse; margin: 3px 0; }
            .meta-table td { width: 50%; vertical-align: top; padding: 0 3px 0 0; border: 0; }
            .block-title { font-size: 7px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #111; margin-bottom: 2px; padding-bottom: 1px; }
            .row { display: flex; justify-content: space-between; gap: 6px; }
            .num { text-align: right; white-space: nowrap; }
            table { width: 100%; border-collapse: collapse; }
            .items td { padding: 3px 0; border-bottom: 1px dotted #999; vertical-align: top; }
            td span, .note { font-size: 8px; color: #333; }
            .total { font-size: 12px; font-weight: bold; }
            .pending { border: 1px solid #111; padding: 3px; text-align: center; font-weight: bold; margin-top: 4px; }
            .qr { width: 82px; height: 82px; margin-top: 4px; }
            .small { font-size: 7px; overflow-wrap: break-word; }
            </style></head><body>
            <table class='header-table'>
              <tr>
                <td class='header-left'>
                  $empresa_html
                </td>
                <td class='header-center'>
                  <div class='letter'>$letra</div>
                  <div class='code'>Codigo<br>$codigo_tipo</div>
                </td>
                <td class='header-right'>
                  $comprobante_html
                </td>
              </tr>
            </table>
            <table class='meta-table'>
              <tr>
                <td>
                  <div class='block-title'>Operacion</div>
                  $venta_html
                </td>
                <td>
                  <div class='block-title'>Receptor</div>
                  $cliente_html
                </td>
              </tr>
            </table>
            <div class='sep'></div>
            <div class='block-title'>Detalle</div>
            <table class='items'><tbody>$filas</tbody></table>
            <div class='sep'></div>
            $iva_html
            <div class='row total'><span>TOTAL</span><b>$total</b></div>
            <div class='sep'></div>
            <div><b>Estado fiscal:</b> $estado_fiscal</div>
            $autorizacion
            $qr_html
            " . ($pie_ticket !== "" ? "<div class='sep'></div><div class='center'>$pie_ticket</div>" : "") . "
            </body></html>";
    }

    private function html_presupuesto(array $presupuesto, array $items): string {
        $id = (int)$presupuesto["id"];
        $fecha = htmlspecialchars((string)$presupuesto["fecha"]);
        $cliente = htmlspecialchars((string)$presupuesto["cliente_nombre"]);
        $usuario = htmlspecialchars((string)$presupuesto["usuario_nombre"]);
        $total = htmlspecialchars(moneda_para_mostrar($presupuesto["total"] ?? 0));
        $config = ConfiguracionSistema::obtener_configuracion_fiscal();
        $empresa = $config["empresa"] ?? [];
        $razon = htmlspecialchars((string)($empresa["razon_social"] ?? ""));
        $cuit = htmlspecialchars((string)($empresa["cuit"] ?? ""));
        $domicilio = htmlspecialchars((string)($empresa["domicilio"] ?? ""));
        $telefonos = htmlspecialchars((string)($empresa["telefonos"] ?? ""));
        $pie_ticket = nl2br(htmlspecialchars((string)($empresa["texto_pie_ticket"] ?? "")));
        $filas = "";
        foreach ($items as $it) {
            $p = htmlspecialchars((string)$it["producto_nombre"]);
            $cant = htmlspecialchars((string)$it["cantidad"]);
            $pu = htmlspecialchars(numero_para_mostrar($it["precio_unit"] ?? 0));
            $desc = htmlspecialchars((string)$it["descuento"]);
            $sub = htmlspecialchars(numero_para_mostrar($it["subtotal"] ?? 0));
            $filas .= "<tr><td>$p<br><span>$cant x $pu Desc $desc%</span></td><td class='num'>$sub</td></tr>";
        }
        return "<!doctype html>
            <html lang='es'><head><meta charset='utf-8'><style>
            @page { margin: 5px; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
            .center { text-align: center; }
            .brand { font-size: 12px; font-weight: bold; }
            .marca { width: 34px; height: 34px; border: 2px solid #111; margin: 4px auto; text-align: center; font-size: 25px; font-weight: bold; line-height: 34px; }
            .sep { border-top: 1px dashed #111; margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 3px 0; border-bottom: 1px dotted #999; vertical-align: top; }
            td span { font-size: 8px; color: #333; }
            .num { text-align: right; white-space: nowrap; }
            .total { display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; }
            .legal { border: 1px solid #111; padding: 4px; text-align: center; font-weight: bold; }
            </style></head><body>
            <div class='center brand'>$razon</div>
            <div class='center'>CUIT $cuit</div>
            " . ($domicilio !== "" ? "<div class='center'>$domicilio</div>" : "") . "
            " . ($telefonos !== "" ? "<div class='center'>Tel: $telefonos</div>" : "") . "
            <div class='marca'>X</div>
            <div class='legal'>DOCUMENTO NO VALIDO COMO FACTURA</div>
            <div class='center'><b>PRESUPUESTO #$id</b></div>
            <div class='sep'></div>
            <div><b>Fecha:</b> $fecha</div>
            <div><b>Cliente:</b> $cliente</div>
            <div><b>Vendedor:</b> $usuario</div>
            <div class='sep'></div>
            <table><tbody>$filas</tbody></table>
            <div class='sep'></div>
            <div class='total'><span>TOTAL</span><b>$total</b></div>
            " . ($pie_ticket !== "" ? "<div class='sep'></div><div class='center'>$pie_ticket</div>" : "") . "
            </body></html>";
    }

    private function leyenda_iva_receptor(string $condicion_iva, int $id_cliente): string {
        $cond = trim($condicion_iva);
        if ($id_cliente === 1 || $cond === "Consumidor Final" || $cond === "")
            return "A CONSUMIDOR FINAL";
        if ($cond === "Responsable Inscripto")
            return "IVA RESPONSABLE INSCRIPTO";
        if ($cond === "Exento")
            return "IVA EXENTO";
        if ($cond === "Monotributista")
            return "RESPONSABLE MONOTRIBUTO";
        if ($cond === "No Responsable")
            return "NO RESPONSABLE IVA";
        return strtoupper($cond);
    }
}
