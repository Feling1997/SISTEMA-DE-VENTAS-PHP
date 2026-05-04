<?php

require_once __DIR__ . "/../../configuraciones/ayudas.php";

class ConfiguracionSistema {
    public static function valores_defecto(): array {
        $arca = self::configuracion_arca_base();
        $empresa = $arca["empresa"] ?? [];
        return [
            "nombre_comercio" => (string)($empresa["razon_social"] ?? "Sistema de Ventas"),
            "razon_social" => (string)($empresa["razon_social"] ?? ""),
            "cuit" => (string)($empresa["cuit"] ?? ""),
            "condicion_iva" => (string)($empresa["condicion_iva"] ?? ""),
            "domicilio" => (string)($empresa["domicilio"] ?? ""),
            "localidad" => "",
            "provincia" => "",
            "telefonos" => "",
            "whatsapp" => "",
            "email" => "",
            "sitio_web" => "",
            "ingresos_brutos" => (string)($empresa["ingresos_brutos"] ?? ""),
            "inicio_actividades" => (string)($empresa["inicio_actividades"] ?? ""),
            "punto_venta" => (int)($empresa["punto_venta"] ?? 1),
            "texto_pie_ticket" => "Gracias por su compra",
        ];
    }

    public static function obtener(): array {
        $datos = self::valores_defecto();
        $archivo = self::archivo_configuracion();
        if (is_file($archivo)) {
            $json = @file_get_contents($archivo);
            $guardado = is_string($json) ? json_decode($json, true) : null;
            if (is_array($guardado))
                $datos = array_merge($datos, self::normalizar($guardado));
        }
        return $datos;
    }

    public static function guardar(array $entrada): bool {
        $datos = self::normalizar($entrada);
        $actual = self::obtener();
        $datos = array_merge($actual, $datos);
        if ((int)$datos["punto_venta"] <= 0)
            $datos["punto_venta"] = 1;

        $carpeta = dirname(self::archivo_configuracion());
        if (!is_dir($carpeta))
            @mkdir($carpeta, 0777, true);

        $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json))
            return false;

        $ok_json = @file_put_contents(self::archivo_configuracion(), $json) !== false;
        $ok_arca = self::sincronizar_arca($datos);
        return $ok_json && $ok_arca;
    }

    public static function obtener_configuracion_fiscal(): array {
        $arca = self::configuracion_arca_base();
        $datos = self::obtener();
        $arca["empresa"] = array_merge($arca["empresa"] ?? [], [
            "cuit" => $datos["cuit"],
            "punto_venta" => (int)$datos["punto_venta"],
            "condicion_iva" => $datos["condicion_iva"],
            "razon_social" => self::razon_social_para_comprobante($datos),
            "domicilio" => self::domicilio_completo($datos),
            "ingresos_brutos" => $datos["ingresos_brutos"],
            "inicio_actividades" => $datos["inicio_actividades"],
            "telefonos" => $datos["telefonos"],
            "whatsapp" => $datos["whatsapp"],
            "email" => $datos["email"],
            "sitio_web" => $datos["sitio_web"],
            "texto_pie_ticket" => $datos["texto_pie_ticket"],
        ]);
        return $arca;
    }

    public static function domicilio_completo(array $datos): string {
        $partes = [];
        foreach (["domicilio", "localidad", "provincia"] as $clave) {
            $valor = trim((string)($datos[$clave] ?? ""));
            if ($valor !== "")
                $partes[] = $valor;
        }
        return implode(", ", $partes);
    }

    public static function razon_social_para_comprobante(array $datos): string {
        $razon = trim((string)($datos["razon_social"] ?? ""));
        if ($razon !== "")
            return $razon;
        return trim((string)($datos["nombre_comercio"] ?? ""));
    }

    private static function normalizar(array $entrada): array {
        $permitidos = array_keys(self::valores_defecto());
        $datos = [];
        foreach ($permitidos as $clave) {
            $valor = $entrada[$clave] ?? "";
            if ($clave === "punto_venta")
                $datos[$clave] = max(1, (int)$valor);
            else
                $datos[$clave] = trim((string)$valor);
        }
        return $datos;
    }

    private static function archivo_configuracion(): string {
        return __DIR__ . "/../../almacenamiento/configuracion_sistema.json";
    }

    private static function archivo_arca(): string {
        return __DIR__ . "/../../configuraciones/arca.php";
    }

    private static function configuracion_arca_base(): array {
        $archivo = self::archivo_arca();
        if (is_file($archivo)) {
            $config = require $archivo;
            if (is_array($config))
                return $config;
        }
        return [
            "habilitado" => false,
            "modo" => "homologacion",
            "proveedor" => "api_rest",
            "timeout_segundos" => 20,
            "api_rest" => ["endpoint" => "", "token" => ""],
            "empresa" => [],
            "comprobante_defecto" => ["tipo" => 6, "concepto" => 1, "moneda" => "PES", "cotizacion" => 1, "iva_porcentaje" => 21, "copia" => "ORIGINAL", "remito" => ""],
        ];
    }

    private static function sincronizar_arca(array $datos): bool {
        $config = self::configuracion_arca_base();
        $config["empresa"] = array_merge($config["empresa"] ?? [], [
            "cuit" => $datos["cuit"],
            "punto_venta" => (int)$datos["punto_venta"],
            "condicion_iva" => $datos["condicion_iva"],
            "razon_social" => self::razon_social_para_comprobante($datos),
            "domicilio" => self::domicilio_completo($datos),
            "ingresos_brutos" => $datos["ingresos_brutos"],
            "inicio_actividades" => $datos["inicio_actividades"],
        ]);
        $contenido = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        return @file_put_contents(self::archivo_arca(), $contenido) !== false;
    }
}
