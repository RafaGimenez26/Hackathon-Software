<?php
session_start();
header('Content-Type: application/json');
require 'conexion.php';

// Verificar autenticación
if (!isset($_SESSION['ProductorID'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

$productor_id = $_SESSION['ProductorID'];
$response = ['success' => false, 'message' => ''];

try {
    // Recibir datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos inválidos');
    }
    
    $tipo_operacion = $data['tipo_operacion'] ?? '';
    $numero_factura = $data['numero_factura'] ?? '';
    $productos = $data['productos'] ?? [];
    
    // Validaciones básicas
    if (!in_array($tipo_operacion, ['alta', 'baja'])) {
        throw new Exception('Tipo de operación inválido');
    }
    
    if (empty($numero_factura)) {
        throw new Exception('Número de factura requerido');
    }
    
    if (empty($productos)) {
        throw new Exception('No hay productos para procesar');
    }
    
    // Obtener nombre del productor
    $stmt = $conexion->prepare("SELECT NombreRazonSocial FROM productores WHERE ProductorID = ?");
    $stmt->bind_param("i", $productor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $productor = $result->fetch_assoc();
    $stmt->close();
    
    if (!$productor) {
        throw new Exception('Productor no encontrado');
    }
    
    // Colecciones MongoDB
    $productosCollection = $database->Productos;
    $perdidasCollection = $database->perdidas;
    $transaccionesCollection = $database->transacciones;
    
    // Generar ID de transacción único
    $transaction_id = strtoupper(uniqid('TRX-'));
    $fecha_transaccion = new MongoDB\BSON\UTCDateTime();
    
    // Arrays para la transacción
    $items_procesados = [];
    $total_costo = 0;
    $total_venta = 0;
    $total_perdida = 0;
    
    // Procesar según tipo de operación
    if ($tipo_operacion === 'alta') {
        // === OPERACIÓN DE ALTA ===
        foreach ($productos as $prod) {
            $producto_id = new MongoDB\BSON\ObjectId($prod['producto_id']);
            $cantidad = (int)$prod['cantidad'];
            $nuevo_costo = (float)$prod['nuevo_costo'];
            $nuevo_precio = (float)$prod['nuevo_precio'];
            $stock_actual = (int)$prod['stock_actual'];
            
            // Calcular nuevo stock
            $nuevo_stock = $stock_actual + $cantidad;
            
            // Actualizar producto en MongoDB
            $resultado = $productosCollection->updateOne(
                [
                    '_id' => $producto_id,
                    'productor_id' => (int)$productor_id
                ],
                [
                    '$set' => [
                        'stock_disponible' => $nuevo_stock,
                        'costo_unitario' => $nuevo_costo,
                        'precio' => $nuevo_precio,
                        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($resultado->getMatchedCount() === 0) {
                throw new Exception("Producto no encontrado: " . $prod['nombre']);
            }
            
            // Calcular totales
            $subtotal_costo = $cantidad * $nuevo_costo;
            $subtotal_venta = $cantidad * $nuevo_precio;
            
            $total_costo += $subtotal_costo;
            $total_venta += $subtotal_venta;
            
            // Agregar a items procesados
            $items_procesados[] = [
                'producto_id' => $producto_id,
                'nombre' => $prod['nombre'],
                'cantidad' => $cantidad,
                'unidad' => $prod['unidad'],
                'stock_anterior' => $stock_actual,
                'stock_nuevo' => $nuevo_stock,
                'costo_unitario' => $nuevo_costo,
                'precio_venta' => $nuevo_precio,
                'subtotal_costo' => $subtotal_costo,
                'subtotal_venta' => $subtotal_venta
            ];
        }
        
    } else {
        // === OPERACIÓN DE BAJA ===
        foreach ($productos as $prod) {
            $producto_id = new MongoDB\BSON\ObjectId($prod['producto_id']);
            $cantidad = (int)$prod['cantidad'];
            $costo_unitario = (float)$prod['costo_unitario'];
            $precio_venta = (float)$prod['precio_venta'];
            $stock_actual = (int)$prod['stock_actual'];
            $motivo = $prod['motivo'];
            $descripcion = $prod['descripcion'] ?? '';
            
            // Validar que hay suficiente stock
            if ($cantidad > $stock_actual) {
                throw new Exception("Stock insuficiente para: " . $prod['nombre']);
            }
            
            // Calcular nuevo stock
            $nuevo_stock = $stock_actual - $cantidad;
            
            // Actualizar stock en MongoDB
            $resultado = $productosCollection->updateOne(
                [
                    '_id' => $producto_id,
                    'productor_id' => (int)$productor_id
                ],
                [
                    '$set' => [
                        'stock_disponible' => $nuevo_stock,
                        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($resultado->getMatchedCount() === 0) {
                throw new Exception("Producto no encontrado: " . $prod['nombre']);
            }
            
            // Calcular pérdida
            $costo_perdida = $cantidad * $costo_unitario;
            $perdida_valor_venta = $cantidad * $precio_venta;
            
            $total_costo += $costo_perdida;
            $total_perdida += $perdida_valor_venta;
            
            // Registrar en colección de pérdidas
            $perdidasCollection->insertOne([
                'productor_id' => (int)$productor_id,
                'productor_nombre' => $productor['NombreRazonSocial'],
                'producto' => [
                    'id' => $producto_id,
                    'nombre' => $prod['nombre']
                ],
                'cantidad_perdida' => $cantidad,
                'unidad' => $prod['unidad'],
                'costo_unitario' => $costo_unitario,
                'precio_venta_unitario' => $precio_venta,
                'costo_total_perdida' => $costo_perdida,
                'valor_venta_perdido' => $perdida_valor_venta,
                'motivo' => $motivo,
                'descripcion' => $descripcion,
                'numero_factura' => $numero_factura,
                'transaction_id' => $transaction_id,
                'fecha_perdida' => $fecha_transaccion,
                'fecha_registro' => $fecha_transaccion
            ]);
            
            // Agregar a items procesados
            $items_procesados[] = [
                'producto_id' => $producto_id,
                'nombre' => $prod['nombre'],
                'cantidad' => $cantidad,
                'unidad' => $prod['unidad'],
                'stock_anterior' => $stock_actual,
                'stock_nuevo' => $nuevo_stock,
                'costo_unitario' => $costo_unitario,
                'precio_venta' => $precio_venta,
                'costo_perdida' => $costo_perdida,
                'valor_venta_perdido' => $perdida_valor_venta,
                'motivo' => $motivo,
                'descripcion' => $descripcion
            ];
        }
    }
    
    // Registrar transacción completa
    $transaccion = [
        'transaction_id' => $transaction_id,
        'productor_id' => (int)$productor_id,
        'productor_nombre' => $productor['NombreRazonSocial'],
        'tipo_operacion' => $tipo_operacion,
        'numero_factura' => $numero_factura,
        'items' => $items_procesados,
        'total_costo' => $total_costo,
        'total_venta' => $total_venta,
        'total_perdida' => $total_perdida,
        'cantidad_productos' => count($productos),
        'fecha_transaccion' => $fecha_transaccion,
        'fecha_registro' => $fecha_transaccion
    ];
    
    $transaccionesCollection->insertOne($transaccion);
    
    // Respuesta exitosa
    $response['success'] = true;
    $response['transaction_id'] = $transaction_id;
    $response['message'] = 'Transacción procesada correctamente';
    $response['detalles'] = [
        'tipo' => $tipo_operacion,
        'productos_procesados' => count($productos),
        'total_costo' => $total_costo,
        'total_venta' => $total_venta,
        'total_perdida' => $total_perdida
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("Error en procesar_alta_baja.php: " . $e->getMessage());
}

echo json_encode($response);
?>