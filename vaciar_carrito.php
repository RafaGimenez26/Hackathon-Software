<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: loginus.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = (int)$_SESSION['usuario_id'];
    $carritosCollection = $database->Carritos;
    
    // Eliminar carrito del usuario
    $carritosCollection->deleteOne(['usuario_id' => $usuario_id]);
}

header("Location: pedidos.php");
exit;
?>