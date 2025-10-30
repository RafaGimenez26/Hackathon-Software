<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['ProductorID'])) {
    header('Location: misproductos.php');
    exit;
}

$productor_id = $_SESSION['ProductorID'];
$nombre_productor = $_SESSION['nombre_productor'];

// Obtener datos del productor
$stmt = $conexion->prepare("SELECT * FROM productores WHERE ProductorID = ?");
$stmt->bind_param("i", $productor_id);
$stmt->execute();
$productor = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fechas por defecto: primer día del mes actual hasta hoy
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

// Filtro por tipo de movimiento
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';

// Convertir a timestamps para MongoDB
$timestamp_desde = strtotime($fecha_desde . ' 00:00:00') * 1000;
$timestamp_hasta = strtotime($fecha_hasta . ' 23:59:59') * 1000;

$fecha_desde_mongo = new MongoDB\BSON\UTCDateTime($timestamp_desde);
$fecha_hasta_mongo = new MongoDB\BSON\UTCDateTime($timestamp_hasta);

// === COLECCIONES ===
$ventasCollection = $database->productos_vendidos;
$gastosCollection = $database->gastos;
$perdidasCollection = $database->perdidas;
$transaccionesCollection = $database->transacciones;

// === 1. SALDO ANTERIOR ===
// Ventas anteriores
$ventas_anteriores = $ventasCollection->aggregate([
    ['$match' => [
        'vendedor.productor_id' => (int)$productor_id,
        'fecha_venta' => ['$lt' => $fecha_desde_mongo]
    ]],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$monto_total']]]
])->toArray();

// Gastos anteriores
$gastos_anteriores = $gastosCollection->aggregate([
    ['$match' => [
        'productor_id' => (int)$productor_id,
        'fecha_gasto' => ['$lt' => $fecha_desde_mongo]
    ]],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$monto']]]
])->toArray();

// Transacciones anteriores (ALTAS de stock - inversión)
$transacciones_anteriores = $transaccionesCollection->aggregate([
    ['$match' => [
        'productor_id' => (int)$productor_id,
        'tipo_operacion' => 'alta',
        'fecha_transaccion' => ['$lt' => $fecha_desde_mongo]
    ]],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$total_costo']]]
])->toArray();

// Pérdidas anteriores (egreso)
$perdidas_anteriores = $perdidasCollection->aggregate([
    ['$match' => [
        'productor_id' => (int)$productor_id,
        'fecha_perdida' => ['$lt' => $fecha_desde_mongo]
    ]],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$costo_total_perdida']]]
])->toArray();

$total_ventas_anteriores = !empty($ventas_anteriores) ? $ventas_anteriores[0]['total'] : 0;
$total_gastos_anteriores = !empty($gastos_anteriores) ? $gastos_anteriores[0]['total'] : 0;
$total_transacciones_anteriores = !empty($transacciones_anteriores) ? $transacciones_anteriores[0]['total'] : 0;
$total_perdidas_anteriores = !empty($perdidas_anteriores) ? $perdidas_anteriores[0]['total'] : 0;

$saldo_anterior = $total_ventas_anteriores - $total_gastos_anteriores - $total_transacciones_anteriores - $total_perdidas_anteriores;

// === 2. MOVIMIENTOS DEL PERÍODO ===
// Ventas del período
$ventas_periodo = $ventasCollection->find([
    'vendedor.productor_id' => (int)$productor_id,
    'fecha_venta' => ['$gte' => $fecha_desde_mongo, '$lte' => $fecha_hasta_mongo]
], ['sort' => ['fecha_venta' => 1]])->toArray();

// Gastos del período
$gastos_periodo = $gastosCollection->find([
    'productor_id' => (int)$productor_id,
    'fecha_gasto' => ['$gte' => $fecha_desde_mongo, '$lte' => $fecha_hasta_mongo]
], ['sort' => ['fecha_gasto' => 1]])->toArray();

// Transacciones del período (altas de stock)
$transacciones_periodo = $transaccionesCollection->find([
    'productor_id' => (int)$productor_id,
    'tipo_operacion' => 'alta',
    'fecha_transaccion' => ['$gte' => $fecha_desde_mongo, '$lte' => $fecha_hasta_mongo]
], ['sort' => ['fecha_transaccion' => 1]])->toArray();

// Pérdidas del período
$perdidas_periodo = $perdidasCollection->find([
    'productor_id' => (int)$productor_id,
    'fecha_perdida' => ['$gte' => $fecha_desde_mongo, '$lte' => $fecha_hasta_mongo]
], ['sort' => ['fecha_perdida' => 1]])->toArray();

// === 3. TOTALES DEL PERÍODO ===
$total_ventas_periodo = 0;
foreach ($ventas_periodo as $venta) {
    $total_ventas_periodo += $venta['monto_total'];
}

$total_gastos_periodo = 0;
foreach ($gastos_periodo as $gasto) {
    $total_gastos_periodo += $gasto['monto'];
}

$total_transacciones_periodo = 0;
foreach ($transacciones_periodo as $trans) {
    $total_transacciones_periodo += $trans['total_costo'];
}

$total_perdidas_periodo = 0;
foreach ($perdidas_periodo as $perdida) {
    $total_perdidas_periodo += $perdida['costo_total_perdida'];
}

$saldo_periodo = $total_ventas_periodo - $total_gastos_periodo - $total_transacciones_periodo - $total_perdidas_periodo;
$saldo_final = $saldo_anterior + $saldo_periodo;

// === 4. UNIFICAR MOVIMIENTOS PARA LIBRO DIARIO ===
$movimientos = [];

// Ventas (INGRESO)
foreach ($ventas_periodo as $venta) {
    $movimientos[] = [
        'fecha' => $venta['fecha_venta']->toDateTime(),
        'tipo' => 'ingreso',
        'concepto' => 'Venta: ' . ($venta['producto']['nombre'] ?? 'Producto'),
        'comprobante' => 'Pedido #' . substr((string)$venta['_id'], -8),
        'cliente' => $venta['cliente']['nombre'] ?? 'Cliente',
        'debe' => $venta['monto_total'],
        'haber' => 0,
        'saldo' => 0,
        'categoria' => 'venta'
    ];
}

// Gastos (EGRESO)
foreach ($gastos_periodo as $gasto) {
    $movimientos[] = [
        'fecha' => $gasto['fecha_gasto']->toDateTime(),
        'tipo' => 'egreso',
        'concepto' => $gasto['concepto'],
        'comprobante' => $gasto['numero_comprobante'] ?? 'S/C',
        'cliente' => $gasto['proveedor'] ?? '-',
        'debe' => 0,
        'haber' => $gasto['monto'],
        'saldo' => 0,
        'categoria' => 'gasto'
    ];
}

// Transacciones - Altas de Stock (EGRESO - inversión)
foreach ($transacciones_periodo as $trans) {
    $detalle_items = [];
    foreach ($trans['items'] as $item) {
        $detalle_items[] = $item['nombre'] . ' (' . $item['cantidad'] . ' ' . $item['unidad'] . ')';
    }
    
    $movimientos[] = [
        'fecha' => $trans['fecha_transaccion']->toDateTime(),
        'tipo' => 'egreso',
        'concepto' => 'Compra de stock: ' . implode(', ', $detalle_items),
        'comprobante' => $trans['numero_factura'] ?? $trans['transaction_id'],
        'cliente' => 'Proveedor',
        'debe' => 0,
        'haber' => $trans['total_costo'],
        'saldo' => 0,
        'categoria' => 'compra_stock'
    ];
}

// Pérdidas (EGRESO)
foreach ($perdidas_periodo as $perdida) {
    $motivos_map = [
        'vencimiento' => '⏰ Vencimiento',
        'daño' => '💔 Daño/Rotura',
        'robo' => '🚨 Robo',
        'clima' => '🌧️ Clima adverso',
        'otro' => '📋 Otro motivo'
    ];
    
    $motivo_texto = $motivos_map[$perdida['motivo']] ?? $perdida['motivo'];
    
    $movimientos[] = [
        'fecha' => $perdida['fecha_perdida']->toDateTime(),
        'tipo' => 'egreso',
        'concepto' => 'Pérdida: ' . $perdida['producto']['nombre'] . ' - ' . $motivo_texto,
        'comprobante' => $perdida['numero_factura'] ?? 'PÉRDIDA',
        'cliente' => $perdida['descripcion'] ? substr($perdida['descripcion'], 0, 30) . '...' : 'Merma',
        'debe' => 0,
        'haber' => $perdida['costo_total_perdida'],
        'saldo' => 0,
        'categoria' => 'perdida'
    ];
}

// Ordenar por fecha
usort($movimientos, function($a, $b) {
    return $a['fecha'] <=> $b['fecha'];
});

// APLICAR FILTRO POR TIPO
if ($filtro_tipo !== 'todos') {
    $movimientos = array_filter($movimientos, function($mov) use ($filtro_tipo) {
        return $mov['categoria'] === $filtro_tipo;
    });
    $movimientos = array_values($movimientos); // Reindexar
}

// Calcular saldo acumulado
$saldo_acumulado = $saldo_anterior;
foreach ($movimientos as &$mov) {
    $saldo_acumulado += $mov['debe'] - $mov['haber'];
    $mov['saldo'] = $saldo_acumulado;
}
unset($mov);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Libro de Caja - AgroHub Misiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .header-caja {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        .card-saldo {
            border-left: 5px solid;
            transition: all 0.3s ease;
        }
        .card-saldo:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .card-saldo.positivo { border-color: #28a745; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); }
        .card-saldo.negativo { border-color: #dc3545; background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); }
        .card-saldo.neutro { border-color: #6c757d; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); }
        .card-saldo.warning { border-color: #ffc107; background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%); }
        .table-movimientos { font-size: 0.9rem; }
        .row-ingreso { background-color: #e8f5e9; }
        .row-egreso { background-color: #ffebee; }
        .row-compra { background-color: #fff3e0; }
        .row-perdida { background-color: #fce4ec; }
        .total-row {
            background-color: #f8f9fa;
            font-weight: 700;
            border-top: 3px solid #495057;
        }
        .fecha-badge {
            background: white;
            color: #495057;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-categoria {
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        @media print {
            .no-print { display: none !important; }
            .card { page-break-inside: avoid; }
            body { background: white !important; }
        }
    </style>
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="header-caja no-print">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 mb-1">📊 Libro de Caja Completo</h1>
                <p class="mb-0">Productor: <strong><?= htmlspecialchars($nombre_productor) ?></strong></p>
            </div>
            <div class="col-md-6 text-end">
                <a href="dashboard_productor.php" class="btn btn-light me-2">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <a href="registrar_gasto.php" class="btn btn-warning me-2">
                    <i class="bi bi-receipt"></i> Gasto
                </a>
                <button onclick="window.print()" class="btn btn-success">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros de Fecha -->
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-body">
            <form method="GET" action="libro_caja.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-calendar-event"></i> Fecha Desde
                    </label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?= $fecha_desde ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-calendar-check"></i> Fecha Hasta
                    </label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?= $fecha_hasta ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-funnel"></i> Tipo de Movimiento
                    </label>
                    <select name="tipo" class="form-select">
                        <option value="todos" <?= $filtro_tipo === 'todos' ? 'selected' : '' ?>>📊 Todos los movimientos</option>
                        <option value="venta" <?= $filtro_tipo === 'venta' ? 'selected' : '' ?>>🟢 Solo Ventas</option>
                        <option value="gasto" <?= $filtro_tipo === 'gasto' ? 'selected' : '' ?>>🔴 Solo Gastos</option>
                        <option value="compra_stock" <?= $filtro_tipo === 'compra_stock' ? 'selected' : '' ?>>🟡 Solo Compras de Stock</option>
                        <option value="perdida" <?= $filtro_tipo === 'perdida' ? 'selected' : '' ?>>❌ Solo Pérdidas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <?php if ($filtro_tipo !== 'todos' || $fecha_desde !== date('Y-m-01') || $fecha_hasta !== date('Y-m-d')): ?>
                        <a href="libro_caja.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-x-circle"></i> Limpiar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen de Saldos -->
    <div class="row mb-4">
        <?php if ($filtro_tipo === 'todos' || $filtro_tipo === 'venta'): ?>
        <div class="col-lg-2 col-md-4 mb-3">
            <div class="card card-saldo <?= $saldo_anterior >= 0 ? 'positivo' : 'negativo' ?> shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-clock-history"></i> Saldo Anterior
                    </h6>
                    <h4 class="mb-0 <?= $saldo_anterior >= 0 ? 'text-success' : 'text-danger' ?>">
                        $<?= number_format($saldo_anterior, 2, ',', '.') ?>
                    </h4>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($filtro_tipo === 'todos' || $filtro_tipo === 'venta'): ?>
        <div class="col-lg-2 col-md-4 mb-3">
            <div class="card card-saldo positivo shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-arrow-up-circle"></i> Ventas
                    </h6>
                    <h4 class="mb-0 text-success">
                        $<?= number_format($total_ventas_periodo, 2, ',', '.') ?>
                    </h4>
                    <small class="text-muted"><?= count($ventas_periodo) ?> ventas</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($filtro_tipo === 'todos' || $filtro_tipo === 'gasto'): ?>
        <div class="col-lg-2 col-md-4 mb-3">
            <div class="card card-saldo negativo shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-receipt"></i> Gastos
                    </h6>
                    <h4 class="mb-0 text-danger">
                        $<?= number_format($total_gastos_periodo, 2, ',', '.') ?>
                    </h4>
                    <small class="text-muted"><?= count($gastos_periodo) ?> gastos</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($filtro_tipo === 'todos' || $filtro_tipo === 'compra_stock'): ?>
        <div class="col-lg-2 col-md-4 mb-3">
            <div class="card card-saldo warning shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-box-seam"></i> Compras Stock
                    </h6>
                    <h4 class="mb-0 text-warning">
                        $<?= number_format($total_transacciones_periodo, 2, ',', '.') ?>
                    </h4>
                    <small class="text-muted"><?= count($transacciones_periodo) ?> compras</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($filtro_tipo === 'todos' || $filtro_tipo === 'perdida'): ?>
        <div class="col-lg-2 col-md-4 mb-3">
            <div class="card card-saldo negativo shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-exclamation-triangle"></i> Pérdidas
                    </h6>
                    <h4 class="mb-0 text-danger">
                        $<?= number_format($total_perdidas_periodo, 2, ',', '.') ?>
                    </h4>
                    <small class="text-muted"><?= count($perdidas_periodo) ?> pérdidas</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($filtro_tipo === 'todos'): ?>
        <div class="col-lg-2 col-md-4 mb-3">
            <div class="card card-saldo <?= $saldo_final >= 0 ? 'positivo' : 'negativo' ?> shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-wallet2"></i> Saldo Final
                    </h6>
                    <h4 class="mb-0 <?= $saldo_final >= 0 ? 'text-success' : 'text-danger' ?>">
                        $<?= number_format($saldo_final, 2, ',', '.') ?>
                    </h4>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Gráfico de Movimientos
    <div class="row mb-4 no-print">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-bar-chart"></i> Comparación del Período</h6>
                    <div style="height: 250px; position: relative;">
                        <canvas id="chartComparacion"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-graph-up"></i> Evolución del Saldo</h6>
                    <div style="height: 250px; position: relative;">
                        <canvas id="chartEvolucion"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Libro Diario -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-journal-text"></i> Libro Diario
                    <span class="fecha-badge ms-3">
                        <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
                    </span>
                </h5>
                <?php if ($filtro_tipo !== 'todos'): ?>
                    <span class="badge bg-warning text-dark">
                        Filtrando: <?php 
                            $tipos = [
                                'venta' => '🟢 Ventas',
                                'gasto' => '🔴 Gastos',
                                'compra_stock' => '🟡 Compras de Stock',
                                'perdida' => '❌ Pérdidas'
                            ];
                            echo $tipos[$filtro_tipo];
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-movimientos mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="90">Fecha</th>
                            <th width="80">Tipo</th>
                            <th>Concepto</th>
                            <th width="130">Comprobante</th>
                            <th width="150">Referencia</th>
                            <th width="110" class="text-end">DEBE</th>
                            <th width="110" class="text-end">HABER</th>
                            <th width="110" class="text-end">SALDO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Saldo Anterior -->
                        <tr class="table-secondary">
                            <td colspan="5"><strong>SALDO ANTERIOR</strong></td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                            <td class="text-end">
                                <strong class="<?= $saldo_anterior >= 0 ? 'text-success' : 'text-danger' ?>">
                                    $<?= number_format($saldo_anterior, 2, ',', '.') ?>
                                </strong>
                            </td>
                        </tr>

                        <!-- Movimientos -->
                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2">No hay movimientos en este período</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <?php
                                $row_class = '';
                                $badge_class = '';
                                $badge_text = '';
                                
                                switch ($mov['categoria']) {
                                    case 'venta':
                                        $row_class = 'row-ingreso';
                                        $badge_class = 'bg-success';
                                        $badge_text = 'VENTA';
                                        break;
                                    case 'gasto':
                                        $row_class = 'row-egreso';
                                        $badge_class = 'bg-danger';
                                        $badge_text = 'GASTO';
                                        break;
                                    case 'compra_stock':
                                        $row_class = 'row-compra';
                                        $badge_class = 'bg-warning text-dark';
                                        $badge_text = 'COMPRA';
                                        break;
                                    case 'perdida':
                                        $row_class = 'row-perdida';
                                        $badge_class = 'bg-danger';
                                        $badge_text = 'PÉRDIDA';
                                        break;
                                }
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td><?= $mov['fecha']->format('d/m/Y') ?></td>
                                    <td>
                                        <span class="badge badge-categoria <?= $badge_class ?>">
                                            <?= $badge_text ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bi bi-<?= $mov['tipo'] === 'ingreso' ? 'arrow-up-circle text-success' : 'arrow-down-circle text-danger' ?>"></i>
                                        <?= htmlspecialchars($mov['concepto']) ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($mov['comprobante']) ?></code></td>
                                    <td><?= htmlspecialchars($mov['cliente']) ?></td>
                                    <td class="text-end <?= $mov['debe'] > 0 ? 'text-success fw-bold' : '' ?>">
                                        <?= $mov['debe'] > 0 ? '$' . number_format($mov['debe'], 2, ',', '.') : '-' ?>
                                    </td>
                                    <td class="text-end <?= $mov['haber'] > 0 ? 'text-danger fw-bold' : '' ?>">
                                        <?= $mov['haber'] > 0 ? '$' . number_format($mov['haber'], 2, ',', '.') : '-' ?>
                                    </td>
                                    <td class="text-end">
                                        <strong class="<?= $mov['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            $<?= number_format($mov['saldo'], 2, ',', '.') ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Totales -->
                        <tr class="total-row">
                            <td colspan="5" class="text-end">TOTALES DEL PERÍODO:</td>
                            <td class="text-end text-success">
                                $<?= number_format($total_ventas_periodo, 2, ',', '.') ?>
                            </td>
                            <td class="text-end text-danger">
                                $<?= number_format($total_gastos_periodo + $total_transacciones_periodo + $total_perdidas_periodo, 2, ',', '.') ?>
                            </td>
                            <td class="text-end">
                                <strong class="<?= $saldo_periodo >= 0 ? 'text-success' : 'text-danger' ?>">
                                    $<?= number_format($saldo_periodo, 2, ',', '.') ?>
                                </strong>
                            </td>
                        </tr>
                        <tr class="table-dark">
                            <td colspan="7" class="text-end"><strong>SALDO FINAL:</strong></td>
                            <td class="text-end">
                                <strong class="fs-5 <?= $saldo_final >= 0 ? 'text-success' : 'text-danger' ?>">
                                    $<?= number_format($saldo_final, 2, ',', '.') ?>
                                </strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Información Adicional -->
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="bi bi-info-circle"></i> Información del Productor</h6>
                    <p class="mb-1"><strong>Razón Social:</strong> <?= htmlspecialchars($productor['NombreRazonSocial']) ?></p>
                    <?php if (!empty($productor['CUIT_CUIL'])): ?>
                        <p class="mb-1"><strong>CUIT/CUIL:</strong> <?= htmlspecialchars($productor['CUIT_CUIL']) ?></p>
                    <?php endif; ?>
                    <p class="mb-0"><strong>Fecha de emisión:</strong> <?= date('d/m/Y H:i') ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="bi bi-calculator"></i> Estadísticas del Período</h6>
                    <p class="mb-1">
                        <strong>Transacciones totales:</strong> <?= count($movimientos) ?>
                    </p>
                    <p class="mb-1">
                        <strong>Ingresos vs Egresos:</strong>
                        <?php 
                        $total_egresos = $total_gastos_periodo + $total_transacciones_periodo + $total_perdidas_periodo;
                        $porcentaje_ganancia = $total_ventas_periodo > 0 ? (($total_ventas_periodo - $total_egresos) / $total_ventas_periodo * 100) : 0;
                        ?>
                        <?= number_format($porcentaje_ganancia, 1) ?>% de margen
                    </p>
                    <p class="mb-0">
                        <strong>Pérdidas representan:</strong>
                        <?= $total_ventas_periodo > 0 ? number_format(($total_perdidas_periodo / $total_ventas_periodo * 100), 1) : 0 ?>% de las ventas
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gráfico de Comparación
const ctxComp = document.getElementById('chartComparacion').getContext('2d');
new Chart(ctxComp, {
    type: 'bar',
    data: {
        labels: ['Ventas', 'Gastos', 'Compras Stock', 'Pérdidas', 'Resultado'],
        datasets: [{
            label: 'Montos ($)',
            data: [
                <?= $total_ventas_periodo ?>,
                <?= $total_gastos_periodo ?>,
                <?= $total_transacciones_periodo ?>,
                <?= $total_perdidas_periodo ?>,
                <?= $saldo_periodo ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.7)',
                'rgba(220, 53, 69, 0.7)',
                'rgba(255, 193, 7, 0.7)',
                'rgba(220, 53, 69, 0.9)',
                <?= $saldo_periodo >= 0 ? "'rgba(40, 167, 69, 0.7)'" : "'rgba(220, 53, 69, 0.7)'" ?>
            ],
            borderColor: [
                'rgba(40, 167, 69, 1)',
                'rgba(220, 53, 69, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(220, 53, 69, 1)',
                <?= $saldo_periodo >= 0 ? "'rgba(40, 167, 69, 1)'" : "'rgba(220, 53, 69, 1)'" ?>
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return ' + context.parsed.y.toLocaleString('es-AR', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return ' + value.toLocaleString('es-AR');
                    }
                }
            }
        }
    }
});

// Gráfico de Evolución del Saldo
<?php 
$saldos_evolucion = [];
$labels_evolucion = [];
$saldo_temp = $saldo_anterior;
$labels_evolucion[] = 'Inicio';
$saldos_evolucion[] = $saldo_temp;

foreach ($movimientos as $mov) {
    $saldo_temp = $mov['saldo'];
    $labels_evolucion[] = $mov['fecha']->format('d/m');
    $saldos_evolucion[] = $saldo_temp;
}
?>

const ctxEvol = document.getElementById('chartEvolucion').getContext('2d');
new Chart(ctxEvol, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels_evolucion) ?>,
        datasets: [{
            label: 'Saldo ($)',
            data: <?= json_encode($saldos_evolucion) ?>,
            borderColor: 'rgba(102, 126, 234, 1)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: 'rgba(102, 126, 234, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Saldo:  + context.parsed.y.toLocaleString('es-AR', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return ' + value.toLocaleString('es-AR');
                    }
                }
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>