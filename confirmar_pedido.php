<?php
session_start();
require 'conexion.php';
require_once 'includes/funciones_carrito.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: loginus.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener carrito
$carrito = obtenerCarritoUsuario($usuario_id);

if (!$carrito || empty($carrito['items'])) {
    header("Location: pedidos.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pedidosCollection = $database->Pedidos;
        $productosCollection = $database->Productos;
        
        // Preparar items para el pedido asegurando que cada uno tenga su estado
        $items = [];
        foreach ($carrito['items'] as $item) {
            // Convertir BSONArray a array si es necesario
            $itemArray = is_array($item) ? $item : iterator_to_array($item);
            
            // Obtener información del productor desde el producto
            $producto = $productosCollection->findOne([
                '_id' => $itemArray['producto_id']
            ]);
            
            $items[] = [
                'producto_id' => $itemArray['producto_id'],
                'nombre' => $itemArray['nombre'],
                'precio_unitario' => (float)$itemArray['precio_unitario'],
                'cantidad' => (int)$itemArray['cantidad'],
                'unidad' => $itemArray['unidad'] ?? 'u',
                'productor_id' => $producto['productor_id'] ?? null,
                'productor_nombre' => $producto['productor_nombre'] ?? 'Desconocido',
                'estado' => 'pendiente', // Estado inicial de cada item
                'fecha_agregado' => new MongoDB\BSON\UTCDateTime()
            ];
        }
        
        // Calcular total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['precio_unitario'] * $item['cantidad'];
        }
        
        // Crear pedido
        $nuevoPedido = [
            'usuario_id' => (int)$usuario_id,
            'items' => $items,
            'total' => $total,
            'estado' => 'pendiente', // Estado general del pedido (para compatibilidad)
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $resultado = $pedidosCollection->insertOne($nuevoPedido);
        
        if ($resultado->getInsertedId()) {
            // Vaciar el carrito después de confirmar
            vaciarCarrito($usuario_id);
            
            // Redirigir con mensaje de éxito
            header("Location: pedidos.php?msg=pedido_confirmado");
            exit;
        } else {
            $error = "No se pudo crear el pedido";
        }
        
    } catch (Exception $e) {
        error_log("Error al confirmar pedido: " . $e->getMessage());
        $error = "Error al procesar el pedido: " . $e->getMessage();
    }
}

// Calcular total para mostrar
$total = calcularTotalCarrito($usuario_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pedido - AgroHub Misiones</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container-custom">
        <div class="header">
            <h1>🌾 AgroHub Misiones</h1>
            <p>Confirmar tu Pedido</p>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="bi bi-check-circle"></i> Resumen del Pedido
                </h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <h5 class="mb-3">Productos en tu pedido:</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carrito['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($item['productor_nombre'] ?? 'Productor local') ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?= $item['cantidad'] ?> <?= htmlspecialchars($item['unidad']) ?>
                                    </td>
                                    <td class="text-end">
                                        $<?= number_format($item['precio_unitario'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-end">
                                        <strong>$<?= number_format($item['precio_unitario'] * $item['cantidad'], 0, ',', '.') ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end">
                                    <h4 class="text-success mb-0">
                                        $<?= number_format($total, 0, ',', '.') ?>
                                    </h4>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <hr>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Importante:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Al confirmar, tu pedido será enviado a los productores</li>
                        <li>Cada productor gestionará sus productos de forma independiente</li>
                        <li>Te contactarán para coordinar la entrega o retiro</li>
                        <li>Podrás ver el estado de cada producto en tu historial de pedidos</li>
                    </ul>
                </div>

                <form method="POST" class="mt-4">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="pedidos.php" class="btn btn-secondary btn-lg">
                            <i class="bi bi-arrow-left"></i> Volver al Carrito
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Confirmar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>