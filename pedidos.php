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
    <title>🛍️ Mis Pedidos - AgroHub Misiones</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
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
            <a class="tab-btn" href="registro.php">👨‍🌾 Registrarse como Productor</a>
            <a class="tab-btn" href="misproductos.php">📦 Mis Productos</a>
            <a class="tab-btn active" href="pedidos.php">🛍️ Carrito y Pedidos</a>
        </div>

        <!-- MENSAJE DE CONFIRMACIÓN -->
        <?= $mensaje ?>

        <!-- CONTENIDO DE MIS PEDIDOS -->
        <div>
            <h2 class="tab-title">Mi Carrito y Pedidos</h2>
            
            <!-- ============================================ -->
            <!-- CARRITO DE COMPRAS ACTUAL -->
            <!-- ============================================ -->
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
                        <!-- Items del carrito -->
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

                        <!-- Total y acciones -->
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
                                    <!-- Botón que lleva a confirmar_pedido.php -->
                                    <a href="confirmar_pedido.php" class="btn btn-warning btn-lg">
                                        <i class="bi bi-check-circle"></i> Confirmar Pedido
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- HISTORIAL DE PEDIDOS -->
            <!-- ============================================ -->
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
                            // Calcular total del pedido
                            $totalPedido = isset($pedido['total']) ? $pedido['total'] : 0;
                            if ($totalPedido == 0) {
                                foreach ($pedido['items'] as $item) {
                                    $totalPedido += $item['precio_unitario'] * $item['cantidad'];
                                }
                            }
                            
                            // Definir color del badge según estado
                            $estadoBadge = [
                                'pendiente' => 'bg-warning text-dark',
                                'confirmado' => 'bg-info',
                                'en_proceso' => 'bg-primary',
                                'en_preparacion' => 'bg-primary',
                                'listo' => 'bg-success',
                                'entregado' => 'bg-success',
                                'cancelado' => 'bg-danger'
                            ];
                            $badgeClass = $estadoBadge[$pedido['estado']] ?? 'bg-secondary';
                            
                            // Formatear fecha
                            $fecha = $pedido['fecha_creacion']->toDateTime()->format('d/m/Y H:i');
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
                                        <span class="badge <?= $badgeClass ?> fs-6">
                                            <?= ucfirst(str_replace('_', ' ', $pedido['estado'])) ?>
                                        </span>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p class="mb-1"><strong>Productos:</strong></p>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($pedido['items'] as $item): ?>
                                                    <li class="mb-1">
                                                        • <?= htmlspecialchars($item['nombre']) ?> 
                                                        (<?= $item['cantidad'] ?> <?= htmlspecialchars($item['unidad'] ?? 'u') ?>) 
                                                        - $<?= number_format($item['precio_unitario'] * $item['cantidad'], 0, ',', '.') ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <p class="h5 text-success mb-2">
                                                $<?= number_format($totalPedido, 0, ',', '.') ?>
                                            </p>
                                            <button class="btn btn-sm btn-outline-primary btn-ver-detalle"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalDetalle"
                                                    data-pedido='<?= json_encode([
                                                        'id' => substr((string)$pedido['_id'], -8),
                                                        'fecha' => $fecha,
                                                        'estado' => ucfirst(str_replace('_', ' ', $pedido['estado'])),
                                                        'badge' => $badgeClass,
                                                        'items' => $pedido['items'],
                                                        'total' => $totalPedido
                                                    ]) ?>'>
                                                <i class="bi bi-eye"></i> Ver detalles
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
        // ============================================
        // ELIMINAR PRODUCTO DEL CARRITO
        // ============================================
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', function() {
                const productoId = this.dataset.productoId;
                const nombre = this.dataset.nombre;
                
                if (confirm(`¿Eliminar "${nombre}" del carrito?`)) {
                    // Crear formulario temporal para enviar POST
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

        // ============================================
        // VACIAR CARRITO COMPLETO
        // ============================================
        document.getElementById('btnVaciarCarrito')?.addEventListener('click', function() {
            if (confirm('⚠️ ¿Estás seguro de vaciar todo el carrito?')) {
                // Crear formulario para vaciar carrito
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'vaciar_carrito.php';
                document.body.appendChild(form);
                form.submit();
            }
        });

        // ============================================
        // VER DETALLE DE PEDIDO EN MODAL
        // ============================================
        document.querySelectorAll('.btn-ver-detalle').forEach(btn => {
            btn.addEventListener('click', function() {
                const pedido = JSON.parse(this.dataset.pedido);
                const contenido = document.getElementById('modalContenido');
                
                let itemsHTML = '';
                pedido.items.forEach(item => {
                    const subtotal = item.precio_unitario * item.cantidad;
                    itemsHTML += `
                        <tr>
                            <td><strong>${item.nombre}</strong></td>
                            <td class="text-center">${item.cantidad} ${item.unidad || 'u'}</td>
                            <td class="text-end">$${item.precio_unitario.toLocaleString('es-AR')}</td>
                            <td class="text-end"><strong>$${subtotal.toLocaleString('es-AR')}</strong></td>
                        </tr>
                    `;
                });
                
                contenido.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Pedido ID:</strong> #${pedido.id}</p>
                            <p><strong>Fecha:</strong> ${pedido.fecha}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge ${pedido.badge} fs-6">${pedido.estado}</span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Productos del Pedido:</h6>
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
                                ${itemsHTML}
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                    <td class="text-end">
                                        <h5 class="text-success mb-0">$${pedido.total.toLocaleString('es-AR')}</h5>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Información importante:</strong><br>
                        Los productores han sido notificados de tu pedido. 
                        Te contactarán para coordinar la entrega o retiro.
                    </div>
                `;
            });
        });
    </script>
</body>
</html>