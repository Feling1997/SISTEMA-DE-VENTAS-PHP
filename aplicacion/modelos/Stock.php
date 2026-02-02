<?php

require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class Stock{
    
    public static function listar_todos():array{
        $lista=[];
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "SELECT id, nombre, unidad, cantidad, precio_costo, activo, creado_en FROM stock ORDER BY id DESC";
                $st=$pdo->prepare($sql);
                $st->execute();
                $rows=$st->fetchAll();
                if(is_array($rows))
                    $lista=$rows;
            }catch(Throwable $e){
                registrar_log("Stock::listar_todos ",$e->getMessage());
            }
        }
        return $lista;
    }

    public static function buscar_por_id(int $id): ?array {
        $fila = null;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, unidad, cantidad, precio_costo, activo, creado_en FROM stock WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Stock::buscar_por_id", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function crear(string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "INSERT INTO stock (nombre, unidad, cantidad, precio_costo, activo) VALUES (?, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $unidad, $cantidad, $precio_costo, $activo]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function actualizar(int $id, string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "UPDATE stock SET nombre = ?, unidad = ?, cantidad = ?, precio_costo = ?, activo = ? WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $unidad, $cantidad, $precio_costo, $activo, $id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::actualizar", $e->getMessage());
            }
        }

        return $ok;
    }

    public static function esta_asociado_a_productos(int $id_stock): bool {
        $rel = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id FROM productos WHERE id_stock = ? OR id_asociado = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock, $id_stock]);
                $r = $st->fetch();
                if ($r)
                    $rel = true;
            } catch (Throwable $e) {
                registrar_log("Stock::esta_asociado_a_productos", $e->getMessage());
            }
        }
        return $rel;
    }

    public static function eliminar(int $id): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "DELETE FROM stock WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::eliminar", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function recalcular_precios_productos_por_stock(int $id_stock): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sqlStock = "SELECT precio_costo FROM stock WHERE id = ? LIMIT 1";
                $st1 = $pdo->prepare($sqlStock);
                $st1->execute([$id_stock]);
                $s = $st1->fetch();
                if ($s) {
                    $precio_costo = (float)$s["precio_costo"];
                    $sqlUpd = "UPDATE productos SET precio_final = ( ? * factor_conversion ) * (1 + (ganancia/100)) WHERE id_stock = ? OR id_asociado = ?";
                    $st2 = $pdo->prepare($sqlUpd);
                    $ok = $st2->execute([$precio_costo, $id_stock, $id_stock]);
                } else 
                    $ok = false;
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::recalcular_precios_productos_por_stock", $e->getMessage());
            }
        }
        return $ok;
    }
}