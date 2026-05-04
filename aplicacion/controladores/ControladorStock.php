<?php
require_once __DIR__ . "/../modelos/Stock.php";
require_once __DIR__ . "/../modelos/Producto.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorStock {
    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenés que iniciar sesión.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN","VENDEDOR"])) {
                flash_error("No tenés permisos para Stock.");
                redirigir("index.php?c=ventas&a=lista");
            } else
                $ok = true;
        }
        return $ok;
    }

    public function index(): void {
        if ($this->permiso()) {
            $items = Stock::listar_todos();
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $campos_busqueda = [
                "id" => "ID",
                "nombre" => "Nombre",
                "unidad" => "Unidad",
                "cantidad" => "Cantidad",
                "precio_costo" => "Precio costo",
                "activo" => "Activo",
                "creado_en" => "Fecha"
            ];
            $items = filtrar_registros_busqueda($items, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/stock/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function nuevo(): void {
        if ($this->permiso()) {
            $modo = "crear";
            $s = ["id" => 0, "nombre" => "", "unidad" => "u", "cantidad" => 0, "precio_costo" => 0, "activo" => 1];
            $datos_form = obtener_form_data("stock_form");
            if ($datos_form !== [])
                $s = array_merge($s, $datos_form);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/stock/formulario.php";
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
                    $unidad = trim((string)obtener_post("unidad", "u"));
                    $cantidad = (float)obtener_post("cantidad", 0);
                    $precio_costo = (float)obtener_post("precio_costo", 0);
                    $activo = (int)obtener_post("activo", 1);
                    if (texto_invalido($nombre))
                        $error = "Nombre inválido (vacío o placeholder).";
                    else {
                        if (texto_invalido($unidad))
                            $unidad = "u";
                        if ($cantidad < 0)
                            $cantidad = 0;
                        if ($precio_costo < 0)
                            $precio_costo = 0;
                        $ok = Stock::crear($nombre, $unidad, $cantidad, $precio_costo, $activo);
                        if ($ok) {
                            flash_ok("Stock creado correctamente.");
                            redirigir("index.php?c=stock&a=index");
                        } else
                            $error = "No se pudo crear el stock (ver logs).";
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                flash_form_data("stock_form", [
                    "id" => 0,
                    "nombre" => $nombre ?? "",
                    "unidad" => $unidad ?? "u",
                    "cantidad" => $cantidad ?? 0,
                    "precio_costo" => $precio_costo ?? 0,
                    "activo" => $activo ?? 1
                ]);
                redirigir("index.php?c=stock&a=nuevo");
            }
        }
    }

    public function editar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $s = Stock::buscar_por_id($id);
            if ($s === null) {
                flash_error("Stock no encontrado.");
                redirigir("index.php?c=stock&a=index");
            } else {
                $modo = "editar";
                $datos_form = obtener_form_data("stock_form");
                if ($datos_form !== [])
                    $s = array_merge($s, $datos_form);
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/stock/formulario.php";
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
                    $s_actual = Stock::buscar_por_id($id);
                    if ($s_actual === null)
                        $error = "Stock no encontrado.";
                    else {
                        $nombre = trim((string)obtener_post("nombre", ""));
                        $unidad = trim((string)obtener_post("unidad", "u"));
                        $cantidad = (float)obtener_post("cantidad", 0);
                        $precio_costo = (float)obtener_post("precio_costo", 0);
                        $activo = (int)obtener_post("activo", 1);
                        if (texto_invalido($nombre))
                            $error = "Nombre inválido (vacío o placeholder).";
                        else {
                            if (texto_invalido($unidad))
                                $unidad = "u";
                            if ($cantidad < 0)
                                $cantidad = 0;
                            if ($precio_costo < 0)
                                $precio_costo = 0;
                            $ok = Stock::actualizar($id, $nombre, $unidad, $cantidad, $precio_costo, $activo);
                            if ($ok) {
                                $costo_anterior = (float)$s_actual["precio_costo"];
                                $costo_nuevo = (float)$precio_costo;
                                if (abs($costo_anterior - $costo_nuevo) > 0.00001) {
                                    $ok_recalc = Stock::recalcular_precios_productos_por_stock($id);
                                    if ($ok_recalc)
                                        flash_ok("Stock actualizado y precios de productos recalculados.");
                                    else
                                        flash_ok("Stock actualizado. (No se pudo recalcular precios: ver logs)");
                                } else
                                    flash_ok("Stock actualizado correctamente.");
                                redirigir("index.php?c=stock&a=index");
                            } else
                                $error = "No se pudo actualizar el stock (ver logs).";
                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                flash_form_data("stock_form", [
                    "id" => $id ?? 0,
                    "nombre" => $nombre ?? "",
                    "unidad" => $unidad ?? "u",
                    "cantidad" => $cantidad ?? 0,
                    "precio_costo" => $precio_costo ?? 0,
                    "activo" => $activo ?? 1
                ]);
                $id_redirigir = (int)($id ?? 0);
                if ($id_redirigir > 0)
                    redirigir("index.php?c=stock&a=editar&id=" . $id_redirigir);
                else
                    redirigir("index.php?c=stock&a=index");
            }
        }
    }

    public function productos(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $s = Stock::buscar_por_id($id);
            if ($s === null) {
                flash_error("Stock no encontrado.");
                redirigir("index.php?c=stock&a=index");
            } else {
                $items = Stock::listar_todos();
                $productos = $this->listar_productos_por_stock($id);
                $texto_buscar = trim((string)obtener_get("buscar", ""));
                $campo_buscar = trim((string)obtener_get("campo", "todos"));
                $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
                $campos_busqueda = [
                    "id" => "ID",
                    "nombre" => "Nombre",
                    "cod_barras" => "Código de barras",
                    "factor_conversion" => "Factor",
                    "ganancia" => "Ganancia",
                    "precio_final" => "Precio final",
                    "activo" => "Activo",
                    "creado_en" => "Fecha"
                ];
                $productos = filtrar_registros_busqueda($productos, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/stock/index.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    private function listar_productos_por_stock(int $id_stock): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null && $id_stock > 0) {
            try {
                $sql = "SELECT id, nombre, cod_barras, id_stock, factor_conversion, ganancia, precio_final, activo, creado_en FROM productos WHERE id_stock = ? ORDER BY nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ControladorStock::listar_productos_por_stock", $e->getMessage());
            }
        }
        return $lista;
    }



    public function eliminar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $s = Stock::buscar_por_id($id);
            if ($s === null) {
                flash_error("Stock no encontrado.");
                redirigir("index.php?c=stock&a=index");
            } else {
                if (Stock::esta_asociado_a_productos($id)) {
                    flash_error("No se puede eliminar: el stock está asociado a productos.");
                    redirigir("index.php?c=stock&a=index");
                } else {
                    $ok = Stock::eliminar($id);
                    if ($ok)
                        flash_ok("Stock eliminado.");
                    else
                        flash_error("No se pudo eliminar (ver logs).");
                    redirigir("index.php?c=stock&a=index");
                }
            }
        }
    }
}
