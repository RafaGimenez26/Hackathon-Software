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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .total {
            font-size: 20px;
            color: #666;
            font-weight: bold;
        }
        
        .usuario-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .usuario-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }
        
        .usuario-id {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .campo {
            margin-bottom: 18px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        
        .campo-label {
            font-size: 16px;
            color: #666;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
            letter-spacing: 1px;
        }
        
        .campo-valor {
            font-size: 22px;
            color: #333;
            font-weight: 500;
            word-wrap: break-word;
        }
        
        .no-datos {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        
        .no-datos-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .no-datos-texto {
            font-size: 24px;
            color: #666;
            font-weight: bold;
        }
        
        .error {
            background: #ff6b6b;
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 20px;
            text-align: center;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        
        .divider {
            height: 3px;
            background: linear-gradient(90deg, transparent, #667eea, transparent);
            margin: 15px 0;
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