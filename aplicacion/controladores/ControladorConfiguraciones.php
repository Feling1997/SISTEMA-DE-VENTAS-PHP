<?php

require_once __DIR__ . "/../modelos/ConfiguracionSistema.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorConfiguraciones {
    private function permiso_admin(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN"])) {
                flash_error("No tenes permiso para acceder a Configuracion.");
                redirigir("index.php?c=ventas&a=lista");
            } else
                $ok = true;
        }
        return $ok;
    }

    public function sistema(): void {
        if ($this->permiso_admin()) {
            $config = ConfiguracionSistema::obtener();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/configuraciones/sistema.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function guardar_sistema(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $csrf = obtener_post("csrf", "");
            if (!csrf_valido((string)$csrf)) {
                flash_error("Token invalido. Recarga la pagina.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $datos = [
                "nombre_comercio" => obtener_post("nombre_comercio", ""),
                "razon_social" => obtener_post("razon_social", ""),
                "cuit" => obtener_post("cuit", ""),
                "condicion_iva" => obtener_post("condicion_iva", ""),
                "domicilio" => obtener_post("domicilio", ""),
                "localidad" => obtener_post("localidad", ""),
                "provincia" => obtener_post("provincia", ""),
                "telefonos" => obtener_post("telefonos", ""),
                "whatsapp" => obtener_post("whatsapp", ""),
                "email" => obtener_post("email", ""),
                "sitio_web" => obtener_post("sitio_web", ""),
                "ingresos_brutos" => obtener_post("ingresos_brutos", ""),
                "inicio_actividades" => obtener_post("inicio_actividades", ""),
                "punto_venta" => obtener_post("punto_venta", 1),
                "texto_pie_ticket" => obtener_post("texto_pie_ticket", ""),
            ];

            if (texto_invalido((string)$datos["nombre_comercio"])) {
                flash_error("El nombre del comercio es obligatorio.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            if (ConfiguracionSistema::guardar($datos))
                flash_ok("Configuracion del sistema guardada.");
            else
                flash_error("No se pudo guardar toda la configuracion. Revisar permisos de escritura.");
            redirigir("index.php?c=configuraciones&a=sistema");
        }
    }
}
