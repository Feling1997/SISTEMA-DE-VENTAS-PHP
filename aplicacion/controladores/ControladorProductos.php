<?php
require_once __DIR__ . "/../modelos/Producto.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";

class ControladorProductos {
    private function generar_codigo_barras_interno(): string {
        do {
            $codigo = "P" . date("YmdHis") . random_int(10, 99);
        } while (Producto::cod_barras_existe($codigo, 0));
        return $codigo;
    }

    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenés que iniciar sesión.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN","VENDEDOR"])) {
                flash_error("No tenés permisos para Productos.");
                redirigir("index.php?c=ventas&a=lista");
            } else
                $ok = true;
        }
        return $ok;
    }

    private function listar_stock_para_select(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, unidad, cantidad, precio_costo FROM stock WHERE activo = 1 ORDER BY nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ControladorProductos::listar_stock_para_select", $e->getMessage());
            }
        }
        return $lista;
    }

    public function index(): void {
        if ($this->permiso()) {
            $productos = Producto::listar_todos();
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $campos_busqueda = [
                "id" => "ID",
                "nombre" => "Nombre",
                "cod_barras" => "Código de barras",
                "stock_nombre" => "Stock",
                "factor_conversion" => "Factor",
                "ganancia" => "Ganancia",
                "precio_final" => "Precio final"
            ];
            $productos = filtrar_registros_busqueda($productos, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/productos/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function nuevo(): void {
        if ($this->permiso()) {
            $modo = "crear";
            $id_stock_pre = (int)obtener_get("id_stock", 0);
            if ($id_stock_pre <= 0)
                $id_stock_pre = 0;
            $nombre_stock_pre = trim((string)obtener_get("nombre_stock", ""));
            if (texto_invalido($nombre_stock_pre))
                $nombre_stock_pre = "";
            $p = ["id" => 0, "nombre" => ($nombre_stock_pre !== "" ? $nombre_stock_pre : ""), "cod_barras" => "", "id_stock" => ($id_stock_pre > 0 ? $id_stock_pre : null), "id_asociado" => null, "factor_conversion" => 1, "ganancia" => 0, "precio_final" => 0, "activo" => 1];
            $stocks = $this->listar_stock_para_select();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/productos/formulario.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function crear(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    $nombre = trim((string)obtener_post("nombre", ""));
                    $cod_barras = trim((string)obtener_post("cod_barras", ""));
                    $id_stock_raw = trim((string)obtener_post("id_stock", ""));
                    $factor_conversion = parsear_numero_form(obtener_post("factor_conversion", 1), 1);
                    $ganancia = parsear_numero_form(obtener_post("ganancia", 0), 0);
                    $activo = (int)obtener_post("activo", 1);
                    $id_stock = 0;
                    if ($id_stock_raw !== "" && ctype_digit($id_stock_raw))
                        $id_stock = (int)$id_stock_raw;
                    if (texto_invalido($nombre))
                        $error = "Nombre inválido (vacío o placeholder).";
                    else {
                        if (texto_invalido($cod_barras))
                            $cod_barras = $this->generar_codigo_barras_interno();
                        if (Producto::cod_barras_existe($cod_barras, 0))
                            $error = "El código del producto ya existe.";
                        else {
                            if ($id_stock <= 0)
                                $error = "Tenés que seleccionar un stock principal.";
                            else {
                                if (!Producto::stock_existe($id_stock))
                                    $error = "El stock principal seleccionado no existe.";
                                else {
                                    $precio_costo = 0.0;
                                    $costo_stock = Producto::obtener_precio_costo_stock($id_stock);
                                    if ($costo_stock !== null)
                                        $precio_costo = $costo_stock;
                                    if ($factor_conversion < 0)
                                        $factor_conversion = 0;
                                    if ($ganancia < 0)
                                        $ganancia = 0;
                                    $precio_final = Producto::calcular_precio_final($precio_costo, $factor_conversion, $ganancia);
                                    $ok = Producto::crear($nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo);
                                    if ($ok) {
                                        flash_ok("Producto creado correctamente.");
                                        redirigir("index.php?c=productos&a=index");
                                    } else
                                        $error = "No se pudo crear el producto (ver logs).";
                                }
                            }

                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                $modo = "crear";
                $id_stock_pre = ($id_stock !== null ? (int)$id_stock : 0);
                $p = ["id" => 0, "nombre" => $nombre, "cod_barras" => $cod_barras, "id_stock" => ($id_stock_pre > 0 ? $id_stock_pre : null),
                    "id_asociado" => null, "factor_conversion" => $factor_conversion, "ganancia" => $ganancia,
                    "precio_final" => 0, "activo" => $activo];
                $stocks = $this->listar_stock_para_select();
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/productos/formulario.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function editar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $p = Producto::buscar_por_id($id);
            if ($p === null) {
                flash_error("Producto no encontrado.");
                redirigir("index.php?c=productos&a=index");
            } else {
                $modo = "editar";
                $datos_form = obtener_form_data("productos_form");
                if ($datos_form !== [])
                    $p = array_merge($p, $datos_form);
                $stocks = $this->listar_stock_para_select();
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/productos/formulario.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function actualizar(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    $id = (int)obtener_post("id", 0);
                    $p_actual = Producto::buscar_por_id($id);
                    if ($p_actual === null)
                        $error = "Producto no encontrado.";
                    else {
                        $nombre = trim((string)obtener_post("nombre", ""));
                        $cod_barras = trim((string)obtener_post("cod_barras", ""));
                        $id_stock_raw = trim((string)obtener_post("id_stock", ""));
                        $factor_conversion = parsear_numero_form(obtener_post("factor_conversion", 1), 1);
                        $ganancia = parsear_numero_form(obtener_post("ganancia", 0), 0);
                        $activo = (int)obtener_post("activo", 1);
                        if (texto_invalido($nombre))
                            $error = "Nombre inválido (vacío o placeholder).";
                        else {
                            if (texto_invalido($cod_barras))
                                $cod_barras = trim((string)($p_actual["cod_barras"] ?? ""));
                            if (texto_invalido($cod_barras))
                                $cod_barras = $this->generar_codigo_barras_interno();
                            if (Producto::cod_barras_existe($cod_barras, $id))
                                $error = "Ya existe otro producto con ese código.";
                            else {
                                $id_stock = 0;
                                if ($id_stock_raw !== "" && ctype_digit($id_stock_raw))
                                    $id_stock = (int)$id_stock_raw;
                                if ($id_stock <= 0)
                                    $error = "Tenés que seleccionar un stock principal.";
                                else {
                                    if (!Producto::stock_existe($id_stock))
                                        $error = "El stock principal seleccionado no existe.";
                                    else {
                                        $precio_costo = 0.0;
                                        $costo_stock = Producto::obtener_precio_costo_stock($id_stock);
                                        if ($costo_stock !== null)
                                            $precio_costo = $costo_stock;

                                        if ($factor_conversion < 0)
                                            $factor_conversion = 0;
                                        if ($ganancia < 0)
                                            $ganancia = 0;

                                        $precio_final = Producto::calcular_precio_final($precio_costo, $factor_conversion, $ganancia);

                                        $ok = Producto::actualizar($id, $nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo);

                                        if ($ok) {
                                            flash_ok("Producto actualizado correctamente.");
                                            redirigir("index.php?c=productos&a=index");
                                        } else
                                            $error = "No se pudo actualizar el producto (ver logs).";
                                    }
                                }
                            }
                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                flash_form_data("productos_form", [
                    "id" => $id ?? 0,
                    "nombre" => $nombre ?? "",
                    "cod_barras" => $cod_barras ?? "",
                    "id_stock" => $id_stock ?? 0,
                    "factor_conversion" => $factor_conversion ?? 1,
                    "ganancia" => $ganancia ?? 0,
                    "precio_final" => $precio_final ?? 0,
                    "activo" => $activo ?? 1
                ]);
                $id_redirigir = (int)($id ?? 0);
                if ($id_redirigir > 0)
                    redirigir("index.php?c=productos&a=editar&id=" . $id_redirigir);
                else
                    redirigir("index.php?c=productos&a=index");
            }
        }
    }

    public function eliminar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $p = Producto::buscar_por_id($id);
          if ($p === null) {
                flash_error("Producto no encontrado.");
                redirigir("index.php?c=productos&a=index");
            } else {
                if (Producto::esta_en_detalle_venta($id)) {
                    flash_error("No se puede eliminar: el producto está en ventas (detalle_venta).");
                    redirigir("index.php?c=productos&a=index");
                } else {
                    $ok = Producto::eliminar($id);
                    if ($ok)
                        flash_ok("Producto eliminado.");
                    else
                        flash_error("No se pudo eliminar (ver logs).");
                    redirigir("index.php?c=productos&a=index");
                }
            }
        }
    }
}
