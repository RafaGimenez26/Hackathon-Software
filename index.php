<?php 
// Incluir archivo de conexión (asumiendo que existe)
require_once 'conexion.php';

// Configuración de paginado
$productos_por_pagina = 11; // 11 + 1 de reciclaje = 12 cards por página
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$skip = ($pagina_actual - 1) * $productos_por_pagina;

// Filtros
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_zona = isset($_GET['zona']) ? $_GET['zona'] : '';
$filtro_dia = isset($_GET['dia']) ? $_GET['dia'] : '';
$filtro_precio = isset($_GET['precio']) ? (float)$_GET['precio'] : 5000;

// Construir query de MongoDB
$query = ['activo' => true];

if (!empty($filtro_categoria) && $filtro_categoria !== 'todos') {
    $query['categoria'] = $filtro_categoria;
}

if (!empty($filtro_zona) && $filtro_zona !== 'todas') {
    $query['zona'] = $filtro_zona;
}

if (!empty($filtro_dia) && $filtro_dia !== 'todos') {
    $query['dias_disponibles'] = $filtro_dia;
}

if ($filtro_precio > 0) {
    $query['precio'] = ['$lte' => $filtro_precio];
}

// Contar total de productos
$total_productos = $collection->countDocuments($query);
$total_paginas = ceil($total_productos / $productos_por_pagina);

// Obtener productos con paginado
$opciones = [
    'skip' => $skip,
    'limit' => $productos_por_pagina,
    'sort' => ['fecha_creacion' => -1]
];

$productos = $collection->find($query, $opciones)->toArray();

// Mapeo de categorías y zonas para los filtros
$categorias_map = [
    'verduras' => 'Verduras de hoja',
    'frutas' => 'Frutas de estación',
    'cereales' => 'Cereales y legumbres',
    'lacteos' => 'Productos lácteos',
    'carnes' => 'Carnes y embutidos',
    'miel' => 'Miel y derivados',
    'huevos' => 'Huevos'
];

$zonas_map = [
    'villa_sarita' => 'Villa Sarita',
    'villa_cabello' => 'Villa Cabello',
    'itaembe_mini' => 'Itaembe Mini',
    'santa_rita' => 'Santa Rita',
    'villa_urquiza' => 'Villa Urquiza',
    'centro' => 'Centro',
    'norte' => 'Norte',
    'sur' => 'Sur',
    'este' => 'Este',
    'oeste' => 'Oeste'
];

$dias_map = [
    'lunes' => 'Lunes',
    'martes' => 'Martes',
    'miercoles' => 'Miércoles',
    'jueves' => 'Jueves',
    'viernes' => 'Viernes',
    'sabado' => 'Sábado',
    'domingo' => 'Domingo'
];

// Función para formatear días disponibles
function formatearDias($dias) {
    // Convertir BSONArray a array PHP
    if ($dias instanceof MongoDB\Model\BSONArray) {
        $dias = iterator_to_array($dias);
    }
    
    // Si no es array, devolver string vacío
    if (!is_array($dias)) {
        return 'Consultar disponibilidad';
    }
    
    $dias_es = [
        'lunes' => 'Lun',
        'martes' => 'Mar',
        'miercoles' => 'Mié',
        'jueves' => 'Jue',
        'viernes' => 'Vie',
        'sabado' => 'Sáb',
        'domingo' => 'Dom'
    ];
    
    $dias_formateados = array_map(function($dia) use ($dias_es) {
        $dia_lower = strtolower(trim($dia));
        return $dias_es[$dia_lower] ?? ucfirst($dia);
    }, $dias);
    
    return implode(', ', $dias_formateados);
}

// Función para obtener emoji de categoría
function getCategoriaEmoji($categoria) {
    $emojis = [
        'verduras' => '🥬',
        'frutas' => '🍎',
        'cereales' => '🌾',
        'lacteos' => '🧀',
        'carnes' => '🥩',
        'miel' => '🍯',
        'huevos' => '🥚'
    ];
    return $emojis[$categoria] ?? '📦';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercado Agrícola Local - Ver Productos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="script.js" rel="script">
</head>
<body>
    <div class="container-custom">
        <div class="header">
            <h1>🌾 AgroHub Misiones</h1>
            <p>Conectando productores locales con la comunidad Misionera</p>
        </div>

        <div class="nav-tabs-custom">
            <a class="tab-btn active" href="index.php">🛒 Ver Productos</a>
            <a class="tab-btn" href="registro.php">👨‍🌾 Registrarse como Productor</a>
            <a class="tab-btn" href="misproductos.php">📦 Mis Productos</a>
            <a class="tab-btn" href="registro_usuario.php">🛍️ Mis Pedidos</a>
        </div>

        <!-- CONTENIDO DE PRODUCTOS -->
        <div>
            <h2 class="tab-title">Productos Frescos Disponibles</h2>

            <!-- FORMULARIO DE FILTROS -->
            <form method="GET" action="index.php" id="filtrosForm">
                <div class="filters-section">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="filter-group">
                                <label class="form-label">🥬 Tipo de Producto</label>
                                <select class="form-select" name="categoria" onchange="this.form.submit()">
                                    <option value="todos">Todos los productos</option>
                                    <?php foreach ($categorias_map as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= $filtro_categoria === $key ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="filter-group">
                                <label class="form-label">📍 Zona de Posadas</label>
                                <select class="form-select" name="zona" onchange="this.form.submit()">
                                    <option value="todas">Todas las zonas</option>
                                    <?php foreach ($zonas_map as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= $filtro_zona === $key ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="filter-group">
                                <label class="form-label">📅 Disponibilidad</label>
                                <select class="form-select" name="dia" onchange="this.form.submit()">
                                    <option value="todos">Cualquier día</option>
                                    <?php foreach ($dias_map as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= $filtro_dia === $key ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="filter-group">
                                <label class="form-label">💰 Rango de Precio</label>
                                <input type="range" class="form-range" name="precio" id="precioRange" 
                                       min="0" max="10000" step="100" value="<?= $filtro_precio ?>"
                                       oninput="updatePrecioLabel(this.value)">
                                <small class="text-muted" id="precioLabel">Hasta $<?= number_format($filtro_precio, 0, ',', '.') ?> por unidad</small>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12 text-end">
                            <a href="index.php" class="btn btn-secondary btn-sm">Limpiar Filtros</a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- CONTADOR Y VISTA -->
            <div class="productos-header">
                <div class="productos-count">
                    <strong><?= $total_productos ?> productos encontrados</strong>
                    <?php if ($total_paginas > 1): ?>
                        <span class="text-muted ms-2">(Página <?= $pagina_actual ?> de <?= $total_paginas ?>)</span>
                    <?php endif; ?>
                </div>
                <!-- <div class="view-toggle">
                    <button class="view-btn active">🔲 Grilla</button>
                    <button class="view-btn">📋 Lista</button>
                </div> -->
            </div>

            <!-- GRID DE PRODUCTOS -->
            <div class="row g-3">
                
                <!-- Contenedor Estático de Reciclaje - Siempre Primero -->
                <?php if ($pagina_actual === 1): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="producto-card recycle-card">
                        <img src="img/frascos.jpg" alt="Trae tus frascos y reciclá" class="product-image-placeholder" />
                        
                        <div class="recycle-header">
                            <div class="recycle-icon">
                                <i class="bi bi-recycle" style="font-size: 2.5rem; color: #4CAF50;"></i>
                            </div>
                            <h3 class="recycle-title">¡Reciclá con Nosotros!</h3>
                        </div>
                        
                        <div class="recycle-content">
                            <h4 style="color: #4CAF50; font-weight: 700; margin-bottom: 15px;">
                                Traé tus frascos y obtené descuentos
                            </h4>
                            
                            <ul class="recycle-benefits">
                                <li><i class="bi bi-check-circle-fill"></i> 10% OFF en productos a granel</li>
                                <li><i class="bi bi-check-circle-fill"></i> 15% OFF en dulces y conservas</li>
                                <li><i class="bi bi-check-circle-fill"></i> Cuidamos el medio ambiente</li>
                            </ul>
                            
                            <div class="recycle-info">
                                <p><strong>Frascos aceptados:</strong></p>
                                <p style="font-size: 0.9rem; color: #666;">
                                    Mermeladas, salsas, pickles - limpios y con tapa
                                </p>
                            </div>
                        </div>
                        
                        <div class="recycle-cta">
                            <button class="btn btn-success btn-sm-custom w-100">
                                <i class="bi bi-info-circle"></i> Más Información
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- PRODUCTOS DINÁMICOS -->
                <?php if (empty($productos)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            No se encontraron productos con los filtros seleccionados.
                            <a href="index.php" class="alert-link">Limpiar filtros</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="producto-card">
                            <img src="<?= htmlspecialchars($producto['imagen'] ?? 'img/default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                                 class="product-image-placeholder" 
                                 onerror="this.src='img/default.jpg'" />

                            <div class="producto-header">
                                <div>
                                    <h3 class="producto-title"><?= htmlspecialchars($producto['nombre']) ?></h3>
                                    <span class="producto-tipo">
                                        <?= getCategoriaEmoji($producto['categoria']) ?> 
                                        <?= ucfirst($producto['categoria']) ?>
                                    </span>
                                    <?php if ($producto['organico']): ?>
                                        <span class="badge bg-success ms-1">Orgánico</span>
                                    <?php endif; ?>
                                    <?php if ($producto['sin_agrotoxicos']): ?>
                                        <span class="badge bg-info ms-1">Sin agrotóxicos</span>
                                    <?php endif; ?>
                                </div>
                                <div class="precio">
                                    $<?= number_format($producto['precio'], 0, ',', '.') ?>/<?= htmlspecialchars($producto['unidad']) ?>
                                </div>
                            </div>

                            <div class="productor-info">
                                <div class="ubicacion">
                                    <i class="bi bi-shop"></i>
                                    <strong><?= htmlspecialchars($producto['punto_venta']) ?></strong>
                                </div>
                                <div class="ubicacion">
                                    <i class="bi bi-geo-alt"></i>
                                    <?= htmlspecialchars($producto['direccion']) ?>
                                </div>
                                <div class="ubicacion">
                                    <i class="bi bi-pin-map"></i>
                                    <?= $zonas_map[$producto['zona']] ?? ucfirst($producto['zona']) ?>
                                </div>
                            </div>

                            <p class="product-description">
                                <?= htmlspecialchars($producto['descripcion']) ?>
                            </p>

                            <div class="disponibilidad">
                                🕐 <?= formatearDias($producto['dias_disponibles']) ?>, 
                                <?= htmlspecialchars($producto['horario']) ?>
                            </div>

                            <div class="stock-info mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-box-seam"></i> 
                                    Stock: <?= $producto['stock_disponible'] ?> <?= htmlspecialchars($producto['unidad']) ?>
                                </small>
                            </div>

                            <div class="cantidad-selector">
                                <span>Cantidad:</span>
                                <input type="number" 
                                       value="1" 
                                       min="1" 
                                       max="<?= $producto['stock_disponible'] ?>" 
                                       class="cantidad-input form-control"
                                       data-producto-id="<?= $producto['_id'] ?>">
                                <span><?= htmlspecialchars($producto['unidad']) ?></span>
                                <button class="btn btn-primary-custom btn-sm-custom btn-agregar"
                                        data-producto-id="<?= $producto['_id'] ?>"
                                        data-nombre="<?= htmlspecialchars($producto['nombre']) ?>"
                                        data-precio="<?= $producto['precio'] ?>">
                                    <i class="bi bi-basket"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PAGINACIÓN -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegación de productos" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Botón Anterior -->
                    <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= $filtro_categoria ? '&categoria='.$filtro_categoria : '' ?><?= $filtro_zona ? '&zona='.$filtro_zona : '' ?><?= $filtro_dia ? '&dia='.$filtro_dia : '' ?>&precio=<?= $filtro_precio ?>">
                            <i class="bi bi-chevron-left"></i> Anterior
                        </a>
                    </li>

                    <!-- Números de página -->
                    <?php
                    $rango = 2; // Mostrar 2 páginas a cada lado
                    $inicio = max(1, $pagina_actual - $rango);
                    $fin = min($total_paginas, $pagina_actual + $rango);

                    // Primera página
                    if ($inicio > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=1<?= $filtro_categoria ? '&categoria='.$filtro_categoria : '' ?><?= $filtro_zona ? '&zona='.$filtro_zona : '' ?><?= $filtro_dia ? '&dia='.$filtro_dia : '' ?>&precio=<?= $filtro_precio ?>">1</a>
                        </li>
                        <?php if ($inicio > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif;

                    // Páginas del rango
                    for ($i = $inicio; $i <= $fin; $i++): ?>
                        <li class="page-item <?= $i === $pagina_actual ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?><?= $filtro_categoria ? '&categoria='.$filtro_categoria : '' ?><?= $filtro_zona ? '&zona='.$filtro_zona : '' ?><?= $filtro_dia ? '&dia='.$filtro_dia : '' ?>&precio=<?= $filtro_precio ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor;

                    // Última página
                    if ($fin < $total_paginas): ?>
                        <?php if ($fin < $total_paginas - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?= $total_paginas ?><?= $filtro_categoria ? '&categoria='.$filtro_categoria : '' ?><?= $filtro_zona ? '&zona='.$filtro_zona : '' ?><?= $filtro_dia ? '&dia='.$filtro_dia : '' ?>&precio=<?= $filtro_precio ?>"><?= $total_paginas ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- Botón Siguiente -->
                    <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?><?= $filtro_categoria ? '&categoria='.$filtro_categoria : '' ?><?= $filtro_zona ? '&zona='.$filtro_zona : '' ?><?= $filtro_dia ? '&dia='.$filtro_dia : '' ?>&precio=<?= $filtro_precio ?>">
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- <script>
        //script.js
    </script> -->
</body>
</html>