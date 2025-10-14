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
            
            // Obtener nombre del productor desde MySQL
            $productorNombre = 'Desconocido';
            if (isset($producto['productor_id'])) {
                $stmt = $conexion->prepare("SELECT NombreRazonSocial FROM productores WHERE ProductorID = ?");
                $stmt->bind_param("i", $producto['productor_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $productorNombre = $row['NombreRazonSocial'];
                }
                $stmt->close();
            }
            
            $items[] = [
                'producto_id' => $itemArray['producto_id'],
                'nombre' => $itemArray['nombre'],
                'precio_unitario' => (float)$itemArray['precio_unitario'],
                'cantidad' => (int)$itemArray['cantidad'],
                'unidad' => $itemArray['unidad'] ?? 'u',
                'productor_id' => $producto['productor_id'] ?? null,
                'productor_nombre' => $productorNombre,
                'estado' => 'pendiente',
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
            'estado' => 'pendiente',
            'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $resultado = $pedidosCollection->insertOne($nuevoPedido);
        
        if ($resultado->getInsertedId()) {
            vaciarCarrito($usuario_id);
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

// Agrupar items por productor
$itemsPorProductor = [];
$totalGeneral = 0;

foreach ($carrito['items'] as $item) {
    $itemArray = is_array($item) ? $item : iterator_to_array($item);
    
    // Obtener el nombre real del productor desde MySQL
    $productorNombre = 'Productor local';
    $productorId = $itemArray['productor_id'] ?? 0;
    
    if ($productorId > 0) {
        $stmt = $conexion->prepare("SELECT NombreRazonSocial FROM productores WHERE ProductorID = ?");
        $stmt->bind_param("i", $productorId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $productorNombre = $row['NombreRazonSocial'];
        }
        $stmt->close();
    }
    
    // Crear clave única para el productor
    $claveProductor = $productorId . '_' . $productorNombre;
    
    if (!isset($itemsPorProductor[$claveProductor])) {
        $itemsPorProductor[$claveProductor] = [
            'nombre' => $productorNombre,
            'id' => $productorId,
            'items' => [],
            'subtotal' => 0
        ];
    }
    
    $subtotal = $itemArray['precio_unitario'] * $itemArray['cantidad'];
    $itemsPorProductor[$claveProductor]['items'][] = $itemArray;
    $itemsPorProductor[$claveProductor]['subtotal'] += $subtotal;
    $totalGeneral += $subtotal;
}

// Ordenar por nombre de productor
ksort($itemsPorProductor);
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
    <style>
        .productor-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin-top: 20px;
        }
        .productor-card {
            border: 2px solid #667eea;
            border-radius: 8px;
            margin-bottom: 25px;
            overflow: hidden;
        }
        .productor-subtotal {
            background-color: #f8f9fa;
            padding: 12px 20px;
            font-weight: bold;
            border-top: 2px solid #dee2e6;
        }
    </style>
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

                <h5 class="mb-3">
                    <i class="bi bi-shop"></i> Productos agrupados por vendedor:
                    <span class="badge bg-secondary"><?= count($itemsPorProductor) ?> vendedor(es)</span>
                </h5>
                
                <?php foreach ($itemsPorProductor as $productor): ?>
                    <div class="productor-card">
                        <div class="productor-header">
                            <h5 class="mb-0">
                                <i class="bi bi-person-badge"></i> 
                                <?= htmlspecialchars($productor['nombre']) ?>
                            </h5>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-end">Precio Unit.</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productor['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($item['nombre']) ?></strong>
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
                            </table>
                        </div>
                        
                        <div class="productor-subtotal text-end">
                            <i class="bi bi-calculator"></i> Subtotal de este vendedor: 
                            <span class="text-primary">
                                $<?= number_format($productor['subtotal'], 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="card bg-light border-success">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-0">
                                    <i class="bi bi-cart-check"></i> TOTAL DEL PEDIDO
                                </h5>
                            </div>
                            <div class="col-md-4 text-end">
                                <h3 class="text-success mb-0">
                                    $<?= number_format($totalGeneral, 0, ',', '.') ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Importante:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Al confirmar, tu pedido será enviado a <strong><?= count($itemsPorProductor) ?> vendedor(es)</strong></li>
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