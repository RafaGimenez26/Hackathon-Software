<?php
/**
 * Archivo de Conexión a Base de Datos (MySQLi)
 */

// 1. Configuración de las credenciales
$host = "localhost"; // Generalmente es 'localhost' cuando usas XAMPP
$usuario = "root";   // Usuario predeterminado de XAMPP. *¡Cámbialo si lo has modificado!*
$password = "";      // Contraseña predeterminada de XAMPP. *¡Cámbiala si la has modificado!*
$base_de_datos = "MercadoAgricolaLocal"; // *** ¡IMPORTANTE! Reemplaza con el nombre real de tu DB ***

// 2. Establecer la conexión
$conexion = new mysqli($host, $usuario, $password, $base_de_datos);

// 3. Verificar si la conexión falló
if ($conexion->connect_error) {
    // Si falla, detenemos la ejecución y mostramos el error
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

// 4. Establecer el juego de caracteres a UTF-8 (es crucial para tildes y eñes)
$conexion->set_charset("utf8");

// La variable $conexion ahora contiene la conexión activa y lista para ser usada.

// Opcional: Para verificar que todo funciona, puedes descomentar la siguiente línea temporalmente:
// echo "Conexión establecida exitosamente.";
require 'vendor/autoload.php';

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Usar las variables de entorno
$mongoUser = $_ENV['MONGO_USER'];
$mongoPass = $_ENV['MONGO_PASS'];
$mongoCluster = $_ENV['MONGO_CLUSTER'];

try {
    $client = new MongoDB\Client("mongodb+srv://{$mongoUser}:{$mongoPass}@{$mongoCluster}/");
    $database = $client->AgroHub_Misiones;
    $collection = $database->Productos;
    
    // Verificar conexión
    $client->listDatabases();
    
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>