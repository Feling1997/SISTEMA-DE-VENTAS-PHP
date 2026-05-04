<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class Reparacion {
    public static function listar_todos(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                self::asegurar_tabla($pdo);
                $sql = "SELECT r.id, r.codigo, r.cliente_nombre, r.cliente_telefono, r.marca, r.modelo, r.imei, r.falla, r.garantia, r.estado, r.precio, r.fecha_ingreso, r.fecha_entrega, r.creado_en, u.usuario AS usuario_nombre 
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
                self::asegurar_tabla($pdo);
                $sql = "SELECT id, codigo, cliente_nombre, cliente_telefono, marca, modelo, imei, falla, diagnostico, garantia, estado, precio, fecha_ingreso, fecha_entrega, observaciones, id_usuario, activo, creado_en FROM reparaciones WHERE id = ? LIMIT 1";
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
                self::asegurar_tabla($pdo);
                $sql = "INSERT INTO reparaciones (codigo, cliente_nombre, cliente_telefono, marca, modelo, imei, falla, diagnostico, garantia, estado, precio, fecha_ingreso, fecha_entrega, observaciones, id_usuario, activo, creado_en) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
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
                    trim($datos["garantia"] ?? ""),
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
                self::asegurar_tabla($pdo);
                $sql = "UPDATE reparaciones SET 
                        cliente_nombre = ?, cliente_telefono = ?, marca = ?, modelo = ?, imei = ?, 
                        falla = ?, diagnostico = ?, garantia = ?, estado = ?, precio = ?, fecha_ingreso = ?, 
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
                    trim($datos["garantia"] ?? ""),
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
                self::asegurar_tabla($pdo);
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

    private static function asegurar_tabla(PDO $pdo): void {
        static $tablaVerificada = false;

        if ($tablaVerificada) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS reparaciones (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    codigo VARCHAR(20) NOT NULL UNIQUE,
                    cliente_nombre VARCHAR(150) NOT NULL,
                    cliente_telefono VARCHAR(30) DEFAULT '',
                    marca VARCHAR(50) DEFAULT '',
                    modelo VARCHAR(50) DEFAULT '',
                    imei VARCHAR(30) DEFAULT '',
                    falla TEXT,
                    diagnostico TEXT,
                    garantia VARCHAR(100) DEFAULT '',
                    estado ENUM('PENDIENTE', 'EN_REPARACION', 'ESP_REPUESTOS', 'REPARADO', 'ENTREGADO', 'CANCELADO') DEFAULT 'PENDIENTE',
                    precio DECIMAL(10,2) DEFAULT 0,
                    fecha_ingreso DATE NOT NULL,
                    fecha_entrega DATE DEFAULT NULL,
                    observaciones TEXT,
                    id_usuario INT DEFAULT 0,
                    activo TINYINT(1) DEFAULT 1,
                    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_estado (estado),
                    INDEX idx_codigo (codigo),
                    INDEX idx_fecha_ingreso (fecha_ingreso)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
        self::asegurar_columna($pdo, "garantia", "ALTER TABLE reparaciones ADD COLUMN garantia VARCHAR(100) DEFAULT '' AFTER diagnostico");
        $tablaVerificada = true;
    }

    private static function asegurar_columna(PDO $pdo, string $columna, string $sqlAlter): void {
        $st = $pdo->prepare("SHOW COLUMNS FROM reparaciones LIKE ?");
        $st->execute([$columna]);
        if (!$st->fetch()) {
            $pdo->exec($sqlAlter);
        }
    }
}
