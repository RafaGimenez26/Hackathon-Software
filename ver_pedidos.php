<?php
session_start();
require 'conexion.php'; // contiene conexión MongoDB y MySQL

if (!isset($_SESSION['ProductorID'])) {
    header("Location: misproductos.php");
    exit;
}

$productor_id = $_SESSION['ProductorID'];


// ---- COLECCIONES MONGO ----
$pedidosCollection = $database->Pedidos;
$productosCollection = $database->Productos;

// --- Cambiar estado individual ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion'])) {
    $pedidoId = new MongoDB\BSON\ObjectId($_POST['pedido_id']);
    $accion = $_POST['accion'];

    $pedido = $pedidosCollection->findOne(['_id' => $pedidoId]);
    if ($pedido) {
        $nuevoEstado = "";
        switch ($accion) {
            case "listo":
                $nuevoEstado = "listo_para_retiro";
                break;
            case "entregado":
                $nuevoEstado = "entregado";
                // Descontar stock de cada producto del productor
                foreach ($pedido['items'] as $item) {
                    if ($item['productor_id'] == $productor_id) {
                        $productosCollection->updateOne(
                            ['_id' => new MongoDB\BSON\ObjectId($item['producto_id']['$oid'])],
                            ['$inc' => ['stock_disponible' => -$item['cantidad']]]
                        );
                    }
                }
                break;
            case "no_se_puedo_entregar":
                $nuevoEstado = "no_entregado";
                break;
        }

        if ($nuevoEstado !== "") {
            $pedidosCollection->updateOne(
                ['_id' => $pedidoId],
                ['$set' => ['estado' => $nuevoEstado, 'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()]]
            );
        }
    }
    header("Location: ver_pedidos.php");
    exit;
}

// --- Marcar todos como “listos para retiro” ---
if (isset($_POST['marcar_todos'])) {
    $pedidos = $pedidosCollection->find([]);
    foreach ($pedidos as $pedido) {
        foreach ($pedido['items'] as $item) {
            if ($item['productor_id'] == $productor_id) {
                $pedidosCollection->updateOne(
                    ['_id' => $pedido['_id']],
                    ['$set' => ['estado' => 'listo_para_retiro']]
                );
            }
        }
    }
    header("Location: ver_pedidos.php");
    exit;
}

// --- Obtener pedidos del productor ---
$pedidos = $pedidosCollection->find([
    'items.productor_id' => $productor_id
]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📦 Pedidos del Productor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-success">📦 Pedidos Recibidos</h1>
        <div>
            <form method="POST" class="d-inline">
                <button type="submit" name="marcar_todos" class="btn btn-success">
                    ✅ Marcar todos como Listos para Retiro
                </button>
            </form>
            <a href="dashboard_productor.php" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    <?php foreach ($pedidos as $pedido): ?>
        <?php
        // Buscar datos del usuario en MySQL
        $usuario_id = $pedido['usuario_id'];
        $cliente = null;
        $sql = "SELECT nombre_usuario, correo, telefono FROM usuarios WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $cliente = $result->fetch_assoc();
        }
        $stmt->close();
        ?>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Pedido ID: <?= $pedido['_id'] ?></h5>
                
                <?php if ($cliente): ?>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente['nombre_usuario']) ?></p>
                    <p><strong>Teléfono:</strong> 
                        <a href="https://wa.me/54<?= preg_replace('/\D/', '', $cliente['telefono']) ?>" target="_blank">
                            📱 <?= htmlspecialchars($cliente['telefono']) ?>
                        </a>
                    </p>
                    <p><strong>Correo:</strong> <?= htmlspecialchars($cliente['correo']) ?></p>
                <?php else: ?>
                    <p class="text-muted">Cliente no encontrado en la base de datos.</p>
                <?php endif; ?>

                <p><strong>Estado:</strong> 
                    <span class="badge 
                        <?= $pedido['estado'] == 'entregado' ? 'bg-success' : 
                            ($pedido['estado'] == 'listo_para_retiro' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                        <?= ucfirst(str_replace('_', ' ', $pedido['estado'])) ?>
                    </span>
                </p>

                <p><strong>Fecha:</strong> <?= $pedido['fecha_creacion']->toDateTime()->format('d/m/Y H:i') ?></p>

                <ul class="list-group mb-3">
                    <?php foreach ($pedido['items'] as $item): ?>
                        <?php if ($item['productor_id'] == $productor_id): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= htmlspecialchars($item['nombre']) ?> (<?= $item['cantidad'] ?> <?= htmlspecialchars($item['unidad']) ?>)</span>
                                <span>$<?= number_format($item['precio_unitario'], 2) ?></span>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>

                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="pedido_id" value="<?= $pedido['_id'] ?>">
                    <button name="accion" value="listo" class="btn btn-warning">🚚 Listo para Retiro</button>
                    <button name="accion" value="entregado" class="btn btn-success">✅ Entregado</button>
                    <button name="accion" value="no_se_puedo_entregar" class="btn btn-danger">❌ No se pudo entregar</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

</div>
</body>
</html>
