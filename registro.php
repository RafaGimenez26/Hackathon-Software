<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mercado Agrícola Local - Registrarse como Productor</title>
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
      <a class="tab-btn active" href="registro.php">👨‍🌾 Registrarse como Productor</a>
      <a class="tab-btn" href="misproductos.php">📦 Mis Productos</a>
      <a class="tab-btn" href="mispedidos.php">🛍️ Mis Pedidos</a>
    </div>

    <!-- CONTENIDO DE REGISTRO -->
    <div>
      <h2 class="tab-title">Únete a Nuestra Comunidad de Productores</h2>
      
      <!-- Información Personal -->
      <div class="form-section">
        <h3 class="mb-4 text-dark">📝 Información Personal</h3>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">👤 Nombre Completo o Razón Social</label>
            <input type="text" class="form-control" placeholder="Ej: María Fernández o Granja Los Álamos SRL">
          </div>
          <div class="col-md-6">
            <label class="form-label">📧 Correo Electrónico</label>
            <input type="email" class="form-control" placeholder="tu.email@ejemplo.com">
          </div>
          <div class="col-md-6">
            <label class="form-label">📱 Teléfono de Contacto</label>
            <input type="tel" class="form-control" placeholder="+54 9 11 1234-5678">
          </div>
          <div class="col-md-6">
            <label class="form-label">🆔 CUIT/CUIL</label>
            <input type="text" class="form-control" placeholder="XX-XXXXXXXX-X">
          </div>
        </div>
      </div>

      <!-- Información de la Producción -->
      <div class="form-section">
        <h3 class="mb-4 text-dark">🏭 Información de la Producción</h3>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">📍 Dirección de la Finca/Establecimiento</label>
            <textarea class="form-control" rows="3" placeholder="Dirección completa de donde produces..."></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">🌾 Tipo de Producción Principal</label>
            <select class="form-select">
              <option>Selecciona tu especialidad</option>
              <option>Verduras de hoja (lechuga, espinaca, acelga)</option>
              <option>Hortalizas (tomate, pimiento, cebolla)</option>
              <option>Frutas de estación</option>
              <option>Cereales y legumbres</option>
              <option>Productos lácteos</option>
              <option>Carnes y derivados</option>
              <option>Producción mixta</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">🏆 Certificaciones (Opcional)</label>
            <input type="text" class="form-control" placeholder="Ej: Orgánico certificado, SENASA, etc.">
          </div>
          <div class="col-md-6">
            <label class="form-label">📏 Tamaño del Establecimiento (Hectáreas)</label>
            <input type="number" class="form-control" placeholder="Ej: 2.5" step="0.1" min="0">
          </div>
          <div class="col-md-6">
            <label class="form-label">👥 Cantidad de Empleados</label>
            <select class="form-select">
              <option>Selecciona el rango</option>
              <option>Solo yo/mi familia (1-3 personas)</option>
              <option>Pequeño equipo (4-10 personas)</option>
              <option>Mediano equipo (11-25 personas)</option>
              <option>Más de 25 personas</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Información de Productos -->
      <div class="form-section">
        <h3 class="mb-4 text-dark">📦 Información de Productos</h3>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">📅 Días de disponibilidad para venta</label>
            <div class="row g-2 mt-1">
              <div class="col-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="lunes">
                  <label class="form-check-label" for="lunes">Lunes</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="martes">
                  <label class="form-check-label" for="martes">Martes</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="miercoles">
                  <label class="form-check-label" for="miercoles">Miércoles</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="jueves">
                  <label class="form-check-label" for="jueves">Jueves</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="viernes">
                  <label class="form-check-label" for="viernes">Viernes</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="sabado">
                  <label class="form-check-label" for="sabado">Sábado</label>
                </div>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="domingo">
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
                <input type="time" class="form-control" value="08:00">
              </div>
              <div class="col-6">
                <label class="form-label small">Hasta:</label>
                <input type="time" class="form-control" value="18:00">
              </div>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">📝 Descripción de tu producción</label>
            <textarea class="form-control" rows="4" placeholder="Cuéntanos sobre tus métodos de cultivo, filosofía de producción, qué hace especiales tus productos..."></textarea>
          </div>
        </div>
      </div>

      <!-- Información de Distribución -->
      <div class="form-section">
        <h3 class="mb-4 text-dark">🚚 Información de Distribución</h3>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">📍 Zonas de Posadas donde vendes (selecciona varias)</label>
            <div class="row g-2 mt-1">
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="sarita">
                  <label class="form-check-label" for="sarita">Villa Sarita</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="cabello">
                  <label class="form-check-label" for="cabello">Villa Cabello</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="itaembe">
                  <label class="form-check-label" for="itaembe">Itaembe Mini</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="santarita">
                  <label class="form-check-label" for="santarita">Santa Rita</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="urquiza">
                  <label class="form-check-label" for="urquiza">Villa Urquiza</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="centro">
                  <label class="form-check-label" for="centro">Centro</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="todas">
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
                  <input class="form-check-input" type="checkbox" id="efectivo">
                  <label class="form-check-label" for="efectivo">💵 Efectivo</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="transferencia">
                  <label class="form-check-label" for="transferencia">🏦 Transferencia</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="mercadopago">
                  <label class="form-check-label" for="mercadopago">📱 MercadoPago</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="tarjeta">
                  <label class="form-check-label" for="tarjeta">💳 Tarjetas</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="todos">
                  <label class="form-check-label" for="todos">✅ Todos los métodos de pago</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón -->
      <div class="text-center">
        <button class="btn btn-primary-custom btn-lg px-5">
          <i class="bi bi-check-circle"></i> Registrarme como Productor
        </button>
        <p class="mt-3 text-muted">
          <small>Al registrarte, aceptas nuestros términos y condiciones de la plataforma.</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
