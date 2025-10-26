<?php
session_start();
require 'conexion.php';

// Verificar autenticación
if (!isset($_SESSION['ProductorID'])) {
    header('Location: misproductos.php');
    exit;
}

$productor_id = $_SESSION['ProductorID'];
$nombre_productor = $_SESSION['nombre_productor'];

// Obtener productos del productor
$productosCollection = $database->Productos;
$productos = $productosCollection->find([
    'productor_id' => (int)$productor_id,
    'activo' => true
])->toArray();

// Catálogo de motivos de baja
$motivos_baja = [
    'vencimiento' => 'Vencimiento / Producto pasado',
    'daño' => 'Daño físico / Deterioro',
    'robo' => 'Pérdida por robo',
    'clima' => 'Daño por clima (helada, granizo, etc.)',
    'transporte' => 'Pérdida en el transporte',
    'plagas' => 'Ataque de plagas',
    'otro' => 'Otro motivo'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta/Baja de Stock - AgroHub Misiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .producto-row {
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .producto-row:hover {
            background-color: #f8f9fa;
        }
        .producto-row.selected {
            border-color: #198754;
            background-color: #e7f3e9;
        }
        .campos-alta, .campos-baja {
            display: none;
        }
        .campos-alta.show, .campos-baja.show {
            display: table-cell;
        }
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }
        .info-transaccion {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .tipo-operacion-btn {
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 10px;
        }
        .tipo-operacion-btn.active {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-success">📦 Alta/Baja de Stock</h1>
            <p class="text-muted mb-0">Productor: <strong><?= htmlspecialchars($nombre_productor) ?></strong></p>
        </div>
        <a href="dashboard_productor.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
    </div>

    <!-- Información de Transacción -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row info-transaccion">
                <div class="col-md-3">
                    <strong><i class="bi bi-hash"></i> ID Transacción:</strong>
                    <p class="mb-0" id="transaction-id">Se generará al confirmar</p>
                </div>
                <div class="col-md-3">
                    <strong><i class="bi bi-calendar"></i> Fecha y Hora:</strong>
                    <p class="mb-0" id="fecha-actual">Cargando...</p>
                </div>
                <div class="col-md-3">
                    <strong><i class="bi bi-receipt"></i> N° Factura/Remito:</strong>
                    <input type="text" class="form-control form-control-sm mt-1" id="numero-factura" 
                           placeholder="Ej: 0001-00012345" required>
                </div>
                <div class="col-md-3">
                    <strong><i class="bi bi-arrow-repeat"></i> Tipo de Operación:</strong>
                    <div class="btn-group w-100 mt-1" role="group">
                        <input type="radio" class="btn-check" name="tipo_operacion" id="tipo_alta" value="alta">
                        <label class="btn btn-outline-success btn-sm" for="tipo_alta">
                            <i class="bi bi-plus-circle"></i> ALTA
                        </label>
                        
                        <input type="radio" class="btn-check" name="tipo_operacion" id="tipo_baja" value="baja">
                        <label class="btn btn-outline-danger btn-sm" for="tipo_baja">
                            <i class="bi bi-dash-circle"></i> BAJA
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-box-seam"></i> Seleccionar Productos
                <span class="badge bg-light text-dark ms-2" id="productos-seleccionados">0 seleccionados</span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($productos)): ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-inbox"></i> No tienes productos publicados
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="tabla-productos">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                </th>
                                <th>Producto</th>
                                <th>Stock Actual</th>
                                <th>Unidad</th>
                                <th>Precio Venta</th>
                                <th>Costo Actual</th>
                                <th class="campos-alta">Nueva Cantidad</th>
                                <th class="campos-alta">Nuevo Costo</th>
                                <th class="campos-alta">Nuevo Precio</th>
                                <th class="campos-baja">Cantidad a Dar de Baja</th>
                                <th class="campos-baja">Motivo</th>
                                <th class="campos-baja">Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr class="producto-row" data-producto-id="<?= $producto['_id'] ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input producto-checkbox">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($producto['categoria']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $producto['stock_disponible'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($producto['unidad']) ?></td>
                                <td>$<?= number_format($producto['precio'], 2, ',', '.') ?></td>
                                <td>$<?= number_format($producto['costo_unitario'] ?? 0, 2, ',', '.') ?></td>
                                
                                <!-- Campos para ALTA -->
                                <td class="campos-alta">
                                    <input type="number" class="form-control form-control-sm cantidad-alta" 
                                           min="1" placeholder="Cant." disabled>
                                </td>
                                <td class="campos-alta">
                                    <input type="number" class="form-control form-control-sm nuevo-costo" 
                                           step="0.01" min="0" placeholder="Costo" 
                                           value="<?= $producto['costo_unitario'] ?? 0 ?>" disabled>
                                </td>
                                <td class="campos-alta">
                                    <input type="number" class="form-control form-control-sm nuevo-precio" 
                                           step="0.01" min="0" placeholder="Precio" 
                                           value="<?= $producto['precio'] ?>" disabled>
                                </td>
                                
                                <!-- Campos para BAJA -->
                                <td class="campos-baja">
                                    <input type="number" class="form-control form-control-sm cantidad-baja" 
                                           min="1" max="<?= $producto['stock_disponible'] ?>" 
                                           placeholder="Cant." disabled>
                                </td>
                                <td class="campos-baja">
                                    <select class="form-select form-select-sm motivo-baja" disabled>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($motivos_baja as $key => $value): ?>
                                            <option value="<?= $key ?>"><?= $value ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="campos-baja">
                                    <input type="text" class="form-control form-control-sm descripcion-baja" 
                                           placeholder="Detalle (opcional)" disabled>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Resumen de Totales -->
                <div class="total-section" id="resumen-totales" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="bi bi-calculator"></i> Resumen de la Operación</h5>
                            <p class="mb-1">Productos seleccionados: <strong id="total-productos">0</strong></p>
                            <p class="mb-1">Unidades totales: <strong id="total-unidades">0</strong></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div id="resumen-alta" style="display: none;">
                                <h5>Totales de Alta</h5>
                                <p class="mb-1">Inversión total (Costos): <strong id="total-costo-alta">$0</strong></p>
                                <p class="mb-0">Valor en venta: <strong id="total-venta-alta">$0</strong></p>
                            </div>
                            <div id="resumen-baja" style="display: none;">
                                <h5>Totales de Baja</h5>
                                <p class="mb-0 fs-4">Pérdida total: <strong id="total-perdida">$0</strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex gap-2 justify-content-end mt-3">
                    <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                    <button type="button" class="btn btn-success btn-lg" id="btn-confirmar" disabled>
                        <i class="bi bi-check-circle"></i> Confirmar Transacción
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Variables globales
let tipoOperacion = null;
let productosSeleccionados = new Set();

// Actualizar fecha y hora en tiempo real (Argentina UTC-3)
function actualizarFechaHora() {
    const now = new Date();
    const opciones = {
        timeZone: 'America/Argentina/Buenos_Aires',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };
    const fechaFormateada = now.toLocaleString('es-AR', opciones);
    document.getElementById('fecha-actual').textContent = fechaFormateada;
}

// Actualizar cada segundo
setInterval(actualizarFechaHora, 1000);
actualizarFechaHora();

// Manejar selección de tipo de operación
document.querySelectorAll('input[name="tipo_operacion"]').forEach(radio => {
    radio.addEventListener('change', function() {
        tipoOperacion = this.value;
        
        // Mostrar/ocultar columnas según el tipo
        const camposAlta = document.querySelectorAll('.campos-alta');
        const camposBaja = document.querySelectorAll('.campos-baja');
        
        if (tipoOperacion === 'alta') {
            camposAlta.forEach(el => el.classList.add('show'));
            camposBaja.forEach(el => el.classList.remove('show'));
            document.getElementById('resumen-alta').style.display = 'block';
            document.getElementById('resumen-baja').style.display = 'none';
        } else {
            camposBaja.forEach(el => el.classList.add('show'));
            camposAlta.forEach(el => el.classList.remove('show'));
            document.getElementById('resumen-baja').style.display = 'block';
            document.getElementById('resumen-alta').style.display = 'none';
        }
        
        // Limpiar selección
        productosSeleccionados.clear();
        document.querySelectorAll('.producto-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.producto-row').forEach(row => row.classList.remove('selected'));
        actualizarContadores();
    });
});

// Seleccionar todos
document.getElementById('select-all').addEventListener('change', function() {
    if (!tipoOperacion) {
        Swal.fire('Atención', 'Primero selecciona el tipo de operación (ALTA o BAJA)', 'warning');
        this.checked = false;
        return;
    }
    
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
        const row = cb.closest('tr');
        if (this.checked) {
            row.classList.add('selected');
            productosSeleccionados.add(row.dataset.productoId);
            habilitarCamposProducto(row, true);
        } else {
            row.classList.remove('selected');
            productosSeleccionados.delete(row.dataset.productoId);
            habilitarCamposProducto(row, false);
        }
    });
    actualizarContadores();
});

// Selección individual de productos
document.querySelectorAll('.producto-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (!tipoOperacion) {
            Swal.fire('Atención', 'Primero selecciona el tipo de operación (ALTA o BAJA)', 'warning');
            this.checked = false;
            return;
        }
        
        const row = this.closest('tr');
        const productoId = row.dataset.productoId;
        
        if (this.checked) {
            row.classList.add('selected');
            productosSeleccionados.add(productoId);
            habilitarCamposProducto(row, true);
        } else {
            row.classList.remove('selected');
            productosSeleccionados.delete(productoId);
            habilitarCamposProducto(row, false);
        }
        
        actualizarContadores();
    });
});

// Habilitar/deshabilitar campos según selección
function habilitarCamposProducto(row, habilitar) {
    if (tipoOperacion === 'alta') {
        row.querySelector('.cantidad-alta').disabled = !habilitar;
        row.querySelector('.nuevo-costo').disabled = !habilitar;
        row.querySelector('.nuevo-precio').disabled = !habilitar;
    } else {
        row.querySelector('.cantidad-baja').disabled = !habilitar;
        row.querySelector('.motivo-baja').disabled = !habilitar;
        row.querySelector('.descripcion-baja').disabled = !habilitar;
    }
}

// Calcular totales en tiempo real
document.querySelectorAll('.cantidad-alta, .nuevo-costo, .nuevo-precio, .cantidad-baja').forEach(input => {
    input.addEventListener('input', calcularTotales);
});

function calcularTotales() {
    let totalProductos = 0;
    let totalUnidades = 0;
    let totalCostoAlta = 0;
    let totalVentaAlta = 0;
    let totalPerdida = 0;
    
    document.querySelectorAll('.producto-row.selected').forEach(row => {
        totalProductos++;
        
        if (tipoOperacion === 'alta') {
            const cantidad = parseFloat(row.querySelector('.cantidad-alta').value) || 0;
            const costo = parseFloat(row.querySelector('.nuevo-costo').value) || 0;
            const precio = parseFloat(row.querySelector('.nuevo-precio').value) || 0;
            
            totalUnidades += cantidad;
            totalCostoAlta += cantidad * costo;
            totalVentaAlta += cantidad * precio;
        } else {
            const cantidad = parseFloat(row.querySelector('.cantidad-baja').value) || 0;
            const precioVenta = parseFloat(row.cells[4].textContent.replace('$', '').replace('.', '').replace(',', '.'));
            
            totalUnidades += cantidad;
            totalPerdida += cantidad * precioVenta;
        }
    });
    
    document.getElementById('total-productos').textContent = totalProductos;
    document.getElementById('total-unidades').textContent = totalUnidades;
    document.getElementById('total-costo-alta').textContent = '$' + totalCostoAlta.toLocaleString('es-AR', {minimumFractionDigits: 2});
    document.getElementById('total-venta-alta').textContent = '$' + totalVentaAlta.toLocaleString('es-AR', {minimumFractionDigits: 2});
    document.getElementById('total-perdida').textContent = '$' + totalPerdida.toLocaleString('es-AR', {minimumFractionDigits: 2});
}

function actualizarContadores() {
    const count = productosSeleccionados.size;
    document.getElementById('productos-seleccionados').textContent = count + ' seleccionados';
    document.getElementById('resumen-totales').style.display = count > 0 ? 'block' : 'none';
    document.getElementById('btn-confirmar').disabled = count === 0;
    calcularTotales();
}

function limpiarFormulario() {
    document.querySelectorAll('.producto-checkbox').forEach(cb => cb.checked = false);
    document.querySelectorAll('.producto-row').forEach(row => {
        row.classList.remove('selected');
        habilitarCamposProducto(row, false);
    });
    document.querySelectorAll('input[type="number"], input[type="text"], select').forEach(input => {
        if (!input.classList.contains('nuevo-costo') && !input.classList.contains('nuevo-precio')) {
            input.value = '';
        }
    });
    productosSeleccionados.clear();
    actualizarContadores();
}

// Confirmar transacción
document.getElementById('btn-confirmar').addEventListener('click', function() {
    const numeroFactura = document.getElementById('numero-factura').value.trim();
    
    if (!numeroFactura) {
        Swal.fire('Error', 'Debes ingresar un número de factura/remito', 'error');
        return;
    }
    
    // Validar datos según tipo de operación
    let datosValidos = true;
    let mensajeError = '';
    
    const productos = [];
    document.querySelectorAll('.producto-row.selected').forEach(row => {
        const productoId = row.dataset.productoId;
        const nombre = row.cells[1].querySelector('strong').textContent;
        const stockActual = parseInt(row.cells[2].querySelector('.badge').textContent);
        const unidad = row.cells[3].textContent;
        const precioVentaActual = parseFloat(row.cells[4].textContent.replace('$', '').replace('.', '').replace(',', '.'));
        const costoActual = parseFloat(row.cells[5].textContent.replace('$', '').replace('.', '').replace(',', '.'));
        
        if (tipoOperacion === 'alta') {
            const cantidad = parseInt(row.querySelector('.cantidad-alta').value);
            const nuevoCosto = parseFloat(row.querySelector('.nuevo-costo').value);
            const nuevoPrecio = parseFloat(row.querySelector('.nuevo-precio').value);
            
            if (!cantidad || cantidad <= 0) {
                datosValidos = false;
                mensajeError = `Debes ingresar una cantidad válida para "${nombre}"`;
                return;
            }
            
            if (!nuevoCosto || nuevoCosto < 0) {
                datosValidos = false;
                mensajeError = `Debes ingresar un costo válido para "${nombre}"`;
                return;
            }
            
            if (!nuevoPrecio || nuevoPrecio < 0) {
                datosValidos = false;
                mensajeError = `Debes ingresar un precio válido para "${nombre}"`;
                return;
            }
            
            productos.push({
                producto_id: productoId,
                nombre: nombre,
                cantidad: cantidad,
                unidad: unidad,
                nuevo_costo: nuevoCosto,
                nuevo_precio: nuevoPrecio,
                stock_actual: stockActual
            });
        } else {
            const cantidad = parseInt(row.querySelector('.cantidad-baja').value);
            const motivo = row.querySelector('.motivo-baja').value;
            const descripcion = row.querySelector('.descripcion-baja').value;
            
            if (!cantidad || cantidad <= 0) {
                datosValidos = false;
                mensajeError = `Debes ingresar una cantidad válida para "${nombre}"`;
                return;
            }
            
            if (cantidad > stockActual) {
                datosValidos = false;
                mensajeError = `La cantidad a dar de baja de "${nombre}" no puede ser mayor al stock actual (${stockActual})`;
                return;
            }
            
            if (!motivo) {
                datosValidos = false;
                mensajeError = `Debes seleccionar un motivo de baja para "${nombre}"`;
                return;
            }
            
            productos.push({
                producto_id: productoId,
                nombre: nombre,
                cantidad: cantidad,
                unidad: unidad,
                costo_unitario: costoActual,
                precio_venta: precioVentaActual,
                stock_actual: stockActual,
                motivo: motivo,
                descripcion: descripcion
            });
        }
    });
    
    if (!datosValidos) {
        Swal.fire('Error', mensajeError, 'error');
        return;
    }
    
    // Confirmar operación
    Swal.fire({
        title: '¿Confirmar transacción?',
        html: `Se procesarán <strong>${productos.length}</strong> producto(s) como <strong>${tipoOperacion.toUpperCase()}</strong>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754'
    }).then((result) => {
        if (result.isConfirmed) {
            procesarTransaccion(numeroFactura, productos);
        }
    });
});

function procesarTransaccion(numeroFactura, productos) {
    const btnConfirmar = document.getElementById('btn-confirmar');
    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
    
    fetch('procesar_alta_baja.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            tipo_operacion: tipoOperacion,
            numero_factura: numeroFactura,
            productos: productos
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Transacción completada',
                html: `ID: <strong>${data.transaction_id}</strong><br>
                       <a href="pdf/generar_pdf_transaccion.php?id=${data.transaction_id}" target="_blank" class="btn btn-sm btn-danger mt-2">
                           <i class="bi bi-file-pdf"></i> Descargar PDF
                       </a>`,
                showConfirmButton: true
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar Transacción';
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error de conexión: ' + err.message, 'error');
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar Transacción';
    });
}
</script>

</body>
</html>