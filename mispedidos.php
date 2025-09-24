<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mercado Agrícola Local - Mis Pedidos</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
</head>
<body>
  <div class="container-custom">
    <div class="header">
      <h1>🌾 Mercado Agrícola Local</h1>
      <p>Conectando productores locales con la comunidad Misionera</p>
    </div>

    <div class="nav-tabs-custom">
      <a class="tab-btn" href="index.php">🛒 Ver Productos</a>
      <a class="tab-btn" href="registro.php">👨‍🌾 Registrarse como Productor</a>
      <a class="tab-btn" href="misproductos.php">📦 Mis Productos</a>
      <a class="tab-btn active" href="mispedidos.php">🛍️ Mis Pedidos</a>
    </div>

    <!-- CONTENIDO DE MIS PEDIDOS -->
    <div>
      <h2 class="tab-title">Mis Pedidos</h2>
      <!-- contenido completo de la sección mis-pedidos -->
      <!-- Carrito de compras -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="bi bi-cart3"></i> Carrito de Pedido</h4>
                </div>
                <div class="card-body">
                    <!-- Item del carrito -->
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div style="width: 60px; height: 60px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">Tomates Cherry Orgánicos</h6>
                                <small class="text-muted">Granja Los Álamos - Itaembe Mini</small>
                                <div class="mt-1">
                                    <span class="badge bg-success">2 kg</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="h5 text-success mb-1">$2.400</div>
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div style="width: 60px; height: 60px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">Lechuga Criolla</h6>
                                <small class="text-muted">Quinta El Sol - Santa Rita</small>
                                <div class="mt-1">
                                    <span class="badge bg-success">3 u</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="h5 text-success mb-1">$2.400</div>
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-2">Total: $4.800</h4>
                            <p class="mb-3">2 productos de 2 productores diferentes</p>
                            <button class="btn btn-light btn-lg">
                                <i class="bi bi-basket"></i> Reservar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de pedidos -->
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Pedidos</h4>
                </div>
                <div class="card-body">
                    <!-- Pedido 1 -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1">Pedido #001</h6>
                                    <small class="text-muted">20 de Septiembre, 2025</small>
                                </div>
                                <span class="badge bg-success">Entregado</span>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <p class="mb-1"><strong>Productos:</strong></p>
                                    <ul class="list-unstyled mb-0">
                                        <li>• Tomates Cherry Orgánicos (1kg) - $1.200</li>
                                        <li>• Queso de Cabra (0.5kg) - $4.750</li>
                                    </ul>
                                </div>
                                <div class="col-md-4 text-end">
                                    <p class="h5 text-success mb-1">$5.950</p>
                                    <button class="btn btn-sm btn-outline-primary">Ver detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pedido 2 -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1">Pedido #002</h6>
                                    <small class="text-muted">15 de Septiembre, 2025</small>
                                </div>
                                <span class="badge bg-warning">En preparación</span>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <p class="mb-1"><strong>Productos:</strong></p>
                                    <ul class="list-unstyled mb-0">
                                        <li>• Lechuga Criolla (2u) - $1.600</li>
                                        <li>• Miel de Eucalipto (1kg) - $3.200</li>
                                    </ul>
                                </div>
                                <div class="col-md-4 text-end">
                                    <p class="h5 text-success mb-1">$4.800</p>
                                    <button class="btn btn-sm btn-outline-primary">Ver detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
