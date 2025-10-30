<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['ProductorID'])) {
    header('Location: misproductos.php');
    exit;
}

$productor_id = $_SESSION['ProductorID'];
$nombre_productor = $_SESSION['nombre_productor'];

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $concepto = trim($_POST['concepto']);
        $categoria = $_POST['categoria'];
        $monto = (float)$_POST['monto'];
        $fecha_gasto = $_POST['fecha_gasto'];
        $numero_comprobante = trim($_POST['numero_comprobante']);
        $proveedor = trim($_POST['proveedor']);
        $metodo_pago = $_POST['metodo_pago'];
        $descripcion = trim($_POST['descripcion']);
        
        // Validaciones
        if (empty($concepto) || empty($categoria) || $monto <= 0 || empty($fecha_gasto)) {
            throw new Exception('Todos los campos obligatorios deben estar completos');
        }
        
        // Convertir fecha a MongoDB UTCDateTime
        $timestamp = strtotime($fecha_gasto . ' ' . date('H:i:s')) * 1000;
        $fecha_mongo = new MongoDB\BSON\UTCDateTime($timestamp);
        
        // Insertar en MongoDB
        $gastosCollection = $database->gastos;
        
        $gasto = [
            'productor_id' => (int)$productor_id,
            'productor_nombre' => $nombre_productor,
            'concepto' => $concepto,
            'categoria' => $categoria,
            'monto' => $monto,
            'fecha_gasto' => $fecha_mongo,
            'numero_comprobante' => $numero_comprobante,
            'proveedor' => $proveedor,
            'metodo_pago' => $metodo_pago,
            'descripcion' => $descripcion,
            'fecha_registro' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $resultado = $gastosCollection->insertOne($gasto);
        
        if ($resultado->getInsertedCount() > 0) {
            $mensaje = '✅ Gasto registrado correctamente';
            $tipo_mensaje = 'success';
            
            // Limpiar formulario
            $_POST = [];
        } else {
            throw new Exception('No se pudo registrar el gasto');
        }
        
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener últimos 10 gastos
$gastosCollection = $database->gastos;
$ultimos_gastos = $gastosCollection->find([
    'productor_id' => (int)$productor_id
], [
    'sort' => ['fecha_gasto' => -1],
    'limit' => 10
])->toArray();

// Categorías de gastos
$categorias_gastos = [
    'alquiler' => 'Alquiler del local/establecimiento',
    'servicios' => 'Servicios (luz, agua, gas)',
    'transporte' => 'Transporte y combustible',
    'insumos' => 'Insumos y materiales',
    'mantenimiento' => 'Mantenimiento y reparaciones',
    'salarios' => 'Salarios y jornales',
    'impuestos' => 'Impuestos y tasas',
    'marketing' => 'Marketing y publicidad',
    'empaque' => 'Materiales de empaque',
    'tecnologia' => 'Tecnología y sistemas',
    'seguros' => 'Seguros',
    'otros' => 'Otros gastos'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💰 Registrar Gasto - AgroHub Misiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .preview-gasto {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 5px solid #dc3545;
            padding: 20px;
            border-radius: 10px;
        }
        .categoria-badge {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }
        .gasto-item {
            transition: all 0.3s ease;
        }
        .gasto-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
    </style>
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-danger">💰 Registrar Gasto</h1>
            <p class="text-muted mb-0">Productor: <strong><?= htmlspecialchars($nombre_productor) ?></strong></p>
        </div>
        <div>
            <a href="libro_caja.php" class="btn btn-outline-primary">
                <i class="bi bi-journal-text"></i> Ver Libro de Caja
            </a>
            <a href="dashboard_productor.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulario -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Nuevo Gasto</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formGasto">
                        <div class="row g-3">
                            <!-- Fecha -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-calendar"></i> Fecha del Gasto *
                                </label>
                                <input type="date" name="fecha_gasto" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" 
                                       max="<?= date('Y-m-d') ?>"
                                       required>
                            </div>

                            <!-- Categoría -->
                            <div class="col-md-8">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-tag"></i> Categoría *
                                </label>
                                <select name="categoria" class="form-select" required id="selectCategoria">
                                    <option value="">-- Seleccione una categoría --</option>
                                    <?php foreach ($categorias_gastos as $key => $valor): ?>
                                        <option value="<?= $key ?>"><?= $valor ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Concepto -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-card-text"></i> Concepto del Gasto *
                                </label>
                                <input type="text" name="concepto" class="form-control" 
                                       placeholder="Ej: Pago de alquiler de enero, Compra de semillas, etc."
                                       required id="inputConcepto">
                            </div>

                            <!-- Monto -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-cash-coin"></i> Monto *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="monto" class="form-control" 
                                           step="0.01" min="0.01" 
                                           placeholder="0.00" 
                                           required id="inputMonto">
                                </div>
                            </div>

                            <!-- Método de Pago -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-credit-card"></i> Método de Pago *
                                </label>
                                <select name="metodo_pago" class="form-select" required id="selectMetodoPago">
                                    <option value="">-- Seleccione --</option>
                                    <option value="efectivo">💵 Efectivo</option>
                                    <option value="transferencia">🏦 Transferencia</option>
                                    <option value="debito">💳 Tarjeta de Débito</option>
                                    <option value="credito">💳 Tarjeta de Crédito</option>
                                    <option value="cheque">📝 Cheque</option>
                                    <option value="otro">➕ Otro</option>
                                </select>
                            </div>

                            <!-- Número de Comprobante -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-file-earmark-text"></i> Nº de Comprobante
                                </label>
                                <input type="text" name="numero_comprobante" class="form-control" 
                                       placeholder="Ej: 0001-00012345" id="inputComprobante">
                                <small class="text-muted">Opcional: Factura, boleta o recibo</small>
                            </div>

                            <!-- Proveedor -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-shop"></i> Proveedor
                                </label>
                                <input type="text" name="proveedor" class="form-control" 
                                       placeholder="Nombre del proveedor" id="inputProveedor">
                                <small class="text-muted">Opcional</small>
                            </div>

                            <!-- Descripción -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-chat-left-text"></i> Descripción Adicional
                                </label>
                                <textarea name="descripcion" class="form-control" rows="3"
                                          placeholder="Detalles adicionales sobre este gasto..." id="textareaDescripcion"></textarea>
                            </div>
                        </div>

                        <!-- Vista Previa -->
                        <div class="preview-gasto mt-4" id="vistaPrevia" style="display: none;">
                            <h6 class="mb-3">
                                <i class="bi bi-eye"></i> Vista Previa del Gasto
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Fecha:</strong> <span id="prevFecha">-</span></p>
                                    <p class="mb-1"><strong>Categoría:</strong> <span id="prevCategoria">-</span></p>
                                    <p class="mb-0"><strong>Concepto:</strong> <span id="prevConcepto">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Monto:</strong> <span id="prevMonto" class="text-danger fs-5">$0.00</span></p>
                                    <p class="mb-1"><strong>Método de Pago:</strong> <span id="prevMetodo">-</span></p>
                                    <p class="mb-0"><strong>Proveedor:</strong> <span id="prevProveedor">-</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-danger btn-lg flex-grow-1">
                                <i class="bi bi-save"></i> Registrar Gasto
                            </button>
                            <button type="reset" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Últimos Gastos -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history"></i> Últimos Gastos Registrados
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($ultimos_gastos)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2 small">Aún no hay gastos registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($ultimos_gastos as $gasto): ?>
                                <?php $fecha = $gasto['fecha_gasto']->toDateTime()->format('d/m/Y'); ?>
                                <div class="list-group-item gasto-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 small">
                                                <?= htmlspecialchars($gasto['concepto']) ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= $fecha ?>
                                                <?php if (!empty($gasto['proveedor'])): ?>
                                                    | <?= htmlspecialchars($gasto['proveedor']) ?>
                                                <?php endif; ?>
                                            </small>
                                            <div class="mt-1">
                                                <span class="badge bg-secondary categoria-badge">
                                                    <?= $categorias_gastos[$gasto['categoria']] ?? $gasto['categoria'] ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-end ms-3">
                                            <strong class="text-danger">
                                                $<?= number_format($gasto['monto'], 2, ',', '.') ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer text-center">
                            <a href="libro_caja.php" class="btn btn-sm btn-outline-primary">
                                Ver todos los gastos <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ayuda -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="bi bi-lightbulb text-warning"></i> Consejos
                    </h6>
                    <ul class="small mb-0 ps-3">
                        <li>Registra tus gastos en el momento para no olvidarlos</li>
                        <li>Guarda todos los comprobantes físicos</li>
                        <li>Categoriza correctamente para análisis posterior</li>
                        <li>Los gastos ayudan a calcular tu rentabilidad real</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Vista previa en tiempo real
const inputs = {
    fecha: document.querySelector('input[name="fecha_gasto"]'),
    categoria: document.getElementById('selectCategoria'),
    concepto: document.getElementById('inputConcepto'),
    monto: document.getElementById('inputMonto'),
    metodoPago: document.getElementById('selectMetodoPago'),
    proveedor: document.getElementById('inputProveedor')
};

const preview = {
    container: document.getElementById('vistaPrevia'),
    fecha: document.getElementById('prevFecha'),
    categoria: document.getElementById('prevCategoria'),
    concepto: document.getElementById('prevConcepto'),
    monto: document.getElementById('prevMonto'),
    metodo: document.getElementById('prevMetodo'),
    proveedor: document.getElementById('prevProveedor')
};

function actualizarVista() {
    const hayDatos = inputs.concepto.value || inputs.monto.value || inputs.categoria.value;
    
    if (hayDatos) {
        preview.container.style.display = 'block';
        
        // Fecha
        if (inputs.fecha.value) {
            const fecha = new Date(inputs.fecha.value + 'T00:00:00');
            preview.fecha.textContent = fecha.toLocaleDateString('es-AR');
        }
        
        // Categoría
        if (inputs.categoria.value) {
            const opcion = inputs.categoria.options[inputs.categoria.selectedIndex];
            preview.categoria.textContent = opcion.text;
        } else {
            preview.categoria.textContent = '-';
        }
        
        // Concepto
        preview.concepto.textContent = inputs.concepto.value || '-';
        
        // Monto
        if (inputs.monto.value) {
            const monto = parseFloat(inputs.monto.value);
            preview.monto.textContent = '$' + monto.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } else {
            preview.monto.textContent = '$0.00';
        }
        
        // Método de pago
        if (inputs.metodoPago.value) {
            const opcion = inputs.metodoPago.options[inputs.metodoPago.selectedIndex];
            preview.metodo.textContent = opcion.text;
        } else {
            preview.metodo.textContent = '-';
        }
        
        // Proveedor
        preview.proveedor.textContent = inputs.proveedor.value || 'Sin especificar';
    } else {
        preview.container.style.display = 'none';
    }
}

// Agregar listeners
Object.values(inputs).forEach(input => {
    input.addEventListener('input', actualizarVista);
    input.addEventListener('change', actualizarVista);
});

// Reset
document.querySelector('button[type="reset"]').addEventListener('click', () => {
    setTimeout(() => {
        preview.container.style.display = 'none';
    }, 100);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>