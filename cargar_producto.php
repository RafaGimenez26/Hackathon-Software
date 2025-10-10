<?php
// carga_producto.php
session_start();
require 'conexion.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['ProductorID'])) {
    header('Location: misproductos.php');
    exit;
}

$productor_id = $_SESSION['ProductorID'];

// Obtener datos del productor para autocompletar
$stmt = $conexion->prepare("
    SELECT p.*, 
           GROUP_CONCAT(DISTINCT dd.DiaID) as dias_ids,
           GROUP_CONCAT(DISTINCT cd.NombreDia) as dias_nombres
    FROM productores p
    LEFT JOIN diasdisponibilidad dd ON p.ProductorID = dd.ProductorID
    LEFT JOIN catalogodias cd ON dd.DiaID = cd.DiaID
    WHERE p.ProductorID = ?
    GROUP BY p.ProductorID
");
$stmt->bind_param("i", $productor_id);
$stmt->execute();
$productor = $stmt->get_result()->fetch_assoc();

// Obtener catálogo de días disponibles
$dias_resultado = $conexion->query("SELECT DiaID, NombreDia FROM catalogodias ORDER BY DiaID");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cargar Nuevo Producto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .preview-container {
      min-height: 200px;
      border: 2px dashed #dee2e6;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f8f9fa;
    }
    .preview-image {
      max-width: 100%;
      max-height: 300px;
      border-radius: 8px;
    }
    .form-section {
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
    }
    .required-label::after {
      content: " *";
      color: #dc3545;
    }
  </style>
  <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="row mb-4">
    <div class="col">
      <h1 class="mb-2">Cargar Nuevo Producto</h1>
      <p class="text-muted">Completa los datos de tu producto. Los campos con (*) son obligatorios.</p>
    </div>
  </div>

  <div id="mensaje-respuesta"></div>

  <form id="form-producto" enctype="multipart/form-data">
    <div class="row">
      <!-- Columna izquierda: Información básica -->
      <div class="col-lg-6">
        <div class="form-section">
          <h2 class="h5 mb-3 border-bottom pb-2">Información del Producto</h2>
          
          <div class="mb-3">
            <label for="nombre" class="form-label required-label">Nombre del Producto</label>
            <input type="text" class="form-control" id="nombre" name="nombre" 
                   placeholder="Ej: Tomate Cherry, Miel de Abeja" required>
            <small class="text-muted">Nombre claro y descriptivo</small>
          </div>

          <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                      placeholder="Describe tu producto: características, origen, método de producción..."></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="categoria" class="form-label required-label">Categoría</label>
              <select id="categoria" name="categoria" class="form-select" required>
                <option value="">-- Seleccione --</option>
                <option value="verduras">Verduras</option>
                <option value="frutas">Frutas</option>
                <option value="hortalizas">Hortalizas</option>
                <option value="miel">Miel</option>
                <option value="huevos">Huevos</option>
                <option value="lacteos">Lácteos</option>
                <option value="carnes">Carnes</option>
                <option value="conservas">Conservas</option>
                <option value="panificados">Panificados</option>
                <option value="otros">Otros</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label for="unidad" class="form-label required-label">Unidad de Venta</label>
              <select id="unidad" name="unidad" class="form-select" required>
                <option value="">-- Seleccione --</option>
                <option value="kg">Kilogramo (kg)</option>
                <option value="unidad">Unidad</option>
                <option value="docena">Docena</option>
                <option value="litro">Litro</option>
                <option value="bolsa">Bolsa</option>
                <option value="atado">Atado</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="precio" class="form-label required-label">Precio</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control" id="precio" name="precio" 
                       step="0.01" min="0" placeholder="0.00" required>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <label for="stock_disponible" class="form-label">Stock Disponible</label>
              <input type="number" class="form-control" id="stock_disponible" name="stock_disponible" 
                     min="0" placeholder="Cantidad disponible">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Características</label>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="organico" name="organico" value="1">
              <label class="form-check-label" for="organico">
                Orgánico / Certificado
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="sin_agrotoxicos" name="sin_agrotoxicos" value="1">
              <label class="form-check-label" for="sin_agrotoxicos">
                Sin agrotóxicos
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Columna derecha: Punto de venta y disponibilidad -->
      <div class="col-lg-6">
        <div class="form-section">
          <h2 class="h5 mb-3 border-bottom pb-2">Imagen del Producto</h2>
          
          <div class="mb-3">
            <label for="imagen" class="form-label">Foto del Producto (opcional)</label>
            <input type="file" class="form-control" id="imagen" name="imagen" 
                   accept=".jpg,.jpeg,.png,.webp">
            <small class="text-muted">Formatos: JPG, PNG, WEBP. Tamaño máximo: 5MB</small>
          </div>

          <div class="preview-container mb-3" id="preview-container">
            <p class="text-muted">Vista previa de la imagen</p>
          </div>
        </div>

        <div class="form-section">
          <h2 class="h5 mb-3 border-bottom pb-2">Punto de Venta y Disponibilidad</h2>
          
          <div class="mb-3">
            <label for="punto_venta" class="form-label required-label">Punto de Venta</label>
            <input type="text" class="form-control" id="punto_venta" name="punto_venta" 
                   placeholder="Ej: Feria Centro, Mi local, Establecimiento" required>
          </div>

          <div class="mb-3">
            <label for="direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="direccion" name="direccion" 
                   value="<?= htmlspecialchars($productor['DireccionEstablecimiento'] ?? '') ?>"
                   placeholder="Dirección completa del punto de venta">
          </div>

          <div class="mb-3">
            <label for="zona" class="form-label">Zona</label>
            <select id="zona" name="zona" class="form-select">
              <option value="">-- Seleccione --</option>
              <option value="centro">Centro</option>
              <option value="norte">Norte</option>
              <option value="sur">Sur</option>
              <option value="este">Este</option>
              <option value="oeste">Oeste</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label required-label">Días Disponibles</label>
            <div id="dias-container">
              <?php while($dia = $dias_resultado->fetch_assoc()): 
                $dias_productor = explode(',', $productor['dias_ids'] ?? '');
                $checked = in_array($dia['DiaID'], $dias_productor) ? 'checked' : '';
              ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="dias_disponibles[]" 
                       value="<?= strtolower($dia['NombreDia']) ?>" 
                       id="dia_<?= $dia['DiaID'] ?>" <?= $checked ?>>
                <label class="form-check-label" for="dia_<?= $dia['DiaID'] ?>">
                  <?= ucfirst($dia['NombreDia']) ?>
                </label>
              </div>
              <?php endwhile; ?>
            </div>
            <small class="text-muted">Selecciona al menos un día</small>
          </div>

          <div class="mb-3">
            <label for="horario" class="form-label">Horario de Disponibilidad</label>
            <input type="text" class="form-control" id="horario" name="horario" 
                   value="<?= htmlspecialchars($productor['HorarioAtencionDesde'] ?? '') ?> - <?= htmlspecialchars($productor['HorarioAtencionHasta'] ?? '') ?>"
                   placeholder="Ej: 8:00 - 18:00">
          </div>
        </div>
      </div>
    </div>

    <div class="form-section">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-lg flex-grow-1" id="btn-submit">
          <span id="btn-text">Publicar Producto</span>
          <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
        </button>
        <a href="misproductos.php" class="btn btn-outline-secondary btn-lg">Cancelar</a>
      </div>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-producto');
    const imagenInput = document.getElementById('imagen');
    const previewContainer = document.getElementById('preview-container');
    const mensajeDiv = document.getElementById('mensaje-respuesta');
    const btnSubmit = document.getElementById('btn-submit');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');

    // Vista previa de imagen
    imagenInput.addEventListener('change', function() {
        const file = this.files[0];
        previewContainer.innerHTML = '';
        
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('La imagen es muy grande. Tamaño máximo: 5MB');
                this.value = '';
                previewContainer.innerHTML = '<p class="text-muted">Vista previa de la imagen</p>';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-image';
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.innerHTML = '<p class="text-muted">Vista previa de la imagen</p>';
        }
    });

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar que al menos un día esté seleccionado
        const diasChecked = document.querySelectorAll('input[name="dias_disponibles[]"]:checked');
        if (diasChecked.length === 0) {
            mensajeDiv.className = 'alert alert-warning';
            mensajeDiv.textContent = 'Debes seleccionar al menos un día disponible';
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        mensajeDiv.innerHTML = '';
        btnSubmit.disabled = true;
        btnText.textContent = 'Publicando...';
        btnSpinner.classList.remove('d-none');

        const formData = new FormData(form);

        fetch('procesar_producto.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mensajeDiv.className = 'alert alert-success';
                mensajeDiv.innerHTML = `
                    <strong>¡Producto publicado con éxito!</strong><br>
                    ${data.message}<br>
                    <a href="misproductos.php" class="alert-link">Ver mis productos</a>
                `;
                form.reset();
                previewContainer.innerHTML = '<p class="text-muted">Vista previa de la imagen</p>';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                mensajeDiv.className = 'alert alert-danger';
                mensajeDiv.innerHTML = `<strong>Error:</strong> ${data.message}`;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        })
        .catch(err => {
            mensajeDiv.className = 'alert alert-danger';
            mensajeDiv.innerHTML = `<strong>Error de conexión:</strong> ${err.message}`;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .finally(() => {
            btnSubmit.disabled = false;
            btnText.textContent = 'Publicar Producto';
            btnSpinner.classList.add('d-none');
        });
    });
});
</script>
</body>
</html>
<?php $conexion->close(); ?>