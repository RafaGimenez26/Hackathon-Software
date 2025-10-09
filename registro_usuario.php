<?php
// Configuración de la base de datos
$servername = "localhost";   // Cambia si usas otro host
$username = "root";          // Usuario de MySQL
$password = "";              // Contraseña (vacía por defecto en XAMPP)
$dbname = "MercadoAgricolaLocal"; // Cambia por el nombre de tu base

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);

    // Validar que los campos no estén vacíos
    if (empty($nombre_usuario) || empty($correo) || empty($contrasena)) {
        echo "Por favor, complete todos los campos.";
    } else {
        // Encriptar la contraseña
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        // Preparar y ejecutar la inserción
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, correo, contrasena) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre_usuario, $correo, $hash);

        if ($stmt->execute()) {
            echo "✅ Usuario registrado correctamente.";
        } else {
            if ($conn->errno === 1062) {
                echo "⚠️ El correo ya está registrado.";
            } else {
                echo "❌ Error al registrar el usuario: " . $conn->error;
            }
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!-- Formulario HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Registrar nuevo usuario</h2>
    <form method="POST" action="">
        <label>Nombre de usuario:</label><br>
        <input type="text" name="nombre_usuario" required><br><br>

        <label>Correo electrónico:</label><br>
        <input type="email" name="correo" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="contrasena" required><br><br>

        <button type="submit">Registrar</button>
    </form>
</body>
</html>
