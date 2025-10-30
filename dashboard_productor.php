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
    $productos = $collection->find(['productor_id' => (int)$productor_id])->toArray();
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
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
        .search-box {
            position: relative;
        }
        .search-box .form-control {
            padding-right: 40px;
        }
        .search-box .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .highlight {
            background-color: #fff3cd;
            font-weight: bold;
        }
        .no-results {
            display: none;
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .result-count {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-success">🌿 Panel del Productor</h1>
        <p class="text-muted mb-0">Bienvenido, <?= htmlspecialchars($nombre_productor) ?></p>
        <div>
            <a href="libro_caja.php" class="btn btn-primary"><i class="bi bi-journal-text"></i> Libro de Caja</a>
            <a href="alta_baja_stock.php" class="btn btn-outline-secondary"><i class="bi bi-gear"></i> Alta/Baja de Stock</a>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="card-title mb-1">Mis Productos Publicados</h5>
                    <p class="result-count mb-0">
                        Mostrando <span id="count-visible"><?= count($productos) ?></span> de <span id="count-total"><?= count($productos) ?></span> productos
                    </p>
                </div>
                <div class="search-box" style="width: 350px;">
                    <input type="text" 
                           id="searchInput" 
                           class="form-control" 
                           placeholder="🔍 Buscar por nombre, categoría o punto de venta...">
                    <i class="bi bi-search search-icon"></i>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="productosTable">
                    <thead class="table-success">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Unidad</th>
                            <th>Punto de Venta</th>
                        </tr>
                    </thead>
                    <tbody id="productosTableBody">
                        <?php
                        $tieneProductos = false;
                        foreach ($productos as $p):
                            $tieneProductos = true;
                        ?>
                        <tr class="producto-row" 
                            data-nombre="<?= htmlspecialchars(strtolower($p->nombre ?? '')) ?>"
                            data-categoria="<?= htmlspecialchars(strtolower($p->categoria ?? '')) ?>"
                            data-punto="<?= htmlspecialchars(strtolower($p->punto_venta ?? '')) ?>"
                            data-descripcion="<?= htmlspecialchars(strtolower($p->descripcion ?? '')) ?>">
                            <td class="search-field"><?= htmlspecialchars($p->nombre ?? '—') ?></td>
                            <td class="search-field"><?= htmlspecialchars($p->descripcion ?? '—') ?></td>
                            <td class="search-field"><?= htmlspecialchars($p->categoria ?? '—') ?></td>
                            <td>$<?= number_format($p->precio ?? 0, 2) ?></td>
                            <td><?= htmlspecialchars($p->stock_disponible ?? '0') ?></td>
                            <td><?= htmlspecialchars($p->unidad ?? '—') ?></td>
                            <td class="search-field"><?= htmlspecialchars($p->punto_venta ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (!$tieneProductos): ?>
                        <tr id="emptyState">
                            <td colspan="7" class="text-muted py-4 text-center">Aún no has publicado productos.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mensaje cuando no hay resultados -->
            <div class="no-results" id="noResults">
                <i class="bi bi-search" style="font-size: 3rem; opacity: 0.3;"></i>
                <h5 class="mt-3">No se encontraron productos</h5>
                <p>Intenta con otros términos de búsqueda</p>
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
    inicializarBuscador();
});

// === BUSCADOR DE PRODUCTOS ===
function inicializarBuscador() {
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('.producto-row');
    const noResults = document.getElementById('noResults');
    const tableBody = document.getElementById('productosTableBody');
    const countVisible = document.getElementById('count-visible');
    const countTotal = document.getElementById('count-total');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        rows.forEach(row => {
            const nombre = row.dataset.nombre;
            const categoria = row.dataset.categoria;
            const punto = row.dataset.punto;
            const descripcion = row.dataset.descripcion;
            
            // Buscar en todos los campos
            const match = nombre.includes(searchTerm) || 
                         categoria.includes(searchTerm) || 
                         punto.includes(searchTerm) ||
                         descripcion.includes(searchTerm);
            
            if (match || searchTerm === '') {
                row.style.display = '';
                visibleCount++;
                
                // Resaltar términos coincidentes
                if (searchTerm !== '') {
                    highlightText(row, searchTerm);
                } else {
                    removeHighlight(row);
                }
            } else {
                row.style.display = 'none';
                removeHighlight(row);
            }
        });
        
        // Actualizar contador
        countVisible.textContent = visibleCount;
        
        // Mostrar/ocultar mensaje de no resultados
        if (visibleCount === 0 && rows.length > 0) {
            noResults.style.display = 'block';
            tableBody.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            tableBody.style.display = '';
        }
    });
}

// Función para resaltar texto
function highlightText(row, searchTerm) {
    const cells = row.querySelectorAll('.search-field');
    cells.forEach(cell => {
        const text = cell.textContent;
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        const highlightedText = text.replace(regex, '<span class="highlight">$1</span>');
        cell.innerHTML = highlightedText;
    });
}

// Función para quitar resaltado
function removeHighlight(row) {
    const cells = row.querySelectorAll('.search-field');
    cells.forEach(cell => {
        cell.innerHTML = cell.textContent;
    });
}

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
</script>

</body>
</html>