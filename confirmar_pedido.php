<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: loginus.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$carritosCollection = $database->Carritos;
$pedidosCollection = $database->Pedidos;

// Obtener carrito del usuario
$carrito = $carritosCollection->findOne(['usuario_id' => $usuario_id]);

// Si no hay carrito o está vacío
if (!$carrito || empty($carrito['items'])) {
    echo "<div style='padding:20px; font-family:Arial'>
            <h2>🛒 Tu carrito está vacío</h2>
            <a href='index.php'>Volver a la tienda</a>
          </div>";
    exit;
}

// Procesar la confirmación del pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pedido'])) {

    $total = 0;
    foreach ($carrito['items'] as $item) {
        $total += $item['precio_unitario'] * $item['cantidad'];
    }

    $nuevoPedido = [
        'usuario_id' => $usuario_id,
        'items' => $carrito['items'],
        'total' => $total,
        'estado' => 'en_proceso',
        'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
    ];

    // Guardar pedido
    $pedidosCollection->insertOne($nuevoPedido);

    // Vaciar carrito
    $carritosCollection->deleteOne(['usuario_id' => $usuario_id]);

    // Redirigir
    header("Location: pedidos.php?msg=pedido_confirmado");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Pedido - AgroHub Misiones</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">

    <div class="container">
        <h1 class="mb-4">🧾 Confirmar Pedido</h1>
        <a href="index.php" class="btn btn-secondary mb-3">⬅️ Seguir comprando</a>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                foreach ($carrito['items'] as $item): 
                    $subtotal = $item['precio_unitario'] * $item['cantidad'];
                    $total += $subtotal;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['nombre']) ?></td>
                    <td><?= $item['cantidad'] ?></td>
                    <td>$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
                    <td>$<?= number_format($subtotal, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td><strong>$<?= number_format($total, 0, ',', '.') ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <form method="POST">
            <button type="submit" name="confirmar_pedido" class="btn btn-success btn-lg">
                ✅ Confirmar Pedido
            </button>
            <a href="index.php" class="btn btn-outline-danger btn-lg">❌ Cancelar</a>
        </form>
    </div>

</body>
</html>
