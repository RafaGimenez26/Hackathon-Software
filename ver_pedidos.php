<?php
session_start();
require 'conexion.php'; // contiene conexión MongoDB y MySQL

if (!isset($_SESSION['ProductorID'])) {
    header("Location: misproductos.php");
    exit;
}

$productor_id = $_SESSION['ProductorID'];

// ---- COLECCIONES MONGO ----
$pedidosCollection = $database->Pedidos;
$productosCollection = $database->Productos;

// --- Cambiar estado INDIVIDUAL de un producto específico ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion'])) {
    $pedidoId = new MongoDB\BSON\ObjectId($_POST['pedido_id']);
    $productoId = new MongoDB\BSON\ObjectId($_POST['producto_id']);
    $accion = $_POST['accion'];

    $pedido = $pedidosCollection->findOne(['_id' => $pedidoId]);
    
    if ($pedido) {
        $items = $pedido['items'];
        if ($items instanceof MongoDB\Model\BSONArray) {
            $items = iterator_to_array($items);
        }

        $itemActualizado = false;
        
        // Buscar y actualizar solo el item específico del productor
        foreach ($items as $index => &$item) {
            if ((string)$item['producto_id'] === (string)$productoId && 
                (int)$item['productor_id'] === (int)$productor_id) {
                
                $nuevoEstado = "";
                switch ($accion) {
                    case "listo":
                        $nuevoEstado = "listo";
                        break;
                    case "entregado":
                        $nuevoEstado = "entregado";
                        // Descontar stock solo de este producto
                        $productosCollection->updateOne(
                            ['_id' => $item['producto_id']],
                            ['$inc' => ['stock_disponible' => -$item['cantidad']]]
                        );
                        break;
                    case "no_se_pudo_entregar":
                        $nuevoEstado = "no_entregado";
                        break;
                    case "en_preparacion":
                        $nuevoEstado = "en_preparacion";
                        break;
                }

                if ($nuevoEstado !== "") {
                    $item['estado'] = $nuevoEstado;
                    $item['fecha_actualizacion_estado'] = new MongoDB\BSON\UTCDateTime();
                    $itemActualizado = true;
                }
                break;
            }
        }
        unset($item);

        if ($itemActualizado) {
            $pedidosCollection->updateOne(
                ['_id' => $pedidoId],
                [
                    '$set' => [
                        'items' => $items,
                        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
        }
    }
    
    header("Location: ver_pedidos.php");
    exit;
}

// --- Marcar TODOS los productos del productor como "listos para retiro" ---
if (isset($_POST['marcar_todos'])) {
    $pedidos = $pedidosCollection->find([
        'items.productor_id' => $productor_id
    ]);
    
    foreach ($pedidos as $pedido) {
        $items = $pedido['items'];
        if ($items instanceof MongoDB\Model\BSONArray) {
            $items = iterator_to_array($items);
        }
        
        $huboActualizacion = false;
        foreach ($items as &$item) {
            if ((int)$item['productor_id'] === (int)$productor_id && 
                $item['estado'] !== 'entregado' && 
                $item['estado'] !== 'no_entregado') {
                $item['estado'] = 'listo';
                $item['fecha_actualizacion_estado'] = new MongoDB\BSON\UTCDateTime();
                $huboActualizacion = true;
            }
        }
        unset($item);
        
        if ($huboActualizacion) {
            $pedidosCollection->updateOne(
                ['_id' => $pedido['_id']],
                ['$set' => ['items' => $items]]
            );
        }
    }
    
    header("Location: ver_pedidos.php");
    exit;
}

// --- Obtener pedidos del productor ---
$pedidos = $pedidosCollection->find([
    'items.productor_id' => $productor_id
], [
    'sort' => ['fecha_creacion' => -1]
])->toArray();

// Filtrar solo los items del productor
$pedidosFiltrados = [];
foreach ($pedidos as $pedido) {
    $itemsDelProductor = [];
    $totalProductor = 0;
    
    foreach ($pedido['items'] as $item) {
        if ((int)$item['productor_id'] === (int)$productor_id) {
            $itemsDelProductor[] = $item;
            $totalProductor += $item['precio_unitario'] * $item['cantidad'];
        }
    }
    
    if (!empty($itemsDelProductor)) {
        $pedidosFiltrados[] = [
            'pedido' => $pedido,
            'items' => $itemsDelProductor,
            'total_productor' => $totalProductor
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📦 Pedidos del Productor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .item-card {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
        }
        .item-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .item-card.pendiente { border-left-color: #ffc107; background-color: #fffbf0; }
        .item-card.en_preparacion { border-left-color: #0dcaf0; background-color: #f0f9ff; }
        .item-card.listo { border-left-color: #198754; background-color: #f0fdf4; }
        .item-card.entregado { border-left-color: #20c997; background-color: #ecfdf5; }
        .item-card.no_entregado { border-left-color: #dc3545; background-color: #fef2f2; }
        
        .estado-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
        }
        
        .btn-estado {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        
        .stats-card {
            border-left: 4px solid;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-success mb-1">📦 Mis Pedidos Recibidos</h1>
            <p class="text-muted mb-0">Gestiona el estado de cada producto individualmente</p>
        </div>
        <div>
            <form method="POST" class="d-inline" onsubmit="return confirm('¿Marcar TODOS tus productos pendientes como listos para retiro?')">
                <button type="submit" name="marcar_todos" class="btn btn-success">
                    <i class="bi bi-check-all"></i> Marcar Todos Listos
                </button>
            </form>
            <a href="dashboard_productor.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <?php if (empty($pedidosFiltrados)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
            <h5 class="mt-3">No hay pedidos pendientes</h5>
            <p>Los pedidos que incluyan tus productos aparecerán aquí</p>
        </div>
    <?php else: ?>
        <!-- Resumen de estados -->
        <?php
        $contadorEstados = [
            'pendiente' => 0,
            'en_preparacion' => 0,
            'listo' => 0,
            'entregado' => 0,
            'no_entregado' => 0
        ];
        
        foreach ($pedidosFiltrados as $data) {
            foreach ($data['items'] as $item) {
                $estado = $item['estado'] ?? 'pendiente';
                if (isset($contadorEstados[$estado])) {
                    $contadorEstados[$estado]++;
                }
            }
        }
        ?>
        
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card border-warning">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-0 text-warning"><?= $contadorEstados['pendiente'] ?></div>
                        <small class="text-muted">Pendientes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card border-info">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-0 text-info"><?= $contadorEstados['en_preparacion'] ?></div>
                        <small class="text-muted">En preparación</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card border-success">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-0 text-success"><?= $contadorEstados['listo'] ?></div>
                        <small class="text-muted">Listos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-success">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-0 text-success"><?= $contadorEstados['entregado'] ?></div>
                        <small class="text-muted">Entregados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-danger">
                    <div class="card-body p-3 text-center">
                        <div class="h2 mb-0 text-danger"><?= $contadorEstados['no_entregado'] ?></div>
                        <small class="text-muted">No entregados</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de pedidos -->
        <?php foreach ($pedidosFiltrados as $data): ?>
            <?php
            $pedido = $data['pedido'];
            $items = $data['items'];
            $totalProductor = $data['total_productor'];
            
            // Buscar datos del usuario en MySQL
            $usuario_id = $pedido['usuario_id'];
            $cliente = null;
            $sql = "SELECT nombre_usuario, correo, telefono FROM usuarios WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $cliente = $result->fetch_assoc();
            }
            $stmt->close();
            
            $fecha = $pedido['fecha_creacion']->toDateTime()->format('d/m/Y H:i');
            ?>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-1">
                                <i class="bi bi-receipt"></i> 
                                Pedido #<?= substr((string)$pedido['_id'], -8) ?>
                            </h5>
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> <?= $fecha ?>
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <h5 class="text-success mb-0">
                                Tu total: $<?= number_format($totalProductor, 0, ',', '.') ?>
                            </h5>
                            <small class="text-muted"><?= count($items) ?> producto(s)</small>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Información del cliente -->
                    <?php if ($cliente): ?>
                        <div class="alert alert-light border mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong><i class="bi bi-person"></i> Cliente:</strong> 
                                    <?= htmlspecialchars($cliente['nombre_usuario']) ?>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="bi bi-telephone"></i> Teléfono:</strong> 
                                    <a href="https://wa.me/54<?= preg_replace('/\D/', '', $cliente['telefono']) ?>" 
                                       target="_blank" 
                                       class="text-success text-decoration-none">
                                        <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($cliente['telefono']) ?>
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="bi bi-envelope"></i> Correo:</strong> 
                                    <a href="mailto:<?= htmlspecialchars($cliente['correo']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($cliente['correo']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Cliente no encontrado en la base de datos.
                        </div>
                    <?php endif; ?>

                    <!-- Productos del pedido -->
                    <?php foreach ($items as $item): ?>
                        <?php
                        $estado = $item['estado'] ?? 'pendiente';
                        $subtotal = $item['precio_unitario'] * $item['cantidad'];
                        
                        $estadoBadges = [
                            'pendiente' => ['class' => 'bg-warning text-dark', 'icon' => 'clock-history', 'texto' => 'Pendiente'],
                            'en_preparacion' => ['class' => 'bg-info', 'icon' => 'box-seam', 'texto' => 'En preparación'],
                            'listo' => ['class' => 'bg-success', 'icon' => 'check-circle', 'texto' => 'Listo para retiro'],
                            'entregado' => ['class' => 'bg-success', 'icon' => 'bag-check', 'texto' => 'Entregado'],
                            'no_entregado' => ['class' => 'bg-danger', 'icon' => 'x-circle', 'texto' => 'No entregado']
                        ];
                        
                        $badgeInfo = $estadoBadges[$estado] ?? ['class' => 'bg-secondary', 'icon' => 'question', 'texto' => ucfirst($estado)];
                        ?>

                        <div class="item-card <?= $estado ?> p-3 mb-3 rounded">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <h6 class="mb-1">
                                        <i class="bi bi-box-seam"></i>
                                        <?= htmlspecialchars($item['nombre']) ?>
                                    </h6>
                                    <div class="text-muted small">
                                        <span class="badge bg-secondary me-1">
                                            <?= $item['cantidad'] ?> <?= htmlspecialchars($item['unidad'] ?? 'u') ?>
                                        </span>
                                        × $<?= number_format($item['precio_unitario'], 0, ',', '.') ?>
                                        = <strong>$<?= number_format($subtotal, 0, ',', '.') ?></strong>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge <?= $badgeInfo['class'] ?> estado-badge">
                                            <i class="bi bi-<?= $badgeInfo['icon'] ?>"></i>
                                            <?= $badgeInfo['texto'] ?>
                                        </span>
                                        <?php if (isset($item['fecha_actualizacion_estado'])): ?>
                                            <small class="text-muted d-block mt-1">
                                                Actualizado: <?= $item['fecha_actualizacion_estado']->toDateTime()->format('d/m H:i') ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-7">
                                    <form method="POST" class="d-flex flex-wrap gap-2 justify-content-end">
                                        <input type="hidden" name="pedido_id" value="<?= $pedido['_id'] ?>">
                                        <input type="hidden" name="producto_id" value="<?= $item['producto_id'] ?>">
                                        
                                        <?php if ($estado !== 'entregado' && $estado !== 'no_entregado'): ?>
                                            <?php if ($estado === 'pendiente'): ?>
                                                <button name="accion" value="en_preparacion" class="btn btn-info btn-sm btn-estado">
                                                    <i class="bi bi-box-seam"></i> En preparación
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button name="accion" value="listo" class="btn btn-warning btn-sm btn-estado">
                                                <i class="bi bi-truck"></i> Listo para retiro
                                            </button>
                                            
                                            <button name="accion" value="entregado" class="btn btn-success btn-sm btn-estado"
                                                    onclick="return confirm('¿Confirmar que este producto fue ENTREGADO? Se descontará del stock.')">
                                                <i class="bi bi-check-circle"></i> Entregado
                                            </button>
                                            
                                            <button name="accion" value="no_se_pudo_entregar" class="btn btn-danger btn-sm btn-estado"
                                                    onclick="return confirm('¿Marcar como NO ENTREGADO?')">
                                                <i class="bi bi-x-circle"></i> No se pudo
                                            </button>
                                        <?php else: ?>
                                            <div class="alert alert-light mb-0 py-2">
                                                <i class="bi bi-info-circle"></i> 
                                                Este producto ya fue procesado
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>