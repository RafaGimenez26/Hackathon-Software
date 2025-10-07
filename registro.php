<?php
// ======================================================================
// 1. LÓGICA DE PROCESAMIENTO PHP
//    Este bloque solo se ejecuta cuando el formulario es enviado (POST)
// ======================================================================

// Manejo de variables de error/éxito (inicialización)
$error = '';
$mensaje_estado = '';

// Detectar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. CONFIGURACIÓN DE LA BASE DE DATOS
    // ------------------------------------
    $host = 'localhost'; 
    $db = 'MercadoAgricolaLocal'; 
    $user = 'root'; // <-- ¡VERIFICA TUS CREDENCIALES!
    $pass = ''; // <-- ¡VERIFICA TUS CREDENCIALES!
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        $error = "Error de conexión a la base de datos: " . $e->getMessage();
    }

    if (empty($error)) {
        // 2. RECIBIR Y SANITIZAR DATOS
        // ----------------------------
        $nombre_razon_social = trim($_POST['nombre_razon_social'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; // [NUEVO: CONTRASEÑA]
        $telefono = trim($_POST['telefono'] ?? '');
        $cuit_cuil = trim($_POST['cuit_cuil'] ?? '');
        $direccion_establecimiento = trim($_POST['direccion_establecimiento'] ?? '');
        $tipo_produccion_txt = trim($_POST['tipo_produccion'] ?? '');
        $certificaciones = trim($_POST['certificaciones'] ?? '');
        $tamano_hectareas = filter_var($_POST['tamano_hectareas'] ?? 0, FILTER_VALIDATE_FLOAT);
        $rango_empleados = trim($_POST['rango_empleados'] ?? '');
        $horario_desde = trim($_POST['horario_desde'] ?? '00:00:00');
        $horario_hasta = trim($_POST['horario_hasta'] ?? '00:00:00');
        $descripcion_produccion = trim($_POST['descripcion_produccion'] ?? '');

        // Arrays de selecciones múltiples
        $dias_disponibles = $_POST['dias_disponibles'] ?? [];
        $zonas_venta = $_POST['zonas_venta'] ?? [];
        $metodos_pago = $_POST['metodos_pago'] ?? [];

        // 3. VALIDACIÓN BÁSICA
        if (empty($nombre_razon_social) || empty($email) || empty($password) || empty($tipo_produccion_txt)) {
            $error = "Error: Faltan campos obligatorios (Nombre, Email, Contraseña, Tipo de Producción).";
        } else if (strlen($password) < 6) { // Validación de longitud mínima
            $error = "Error: La contraseña debe tener al menos 6 caracteres.";
        }

        if (empty($error)) {
            // [NUEVO: CONTRASEÑA] Generar el hash de la contraseña de forma segura
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // 4. INSERCIÓN EN LA BASE DE DATOS USANDO TRANSACCIONES
            try {
                $pdo->beginTransaction();

                // A. Buscar el ID del tipo de producción
                $stmt_tipo = $pdo->prepare("SELECT TipoProduccionID FROM TiposProduccion WHERE NombreTipo = ?");
                $stmt_tipo->execute([$tipo_produccion_txt]);
                $tipo_produccion_obj = $stmt_tipo->fetch();

                if (!$tipo_produccion_obj) {
                    throw new Exception("El tipo de producción seleccionado no es válido en el catálogo.");
                }
                $tipo_id = $tipo_produccion_obj['TipoProduccionID'];

                // B. Insertar datos principales en 'Productores' (incluyendo PasswordHash)
                $sql_productor = "INSERT INTO Productores (
                    NombreRazonSocial, CorreoElectronico, PasswordHash, TelefonoContacto, CUIT_CUIL, 
                    DireccionEstablecimiento, TipoProduccionPrincipalID, Certificaciones, 
                    TamanoHectareas, RangoEmpleados, HorarioAtencionDesde, 
                    HorarioAtencionHasta, DescripcionProduccion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 13 Placeholders

                $stmt = $pdo->prepare($sql_productor);
                $stmt->execute([
                    $nombre_razon_social, $email, $password_hash, $telefono, $cuit_cuil, // Se agrega $password_hash
                    $direccion_establecimiento, $tipo_id, $certificaciones,
                    $tamano_hectareas, $rango_empleados, $horario_desde,
                    $horario_hasta, $descripcion_produccion
                ]);

                $productor_id = $pdo->lastInsertId();

                // C, D, E. Inserción de selecciones múltiples (Días, Zonas, Pagos)
                // *Esta lógica es la misma y funciona correctamente, se mantiene.*
                
                // C. Días de Disponibilidad
                if (!empty($dias_disponibles)) {
                    $placeholders = implode(',', array_fill(0, count($dias_disponibles), '?'));
                    $stmt_ids = $pdo->prepare("SELECT DiaID FROM CatalogoDias WHERE NombreDia IN ($placeholders)");
                    $stmt_ids->execute($dias_disponibles);
                    $catalogo_ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);
                    $sql_insert = "INSERT INTO DiasDisponibilidad (ProductorID, DiaID) VALUES (:pid, :did)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    foreach ($catalogo_ids as $id) {
                        $stmt_insert->execute([':pid' => $productor_id, ':did' => $id]);
                    }
                }

                // D. Zonas de Distribución
                if (!empty($zonas_venta)) {
                    $placeholders = implode(',', array_fill(0, count($zonas_venta), '?'));
                    $stmt_ids = $pdo->prepare("SELECT ZonaID FROM CatalogoZonas WHERE NombreZona IN ($placeholders)");
                    $stmt_ids->execute($zonas_venta);
                    $catalogo_ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);
                    $sql_insert = "INSERT INTO ZonasDistribucion (ProductorID, ZonaID) VALUES (:pid, :zid)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    foreach ($catalogo_ids as $id) {
                        $stmt_insert->execute([':pid' => $productor_id, ':zid' => $id]);
                    }
                }

                // E. Métodos de Pago
                if (!empty($metodos_pago)) {
                    $placeholders = implode(',', array_fill(0, count($metodos_pago), '?'));
                    $stmt_ids = $pdo->prepare("SELECT MetodoPagoID FROM CatalogoMetodosPago WHERE NombreMetodo IN ($placeholders)");
                    $stmt_ids->execute($metodos_pago);
                    $catalogo_ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);
                    $sql_insert = "INSERT INTO MetodosPagoAceptados (ProductorID, MetodoPagoID) VALUES (:pid, :mpid)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    foreach ($catalogo_ids as $id) {
                        $stmt_insert->execute([':pid' => $productor_id, ':mpid' => $id]);
                    }
                }
                
                // Si todo fue bien, confirmar la transacción
                $pdo->commit();
                
                // Éxito: Redirigir
                header("Location: registro.php?estado=exito&id=" . $productor_id);
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                // En un entorno real, solo mostraríamos un mensaje genérico al usuario
                $error = "Ocurrió un error al guardar los datos. Intente nuevamente. Detalles: " . $e->getMessage();
            }
        }
    }
} // Fin del if ($_SERVER["REQUEST_METHOD"] == "POST")

// ======================================================================
// 2. HTML Y PRESENTACIÓN
// ======================================================================

// Manejo de mensajes de estado (éxito/error)
if (isset($_GET['estado']) && $_GET['estado'] == 'exito') {
    $mensaje_estado = '<div class="alert alert-success mt-4" role="alert">¡Registro exitoso! Ya eres parte de la comunidad. Ya puedes iniciar sesión.</div>';
} elseif (!empty($error)) { // Si $error fue seteado en el bloque POST
    $mensaje_estado = '<div class="alert alert-danger mt-4" role="alert">' . htmlspecialchars($error) . '</div>';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mercado Agrícola Local - Registrarse como Productor</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
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
      <a class="tab-btn active" href="registro.php">👨‍🌾 Registrarse como Productor</a>
      <a class="tab-btn" href="misproductos.php">📦 Mis Productos</a>
      <a class="tab-btn" href="mispedidos.php">🛍️ Mis Pedidos</a>
    </div>

    <?php echo $mensaje_estado; ?>

    <div>
      <h2 class="tab-title">Únete a Nuestra Comunidad de Productores</h2>
      
      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            
        <div class="form-section">
          <h3 class="mb-4 text-dark">📝 Información de Acceso y Personal</h3>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">👤 Nombre Completo o Razón Social</label>
              <input type="text" class="form-control" name="nombre_razon_social" required placeholder="Ej: María Fernández o Granja Los Álamos SRL">
            </div>
            <div class="col-md-6">
              <label class="form-label">📧 Correo Electrónico (Tu usuario de Login)</label>
              <input type="email" class="form-control" name="email" required placeholder="tu.email@ejemplo.com">
            </div>
            <div class="col-md-6">
              <label class="form-label">🔒 Contraseña</label>
              <input type="password" class="form-control" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
            </div>
            <div class="col-md-6">
              <label class="form-label">📱 Teléfono de Contacto</label>
              <input type="tel" class="form-control" name="telefono" placeholder="+54 9 11 1234-5678">
            </div>
            <div class="col-md-6">
              <label class="form-label">🆔 CUIT/CUIL</label>
              <input type="text" class="form-control" name="cuit_cuil" placeholder="XX-XXXXXXXX-X">
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3 class="mb-4 text-dark">🏭 Información de la Producción</h3>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">📍 Dirección de la Finca/Establecimiento</label>
              <textarea class="form-control" rows="3" name="direccion_establecimiento" placeholder="Dirección completa de donde produces..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">🌾 Tipo de Producción Principal</label>
              <select class="form-select" name="tipo_produccion" required>
                <option value="">Selecciona tu especialidad</option>
                <option value="Verduras de hoja (lechuga, espinaca, acelga)">Verduras de hoja (lechuga, espinaca, acelga)</option>
                <option value="Hortalizas (tomate, pimiento, cebolla)">Hortalizas (tomate, pimiento, cebolla)</option>
                <option value="Frutas de estación">Frutas de estación</option>
                <option value="Cereales y legumbres">Cereales y legumbres</option>
                <option value="Productos lácteos">Productos lácteos</option>
                <option value="Carnes y derivados">Carnes y derivados</option>
                <option value="Producción mixta">Producción mixta</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">🏆 Certificaciones (Opcional)</label>
              <input type="text" class="form-control" name="certificaciones" placeholder="Ej: Orgánico certificado, SENASA, etc.">
            </div>
            <div class="col-md-6">
              <label class="form-label">📏 Tamaño del Establecimiento (Hectáreas)</label>
              <input type="number" class="form-control" name="tamano_hectareas" placeholder="Ej: 2.5" step="0.1" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">👥 Cantidad de Empleados</label>
              <select class="form-select" name="rango_empleados">
                <option value="">Selecciona el rango</option>
                <option value="1-3 personas">Solo yo/mi familia (1-3 personas)</option>
                <option value="4-10 personas">Pequeño equipo (4-10 personas)</option>
                <option value="11-25 personas">Mediano equipo (11-25 personas)</option>
                <option value="Más de 25 personas">Más de 25 personas</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3 class="mb-4 text-dark">📦 Información de Productos</h3>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">📅 Días de disponibilidad para venta</label>
              <div class="row g-2 mt-1">
                <div class="col-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="dias_disponibles[]" value="Lunes" id="lunes">
                    <label class="form-check-label" for="lunes">Lunes</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="dias_disponibles[]" value="Martes" id="martes">
                    <label class="form-check-label" for="martes">Martes</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="dias_disponibles[]" value="Miércoles" id="miercoles">
                    <label class="form-check-label" for="miercoles">Miércoles</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="dias_disponibles[]" value="Jueves" id="jueves">
                    <label class="form-check-label" for="jueves">Jueves</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="dias_disponibles[]" value="Viernes" id="viernes">
                    <label class="form-check-label" for="viernes">Viernes</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="dias_disponibles[]" value="Sábado" id="sabado">
                    <label class="form-check-label" for="sabado">Sábado</label>
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="dias_disponibles[]" value="Domingo" id="domingo">
                    <label class="form-check-label" for="domingo">Domingo</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">⏰ Horario de atención</label>
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label small">Desde:</label>
                  <input type="time" class="form-control" name="horario_desde" value="08:00">
                </div>
                <div class="col-6">
                  <label class="form-label small">Hasta:</label>
                  <input type="time" class="form-control" name="horario_hasta" value="18:00">
                </div>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">📝 Descripción de tu producción</label>
              <textarea class="form-control" rows="4" name="descripcion_produccion" placeholder="Cuéntanos sobre tus métodos de cultivo, filosofía de producción, qué hace especiales tus productos..."></textarea>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3 class="mb-4 text-dark">🚚 Información de Distribución</h3>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">📍 Zonas de Posadas donde vendes (selecciona varias)</label>
              <div class="row g-2 mt-1">
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="zonas_venta[]" value="Villa Sarita" id="sarita">
                    <label class="form-check-label" for="sarita">Villa Sarita</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="zonas_venta[]" value="Villa Cabello" id="cabello">
                    <label class="form-check-label" for="cabello">Villa Cabello</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="zonas_venta[]" value="Itaembe Mini" id="itaembe">
                    <label class="form-check-label" for="itaembe">Itaembe Mini</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="zonas_venta[]" value="Santa Rita" id="santarita">
                    <label class="form-check-label" for="santarita">Santa Rita</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="zonas_venta[]" value="Villa Urquiza" id="urquiza">
                    <label class="form-check-label" for="urquiza">Villa Urquiza</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="zonas_venta[]" value="Centro" id="centro">
                    <label class="form-check-label" for="centro">Centro</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="zonas_venta[]" value="Todas las zonas" id="todas">
                    <label class="form-check-label" for="todas">Todas las zonas</label>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">💳 Métodos de pago que aceptas</label>
              <div class="row g-2 mt-1">
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metodos_pago[]" value="Efectivo" id="efectivo">
                    <label class="form-check-label" for="efectivo">💵 Efectivo</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metodos_pago[]" value="Transferencia" id="transferencia">
                    <label class="form-check-label" for="transferencia">🏦 Transferencia</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metodos_pago[]" value="MercadoPago" id="mercadopago">
                    <label class="form-check-label" for="mercadopago">📱 MercadoPago</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metodos_pago[]" value="Tarjetas" id="tarjeta">
                    <label class="form-check-label" for="tarjeta">💳 Tarjetas</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metodos_pago[]" value="Todos los métodos de pago" id="todos">
                    <label class="form-check-label" for="todos">✅ Todos los métodos de pago</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center">
          <button type="submit" class="btn btn-primary-custom btn-lg px-5">
            <i class="bi bi-check-circle"></i> Registrarme como Productor
          </button>
          <p class="mt-3 text-muted">
            <small>Al registrarte, aceptas nuestros términos y condiciones de la plataforma.</small>
          </p>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>