<?php
// ======================================================================
// 1. INICIO Y GESTIÓN DE SESIÓN
// ======================================================================
session_start();

// Si el usuario ya está logueado, redirigir a la página de carga de productos
if (isset($_SESSION['ProductorID'])) {
    header('Location: dashboard_productor.php');
    exit();
}

// Inicialización de variables de estado
$error_login = '';
$email_previo = ''; // Para mantener el email si el login falla

// ======================================================================
// 2. LÓGICA DE PROCESAMIENTO PHP (Login)
// ======================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2.1. INCLUIR ARCHIVO DE CONEXIÓN REMOTA
    // ------------------------------------
    require_once 'conexion.php';

    // Verificar que la conexión MySQL esté disponible
    if (!isset($conexion) || $conexion->connect_error) {
        $error_login = "Error del servidor: No se pudo conectar a la base de datos.";
    }

    if (empty($error_login)) {
        // 2.2. RECIBIR Y SANITIZAR DATOS
        $email = trim($_POST['email'] ?? '');
        $password_ingresada = $_POST['password'] ?? '';
        $email_previo = htmlspecialchars($email); 

        if (empty($email) || empty($password_ingresada)) {
            $error_login = "Por favor, ingresa tu email y contraseña.";
        } else {
            try {
                // Usar consulta preparada con MySQLi
                $stmt = $conexion->prepare("SELECT ProductorID, NombreRazonSocial, PasswordHash FROM productores WHERE CorreoElectronico = ? AND Activo = 1");
                
                if (!$stmt) {
                    throw new Exception("Error al preparar la consulta: " . $conexion->error);
                }
                
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $productor = $result->fetch_assoc();

                if ($productor && password_verify($password_ingresada, $productor['PasswordHash'])) {
                    
                    // Inicio de sesión exitoso
                    // IMPORTANTE: Usar ProductorID (con mayúscula) para consistencia
                    $_SESSION['ProductorID'] = $productor['ProductorID'];
                    $_SESSION['nombre_productor'] = $productor['NombreRazonSocial'];
                    
                    // Cerrar statement
                    $stmt->close();
                    
                    // Redirigir a la página de carga de productos
                    header('Location: dashboard_productor.php'); 
                    exit();

                } else {
                    $error_login = "Credenciales incorrectas. Verifica tu email y contraseña.";
                }

                $stmt->close();

            } catch (Exception $e) {
                $error_login = "Ocurrió un error al intentar iniciar sesión. Intenta más tarde.";
                // Para debugging (comentar en producción):
                // $error_login .= " - " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgroHub Misiones - Acceso Productores</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet"> 
</head>
<body>
  <div class="container-custom">
    <div class="header">
      <h1>🌾 AgroHub Misiones</h1>
      <p>Conectando productores locales con la comunidad Misionera</p>
    </div>

    <div class="nav-tabs-custom">
      <a class="tab-btn" href="index.php">🛒 Ver Productos</a>
      <a class="tab-btn" href="registro.php">👨‍🌾 Registrarse como Productor</a>
      <a class="tab-btn active" href="misproductos.php">📦 Acceso Productor</a>
      <a class="tab-btn" href="registro_usuario.php">🛍️ Mis Pedidos</a>
    </div>

    <div class="tab-content active">
      <h2 class="tab-title">Acceso y Gestión de Productos</h2>

        <?php if (!empty($error_login)): ?>
            <div class="alert alert-danger mt-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($error_login); ?>
            </div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm recycle-card mb-4">
            <div class="card-body text-center py-4">
                <h3 class="mb-4 recycle-title">👨‍🌾 Acceso para Productores</h3>
                <p class="mb-4">Ingresa tu email y contraseña para gestionar tus productos y pedidos.</p>
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <form method="POST" action="misproductos.php" class="row g-3">
                            <div class="col-md-5">
                                <input type="email" class="form-control" name="email" required 
                                    placeholder="Tu email" value="<?php echo $email_previo; ?>">
                            </div>
                            <div class="col-md-5">
                                <input type="password" class="form-control" name="password" required 
                                    placeholder="Contraseña">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary-custom w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> Ingresar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <p class="mt-3">
                    <a href="registro.php" class="text-success text-decoration-underline">¿No tienes cuenta? Regístrate aquí</a>
                </p>
            </div>
        </div>

        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>Gestiona tus productos</h3>
            <p>Una vez que inicies sesión, podrás:</p>
            <div class="row justify-content-center mt-4">
                <div class="col-md-12">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="bi bi-plus-circle display-4 text-success mb-3"></i>
                                    <h5>Agregar productos</h5>
                                    <p class="text-muted small">Publica nuevos productos con fotos y detalles</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="bi bi-pencil-square display-4 text-primary mb-3"></i>
                                    <h5>Editar precios</h5>
                                    <p class="text-muted small">Actualiza precios y disponibilidad en tiempo real</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="bi bi-graph-up display-4 text-info mb-3"></i>
                                    <h5>Ver estadísticas</h5>
                                    <p class="text-muted small">Analiza tus ventas y productos más populares</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
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

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>