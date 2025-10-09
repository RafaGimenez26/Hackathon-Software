<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "MercadoAgricolaLocal";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

// Iniciar sesión
session_start();

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);

    // Validar campos
    if (empty($correo) || empty($contrasena)) {
        $mensaje = "Por favor, complete todos los campos.";
    } else {
        // Buscar usuario por correo
        $stmt = $conn->prepare("SELECT id, nombre_usuario, contrasena FROM usuarios WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();

            // Verificar la contraseña
            if (password_verify($contrasena, $usuario['contrasena'])) {
                // Iniciar sesión correctamente
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];

                header("Location: bienvenida.php");
                exit();
            } else {
                $mensaje = "❌ Contraseña incorrecta.";
            }
        } else {
            $mensaje = "⚠️ No existe una cuenta con ese correo.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión</title>
</head>
<body>
    <h2>Iniciar Sesión</h2>

    <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>

    <form method="POST" action="">
        <label>Correo electrónico:</label><br>
        <input type="email" name="correo" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="contrasena" required><br><br>

        <button type="submit">Iniciar Sesión</button>
    </form>

    <p>¿No tienes cuenta? <a href="registro_usuario.php">Regístrate aquí</a></p>
</body>
</html>
