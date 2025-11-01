<?php
// listar.php
// Muestra todos los usuarios en formato HTML para WebViewer

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
            max-width: 100%;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 12px;
            border-bottom: 2px solid #ddd;
            margin-bottom: 10px;
        }
        
        .header h1 {
            color: #333;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .total {
            font-size: 13px;
            color: #666;
        }
        
        .usuario-card {
            background: white;
            border: 1px solid #ddd;
            padding: 12px;
            margin-bottom: 10px;
        }
        
        .usuario-id {
            background: #4a5568;
            color: white;
            display: inline-block;
            padding: 4px 10px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .campo {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .campo:last-child {
            border-bottom: none;
        }
        
        .campo-label {
            font-size: 11px;
            color: #666;
            font-weight: bold;
            margin-bottom: 3px;
            display: block;
        }
        
        .campo-valor {
            font-size: 14px;
            color: #333;
            word-wrap: break-word;
        }
        
        .no-datos {
            background: white;
            padding: 20px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .no-datos-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .no-datos-texto {
            font-size: 14px;
            color: #666;
        }
        
        .error {
            background: #f44336;
            color: white;
            padding: 12px;
            margin-bottom: 10px;
            font-size: 13px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Listado de Usuarios</h1>
            <div class="total">
                <?php echo isset($error) ? '⚠️ Error de conexión' : '✅ Total: ' . count($usuarios) . ' usuario' . (count($usuarios) != 1 ? 's' : ''); ?>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (count($usuarios) == 0): ?>
            <div class="no-datos">
                <div class="no-datos-icon">📭</div>
                <div class="no-datos-texto">No hay usuarios registrados</div>
            </div>
        <?php else: ?>
            <?php foreach ($usuarios as $usuario): ?>
                <div class="usuario-card">
                    <div class="usuario-id">
                        🆔 ID: <?php echo htmlspecialchars($usuario['id']); ?>
                    </div>
                    
                    <div class="campo">
                        <span class="campo-label">👤 Nombre</span>
                        <div class="campo-valor"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                    </div>
                    
                    <div class="campo">
                        <span class="campo-label">🪪 DNI</span>
                        <div class="campo-valor"><?php echo htmlspecialchars($usuario['dni']); ?></div>
                    </div>
                    
                    <div class="campo">
                        <span class="campo-label">📱 Número</span>
                        <div class="campo-valor">
                            <?php echo !empty($usuario['numero']) ? htmlspecialchars($usuario['numero']) : '<em style="color: #999;">Sin número</em>'; ?>
                        </div>
                    </div>
                    
                    <div class="campo">
                        <span class="campo-label">📧 Email</span>
                        <div class="campo-valor">
                            <?php echo !empty($usuario['email']) ? htmlspecialchars($usuario['email']) : '<em style="color: #999;">Sin email</em>'; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>