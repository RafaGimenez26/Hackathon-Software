<?php
session_start();
require 'conexion.php';

// Si no hay sesión activa, redirigir a login
if (!isset($_SESSION['ProductorID'])) {
    header('Location: misproductos.php');
    exit;
}

// Obtener ID del productor logueado
$productor_id = $_SESSION['ProductorID'];
$nombre_productor = $_SESSION['nombre_productor'];

// === ACTUALIZAR STOCK (llamado AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $producto_id = $_POST['producto_id'];
    $nuevo_stock = (int)$_POST['nuevo_stock'];

    try {
        $result = $collection->updateOne(
            [
                '_id' => new MongoDB\BSON\ObjectId($producto_id),
                'productor_id' => (int)$productor_id
            ],
            ['$set' => ['stock_disponible' => $nuevo_stock]]
        );

        if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró el producto o no pertenece al productor.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// === ELIMINAR PRODUCTO (llamado AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    try {
        $result = $collection->deleteOne([
            '_id' => new MongoDB\BSON\ObjectId($delete_id),
            'productor_id' => (int)$productor_id
        ]);

        if ($result->getDeletedCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró el producto o no pertenece al productor.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// === OBTENER DATOS DE VENTAS PARA GRÁFICOS ===
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_ventas_data'])) {
    try {
        $collectionVentas = $database->productos_vendidos;
        
        // Fecha de hace un año
        $fechaHaceUnAno = new MongoDB\BSON\UTCDateTime(strtotime('-1 year') * 1000);
        
        // Ventas por mes
        $ventasPorMes = $collectionVentas->aggregate([
            [
                '$match' => [
                    'vendedor.productor_id' => (int)$productor_id,
                    'fecha_venta' => ['$gte' => $fechaHaceUnAno]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        'year' => ['$year' => '$fecha_venta'],
                        'month' => ['$month' => '$fecha_venta']
                    ],
                    'total' => ['$sum' => '$monto_total'],
                    'cantidad_ventas' => ['$sum' => 1]
                ]
            ],
            ['$sort' => ['_id.year' => 1, '_id.month' => 1]]
        ]);
        
        // Productos más vendidos
        $productosMasVendidos = $collectionVentas->aggregate([
            [
                '$match' => [
                    'vendedor.productor_id' => (int)$productor_id,
                    'fecha_venta' => ['$gte' => $fechaHaceUnAno]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$producto.nombre',
                    'total_vendido' => ['$sum' => '$cantidad'],
                    'monto_total' => ['$sum' => '$monto_total']
                ]
            ],
            ['$sort' => ['total_vendido' => -1]],
            ['$limit' => 10]
        ]);
        
        $dataMeses = [];
        foreach ($ventasPorMes as $venta) {
            $dataMeses[] = [
                'mes' => $venta->_id->month,
                'anio' => $venta->_id->year,
                'total' => $venta->total,
                'cantidad' => $venta->cantidad_ventas
            ];
        }
        
        $dataProductos = [];
        foreach ($productosMasVendidos as $prod) {
            $dataProductos[] = [
                'nombre' => $prod->_id,
                'cantidad' => $prod->total_vendido,
                'monto' => $prod->monto_total
            ];
        }
        
        echo json_encode([
            'ventasPorMes' => $dataMeses,
            'productosMasVendidos' => $dataProductos
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// === CONSULTAR PRODUCTOS DEL PRODUCTOR ===
try {
    $productos = $collection->find(['productor_id' => (int)$productor_id]);
} catch (Exception $e) {
    die("Error al obtener los productos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Productor - Mis Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .stats-card {
            border-left: 4px solid #198754;
        }
        .stock-input {
            width: 80px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-success">🌿 Panel del Productor</h1>
        <p class="text-muted mb-0">Bienvenido, <?= htmlspecialchars($nombre_productor) ?></p>
        <div>
            <a href="cargar_producto.php" class="btn btn-success">➕ Cargar Nuevo Producto</a>
            <a href="ver_pedidos.php" class="btn btn-outline-primary">📦 Ver mis pedidos</a>
            <a href="logout.php" class="btn btn-outline-danger">Cerrar sesión</a>
        </div>
    </div>

    <!-- Gráficos de Estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-3">📊 Ventas por Mes (Último Año)</h5>
                    <div class="chart-container">
                        <canvas id="ventasPorMesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-3">🏆 Productos Más Vendidos</h5>
                    <div class="chart-container">
                        <canvas id="productosMasVendidosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title mb-4">Mis Productos Publicados</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-success">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Unidad</th>
                            <th>Punto de Venta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tieneProductos = false;
                        foreach ($productos as $p):
                            $tieneProductos = true;
                        ?>
                        <tr id="row-<?php echo $p->_id; ?>">
                            <td><?= htmlspecialchars($p->nombre ?? '—') ?></td>
                            <td><?= htmlspecialchars($p->descripcion ?? '—') ?></td>
                            <td><?= htmlspecialchars($p->categoria ?? '—') ?></td>
                            <td>$<?= number_format($p->precio ?? 0, 2) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <input 
                                        type="number" 
                                        class="form-control form-control-sm stock-input" 
                                        id="stock-<?= $p->_id ?>" 
                                        value="<?= htmlspecialchars($p->stock_disponible ?? '0') ?>"
                                        min="0"
                                    >
                                    <button 
                                        class="btn btn-sm btn-outline-primary" 
                                        onclick="actualizarStock('<?= $p->_id ?>')"
                                        title="Actualizar stock"
                                    >
                                        💾
                                    </button>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($p->unidad ?? '—') ?></td>
                            <td><?= htmlspecialchars($p->punto_venta ?? '—') ?></td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="eliminarProducto('<?= $p->_id ?>')">
                                    🗑️ Eliminar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (!$tieneProductos): ?>
                        <tr>
                            <td colspan="8" class="text-muted py-4 text-center">Aún no has publicado productos.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let ventasChart = null;
let productosChart = null;

// Cargar datos de ventas al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarDatosVentas();
});

// === Función para cargar datos de ventas ===
function cargarDatosVentas() {
    fetch('dashboard_productor.php?get_ventas_data=1')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error('Error al cargar ventas:', data.error);
                return;
            }
            crearGraficoVentasPorMes(data.ventasPorMes);
            crearGraficoProductosMasVendidos(data.productosMasVendidos);
        })
        .catch(err => {
            console.error('Error al obtener datos:', err);
        });
}

// === Crear gráfico de ventas por mes ===
function crearGraficoVentasPorMes(datos) {
    const ctx = document.getElementById('ventasPorMesChart');
    
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const labels = datos.map(d => `${meses[d.mes - 1]} ${d.anio}`);
    const montos = datos.map(d => d.total);
    
    if (ventasChart) ventasChart.destroy();
    
    ventasChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Monto Total ($)',
                data: montos,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toLocaleString('es-AR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-AR');
                        }
                    }
                }
            }
        }
    });
}

// === Crear gráfico de productos más vendidos ===
function crearGraficoProductosMasVendidos(datos) {
    const ctx = document.getElementById('productosMasVendidosChart');
    
    const labels = datos.map(d => d.nombre);
    const cantidades = datos.map(d => d.cantidad);
    
    const colores = [
        '#198754', '#20c997', '#0dcaf0', '#0d6efd', 
        '#28a745'
    ];
    
    if (productosChart) productosChart.destroy();
    
    productosChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Unidades Vendidas',
                data: cantidades,
                backgroundColor: colores.slice(0, datos.length),
                borderColor: colores.slice(0, datos.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// === Función para actualizar stock ===
function actualizarStock(id) {
    const nuevoStock = document.getElementById('stock-' + id).value;
    
    if (nuevoStock < 0) {
        Swal.fire('Error', 'El stock no puede ser negativo.', 'error');
        return;
    }
    
    fetch('dashboard_productor.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'update_stock=1&producto_id=' + encodeURIComponent(id) + '&nuevo_stock=' + nuevoStock
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Stock actualizado',
                text: 'El stock se actualizó correctamente.',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Error', data.error || 'No se pudo actualizar el stock.', 'error');
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Hubo un problema al actualizar el stock.', 'error');
    });
}

// === Función para eliminar producto ===
function eliminarProducto(id) {
    Swal.fire({
        title: '¿Eliminar producto?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('dashboard_productor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'delete_id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('row-' + id).remove();
                    Swal.fire('Eliminado', 'El producto fue eliminado correctamente.', 'success');
                    // Recargar gráficos
                    cargarDatosVentas();
                } else {
                    Swal.fire('Error', data.error || 'No se pudo eliminar el producto.', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Hubo un problema al eliminar el producto.', 'error');
            });
        }
    });
}
</script>

</body>
</html>