<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: loginus.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
    $producto_id = $_POST['producto_id'];
    
    $carritosCollection = $database->Carritos;
    
    // Obtener carrito
    $carrito = $carritosCollection->findOne(['usuario_id' => $usuario_id]);
    
    if ($carrito) {
        // Filtrar items (eliminar el producto especificado)
        $items = [];
        foreach ($carrito['items'] as $item) {
            if ((string)$item['producto_id'] !== $producto_id) {
                $items[] = $item;
            }
        }
        
        // Actualizar carrito
        $carritosCollection->updateOne(
            ['usuario_id' => $usuario_id],
            ['$set' => ['items' => $items]]
        );
    }
}

header("Location: pedidos.php");
exit;
?>