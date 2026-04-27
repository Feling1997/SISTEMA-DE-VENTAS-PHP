<?php
require_once __DIR__ . "/../../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../../configuraciones/ayudas.php";
iniciar_sesion();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema de Ventas</title>
  <link rel="stylesheet" href="/VENTAS/publico/assets/css/app.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . "/menu.php"; ?>
<div class="container py-5">
<?php include __DIR__ . "/alertas.php"; ?>
