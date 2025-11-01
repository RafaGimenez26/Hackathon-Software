<?php
// listar.php
// Muestra todos los usuarios en formato tabla HTML para WebViewer

// Configuración de la base de datos
$host = 'mysql-j-et.railway.internal';
$dbname = 'railway';
$user = 'root';
$pass = 'AHctqRzHOQlDbhpMdwQPQcDCwMEvCBWd';

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener todos los usuarios
    $sql = "SELECT * FROM personas ORDER BY id ASC";
    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $usuarios = array();
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Usuarios</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 10px;
        }
        
        .container {
            background: white;
            border: 1px solid #ddd;
        }
        
        .header {
            background: #f8f8f8;
            padding: 10px;
            border-bottom: 2px solid #ddd;
        }
        
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .header-info {
            font-size: 12px;
            color: #666;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        th {
            background: #e8e8e8;
            color: #333;
            font-weight: bold;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #ccc;
            border-right: 1px solid #ddd;
            white-space: nowrap;
        }
        
        th:last-child {
            border-right: none;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            border-right: 1px solid #e8e8e8;
            color: #333;
        }
        
        td:last-child {
            border-right: none;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .empty-value {
            color: #999;
            font-style: italic;
        }
        
        .no-data {
            padding: 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border: 1px solid #ef5350;
            margin: 10px;
            font-size: 13px;
        }
        
        .id-cell {
            font-weight: bold;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">Listado de Usuarios</div>
            <div class="header-info">
                <?php 
                if (isset($error)) {
                    echo 'Error de conexión';
                } else {
                    echo count($usuarios) . ' registro' . (count($usuarios) != 1 ? 's' : '');
                }
                ?>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (count($usuarios) == 0): ?>
            <div class="no-data">
                No hay registros en la tabla
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>DNI</th>
                            <th>Número</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td class="id-cell"><?php echo htmlspecialchars($usuario['id']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['dni']); ?></td>
                                <td>
                                    <?php 
                                    echo !empty($usuario['numero']) 
                                        ? htmlspecialchars($usuario['numero']) 
                                        : '<span class="empty-value">NULL</span>'; 
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo !empty($usuario['email']) 
                                        ? htmlspecialchars($usuario['email']) 
                                        : '<span class="empty-value">NULL</span>'; 
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>