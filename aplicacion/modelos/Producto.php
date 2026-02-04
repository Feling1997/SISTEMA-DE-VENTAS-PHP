<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class Producto {
    public static function listar_todos(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT p.id, p.nombre, p.cod_barras, p.id_stock, p.factor_conversion, p.ganancia, p.precio_final, p.activo, p.creado_en, s.nombre AS stock_nombre FROM productos p INNER JOIN stock s ON s.id = p.id_stock ORDER BY p.id DESC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Producto::listar_todos", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function buscar_por_id(int $id): ?array {
        $fila = null;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, cod_barras, id_stock, factor_conversion, ganancia, precio_final, activo, creado_en FROM productos WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Producto::buscar_por_id", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function cod_barras_existe(string $cod_barras, int $excepto_id = 0): bool {
        $existe = false;
        $pdo = obtener_pdo();
        $cb = trim($cod_barras);
        if ($pdo !== null && $cb !== "") {
            try {
                $sql = "SELECT id FROM productos WHERE cod_barras = ? AND id <> ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$cb, $excepto_id]);
                $r = $st->fetch();
                if ($r)
                    $existe = true;
            } catch (Throwable $e) {
                registrar_log("Producto::cod_barras_existe", $e->getMessage());
            }
        }
        return $existe;
    }

    public static function obtener_precio_costo_stock(int $id_stock): ?float {
        $precio = null;
        $pdo = obtener_pdo();
        if ($pdo !== null && $id_stock > 0) {
            try {
                $sql = "SELECT precio_costo FROM stock WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $precio = (float)$r["precio_costo"];
            } catch (Throwable $e) {
                registrar_log("Producto::obtener_precio_costo_stock", $e->getMessage());
            }
        }
        return $precio;
    }

    public static function stock_existe(int $id_stock): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null && $id_stock > 0) {
            try {
                $sql = "SELECT id FROM stock WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $ok = true;
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Producto::stock_existe", $e->getMessage());
            }
        }
        return $ok;
    }


    public static function calcular_precio_final(float $precio_costo, float $factor_conversion, float $ganancia): float {
        $precio = 0.0;
        if ($precio_costo < 0)
            $precio_costo = 0;
        if ($factor_conversion < 0)
            $factor_conversion = 0;
        $precio = ($precio_costo * $factor_conversion) * (1 + ($ganancia / 100));
        return $precio;
    }

    public static function crear(string $nombre, string $cod_barras, int $id_stock, float $factor_conversion, float $ganancia, float $precio_final, int $activo): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "INSERT INTO productos (nombre, cod_barras, id_stock, id_asociado, factor_conversion, ganancia, precio_final, activo) VALUES (?, ?, ?, NULL, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Producto::crear", $e->getMessage());
            }
        }
        return $ok;
    }


    public static function actualizar(int $id, string $nombre, string $cod_barras, int $id_stock, float $factor_conversion, float $ganancia, float $precio_final, int $activo): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "UPDATE productos
                        SET nombre = ?, cod_barras = ?, id_stock = ?, id_asociado = NULL,
                            factor_conversion = ?, ganancia = ?, precio_final = ?, activo = ?
                        WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo, $id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Producto::actualizar", $e->getMessage());
            }
        }
        return $ok;
    }


    public static function esta_en_detalle_venta(int $id_producto): bool {
        $rel = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id FROM detalle_venta WHERE id_producto = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_producto]);
                $r = $st->fetch();
                if ($r)
                    $rel = true;
            } catch (Throwable $e) {
                registrar_log("Producto::esta_en_detalle_venta", $e->getMessage());
            }
        }
        return $rel;
    }

    public static function eliminar(int $id): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "DELETE FROM productos WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Producto::eliminar", $e->getMessage());
            }
        }
        return $ok;
    }
}
