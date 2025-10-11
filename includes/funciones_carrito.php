<?php
// includes/funciones_carrito.php
require_once __DIR__ . '/../conexion.php';

/**
 * Obtener el carrito de un usuario
 */
function obtenerCarritoUsuario($usuario_id) {
    global $database;
    $carritos = $database->Carritos;
    
    // Convertir a entero para asegurar consistencia
    $usuario_id = (int)$usuario_id;
    
    error_log("Buscando carrito para usuario_id: " . $usuario_id);
    
    $carrito = $carritos->findOne(['usuario_id' => $usuario_id]);
    
    error_log("Carrito encontrado: " . ($carrito ? "SÍ" : "NO"));
    
    return $carrito;
}

/**
 * Agregar producto al carrito
 */
function agregarAlCarrito($usuario_id, $producto_id, $cantidad) {
    global $database;

    $carritos = $database->Carritos;
    $productos = $database->Productos;

    error_log("=== AGREGAR AL CARRITO ===");
    error_log("Usuario ID: " . $usuario_id);
    error_log("Producto ID: " . $producto_id);
    error_log("Cantidad: " . $cantidad);

    // Buscar producto
    try {
        $producto = $productos->findOne(['_id' => new MongoDB\BSON\ObjectId($producto_id)]);
        if (!$producto) {
            error_log("ERROR: Producto no encontrado");
            return ['error' => 'Producto no encontrado'];
        }
        error_log("Producto encontrado: " . $producto['nombre']);
    } catch (Exception $e) {
        error_log("ERROR al buscar producto: " . $e->getMessage());
        return ['error' => 'Error al buscar producto: ' . $e->getMessage()];
    }

    // Convertir usuario_id a entero
    $usuario_id = (int)$usuario_id;
    
    // Buscar carrito existente
    $carrito = $carritos->findOne(['usuario_id' => $usuario_id]);
    error_log("Carrito existente: " . ($carrito ? "SÍ" : "NO"));

    if (!$carrito) {
        // Crear nuevo carrito
        error_log("Creando nuevo carrito...");
        
        $nuevoCarrito = [
            'usuario_id' => $usuario_id,
            'items' => [[
                'producto_id' => $producto['_id'],
                'nombre' => $producto['nombre'],
                'precio_unitario' => (float)$producto['precio'],
                'cantidad' => (int)$cantidad,
                'unidad' => $producto['unidad'] ?? 'u'
            ]],
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];
        
        try {
            $resultado = $carritos->insertOne($nuevoCarrito);
            error_log("Carrito creado con ID: " . $resultado->getInsertedId());
            return ['success' => true, 'mensaje' => 'Producto agregado al carrito'];
        } catch (Exception $e) {
            error_log("ERROR al crear carrito: " . $e->getMessage());
            return ['error' => 'Error al crear carrito: ' . $e->getMessage()];
        }
    } else {
        // Actualizar carrito existente
        error_log("Actualizando carrito existente...");
        
        $itemExistente = false;
        $items = $carrito['items'];
        
        // Convertir a array si es BSONArray
        if ($items instanceof MongoDB\Model\BSONArray) {
            $items = iterator_to_array($items);
        }
        
        // Verificar si el producto ya está en el carrito
        foreach ($items as $index => &$item) {
            if ((string)$item['producto_id'] === $producto_id) {
                error_log("Producto ya existe en carrito, incrementando cantidad");
                $item['cantidad'] = (int)$item['cantidad'] + (int)$cantidad;
                $itemExistente = true;
                break;
            }
        }
        unset($item);

        if (!$itemExistente) {
            error_log("Agregando nuevo producto al carrito");
            $items[] = [
                'producto_id' => $producto['_id'],
                'nombre' => $producto['nombre'],
                'precio_unitario' => (float)$producto['precio'],
                'cantidad' => (int)$cantidad,
                'unidad' => $producto['unidad'] ?? 'u'
            ];
        }

        try {
            $resultado = $carritos->updateOne(
                ['usuario_id' => $usuario_id],
                [
                    '$set' => [
                        'items' => $items,
                        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            error_log("Documentos modificados: " . $resultado->getModifiedCount());
            return ['success' => true, 'mensaje' => 'Producto agregado al carrito'];
        } catch (Exception $e) {
            error_log("ERROR al actualizar carrito: " . $e->getMessage());
            return ['error' => 'Error al actualizar carrito: ' . $e->getMessage()];
        }
    }
}

/**
 * Eliminar un producto del carrito
 */
function eliminarDelCarrito($usuario_id, $producto_id) {
    global $database;
    $carritos = $database->Carritos;
    
    $usuario_id = (int)$usuario_id;
    
    $carrito = $carritos->findOne(['usuario_id' => $usuario_id]);
    if (!$carrito) {
        return ['error' => 'Carrito no encontrado'];
    }
    
    $items = $carrito['items'];
    if ($items instanceof MongoDB\Model\BSONArray) {
        $items = iterator_to_array($items);
    }
    
    // Filtrar el item a eliminar
    $items = array_values(array_filter($items, function($item) use ($producto_id) {
        return (string)$item['producto_id'] !== $producto_id;
    }));
    
    // Actualizar el carrito
    $carritos->updateOne(
        ['usuario_id' => $usuario_id],
        [
            '$set' => [
                'items' => $items,
                'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );
    
    return ['success' => true];
}

/**
 * Vaciar el carrito completo
 */
function vaciarCarrito($usuario_id) {
    global $database;
    $carritos = $database->Carritos;
    
    $usuario_id = (int)$usuario_id;
    
    $carritos->deleteOne(['usuario_id' => $usuario_id]);
    return ['success' => true];
}

/**
 * Calcular el total del carrito
 */
function calcularTotalCarrito($usuario_id) {
    $carrito = obtenerCarritoUsuario($usuario_id);
    
    if (!$carrito || empty($carrito['items'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($carrito['items'] as $item) {
        $total += $item['precio_unitario'] * $item['cantidad'];
    }
    
    return $total;
}

/**
 * Contar items en el carrito
 */
function contarItemsCarrito($usuario_id) {
    $carrito = obtenerCarritoUsuario($usuario_id);
    
    if (!$carrito || empty($carrito['items'])) {
        return 0;
    }
    
    return count($carrito['items']);
}
?>