<?php
require 'vendor/autoload.php';

/**
 * Archivo de Conexión a Base de Datos (MySQLi + MongoDB)
 * Usa variables de entorno definidas en el archivo .env
 */

// 1. Cargar variables de entorno desde .env si existe
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// 2. Obtener las credenciales desde las variables de entorno
$host = $_ENV['host'] ?? 'sql10802401';
$usuario = $_ENV['user'] ?? 'root';
$password = $_ENV['pass'] ?? '';
$base_de_datos = $_ENV['dbname'] ?? 'MercadoAgricolaLocal';

// 3. Establecer la conexión MySQL
$conexion = new mysqli($host, $usuario, $password, $base_de_datos);

// 4. Verificar si la conexión falló
if ($conexion->connect_error) {
    die("Error de conexión a la base de datos MySQL: " . $conexion->connect_error);
}

// 5. Establecer el juego de caracteres UTF-8
$conexion->set_charset("utf8");

// -----------------------------
// Conexión a MongoDB
// -----------------------------
try {
    $mongoUser = $_ENV['MONGO_USER'] ?? '';
    $mongoPass = $_ENV['MONGO_PASS'] ?? '';
    $mongoCluster = $_ENV['MONGO_CLUSTER'] ?? '';

    $client = new MongoDB\Client("mongodb+srv://{$mongoUser}:{$mongoPass}@{$mongoCluster}/");
    $database = $client->AgroHub_Misiones;
    $collection = $database->Productos;

    // Verificar conexión con MongoDB
    $client->listDatabases();

} catch (Exception $e) {
    die("Error de conexión a MongoDB: " . $e->getMessage());
}

// Si deseas verificar que todo funciona correctamente, puedes activar esta línea temporalmente:
// echo "Conexión a MySQL y MongoDB establecidas correctamente.";
?>
