<?php
require 'vendor/autoload.php';

/**
 * Archivo de Conexión a Base de Datos (MySQLi + MongoDB)
 * Compatible con desarrollo local (.env) y Railway (variables de entorno)
 */

// 1. Cargar variables de entorno desde .env si existe (desarrollo local)
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// 2. Detectar si estamos en Railway
$isRailway = isset($_ENV['RAILWAY_ENVIRONMENT']) || getenv('RAILWAY_ENVIRONMENT');

// 3. Obtener credenciales MySQL según el entorno
if ($isRailway) {
    // Variables de Railway MySQL (se generan automáticamente)
    $host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? 'localhost';
    $usuario = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? 'root';
    $password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? '';
    $base_de_datos = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'railway';
    $puerto = $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?? 3306;
} else {
    // Variables locales desde .env
    $host = $_ENV['host'] ?? 'localhost';
    $usuario = $_ENV['user'] ?? 'root';
    $password = $_ENV['pass'] ?? '';
    $base_de_datos = $_ENV['dbname'] ?? 'MercadoAgricolaLocal';
    $puerto = $_ENV['port'] ?? 3306;
}

// 4. Establecer la conexión MySQL
$conexion = new mysqli($host, $usuario, $password, $base_de_datos, $puerto);

// 5. Verificar si la conexión falló
if ($conexion->connect_error) {
    die("Error de conexión a la base de datos MySQL: " . $conexion->connect_error);
}

// 6. Establecer el juego de caracteres UTF-8
$conexion->set_charset("utf8");

// -----------------------------
// Conexión a MongoDB
// -----------------------------
try {
    $mongoUser = $_ENV['MONGO_USER'] ?? getenv('MONGO_USER') ?? '';
    $mongoPass = $_ENV['MONGO_PASS'] ?? getenv('MONGO_PASS') ?? '';
    $mongoCluster = $_ENV['MONGO_CLUSTER'] ?? getenv('MONGO_CLUSTER') ?? '';

    if (empty($mongoUser) || empty($mongoPass) || empty($mongoCluster)) {
        throw new Exception("Faltan credenciales de MongoDB en las variables de entorno");
    }

    $client = new MongoDB\Client("mongodb+srv://{$mongoUser}:{$mongoPass}@{$mongoCluster}/");
    $database = $client->AgroHub_Misiones;
    $collection = $database->Productos;

    // Verificar conexión con MongoDB
    $client->listDatabases();

} catch (Exception $e) {
    die("Error de conexión a MongoDB: " . $e->getMessage());
}

// Debug: descomentar para verificar conexión (solo en desarrollo)
// $entorno = $isRailway ? 'Railway (Producción)' : 'Local (Desarrollo)';
// echo "✓ Conectado a {$entorno} | MySQL: {$host}:{$puerto}/{$base_de_datos} | MongoDB: {$mongoCluster}";
?>