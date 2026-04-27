<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class Reparacion {
    public static function listar_todos(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT r.id, r.codigo, r.cliente_nombre, r.cliente_telefono, r.marca, r.modelo, r.imei, r.falla, r.estado, r.precio, r.fecha_ingreso, r.fecha_entrega, r.creado_en, u.nombre AS usuario_nombre 
                        FROM reparaciones r 
                        LEFT JOIN usuarios u ON u.id = r.id_usuario 
                        ORDER BY r.id DESC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Reparacion::listar_todos", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function buscar_por_id(int $id): ?array {
        $fila = null;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, codigo, cliente_nombre, cliente_telefono, marca, modelo, imei, falla, diagnostico, estado, precio, fecha_ingreso, fecha_entrega, observaciones, id_usuario, activo, creado_en FROM reparaciones WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Reparacion::buscar_por_id", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function crear(array $datos): int|false {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "INSERT INTO reparaciones (codigo, cliente_nombre, cliente_telefono, marca, modelo, imei, falla, diagnostico, estado, precio, fecha_ingreso, fecha_entrega, observaciones, id_usuario, activo, creado_en) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                $st = $pdo->prepare($sql);
                $codigo = self::generar_codigo();
                $st->execute([
                    $codigo,
                    trim($datos["cliente_nombre"] ?? ""),
                    trim($datos["cliente_telefono"] ?? ""),
                    trim($datos["marca"] ?? ""),
                    trim($datos["modelo"] ?? ""),
                    trim($datos["imei"] ?? ""),
                    trim($datos["falla"] ?? ""),
                    trim($datos["diagnostico"] ?? ""),
                    trim($datos["estado"] ?? "PENDIENTE"),
                    (float)($datos["precio"] ?? 0),
                    !empty($datos["fecha_ingreso"]) ? $datos["fecha_ingreso"] : date("Y-m-d"),
                    !empty($datos["fecha_entrega"]) ? $datos["fecha_entrega"] : null,
                    trim($datos["observaciones"] ?? ""),
                    (int)($datos["id_usuario"] ?? 0)
                ]);
                $ok = (int)$pdo->lastInsertId();
            } catch (Throwable $e) {
                registrar_log("Reparacion::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function actualizar(int $id, array $datos): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "UPDATE reparaciones SET 
                        cliente_nombre = ?, cliente_telefono = ?, marca = ?, modelo = ?, imei = ?, 
                        falla = ?, diagnostico = ?, estado = ?, precio = ?, fecha_ingreso = ?, 
                        fecha_entrega = ?, observaciones = ?, activo = ? 
                        WHERE id = ?";
                $st = $pdo->prepare($sql);
                $st->execute([
                    trim($datos["cliente_nombre"] ?? ""),
                    trim($datos["cliente_telefono"] ?? ""),
                    trim($datos["marca"] ?? ""),
                    trim($datos["modelo"] ?? ""),
                    trim($datos["imei"] ?? ""),
                    trim($datos["falla"] ?? ""),
                    trim($datos["diagnostico"] ?? ""),
                    trim($datos["estado"] ?? "PENDIENTE"),
                    (float)($datos["precio"] ?? 0),
                    !empty($datos["fecha_ingreso"]) ? $datos["fecha_ingreso"] : date("Y-m-d"),
                    !empty($datos["fecha_entrega"]) ? $datos["fecha_entrega"] : null,
                    trim($datos["observaciones"] ?? ""),
                    (int)($datos["activo"] ?? 1),
                    $id
                ]);
                $ok = $st->rowCount() > 0;
            } catch (Throwable $e) {
                registrar_log("Reparacion::actualizar", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function eliminar(int $id): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "UPDATE reparaciones SET activo = 0 WHERE id = ?";
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $ok = $st->rowCount() > 0;
            } catch (Throwable $e) {
                registrar_log("Reparacion::eliminar", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function obtener_estados(): array {
        return [
            "PENDIENTE" => "Pendiente",
            "EN_REPARACION" => "En reparación",
            "ESP_REPUESTOS" => "Esperando repuestos",
            "REPARADO" => "Reparado",
            "ENTREGADO" => "Entregado",
            "CANCELADO" => "Cancelado"
        ];
    }

    private static function generar_codigo(): string {
        return "REP-" . date("Ymd") . "-" . str_pad(mt_rand(1, 9999), 4, "0", STR_PAD_LEFT);
    }
}