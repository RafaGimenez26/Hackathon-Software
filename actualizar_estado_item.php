<?php
session_start();
require 'conexion.php';

// Verificar que el usuario sea un productor
if (!isset($_SESSION['productor_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$productor_id = $_SESSION['productor_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = $_POST['pedido_id'] ?? null;
    $producto_id = $_POST['producto_id'] ?? null;
    $nuevo_estado = $_POST['estado'] ?? null;
    
    if (!$pedido_id || !$producto_id || !$nuevo_estado) {
        echo json_encode(['error' => 'Datos incompletos']);
        exit;
    }
    
    try {
        $pedidosCollection = $database->Pedidos;
        
        // Buscar el pedido
        $pedido = $pedidosCollection->findOne([
            '_id' => new MongoDB\BSON\ObjectId($pedido_id)
        ]);
        
        if (!$pedido) {
            echo json_encode(['error' => 'Pedido no encontrado']);
            exit;
        }
        
        // Convertir items a array
        $items = $pedido['items'];
        if ($items instanceof MongoDB\Model\BSONArray) {
            $items = iterator_to_array($items);
        }
        
        // Buscar y actualizar el item específico del productor
        $itemActualizado = false;
        foreach ($items as $index => &$item) {
            // Verificar que el producto pertenece a este productor
            if ((string)$item['producto_id'] === $producto_id && 
                (int)$item['productor_id'] === (int)$productor_id) {
                
                $item['estado'] = $nuevo_estado;
                $item['fecha_actualizacion_estado'] = new MongoDB\BSON\UTCDateTime();
                $itemActualizado = true;
                break;
            }
        }
        unset($item);
        
        if (!$itemActualizado) {
            echo json_encode(['error' => 'Producto no encontrado o no pertenece al productor']);
            exit;
        }
        
        // Actualizar el pedido en la base de datos
        $resultado = $pedidosCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($pedido_id)],
            [
                '$set' => [
                    'items' => $items,
                    'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                ]
            ]
        );
        
        if ($resultado->getModifiedCount() > 0) {
            echo json_encode([
                'success' => true,
                'mensaje' => 'Estado actualizado correctamente'
            ]);
        } else {
            echo json_encode(['error' => 'No se pudo actualizar el estado']);
        }
        
    } catch (Exception $e) {
        error_log("Error al actualizar estado: " . $e->getMessage());
        echo json_encode(['error' => 'Error al actualizar: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Método no permitido']);
}
?>