<?php
// procesar_producto.php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['ProductorID'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida. Por favor, inicia sesión nuevamente.']);
    exit;
}

$response = ['success' => false, 'message' => ''];

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Validar campos obligatorios
$campos_requeridos = ['nombre', 'precio', 'punto_venta', 'categoria', 'unidad', 'costo_unitario'];
foreach ($campos_requeridos as $campo) {
    if (empty($_POST[$campo])) {
        $response['message'] = "El campo '$campo' es obligatorio";
        echo json_encode($response);
        exit;
    }
}

// Validar días disponibles
if (empty($_POST['dias_disponibles']) || !is_array($_POST['dias_disponibles'])) {
    $response['message'] = 'Debes seleccionar al menos un día disponible';
    echo json_encode($response);
    exit;
}

// Procesar imagen (opcional)
$nombre_imagen = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'img/';
    
    // Crear directorio si no existe
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Validar tipo de archivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $_FILES['imagen']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        $response['message'] = 'Formato de imagen no válido. Use JPG, PNG o WEBP';
        echo json_encode($response);
        exit;
    }

    // Validar tamaño (5MB máximo)
    if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'La imagen es muy grande. Tamaño máximo: 5MB';
        echo json_encode($response);
        exit;
    }

    // Generar nombre único
    $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombre_imagen = 'producto_' . $_SESSION['ProductorID'] . '_' . time() . '.' . $extension;
    $ruta_completa = $upload_dir . $nombre_imagen;

    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
        $response['message'] = 'Error al subir la imagen';
        echo json_encode($response);
        exit;
    }
}

try {
    // Incluir la conexión que ya tienes configurada
    require_once 'conexion.php';
    
    // Usar la variable $collection que ya está definida en conexion.php
    // $collection ya apunta a AgroHub_Misiones.Productos
    
    // Preparar documento
    $documento = [
        'productor_id' => (int)$_SESSION['ProductorID'],
        'nombre' => trim($_POST['nombre']),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'categoria' => $_POST['categoria'],
        'precio' => (float)$_POST['precio'],
        'costo_unitario' => (float)$_POST['costo_unitario'],
        'unidad' => $_POST['unidad'],
        'stock_disponible' => isset($_POST['stock_disponible']) && $_POST['stock_disponible'] !== '' 
            ? (int)$_POST['stock_disponible'] 
            : 0,
        'punto_venta' => trim($_POST['punto_venta']),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'zona' => !empty($_POST['zona']) ? $_POST['zona'] : '',
        'dias_disponibles' => $_POST['dias_disponibles'],
        'horario' => trim($_POST['horario'] ?? ''),
        'imagen' => $nombre_imagen ? $upload_dir . $nombre_imagen : null,
        'organico' => isset($_POST['organico']) ? true : false,
        'sin_agrotoxicos' => isset($_POST['sin_agrotoxicos']) ? true : false,
        'activo' => true,
        'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
    ];

    // Insertar en MongoDB
    $resultado = $collection->insertOne($documento);

    if ($resultado->getInsertedCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Tu producto "' . htmlspecialchars($documento['nombre']) . '" ha sido publicado correctamente';
        $response['producto_id'] = (string)$resultado->getInsertedId();
    } else {
        throw new Exception('No se pudo insertar el producto');
    }

} catch (MongoDB\Driver\Exception\Exception $e) {
    $response['message'] = 'Error de MongoDB: ' . $e->getMessage();
    
    // Eliminar imagen si hubo error en la BD
    if ($nombre_imagen && file_exists($upload_dir . $nombre_imagen)) {
        unlink($upload_dir . $nombre_imagen);
    }
} catch (Exception $e) {
    $response['message'] = 'Error al guardar el producto: ' . $e->getMessage();
    
    // Eliminar imagen si hubo error
    if ($nombre_imagen && file_exists($upload_dir . $nombre_imagen)) {
        unlink($upload_dir . $nombre_imagen);
    }
}

echo json_encode($response);
?>