<?php
require 'conexion.php';
session_start();

$mensaje = "";

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $contrasena = trim($_POST['contrasena']);

    if (empty($nombre_usuario) || empty($correo) || empty($telefono) || empty($contrasena)) {
        $mensaje = "Por favor, complete todos los campos.";
    } else {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre_usuario, correo, telefono, contrasena) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre_usuario, $correo, $telefono, $hash);

        if ($stmt->execute()) {
            $mensaje = "✅ Usuario registrado correctamente. <a href='loginus.php'>Iniciar sesión</a>";
        } else {
            if ($conexion->errno === 1062) {
                $mensaje = "⚠️ El correo ya está registrado.";
            } else {
                $mensaje = "❌ Error al registrar el usuario: " . $conexion->error;
            }
        }
        $stmt->close();
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .register-card {
            max-width: 400px;
            margin: 80px auto;
            padding: 2rem;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
    <link href="style.css" rel="stylesheet">
</head>
<body>

<div class="register-card">
    <h3 class="text-center mb-4">Registrar nuevo usuario</h3>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
            <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
        </div>

        <div class="mb-3">
            <label for="correo" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="correo" name="correo" required>
        </div>

        <div class="mb-3">
            <label for="telefono" class="form-label">Número de teléfono</label>
            <input type="tel" class="form-control" id="telefono" name="telefono" required pattern="[0-9]{6,15}" title="Ingrese solo números (entre 6 y 15 dígitos)">
        </div>

        <div class="mb-3">
            <label for="contrasena" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
        </div>

        <button type="submit" class="btn btn-success w-100 mb-2">Registrar</button>
        <a href="index.php" class="btn btn-secondary w-100">Volver al inicio</a>
    </form>

    <p class="mt-3 text-center">
        ¿Ya tienes cuenta? <a href="loginus.php">Iniciar sesión</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
