<?php
session_start();
header('Content-Type: application/json');
require_once 'includes/funciones_carrito.php';

// Log para debugging (eliminar en producción)
error_log("=== CARRITO.PHP DEBUG ===");
error_log("Session completa: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

if (!isset($_SESSION['usuario_id'])) {
    error_log("ERROR: No hay usuario_id en sesión");
    echo json_encode(['error' => 'Debes iniciar sesión para agregar productos al carrito.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$producto_id = $_POST['producto_id'] ?? '';
$cantidad = $_POST['cantidad'] ?? 1;

error_log("Usuario ID: " . $usuario_id);
error_log("Producto ID: " . $producto_id);
error_log("Cantidad: " . $cantidad);

if (empty($producto_id)) {
    echo json_encode(['error' => 'ID de producto inválido.']);
    exit;
}

$resultado = agregarAlCarrito($usuario_id, $producto_id, (int)$cantidad);
error_log("Resultado: " . print_r($resultado, true));

echo json_encode($resultado);
?>