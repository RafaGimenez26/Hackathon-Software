<?php
session_start();
require 'conexion.php';
require_once 'includes/funciones_carrito.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: loginus.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener carrito actual
$carrito = obtenerCarritoUsuario($usuario_id);
$total = calcularTotalCarrito($usuario_id);

// Obtener historial de pedidos
$pedidosCollection = $database->Pedidos;
$pedidos = $pedidosCollection->find(['usuario_id' => $usuario_id], [
    'sort' => ['fecha_creacion' => -1]
])->toArray();

// Verificar si hay mensaje de confirmación
$mensaje = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'pedido_confirmado') {
    $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <strong>¡Pedido confirmado exitosamente!</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛒️ Mis Pedidos - AgroHub Misiones</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .item-estado-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .producto-item {
            transition: all 0.3s ease;
        }
        .producto-item:hover {
            background-color: #f8f9fa;
        }
        .estado-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <div class="header">
            <h1>🌾 AgroHub Misiones</h1>
            <p>Conectando productores locales con la comunidad Misionera</p>
            <div class="alert alert-success alert-sm mt-2">
                Usuario: <strong><?= htmlspecialchars($_SESSION['nombre_usuario']) ?></strong>
                | <a href="logout.php">Cerrar sesión</a>
            </div>
        </div>

        <div class="nav-tabs-custom">
            <a class="tab-btn" href="index.php">🛒 Ver Productos</a>
            <a class="tab-btn active" href="pedidos.php">🛒️ Carrito y Pedidos</a>
        </div>

        <?= $mensaje ?>

        <div>
            <h2 class="tab-title">Mi Carrito y Pedidos</h2>
            
            <!-- CARRITO DE COMPRAS ACTUAL -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-cart3"></i> Carrito de Pedido Actual
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (!$carrito || empty($carrito['items'])): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x" style="font-size: 4rem; color: #ccc;"></i>
                            <h5 class="mt-3 text-muted">Tu carrito está vacío</h5>
                            <p class="text-muted">Comienza a agregar productos desde nuestra tienda</p>
                            <a href="index.php" class="btn btn-primary mt-3">
                                <i class="bi bi-shop"></i> Ir a Productos
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($carrito['items'] as $item): ?>
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <div class="me-3">
                                        <div style="width: 60px; height: 60px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-box-seam text-muted"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($item['nombre']) ?></h6>
                                        <small class="text-muted">
                                            Precio unitario: $<?= number_format($item['precio_unitario'], 0, ',', '.') ?>
                                        </small>
                                        <div class="mt-1">
                                            <span class="badge bg-success">
                                                <?= $item['cantidad'] ?> <?= htmlspecialchars($item['unidad'] ?? 'u') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="h5 text-success mb-1">
                                        $<?= number_format($item['precio_unitario'] * $item['cantidad'], 0, ',', '.') ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger btn-eliminar"
                                            data-producto-id="<?= $item['producto_id'] ?>"
                                            data-nombre="<?= htmlspecialchars($item['nombre']) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="card bg-success text-white mt-4">
                            <div class="card-body text-center">
                                <h4 class="mb-2">Total: $<?= number_format($total, 0, ',', '.') ?></h4>
                                <p class="mb-3">
                                    <?= count($carrito['items']) ?> producto(s) en tu carrito
                                </p>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <button class="btn btn-light me-2" id="btnVaciarCarrito">
                                        <i class="bi bi-trash3"></i> Vaciar Carrito
                                    </button>
                                    <a href="confirmar_pedido.php" class="btn btn-warning btn-lg">
                                        <i class="bi bi-check-circle"></i> Confirmar Pedido
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- HISTORIAL DE PEDIDOS -->
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="mb-0">
                        <i class="bi bi-clock-history"></i> Historial de Pedidos
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (empty($pedidos)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3">Aún no tienes pedidos confirmados</p>
                            <small>Los pedidos que confirmes aparecerán aquí</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <?php
                            $totalPedido = isset($pedido['total']) ? $pedido['total'] : 0;
                            if ($totalPedido == 0) {
                                foreach ($pedido['items'] as $item) {
                                    $totalPedido += $item['precio_unitario'] * $item['cantidad'];
                                }
                            }
                            
                            $fecha = $pedido['fecha_creacion']->toDateTime()->format('d/m/Y H:i');
                            
                            // Agrupar items por productor
                            $itemsPorProductor = [];
                            foreach ($pedido['items'] as $item) {
                                $prodId = $item['productor_id'] ?? 'sin_productor';
                                if (!isset($itemsPorProductor[$prodId])) {
                                    $itemsPorProductor[$prodId] = [
                                        'nombre' => $item['productor_nombre'] ?? 'Productor desconocido',
                                        'items' => []
                                    ];
                                }
                                $itemsPorProductor[$prodId]['items'][] = $item;
                            }
                            ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="mb-1">
                                                Pedido #<?= substr((string)$pedido['_id'], -8) ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= $fecha ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-info fs-6">
                                            <?= count($pedido['items']) ?> productos
                                        </span>
                                    </div>
                                    
                                    <!-- Productos agrupados por productor -->
                                    <?php foreach ($itemsPorProductor as $prodId => $prodData): ?>
                                        <div class="mb-3 p-3 border rounded">
                                            <h6 class="mb-2">
                                                <i class="bi bi-person-badge"></i> 
                                                <?= htmlspecialchars($prodData['nombre']) ?>
                                            </h6>
                                            
                                            <?php foreach ($prodData['items'] as $item): ?>
                                                <?php 
                                                $estadoItem = $item['estado'] ?? 'pendiente';
                                                $estadoInfo = getEstadoItemBadge($estadoItem);
                                                ?>
                                                <div class="producto-item d-flex justify-content-between align-items-center py-2 px-3 mb-2 border-start border-3 <?= $estadoInfo['class'] === 'bg-success' ? 'border-success' : ($estadoInfo['class'] === 'bg-danger' ? 'border-danger' : 'border-warning') ?>">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-<?= $estadoInfo['icon'] ?> estado-icon"></i>
                                                            <div>
                                                                <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                                                <small class="d-block text-muted">
                                                                    <?= $item['cantidad'] ?> <?= htmlspecialchars($item['unidad'] ?? 'u') ?> 
                                                                    × $<?= number_format($item['precio_unitario'], 0, ',', '.') ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="mb-1">
                                                            <strong>$<?= number_format($item['precio_unitario'] * $item['cantidad'], 0, ',', '.') ?></strong>
                                                        </div>
                                                        <span class="badge <?= $estadoInfo['class'] ?> item-estado-badge">
                                                            <?= $estadoInfo['texto'] ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="row mt-3 pt-3 border-top">
                                        <div class="col-md-8">
                                            <?php
                                            $estadosContador = [];
                                            foreach ($pedido['items'] as $item) {
                                                $est = $item['estado'] ?? 'pendiente';
                                                $estadosContador[$est] = ($estadosContador[$est] ?? 0) + 1;
                                            }
                                            ?>
                                            <small class="text-muted">
                                                <strong>Resumen:</strong>
                                                <?php foreach ($estadosContador as $estado => $count): ?>
                                                    <?php $info = getEstadoItemBadge($estado); ?>
                                                    <span class="badge <?= $info['class'] ?> ms-1">
                                                        <?= $count ?> <?= $info['texto'] ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <p class="h5 text-success mb-2">
                                                Total: $<?= number_format($totalPedido, 0, ',', '.') ?>
                                            </p>
                                            <button class="btn btn-sm btn-outline-primary btn-ver-detalle"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalDetalle"
                                                    data-pedido='<?= json_encode([
                                                        'id' => substr((string)$pedido['_id'], -8),
                                                        'fecha' => $fecha,
                                                        'items' => iterator_to_array($pedido['items']),
                                                        'total' => $totalPedido,
                                                        'itemsPorProductor' => $itemsPorProductor
                                                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                                <i class="bi bi-eye"></i> Ver detalles completos
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles del pedido -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-receipt"></i> Detalle del Pedido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContenido">
                    <!-- Contenido cargado dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', function() {
                const productoId = this.dataset.productoId;
                const nombre = this.dataset.nombre;
                
                if (confirm(`¿Eliminar "${nombre}" del carrito?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'eliminar_carrito.php';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'producto_id';
                    input.value = productoId;
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        document.getElementById('btnVaciarCarrito')?.addEventListener('click', function() {
            if (confirm('⚠️ ¿Estás seguro de vaciar todo el carrito?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'vaciar_carrito.php';
                document.body.appendChild(form);
                form.submit();
            }
        });

        document.querySelectorAll('.btn-ver-detalle').forEach(btn => {
            btn.addEventListener('click', function() {
                const pedido = JSON.parse(this.dataset.pedido);
                const contenido = document.getElementById('modalContenido');
                
                let productoresHTML = '';
                for (const [prodId, prodData] of Object.entries(pedido.itemsPorProductor)) {
                    let itemsHTML = '';
                    prodData.items.forEach(item => {
                        const subtotal = item.precio_unitario * item.cantidad;
                        const estadoInfo = getEstadoBadgeJS(item.estado || 'pendiente');
                        
                        itemsHTML += `
                            <tr>
                                <td>
                                    <strong>${item.nombre}</strong>
                                    <br>
                                    <span class="badge ${estadoInfo.class} item-estado-badge">
                                        <i class="bi bi-${estadoInfo.icon}"></i> ${estadoInfo.texto}
                                    </span>
                                </td>
                                <td class="text-center">${item.cantidad} ${item.unidad || 'u'}</td>
                                <td class="text-end">$${item.precio_unitario.toLocaleString('es-AR')}</td>
                                <td class="text-end"><strong>$${subtotal.toLocaleString('es-AR')}</strong></td>
                            </tr>
                        `;
                    });
                    
                    productoresHTML += `
                        <div class="mb-4">
                            <h6 class="bg-light p-2 rounded">
                                <i class="bi bi-person-badge"></i> ${prodData.nombre}
                            </h6>
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-end">Precio Unit.</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsHTML}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
                
                contenido.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <p><strong>Pedido ID:</strong> #${pedido.id}</p>
                            <p><strong>Fecha:</strong> ${pedido.fecha}</p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Productos del Pedido por Productor:</h6>
                    ${productoresHTML}
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                        <h5 class="mb-0">TOTAL DEL PEDIDO:</h5>
                        <h4 class="text-success mb-0">${pedido.total.toLocaleString('es-AR')}</h4>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Información importante:</strong><br>
                        Cada productor gestiona el estado de sus productos de forma independiente. 
                        Te contactarán para coordinar la entrega o retiro de sus productos.
                    </div>
                `;
            });
        });
        
        // Función auxiliar para obtener info de estado en JavaScript
        function getEstadoBadgeJS(estado) {
            const estados = {
                'pendiente': {class: 'bg-warning text-dark', icon: 'clock-history', texto: 'Pendiente'},
                'confirmado': {class: 'bg-info', icon: 'check-circle', texto: 'Confirmado'},
                'en_preparacion': {class: 'bg-primary', icon: 'box-seam', texto: 'En preparación'},
                'listo': {class: 'bg-success', icon: 'check-all', texto: 'Listo para entregar'},
                'entregado': {class: 'bg-success', icon: 'bag-check', texto: 'Entregado'},
                'no_entregado': {class: 'bg-danger', icon: 'x-circle', texto: 'No se pudo entregar'},
                'cancelado': {class: 'bg-secondary', icon: 'x-circle', texto: 'Cancelado'}
            };
            return estados[estado] || {class: 'bg-secondary', icon: 'question-circle', texto: estado};
        }
    </script>
</body>
</html>