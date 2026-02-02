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
        $t=trim($texto);
    if($t==="")
        $invalido=true;
    else{
        $lower=mb_strtolower($t);
        $placeholder=["ingresar", "ingrese", "buscar", "seleccioná", "seleccione", "—"];
        foreach($placeholder as $p){
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

function redirigir(string $url): void {
    header("Location: $url");
}