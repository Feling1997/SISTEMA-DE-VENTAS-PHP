<?php
require_once __DIR__ . "/seguridad.php";

function registrar_log(string $etiqueta,string $mensaje):void{
    $carpeta=__DIR__."/../almacenamiento/logs";
    //si no existe la carpeta la creamos
    if(!is_dir($carpeta))
        @mkdir($carpeta);
    $linea = "[" . date("Y-m-d H:i:s") . "] [$etiqueta] $mensaje\n";
    @file_put_contents($carpeta . "/app.log", $linea, FILE_APPEND);
}

function texto_invalido($texto):bool{
    $invalido=false;
    $t="";
    if(is_string($texto))
        //eliminamos espacios en blanco
        $t=trim($texto);
    if($t==="")
        $invalido=true;
    else{
        //pasamos a minúsculas
        $lower=mb_strtolower($t);
        $placeholder=["ingresar", "ingrese", "buscar", "seleccioná", "seleccione", "—"];
        foreach($placeholder as $p){
            //si empieza con alguno de los placeholder entonces es inválido
            if(str_starts_with($lower,$p))
                $invalido=true;
        }
    }
    return $invalido;
}

function obtener_post(string $clave, $defecto=""){
    $valor=$defecto;
    if(isset($_POST[$clave]))
        $valor=$_POST[$clave];
    return $valor;
}

function obtener_get(string $clave, $defecto=""){
    $valor=$defecto;
    if(isset($_GET[$clave]))
        $valor=$_GET[$clave];
    return $valor;
}

function flash_ok(string $mensaje):void{
    iniciar_sesion();
    $_SESSION["flash_ok"]=$mensaje;
}

function flash_error(string $mensaje): void {
    iniciar_sesion();
    $_SESSION["flash_error"] = $mensaje;
}

function flash_form_data(string $clave, array $datos): void {
    iniciar_sesion();
    $_SESSION["flash_form_data"][$clave] = $datos;
}

function obtener_form_data(string $clave): array {
    iniciar_sesion();
    $datos = [];
    if (isset($_SESSION["flash_form_data"][$clave]) && is_array($_SESSION["flash_form_data"][$clave]))
        $datos = $_SESSION["flash_form_data"][$clave];
    unset($_SESSION["flash_form_data"][$clave]);
    if (isset($_SESSION["flash_form_data"]) && count($_SESSION["flash_form_data"]) === 0)
        unset($_SESSION["flash_form_data"]);
    return $datos;
}

function normalizar_texto_busqueda($valor): string {
    $texto = "";
    if (is_scalar($valor) || $valor === null)
        $texto = trim((string)$valor);
    if ($texto === "")
        return "";
    return mb_strtolower($texto, "UTF-8");
}

function valor_coincide_busqueda($valor, string $busqueda, string $metodo): bool {
    $texto = normalizar_texto_busqueda($valor);
    if ($busqueda === "")
        return true;
    if ($texto === "")
        return false;
    $ok = false;
    switch ($metodo) {
        case "exacto":
            $ok = ($texto === $busqueda);
            break;
        case "empieza":
            $ok = str_starts_with($texto, $busqueda);
            break;
        case "termina":
            $ok = str_ends_with($texto, $busqueda);
            break;
        default:
            $ok = str_contains($texto, $busqueda);
            break;
    }
    return $ok;
}

function filtrar_registros_busqueda(array $registros, string $texto, string $campo, array $campos_permitidos, string $metodo = "contiene"): array {
    $busqueda = normalizar_texto_busqueda($texto);
    if ($busqueda === "")
        return $registros;
    $metodos_validos = ["contiene", "exacto", "empieza", "termina"];
    if (!in_array($metodo, $metodos_validos, true))
        $metodo = "contiene";
    if (!isset($campos_permitidos[$campo]) && $campo !== "todos")
        $campo = "todos";
    $filtrados = [];
    foreach ($registros as $registro) {
        $coincide = false;
        foreach ($campos_permitidos as $clave => $alias) {
            if ($campo !== "todos" && $campo !== $clave)
                continue;
            $valor = $registro[$clave] ?? "";
            if (valor_coincide_busqueda($valor, $busqueda, $metodo)) {
                $coincide = true;
                break;
            }
        }
        if ($coincide)
            $filtrados[] = $registro;
    }
    return $filtrados;
}

function parsear_numero_form($valor, float $defecto = 0.0): float {
    if (is_int($valor) || is_float($valor))
        return (float)$valor;
    if (!is_string($valor))
        return $defecto;
    $texto = trim($valor);
    if ($texto === "")
        return $defecto;
    $texto = str_replace(" ", "", $texto);
    $pos_punto = strrpos($texto, ".");
    $pos_coma = strrpos($texto, ",");
    if ($pos_punto !== false && $pos_coma !== false) {
        if ($pos_coma > $pos_punto) {
            $texto = str_replace(".", "", $texto);
            $texto = str_replace(",", ".", $texto);
        } else
            $texto = str_replace(",", "", $texto);
    } else {
        if ($pos_coma !== false)
            $texto = str_replace(",", ".", $texto);
    }
    if (!is_numeric($texto))
        return $defecto;
    return (float)$texto;
}

function numero_para_input($valor, int $decimales = 4): string {
    $numero = parsear_numero_form($valor, 0);
    $texto = number_format($numero, $decimales, ".", "");
    $texto = rtrim(rtrim($texto, "0"), ".");
    if ($texto === "")
        $texto = "0";
    return $texto;
}

function numero_para_mostrar($valor, int $decimales = 2): string {
    $numero = parsear_numero_form($valor, 0);
    $texto = number_format($numero, $decimales, ",", ".");
    $texto = rtrim(rtrim($texto, "0"), ",");
    if (str_ends_with($texto, ","))
        $texto = substr($texto, 0, -1);
    return $texto;
}

function moneda_para_mostrar($valor, string $simbolo = "$"): string {
    $numero = parsear_numero_form($valor, 0);
    return $simbolo . " " . number_format($numero, 2, ",", ".");
}

function menu_modulos_base(): array {
    return [
        "inicio" => ["url" => "index.php?c=ventas&a=inicio", "icono" => "bi-house-door-fill", "clase" => "icono-inicio", "texto" => "Inicio"],
        "ventas" => ["url" => "index.php?c=ventas&a=lista", "icono" => "bi-cash-stack", "clase" => "icono-ventas", "texto" => "Ventas"],
        "nueva_venta" => ["url" => "index.php?c=ventas&a=nueva", "icono" => "bi-cart-plus-fill", "clase" => "icono-nueva", "texto" => "Nueva venta"],
        "clientes" => ["url" => "index.php?c=clientes&a=index", "icono" => "bi-people-fill", "clase" => "icono-clientes", "texto" => "Clientes"],
        "stock" => ["url" => "index.php?c=stock&a=index", "icono" => "bi-box-seam-fill", "clase" => "icono-stock", "texto" => "Stock"],
        "productos" => ["url" => "index.php?c=productos&a=index", "icono" => "bi-bag-fill", "clase" => "icono-productos", "texto" => "Productos"],
        "reparaciones" => ["url" => "index.php?c=reparaciones&a=index", "icono" => "bi-tools", "clase" => "icono-reparaciones", "texto" => "Reparaciones"],
        "configuraciones" => ["url" => "index.php?c=configuraciones&a=sistema", "icono" => "bi-gear-fill", "clase" => "icono-configuraciones", "texto" => "Configuracion"],
        "usuarios" => ["url" => "index.php?c=usuarios&a=index", "icono" => "bi-person-gear", "clase" => "icono-usuarios", "texto" => "Usuarios"]
    ];
}

function menu_claves_permitidas_por_rol(string $rol): array {
    $claves = ["inicio", "ventas", "nueva_venta", "clientes", "stock", "productos", "reparaciones"];
    if ($rol === "ADMIN") {
        $claves[] = "configuraciones";
        $claves[] = "usuarios";
    }
    return $claves;
}

function menu_modulos_permitidos_por_rol(string $rol): array {
    $base = menu_modulos_base();
    $lista = [];
    foreach (menu_claves_permitidas_por_rol($rol) as $clave) {
        if (isset($base[$clave]))
            $lista[$clave] = $base[$clave];
    }
    return $lista;
}

function menu_preferencias_path_usuario(int $id_usuario): string {
    $carpeta = __DIR__ . "/../almacenamiento/preferencias_menu";
    if (!is_dir($carpeta))
        @mkdir($carpeta, 0777, true);
    return $carpeta . "/usuario_" . $id_usuario . ".json";
}

function menu_obtener_preferencias_usuario(int $id_usuario, string $rol): array {
    $permitidos = menu_modulos_permitidos_por_rol($rol);
    $claves_permitidas = array_keys($permitidos);
    $visibles_defecto = array_values(array_filter($claves_permitidas, fn($clave) => $clave !== "inicio"));
    if ($id_usuario <= 0)
        return $visibles_defecto;
    $archivo = menu_preferencias_path_usuario($id_usuario);
    if (!is_file($archivo))
        return $visibles_defecto;
    $json = @file_get_contents($archivo);
    if (!is_string($json) || trim($json) === "")
        return $visibles_defecto;
    $datos = json_decode($json, true);
    if (!is_array($datos))
        return $visibles_defecto;
    $seleccion = $datos["visibles"] ?? [];
    if (!is_array($seleccion))
        return $visibles_defecto;
    $filtrado = [];
    foreach ($seleccion as $clave) {
        if (is_string($clave) && in_array($clave, $claves_permitidas, true) && $clave !== "inicio")
            $filtrado[] = $clave;
    }
    return array_values(array_unique($filtrado));
}

function menu_guardar_preferencias_usuario(int $id_usuario, string $rol, array $seleccion): bool {
    if ($id_usuario <= 0)
        return false;
    $permitidas = menu_claves_permitidas_por_rol($rol);
    $visibles = [];
    foreach ($seleccion as $clave) {
        if (is_string($clave) && in_array($clave, $permitidas, true) && $clave !== "inicio")
            $visibles[] = $clave;
    }
    $payload = json_encode(["visibles" => array_values(array_unique($visibles))], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (!is_string($payload))
        return false;
    $archivo = menu_preferencias_path_usuario($id_usuario);
    return @file_put_contents($archivo, $payload) !== false;
}

function redirigir(string $url): void {
    header("Location: $url");
}
