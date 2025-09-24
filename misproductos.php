<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mercado Agrícola Local - Mis Productos</title>
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
      <a class="tab-btn active" href="misproductos.php">📦 Mis Productos</a>
      <a class="tab-btn" href="mispedidos.php">🛍️ Mis Pedidos</a>
    </div>

    <!-- CONTENIDO DE MIS PRODUCTOS -->
    <div>
      <h2 class="tab-title">Gestión de Mis Productos</h2>
      <!-- contenido completo de la sección mis-productos -->
      <!-- Login section -->
            <div class="card mb-4" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                <div class="card-body text-center py-5">
                    <h3 class="mb-4">👨‍🌾 Acceso para Productores</h3>
                    <p class="mb-4">Inicia sesión para gestionar tus productos y ver estadísticas de ventas</p>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="email" class="form-control" placeholder="Tu email">
                                </div>
                                <div class="col-md-4">
                                    <input type="password" class="form-control" placeholder="Contraseña">
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-light btn-primary-custom w-100">
                                        <i class="bi bi-box-arrow-in-right"></i> Ingresar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state cuando no está logueado -->
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>Gestiona tus productos</h3>
                <p>Una vez que inicies sesión, podrás:</p>
                <div class="row justify-content-center mt-4">
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="bi bi-plus-circle display-4 text-success mb-3"></i>
                                        <h5>Agregar productos</h5>
                                        <p class="text-muted small">Publica nuevos productos con fotos y detalles</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="bi bi-pencil-square display-4 text-primary mb-3"></i>
                                        <h5>Editar precios</h5>
                                        <p class="text-muted small">Actualiza precios y disponibilidad en tiempo real</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="bi bi-graph-up display-4 text-info mb-3"></i>
                                        <h5>Ver estadísticas</h5>
                                        <p class="text-muted small">Analiza tus ventas y productos más populares</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="bi bi-chat-dots display-4 text-warning mb-3"></i>
                                        <h5>Comunicarte con clientes</h5>
                                        <p class="text-muted small">Recibe consultas y coordina entregas</p>
                                    </div>
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
