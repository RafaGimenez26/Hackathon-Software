<?php
session_start();
require 'conexion.php';

// Si no hay sesión activa, redirigir a login
if (!isset($_SESSION['ProductorID'])) {
    header('Location: misproductos.php');
    exit;
}

// Obtener ID del productor logueado
$productor_id = $_SESSION['ProductorID'];

// === ELIMINAR PRODUCTO (llamado AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    try {
        $result = $collection->deleteOne([
            '_id' => new MongoDB\BSON\ObjectId($delete_id),
            'productor_id' => (int)$productor_id
        ]);

        if ($result->getDeletedCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró el producto o no pertenece al productor.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// === CONSULTAR PRODUCTOS DEL PRODUCTOR ===
try {
    $productos = $collection->find(['productor_id' => (int)$productor_id]);
} catch (Exception $e) {
    die("Error al obtener los productos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Productor - Mis Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-success">🌿 Panel del Productor</h1>
        <div>
            <a href="cargar_producto.php" class="btn btn-success">➕ Cargar Nuevo Producto</a>
            <a href="logout.php" class="btn btn-outline-danger">Cerrar sesión</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title mb-4">Mis Productos Publicados</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-success">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Unidad</th>
                            <th>Punto de Venta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tieneProductos = false;
                        foreach ($productos as $p):
                            $tieneProductos = true;
                        ?>
                        <tr id="row-<?php echo $p->_id; ?>">
                            <td><?= htmlspecialchars($p->nombre ?? '—') ?></td>
                            <td><?= htmlspecialchars($p->descripcion ?? '—') ?></td>
                            <td><?= htmlspecialchars($p->categoria ?? '—') ?></td>
                            <td>$<?= number_format($p->precio ?? 0, 2) ?></td>
                            <td><?= htmlspecialchars($p->stock_disponible ?? '0') ?></td>
                            <td><?= htmlspecialchars($p->unidad ?? '—') ?></td>
                            <td><?= htmlspecialchars($p->punto_venta ?? '—') ?></td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="eliminarProducto('<?= $p->_id ?>')">
                                    🗑️ Eliminar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (!$tieneProductos): ?>
                        <tr>
                            <td colspan="8" class="text-muted py-4">Aún no has publicado productos.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// === Función para eliminar producto ===
function eliminarProducto(id) {
    Swal.fire({
        title: '¿Eliminar producto?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('dashboard_productor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'delete_id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('row-' + id).remove();
                    Swal.fire('Eliminado', 'El producto fue eliminado correctamente.', 'success');
                } else {
                    Swal.fire('Error', data.error || 'No se pudo eliminar el producto.', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Hubo un problema al eliminar el producto.', 'error');
            });
        }
    });
}
</script>

</body>
</html>
